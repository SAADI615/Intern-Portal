<?php
/* InternPortal – dashboard.php  (role-adaptive dashboard) */
require_once 'php/config.php';
requireLogin();

$user    = currentUser();
$success = getFlash('success');
$error   = getFlash('error');
$csrf    = csrfToken();
$role    = $user['role'];
$uid     = $user['id'];
$db      = db();

/* ── Fetch data by role ─────────────────────────────────── */
if ($role === 'intern') {

    /* Projects assigned to this intern */
    $pStmt = $db->prepare(
        "SELECT p.id, p.title, p.description, p.deadline,
                u.name AS mentor_name,
                s.id AS sub_id, s.grade, s.feedback, s.submitted_at, s.file_name, s.file_path
         FROM assignments a
         JOIN projects p ON p.id = a.project_id
         JOIN users u    ON u.id = p.mentor_id
         LEFT JOIN submissions s ON s.project_id = p.id AND s.intern_id = ?
         WHERE a.intern_id = ?
         ORDER BY p.deadline ASC"
    );
    $pStmt->bind_param('ii', $uid, $uid);
    $pStmt->execute();
    $projects = $pStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $pStmt->close();

    $totalProj   = count($projects);
    $submitted   = count(array_filter($projects, fn($p) => $p['sub_id']));
    $graded      = count(array_filter($projects, fn($p) => $p['grade'] !== null));
    $grades      = array_filter(array_column($projects, 'grade'), fn($g) => $g !== null);
    $avgGrade    = $grades ? round(array_sum($grades) / count($grades)) : null;

} elseif ($role === 'mentor') {

    /* Projects this mentor created */
    $pStmt = $db->prepare(
        "SELECT p.*,
                COUNT(DISTINCT a.intern_id) AS assigned_count,
                COUNT(DISTINCT s.id)        AS sub_count
         FROM projects p
         LEFT JOIN assignments a ON a.project_id = p.id
         LEFT JOIN submissions s ON s.project_id = p.id
         WHERE p.mentor_id = ?
         GROUP BY p.id
         ORDER BY p.deadline ASC"
    );
    $pStmt->bind_param('i', $uid);
    $pStmt->execute();
    $projects = $pStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $pStmt->close();

    /* Pending submissions to grade */
    $pendStmt = $db->prepare(
        "SELECT s.*, u.name AS intern_name, p.title AS proj_title
         FROM submissions s
         JOIN projects p ON p.id = s.project_id
         JOIN users u    ON u.id = s.intern_id
         WHERE p.mentor_id = ? AND s.grade IS NULL
         ORDER BY s.submitted_at ASC"
    );
    $pendStmt->bind_param('i', $uid);
    $pendStmt->execute();
    $pending = $pendStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $pendStmt->close();

    /* List of interns for assignment modal */
    $intStmt = $db->query("SELECT id, name, student_id FROM users WHERE role='intern' AND status='active' ORDER BY name");
    $interns = $intStmt->fetch_all(MYSQLI_ASSOC);

} elseif ($role === 'admin') {

    $users = $db->query(
        "SELECT id, name, email, role, batch, section, status, created_at FROM users ORDER BY role, name"
    )->fetch_all(MYSQLI_ASSOC);

    $totals = $db->query(
        "SELECT
           (SELECT COUNT(*) FROM users WHERE role='intern') AS interns,
           (SELECT COUNT(*) FROM users WHERE role='mentor') AS mentors,
           (SELECT COUNT(*) FROM projects) AS projects,
           (SELECT COUNT(*) FROM submissions) AS submissions"
    )->fetch_assoc();
}

/* ── Notifications (all roles) ──────────────────────────── */
$nStmt = $db->prepare(
    "SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 10"
);
$nStmt->bind_param('i', $uid);
$nStmt->execute();
$notifications = $nStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$nStmt->close();

/* Calculate unread count BEFORE using it in sidebar */
$unreadCount = count(array_filter($notifications, fn($n) => !$n['is_read']));

