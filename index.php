<?php
/* InternPortal – index.php  (Landing + Auth page) */
require_once 'php/config.php';
sessionStart();
if (isLoggedIn()) redirect('dashboard.php');

$tab   = $_GET['tab']   ?? 'login';
$error = getFlash('error');
$csrf  = csrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>InternPortal – Virtual Internship & Project Submission System</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

<!-- ═══════════════════ NAVBAR ═══════════════════ -->
<nav class="navbar">
  <a class="nav-logo" href="index.php">Intern<span>Portal</span></a>
  <div class="nav-links">
    <a class="nav-link active" href="#features">Features</a>
    <a class="nav-link" href="#roles">Roles</a>
    <a class="nav-link" href="#stack">Stack</a>
  </div>
  <a class="nav-cta" href="#auth">Get Started</a>
</nav>

<!-- ═══════════════════ HERO ═══════════════════ -->
<section class="hero">
  <div class="hero-orb hero-orb-1"></div>
  <div class="hero-orb hero-orb-2"></div>
  <div class="hero-badge">Virtual Internship &amp; Project Submission System</div>
  <h1>Manage <em>internships</em><br>without the chaos</h1>
  <p>A centralized platform where mentors assign tasks and interns submit work — tracked, graded, and organized in one place.</p>
  <div class="hero-btns">
    <button class="btn-hero-primary" onclick="document.getElementById('auth').scrollIntoView({behavior:'smooth'})">Start as Intern</button>
    <button class="btn-hero-outline" onclick="document.getElementById('auth').scrollIntoView({behavior:'smooth'})">Join as Mentor</button>
  </div>
</section>

<!-- ═══════════════════ STATS ═══════════════════ -->
<div class="stats-bar">
  <div class="stat-item"><span class="stat-num">3</span><span class="stat-label">User Roles</span></div>
  <div class="stat-item"><span class="stat-num">5+</span><span class="stat-label">Core Features</span></div>
  <div class="stat-item"><span class="stat-num">100%</span><span class="stat-label">Secure Uploads</span></div>
  <div class="stat-item"><span class="stat-num">MySQL</span><span class="stat-label">Powered Database</span></div>
</div>

<!-- ═══════════════════ FEATURES ═══════════════════ -->
<section class="section" id="features">
  <span class="section-eyebrow">Features</span>
  <h2 class="section-title">Everything you need to run<br>a virtual internship</h2>
  <p class="section-sub">Built for educators, mentors, and interns who want structure without complexity.</p>
  <div class="features-grid">
    <div class="feature-card">
      <div class="feat-icon green">🔐</div>
      <h3>Role-Based Access</h3>
      <p>Separate dashboards for Admin, Mentor, and Intern with secure session management and password hashing.</p>
    </div>
    <div class="feature-card">
      <div class="feat-icon orange">📋</div>
      <h3>Task Assignment</h3>
      <p>Mentors create and assign project tasks with deadlines. Interns see only their assigned work.</p>
    </div>
    <div class="feature-card">
      <div class="feat-icon blue">📁</div>
      <h3>File Submission</h3>
      <p>Interns upload project files securely. Submissions are tracked and stored with full audit trail.</p>
    </div>
    <div class="feature-card">
      <div class="feat-icon purple">⭐</div>
      <h3>Grading &amp; Feedback</h3>
      <p>Mentors evaluate submissions and provide structured feedback directly on the platform.</p>
    </div>
    <div class="feature-card">
      <div class="feat-icon amber">📊</div>
      <h3>Progress Tracking</h3>
      <p>Visual overview of submission status, grades, and intern performance at a glance.</p>
    </div>
    <div class="feature-card">
      <div class="feat-icon green">🔔</div>
      <h3>Notifications</h3>
      <p>Real-time in-app notifications keep every user updated on deadlines, submissions, and grades.</p>
    </div>
  </div>
</section>

<!-- ═══════════════════ ROLES ═══════════════════ -->
<div class="roles-wrap" id="roles">
  <div class="roles-inner">
    <span class="section-eyebrow">Who it's for</span>
    <h2 class="section-title" style="margin-bottom:40px;">Three roles, one platform</h2>
    <div class="roles-grid">
      <div class="role-card admin">
        <h3>Admin</h3>
        <div class="role-tag">System Manager</div>
        <ul>
          <li>Manage all users &amp; roles</li>
          <li>View system-wide reports</li>
          <li>Activate / deactivate accounts</li>
          <li>Full database oversight</li>
        </ul>
      </div>
      <div class="role-card mentor">
        <h3>Mentor</h3>
        <div class="role-tag">Company / Supervisor</div>
        <ul>
          <li>Create project tasks</li>
          <li>Assign to specific interns</li>
          <li>Review &amp; download submissions</li>
          <li>Provide grades &amp; feedback</li>
        </ul>
      </div>
      <div class="role-card intern">
        <h3>Intern</h3>
        <div class="role-tag">Student / Employee</div>
        <ul>
          <li>View assigned projects</li>
          <li>Submit work files securely</li>
          <li>Track submission status</li>
          <li>Receive grades &amp; feedback</li>
        </ul>
      </div>
    </div>
  </div>
