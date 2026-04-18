<?php
/* ============================================================
   InternPortal – php/projects.php
   Handles project creation, assignment & file submissions
   ============================================================ */

require_once __DIR__ . '/config.php';
requireLogin('../index.php');
verifyCsrf();

$action = $_POST['action'] ?? '';
$user   = currentUser();

/* ══════════════════════════════════════════════════════════
   CREATE PROJECT  (mentor only)
   ══════════════════════════════════════════════════════════ */
if ($action === 'create_project') {
    requireRole('mentor', '../dashboard.php');

    $title       = trim($_POST['title']       ?? '');
    $description = trim($_POST['description'] ?? '');
    $deadline    = trim($_POST['deadline']    ?? '');

    if (!$title || !$deadline) {
        flash('error', 'Title and deadline are required.');
        redirect('../dashboard.php');
    }

    $stmt = db()->prepare(
        "INSERT INTO projects (mentor_id, title, description, deadline) VALUES (?,?,?,?)"
    );
    $stmt->bind_param('isss', $user['id'], $title, $description, $deadline);
    $stmt->execute();
    $projectId = db()->insert_id;
    $stmt->close();

    /* Assign to selected interns */
    $internIds = $_POST['intern_ids'] ?? [];
    foreach ($internIds as $iid) {
        $iid = (int)$iid;
        $a   = db()->prepare("INSERT IGNORE INTO assignments (project_id, intern_id) VALUES (?,?)");
        $a->bind_param('ii', $projectId, $iid);
        $a->execute();
        $a->close();

        /* Notify each intern */
        $msg = "New project assigned: \"{$title}\" – due {$deadline}";
        $n   = db()->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'info')");
        $n->bind_param('is', $iid, $msg);
        $n->execute();
        $n->close();
    }

    flash('success', 'Project created and assigned successfully!');
    redirect('../dashboard.php');
}

/* ══════════════════════════════════════════════════════════
   SUBMIT PROJECT  (intern only)
   ══════════════════════════════════════════════════════════ */
if ($action === 'submit_project') {
    requireRole('intern', '../dashboard.php');

    $projectId = (int)($_POST['project_id'] ?? 0);
    $notes     = trim($_POST['notes'] ?? '');

    if (!$projectId) {
        flash('error', 'Please select a project.');
        redirect('../dashboard.php');
    }

    /* Verify the intern is actually assigned to this project */
    $chk = db()->prepare(
        "SELECT id FROM assignments WHERE project_id=? AND intern_id=? LIMIT 1"
    );
    $chk->bind_param('ii', $projectId, $user['id']);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows === 0) {
        $chk->close();
        flash('error', 'You are not assigned to this project.');
        redirect('../dashboard.php');
    }
    $chk->close();

    /* Handle file upload */
    $file = $_FILES['projectFile'] ?? null;
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        flash('error', 'Please upload a valid file.');
        redirect('../dashboard.php');
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        flash('error', 'File exceeds the 10 MB limit.');
        redirect('../dashboard.php');
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXT)) {
        flash('error', 'File type not allowed. Accepted: ' . implode(', ', ALLOWED_EXT));
        redirect('../dashboard.php');
    }

    /* Build safe filename */
    $safeName = sprintf('%d_%d_%s.%s', $user['id'], $projectId, time(), $ext);
    $destPath = UPLOAD_DIR . $safeName;

    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        flash('error', 'Upload failed. Please try again.');
        redirect('../dashboard.php');
    }

    /* Upsert submission */
    $origName = basename($file['name']);
    $rel      = 'uploads/' . $safeName;

    $dup = db()->prepare(
        "SELECT id FROM submissions WHERE project_id=? AND intern_id=? LIMIT 1"
    );
    $dup->bind_param('ii', $projectId, $user['id']);
    $dup->execute();
    $existing = $dup->get_result()->fetch_assoc();
    $dup->close();

    if ($existing) {
        $upd = db()->prepare(
            "UPDATE submissions SET file_path=?, file_name=?, notes=?, grade=NULL,
             feedback=NULL, submitted_at=NOW(), graded_at=NULL WHERE id=?"
        );
        $upd->bind_param('sssi', $rel, $origName, $notes, $existing['id']);
        $upd->execute();
        $upd->close();
    } else {
        $ins = db()->prepare(
            "INSERT INTO submissions (project_id, intern_id, file_path, file_name, notes)
             VALUES (?,?,?,?,?)"
        );
        $ins->bind_param('iisss', $projectId, $user['id'], $rel, $origName, $notes);
        $ins->execute();
        $ins->close();
    }

    /* Notify mentor */
    $proj = db()->prepare("SELECT mentor_id, title FROM projects WHERE id=? LIMIT 1");
    $proj->bind_param('i', $projectId);
    $proj->execute();
    $prow = $proj->get_result()->fetch_assoc();
    $proj->close();

    if ($prow) {
        $msg = "{$user['name']} submitted work for \"{$prow['title']}\"";
        $nm  = db()->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'success')");
        $nm->bind_param('is', $prow['mentor_id'], $msg);
        $nm->execute();
        $nm->close();
    }

    flash('success', 'Your project was submitted successfully!');
    redirect('../dashboard.php');
}

/* ══════════════════════════════════════════════════════════
   GRADE SUBMISSION  (mentor only)
   ══════════════════════════════════════════════════════════ */
if ($action === 'grade_submission') {
    requireRole('mentor', '../dashboard.php');

    $subId    = (int)($_POST['submission_id'] ?? 0);
    $grade    = max(0, min(100, (int)($_POST['grade'] ?? 0)));
    $feedback = trim($_POST['feedback'] ?? '');

    if (!$subId) {
        flash('error', 'Invalid submission.');
        redirect('../dashboard.php');
    }

    $stmt = db()->prepare(
        "UPDATE submissions SET grade=?, feedback=?, graded_at=NOW() WHERE id=?"
    );
    $stmt->bind_param('isi', $grade, $feedback, $subId);
    $stmt->execute();
    $stmt->close();

    /* Fetch intern_id & project title to notify */
    $info = db()->prepare(
        "SELECT s.intern_id, p.title FROM submissions s
         JOIN projects p ON p.id=s.project_id WHERE s.id=? LIMIT 1"
    );
    $info->bind_param('i', $subId);
    $info->execute();
    $row = $info->get_result()->fetch_assoc();
    $info->close();

    if ($row) {
        $type = $grade >= 75 ? 'success' : ($grade >= 50 ? 'warning' : 'danger');
        $msg  = "Your submission for \"{$row['title']}\" was graded: {$grade}%";
        $nn   = db()->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, ?)");
        $nn->bind_param('iss', $row['intern_id'], $msg, $type);
        $nn->execute();
        $nn->close();
    }

    flash('success', "Submission graded: {$grade}%");
    redirect('../dashboard.php');
}

redirect('../dashboard.php');