/* Mark all as read */
$markRead = $db->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?");
$markRead->bind_param('i', $uid);
$markRead->execute();
$markRead->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard – InternPortal</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
  <a class="nav-logo" href="index.php">Intern<span>Portal</span></a>
  <div class="nav-links">
    <a class="nav-link active" data-page="dashboard.php" href="dashboard.php">Dashboard</a>
  </div>
  <form action="php/auth.php" method="POST" style="display:inline;">
    <input type="hidden" name="action" value="logout">
    <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
    <button type="submit" class="nav-cta" style="background:var(--red);">Sign Out</button>
  </form>
</nav>

<div class="dash-layout">

  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="sidebar-user">
      <div class="avatar <?= h($role) ?>"><?= h(initials($user['name'])) ?></div>
      <div>
        <div class="user-name"><?= h(explode(' ', $user['name'])[0]) ?></div>
        <div class="user-role"><?= ucfirst(h($role)) ?></div>
      </div>
    </div>

    <div class="sidebar-nav">
      <?php if ($role === 'intern'): ?>
        <button class="sidebar-item active" data-panel="overview"><span class="sidebar-icon">◼</span>Overview</button>
        <button class="sidebar-item" data-panel="submit"><span class="sidebar-icon">↑</span>Submit Work</button>
        <button class="sidebar-item" data-panel="grades"><span class="sidebar-icon">★</span>Grades</button>
        <button class="sidebar-item" data-panel="notifications"><span class="sidebar-icon">●</span>Notifications <?= $unreadCount ? "<span class='badge warning' style='margin-left:auto;'>{$unreadCount}</span>" : '' ?></button>
      <?php elseif ($role === 'mentor'): ?>
        <button class="sidebar-item active" data-panel="overview"><span class="sidebar-icon">◼</span>Overview</button>
        <button class="sidebar-item" data-panel="create"><span class="sidebar-icon">+</span>New Project</button>
        <button class="sidebar-item" data-panel="grade-sub"><span class="sidebar-icon">★</span>Grade Submissions</button>
        <button class="sidebar-item" data-panel="notifications"><span class="sidebar-icon">●</span>Notifications</button>
      <?php elseif ($role === 'admin'): ?>
        <button class="sidebar-item active" data-panel="overview"><span class="sidebar-icon">◼</span>Overview</button>
        <button class="sidebar-item" data-panel="users"><span class="sidebar-icon">◑</span>Users</button>
        <button class="sidebar-item" data-panel="notifications"><span class="sidebar-icon">●</span>Notifications</button>
      <?php endif; ?>
    </div>

    <div class="sidebar-spacer"></div>
    <div class="sidebar-nav">
      <form action="php/auth.php" method="POST">
        <input type="hidden" name="action" value="logout">
        <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
        <button type="submit" class="sidebar-item danger" style="width:100%;">
          <span class="sidebar-icon">←</span>Sign Out
        </button>
      </form>
    </div>
  </aside>

  <!-- MAIN -->
  <main class="dash-main">

    <?php if ($success): ?><div class="alert success show"><?= h($success) ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="alert error   show"><?= h($error)   ?></div><?php endif; ?>

    <!-- ════════════════ INTERN PANELS ════════════════ -->
    <?php if ($role === 'intern'): ?>

      <!-- OVERVIEW -->
      <div class="panel-section" data-panel="overview">
        <div class="dash-header">
          <h2>Intern Dashboard</h2>
          <p>Welcome back, <?= h($user['name']) ?> · Spring 2026</p>
        </div>
        <div class="metrics-row">
          <div class="metric-card"><div class="metric-label">Assigned Projects</div><div class="metric-val"><?= $totalProj ?></div><div class="metric-sub">Total assigned</div></div>
          <div class="metric-card"><div class="metric-label">Submitted</div><div class="metric-val green"><?= $submitted ?></div><div class="metric-sub">Files uploaded</div></div>
          <div class="metric-card"><div class="metric-label">Graded</div><div class="metric-val blue"><?= $graded ?></div><div class="metric-sub">Reviews received</div></div>
          <div class="metric-card"><div class="metric-label">Avg Grade</div><div class="metric-val <?= $avgGrade ? gradeClass($avgGrade) : '' ?>"><?= $avgGrade !== null ? $avgGrade.'%' : '—' ?></div><div class="metric-sub">Across all graded</div></div>
        </div>
        <div class="panel">
          <div class="panel-header">
            <span class="panel-title">My Projects</span>
            <button class="btn btn-green btn-sm" data-goto="submit">Submit Work</button>
          </div>
          <div class="panel-body">
            <?php if (empty($projects)): ?>
              <div class="empty-state"><div class="empty-icon">📭</div><div class="empty-title">No projects yet</div><div class="empty-sub">Your mentor hasn't assigned any projects.</div></div>
            <?php else: ?>
              <div class="project-list">
                <?php foreach ($projects as $p):
                  $overdue = !$p['sub_id'] && strtotime($p['deadline']) < time();
                  $statusKey = $p['grade'] !== null ? 'graded' : ($p['sub_id'] ? 'submitted' : ($overdue ? 'overdue' : 'pending'));
                  $statusLabel = ucfirst($statusKey);
                ?>
                <div class="project-item">
                  <div class="status-dot <?= $statusKey ?>"></div>
                  <div class="proj-info">
                    <div class="proj-name"><?= h($p['title']) ?></div>
                    <div class="proj-meta">Due: <?= h($p['deadline']) ?> · Mentor: <?= h($p['mentor_name']) ?></div>
                  </div>
                  <?php if ($p['grade'] !== null): ?>
                    <span class="badge graded"><?= $p['grade'] ?>%</span>
                  <?php else: ?>
                    <span class="badge <?= $statusKey ?>"><?= $statusLabel ?></span>
                  <?php endif; ?>
                </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- SUBMIT WORK -->
      <div class="panel-section hidden" data-panel="submit">
        <div class="dash-header"><h2>Submit Project</h2><p>Upload your work for evaluation</p></div>
        <div class="panel">
          <div class="panel-header"><span class="panel-title">Project Submission</span></div>
          <div class="panel-body">
            <form id="submit-form" action="php/projects.php" method="POST" enctype="multipart/form-data">
              <input type="hidden" name="action" value="submit_project">
              <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
              <div class="form-group">
                <label class="form-label">Select Project</label>
                <select class="form-select" name="project_id" required>
                  <option value="">— Choose a project —</option>
                  <?php foreach ($projects as $p): ?>
                    <option value="<?= $p['id'] ?>"><?= h($p['title']) ?> (Due: <?= h($p['deadline']) ?>)</option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Notes for mentor (optional)</label>
                <textarea class="form-textarea" name="notes" placeholder="Describe your approach, challenges faced, or anything relevant…"></textarea>
              </div>
              <div class="form-group">
                <label class="form-label">Project file</label>
                <div class="upload-zone" id="upload-zone">
                  <div id="upload-label">
                    <div class="upload-icon">📁</div>
                    <div class="upload-text">Drop your file here or click to browse</div>
                    <div class="upload-sub">PDF, ZIP, DOCX, DOC, RAR · Max 10 MB</div>
                  </div>
                </div>
                <input type="file" id="file-input" name="projectFile" accept=".pdf,.zip,.docx,.doc,.rar,.txt" style="display:none;">
                <span class="form-error" id="file-error"></span>
              </div>
              <button type="submit" class="btn btn-green btn-lg">Submit Project →</button>
            </form>
          </div>
        </div>
      </div>

      <!-- GRADES -->
      <div class="panel-section hidden" data-panel="grades">
        <div class="dash-header"><h2>Grades &amp; Feedback</h2><p>Your evaluation results from mentors</p></div>
        <div class="panel">
          <div class="panel-header"><span class="panel-title">Performance Summary</span></div>
          <div class="panel-body">
            <?php $graded_projects = array_filter($projects, fn($p) => $p['grade'] !== null); ?>
            <?php if (empty($graded_projects)): ?>
              <div class="empty-state"><div class="empty-icon">⏳</div><div class="empty-title">No grades yet</div><div class="empty-sub">Submit your work and wait for mentor evaluation.</div></div>
            <?php else: ?>
              <div class="table-wrap">
                <table class="data-table">
                  <thead><tr><th>Project</th><th>Submitted</th><th>Score</th><th>Progress</th><th>Feedback</th></tr></thead>
                  <tbody>
                    <?php foreach ($graded_projects as $p): ?>
                    <tr>
                      <td><strong><?= h($p['title']) ?></strong></td>
                      <td class="text-hint"><?= date('M j', strtotime($p['submitted_at'])) ?></td>
                      <td><strong class="text-<?= gradeClass($p['grade']) ?>"><?= $p['grade'] ?>%</strong></td>
                      <td><div class="grade-bar"><div class="grade-fill" data-pct="<?= $p['grade'] ?>"></div></div></td>
                      <td class="text-hint" style="font-size:13px;"><?= $p['feedback'] ? h($p['feedback']) : '—' ?></td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

    <!-- ════════════════ MENTOR PANELS ════════════════ -->
    <?php elseif ($role === 'mentor'): ?>

      <!-- OVERVIEW -->
      <div class="panel-section" data-panel="overview">
        <div class="dash-header"><h2>Mentor Dashboard</h2><p>SE-322 Web Application Lab · Spring 2026</p></div>
        <div class="metrics-row">
          <div class="metric-card"><div class="metric-label">Projects Created</div><div class="metric-val"><?= count($projects) ?></div></div>
          <div class="metric-card"><div class="metric-label">Total Submissions</div><div class="metric-val green"><?= array_sum(array_column($projects,'sub_count')) ?></div></div>
          <div class="metric-card"><div class="metric-label">Pending Review</div><div class="metric-val orange"><?= count($pending) ?></div></div>
          <div class="metric-card"><div class="metric-label">Interns</div><div class="metric-val blue"><?= count($interns) ?></div></div>
        </div>
        <div class="panel">
          <div class="panel-header">
            <span class="panel-title">All Projects</span>
            <button class="btn btn-green btn-sm" data-goto="create">+ New Project</button>
          </div>
          <div class="panel-body">
            <?php if (empty($projects)): ?>
              <div class="empty-state"><div class="empty-icon">📝</div><div class="empty-title">No projects yet</div><div class="empty-sub">Create your first project to get started.</div></div>
            <?php else: ?>
              <div class="table-wrap">
                <table class="data-table">
                  <thead><tr><th>Title</th><th>Deadline</th><th>Assigned</th><th>Submissions</th></tr></thead>
                  <tbody>
                    <?php foreach ($projects as $p): ?>
                    <tr>
                      <td><strong><?= h($p['title']) ?></strong></td>
                      <td class="text-hint"><?= h($p['deadline']) ?></td>
                      <td><?= $p['assigned_count'] ?> intern<?= $p['assigned_count']!=1?'s':'' ?></td>
                      <td><span class="badge <?= $p['sub_count']>0?'submitted':'pending' ?>"><?= $p['sub_count'] ?>/<?= $p['assigned_count'] ?> submitted</span></td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- CREATE PROJECT -->
      <div class="panel-section hidden" data-panel="create">
        <div class="dash-header"><h2>New Project</h2><p>Create a task and assign it to interns</p></div>
        <div class="panel">
          <div class="panel-header"><span class="panel-title">Project Details</span></div>
          <div class="panel-body">
            <form action="php/projects.php" method="POST">
              <input type="hidden" name="action" value="create_project">
              <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
              <div class="form-row">
                <div class="form-group"><label class="form-label">Project title *</label><input class="form-input" name="title" required placeholder="e.g. Authentication Module"></div>
                <div class="form-group"><label class="form-label">Deadline *</label><input class="form-input" type="date" name="deadline" required></div>
              </div>
              <div class="form-group"><label class="form-label">Description</label><textarea class="form-textarea" name="description" placeholder="Describe the requirements, deliverables, and evaluation criteria…"></textarea></div>
              <div class="form-group">
                <label class="form-label">Assign to interns</label>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:8px;background:var(--bg);padding:14px;border-radius:var(--radius);border:1.5px solid var(--border-md);">
                  <?php foreach ($interns as $intern): ?>
                  <label style="display:flex;align-items:center;gap:8px;font-size:14px;cursor:pointer;">
                    <input type="checkbox" name="intern_ids[]" value="<?= $intern['id'] ?>">
                    <?= h($intern['name']) ?> <?= $intern['student_id'] ? '<span style="color:var(--text-3);font-size:12px;">('.h($intern['student_id']).')</span>' : '' ?>
                  </label>
                  <?php endforeach; ?>
                  <?php if (empty($interns)): ?><p style="color:var(--text-3);font-size:14px;">No interns registered yet.</p><?php endif; ?>
                </div>
              </div>
              <button type="submit" class="btn btn-green btn-lg">Create &amp; Assign →</button>
            </form>
          </div>
        </div>
      </div>

      <!-- GRADE SUBMISSIONS -->
      <div class="panel-section hidden" data-panel="grade-sub">
        <div class="dash-header"><h2>Grade Submissions</h2><p>Review and score intern work</p></div>
        <div class="panel">
          <div class="panel-header"><span class="panel-title">Pending Review</span></div>
          <div class="panel-body">
            <?php if (empty($pending)): ?>
              <div class="empty-state"><div class="empty-icon">🎉</div><div class="empty-title">All caught up!</div><div class="empty-sub">No submissions waiting for your review.</div></div>
            <?php else: ?>
              <div class="project-list">
                <?php foreach ($pending as $s): ?>
                <div class="project-item" style="flex-wrap:wrap;gap:10px;">
                  <div class="status-dot submitted"></div>
                  <div class="proj-info">
                    <div class="proj-name"><?= h($s['proj_title']) ?></div>
                    <div class="proj-meta"><?= h($s['intern_name']) ?> · <?= date('M j, Y', strtotime($s['submitted_at'])) ?> · <?= h($s['file_name']) ?></div>
                  </div>
                  <a href="<?= h($s['file_path']) ?>" target="_blank" class="btn btn-ghost btn-sm">Download</a>
                  <button class="btn btn-green btn-sm" onclick="openGradeModal(<?= $s['id'] ?>, '<?= addslashes(h($s['proj_title'])) ?>', '<?= addslashes(h($s['intern_name'])) ?>')">Grade</button>
                </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

    <!-- ════════════════ ADMIN PANELS ════════════════ -->
    <?php elseif ($role === 'admin'): ?>

      <!-- OVERVIEW -->
      <div class="panel-section" data-panel="overview">
        <div class="dash-header"><h2>Admin Dashboard</h2><p>System Overview · Spring 2026</p></div>
        <div class="metrics-row">
          <div class="metric-card"><div class="metric-label">Interns</div><div class="metric-val green"><?= $totals['interns'] ?></div></div>
          <div class="metric-card"><div class="metric-label">Mentors</div><div class="metric-val orange"><?= $totals['mentors'] ?></div></div>
          <div class="metric-card"><div class="metric-label">Projects</div><div class="metric-val blue"><?= $totals['projects'] ?></div></div>
          <div class="metric-card"><div class="metric-label">Submissions</div><div class="metric-val purple"><?= $totals['submissions'] ?></div></div>
        </div>
      </div>

      <!-- USERS -->
      <div class="panel-section hidden" data-panel="users">
        <div class="dash-header"><h2>User Management</h2><p>All registered accounts</p></div>
        <div class="panel">
          <div class="panel-header"><span class="panel-title">All Users</span></div>
          <div class="panel-body">
            <div class="table-wrap">
              <table class="data-table">
                <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Batch/Sec</th><th>Status</th><th>Joined</th></tr></thead>
                <tbody>
                  <?php foreach ($users as $u): ?>
                  <tr>
                    <td><strong><?= h($u['name']) ?></strong></td>
                    <td class="text-hint" style="font-size:13px;"><?= h($u['email']) ?></td>
                    <td><span class="badge <?= $u['role']==='admin'?'admin-tag':($u['role']==='mentor'?'warning':'info') ?>"><?= ucfirst(h($u['role'])) ?></span></td>
                    <td class="text-hint" style="font-size:13px;"><?= $u['batch'] ? h($u['batch']).'/'.$u['section'] : '—' ?></td>
                    <td><span class="badge <?= $u['status']==='active'?'submitted':'overdue' ?>"><?= ucfirst($u['status']) ?></span></td>
                    <td class="text-hint" style="font-size:12px;"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

    <?php endif; ?>

    <!-- NOTIFICATIONS (all roles) -->
    <div class="panel-section hidden" data-panel="notifications">
      <div class="dash-header"><h2>Notifications</h2><p>Recent activity and updates</p></div>
      <div class="panel">
        <div class="panel-header"><span class="panel-title">Recent Activity</span></div>
        <div class="panel-body">
          <?php if (empty($notifications)): ?>
            <div class="empty-state"><div class="empty-icon">🔔</div><div class="empty-title">No notifications</div></div>
          <?php else: ?>
            <?php foreach ($notifications as $n):
              $dotClass = match($n['type']) { 'success'=>'green','warning'=>'orange','danger'=>'red',default=>'blue' };
            ?>
            <div class="notif-item">
              <div class="notif-dot <?= $dotClass ?>"></div>
              <div>
                <div class="notif-body"><?= h($n['message']) ?></div>
                <div class="notif-time"><?= date('M j, g:i a', strtotime($n['created_at'])) ?></div>
              </div>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </main>