</div>

<!-- ═══════════════════ STACK ═══════════════════ -->
<section class="section section-center" id="stack">
  <span class="section-eyebrow">Tech Stack</span>
  <h2 class="section-title">Built with proven technology</h2>
  <div class="tech-grid">
    <span class="tech-pill">HTML5 / CSS3</span>
    <span class="tech-pill">JavaScript</span>
    <span class="tech-pill">PHP Backend</span>
    <span class="tech-pill">MySQL Database</span>
    <span class="tech-pill">XAMPP Server</span>
    <span class="tech-pill">VS Code IDE</span>
  </div>
</section>

<!-- ═══════════════════ AUTH ═══════════════════ -->
<section id="auth" style="background:var(--bg);padding:60px 0;">
  <div class="auth-wrap" style="min-height:auto;">
    <div class="auth-card">
      <span class="auth-logo">Intern<span>Portal</span></span>
      <h2 class="auth-title">Welcome</h2>
      <p class="auth-sub">Sign in or create an account to continue</p>

      <?php if ($error): ?>
        <div class="alert error show"><?= h($error) ?></div>
      <?php endif; ?>

      <!-- Tabs -->
      <div class="auth-tabs">
        <button class="auth-tab <?= $tab==='login'?'active':'' ?>" data-tab="login">Sign In</button>
        <button class="auth-tab <?= $tab==='register'?'active':'' ?>" data-tab="register">Register</button>
      </div>

      <!-- LOGIN FORM -->
      <form id="login-form" action="php/auth.php" method="POST" class="<?= $tab!=='login'?'hidden':'' ?>">
        <input type="hidden" name="action" value="login">
        <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
        <div class="form-group">
          <label class="form-label">Email address</label>
          <input class="form-input" type="email" name="email" placeholder="you@example.com" required>
          <span class="form-error"></span>
        </div>
        <div class="form-group">
          <label class="form-label">Password</label>
          <input class="form-input" type="password" name="password" placeholder="••••••••" required>
          <span class="form-error"></span>
        </div>
        <button type="submit" class="btn-primary">Sign In →</button>
        <p style="font-size:12px;color:var(--text-3);text-align:center;margin-top:14px;">
          Demo — Admin: admin@internportal.com | Mentor: sadia@internportal.com | Intern: dhroubo@internportal.com<br>
          All passwords: <strong>password</strong>
        </p>
      </form>

      <!-- REGISTER FORM -->
      <form id="register-form" action="php/auth.php" method="POST" class="<?= $tab!=='register'?'hidden':'' ?>">
        <input type="hidden" name="action" value="register">
        <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
        <div class="form-group">
          <label class="form-label">Full name <span style="color:var(--red)">*</span></label>
          <input class="form-input" type="text" name="name" placeholder="Dhroubo Jyoti Mondal" required>
          <span class="form-error"></span>
        </div>
        <div class="form-row">
          <div class="form-group mb-0">
            <label class="form-label">Student ID</label>
            <input class="form-input" type="text" name="student_id" placeholder="232-35-182">
          </div>
          <div class="form-group mb-0">
            <label class="form-label">Role <span style="color:var(--red)">*</span></label>
            <select class="form-select" name="role">
              <option value="intern">Intern</option>
              <option value="mentor">Mentor</option>
            </select>
          </div>
        </div>
        <div class="form-row" style="margin-top:18px;">
          <div class="form-group mb-0">
            <label class="form-label">Batch</label>
            <input class="form-input" type="text" name="batch" placeholder="41">
          </div>
          <div class="form-group mb-0">
            <label class="form-label">Section</label>
            <input class="form-input" type="text" name="section" placeholder="E1">
          </div>
        </div>
        <div class="form-group" style="margin-top:18px;">
          <label class="form-label">Email address <span style="color:var(--red)">*</span></label>
          <input class="form-input" type="email" name="email" placeholder="you@example.com" required>
          <span class="form-error"></span>
        </div>
        <div class="form-row">
          <div class="form-group mb-0">
            <label class="form-label">Password <span style="color:var(--red)">*</span></label>
            <input class="form-input" type="password" name="password" placeholder="Min. 6 characters" required>
            <span class="form-error"></span>
          </div>
          <div class="form-group mb-0">
            <label class="form-label">Confirm password <span style="color:var(--red)">*</span></label>
            <input class="form-input" type="password" name="confirm_password" placeholder="Repeat password" required>
            <span class="form-error"></span>
          </div>
        </div>
        <button type="submit" class="btn-primary" style="margin-top:18px;">Create Account →</button>
      </form>
    </div>
  </div>
</section>

<!-- ═══════════════════ FOOTER ═══════════════════ -->
<footer>
  <div class="foot-logo">Intern<span>Portal</span></div>
  <p>SE-322 Software Engineering Web Application Lab · Spring 2026<br>
  Dhroubo Jyoti Mondal (232-35-182) · Salim Sadman Sadi (129) · Rakib Hossain (164)<br>
  Course Teacher: Sadia Sultana · Batch 41, Section E1</p>
</footer>

<script src="js/main.js"></script>
</body>
</html>