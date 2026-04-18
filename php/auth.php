<?php
/* ============================================================
   InternPortal – php/auth.php
   Handles POST for login and registration
   ============================================================ */

require_once __DIR__ . '/config.php';
sessionStart();

$action = $_POST['action'] ?? '';

/* ══════════════════════════════════════════════════════════
   LOGIN
   ══════════════════════════════════════════════════════════ */
if ($action === 'login') {
    verifyCsrf();

    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$email || !$password) {
        flash('error', 'Please fill in all fields.');
        redirect('../index.php');
    }

    $stmt = db()->prepare(
        "SELECT id, name, password, role, status FROM users WHERE email = ? LIMIT 1"
    );
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row || !password_verify($password, $row['password'])) {
        flash('error', 'Invalid email or password.');
        redirect('../index.php');
    }

    if ($row['status'] !== 'active') {
        flash('error', 'Your account has been deactivated. Contact admin.');
        redirect('../index.php');
    }

    $_SESSION['user_id']   = $row['id'];
    $_SESSION['user_name'] = $row['name'];
    $_SESSION['user_role'] = $row['role'];

    redirect('../dashboard.php');
}

/* ══════════════════════════════════════════════════════════
   REGISTER
   ══════════════════════════════════════════════════════════ */
if ($action === 'register') {
    verifyCsrf();

    $name       = trim($_POST['name']             ?? '');
    $studentId  = trim($_POST['student_id']       ?? '');
    $email      = trim($_POST['email']            ?? '');
    $password   = trim($_POST['password']         ?? '');
    $confirm    = trim($_POST['confirm_password'] ?? '');
    $role       = $_POST['role']  ?? 'intern';
    $batch      = trim($_POST['batch']   ?? '');
    $section    = trim($_POST['section'] ?? '');

    // Only allow intern or mentor self-registration
    if (!in_array($role, ['intern', 'mentor'])) $role = 'intern';

    if (!$name || !$email || !$password) {
        flash('error', 'Please fill in all required fields.');
        redirect('../index.php?tab=register');
    }

    if ($password !== $confirm) {
        flash('error', 'Passwords do not match.');
        redirect('../index.php?tab=register');
    }

    if (strlen($password) < 6) {
        flash('error', 'Password must be at least 6 characters.');
        redirect('../index.php?tab=register');
    }

    /* Check duplicate email */
    $chk = db()->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $chk->bind_param('s', $email);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows > 0) {
        $chk->close();
        flash('error', 'An account with that email already exists.');
        redirect('../index.php?tab=register');
    }
    $chk->close();

    $hash = password_hash($password, PASSWORD_BCRYPT);

    $ins = db()->prepare(
        "INSERT INTO users (name, student_id, email, password, role, batch, section)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $ins->bind_param('sssssss', $name, $studentId, $email, $hash, $role, $batch, $section);
    $ins->execute();
    $newId = db()->insert_id;
    $ins->close();

    $_SESSION['user_id']   = $newId;
    $_SESSION['user_name'] = $name;
    $_SESSION['user_role'] = $role;

    redirect('../dashboard.php');
}

/* ══════════════════════════════════════════════════════════
   LOGOUT
   ══════════════════════════════════════════════════════════ */
if ($action === 'logout') {
    session_destroy();
    redirect('../index.php');
}

redirect('../index.php');