</div>

<!-- GRADE MODAL -->
<div class="modal-overlay" id="grade-modal">
  <div class="modal">
    <h3 class="modal-title" id="grade-modal-title">Grade Submission</h3>
    <p style="font-size:14px;color:var(--text-2);margin-bottom:20px;">Intern: <strong id="grade-intern-name"></strong></p>
    <form action="php/projects.php" method="POST">
      <input type="hidden" name="action" value="grade_submission">
      <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
      <input type="hidden" name="submission_id" id="grade-sub-id">
      <div class="form-group">
        <label class="form-label">Score: <span id="grade-display">75%</span></label>
        <input type="range" id="grade-slider" name="grade" min="0" max="100" value="75" style="width:100%;margin:8px 0 6px;">
        <div class="grade-bar"><div class="grade-fill high" id="grade-preview-bar" style="width:75%;"></div></div>
      </div>
      <div class="form-group">
        <label class="form-label">Feedback</label>
        <textarea class="form-textarea" name="feedback" placeholder="Write constructive feedback for the intern…"></textarea>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('grade-modal')">Cancel</button>
        <button type="submit" class="btn btn-green">Save Grade</button>
      </div>
    </form>
  </div>
</div>

<script src="js/main.js"></script>
<script>
/* Panel switching */
document.querySelectorAll('[data-panel]').forEach(btn => {
  if (!btn.classList.contains('panel-section')) {
    btn.addEventListener('click', () => {
      const target = btn.dataset.panel;
      document.querySelectorAll('.sidebar-item').forEach(s => s.classList.remove('active'));
      btn.classList.add('active');
      document.querySelectorAll('.panel-section').forEach(sec => {
        sec.classList.toggle('hidden', sec.dataset.panel !== target);
      });
    });
  }
});

/* "data-goto" shortcut buttons */
document.querySelectorAll('[data-goto]').forEach(btn => {
  btn.addEventListener('click', () => {
    const target = btn.dataset.goto;
    const sideBtn = document.querySelector(`.sidebar-item[data-panel="${target}"]`);
    if (sideBtn) sideBtn.click();
  });
});
</script>
</body>
</html>