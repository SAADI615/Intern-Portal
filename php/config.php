<?php
/* ============================================================
   InternPortal – php/config.php
   Database configuration & shared helpers
   ============================================================ */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'internportal');

define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10 MB
define('ALLOWED_EXT', ['pdf', 'zip', 'docx', 'doc', 'rar', 'txt', 'png', 'jpg']);

/* ── Database connection (singleton) ────────────────────── */
function db(): mysqli {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            die('Database connection failed: ' . $conn->connect_error);
        }
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}

/* ── Session helpers ─────────────────────────────────────── */
function sessionStart(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
}

function isLoggedIn(): bool {
    sessionStart();
    return isset($_SESSION['user_id']);
}

function currentUser(): ?array {
    sessionStart();
    if (!isset($_SESSION['user_id'])) return null;
    return [
        'id'   => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'],
        'role' => $_SESSION['user_role'],
    ];
}

function requireLogin(string $redirectTo = '../index.php'): void {
    if (!isLoggedIn()) {
        header('Location: ' . $redirectTo);
        exit;
    }
}

function requireRole(string $role, string $redirectTo = '../dashboard.php'): void {
    requireLogin();
    if (currentUser()['role'] !== $role) {
        header('Location: ' . $redirectTo);
        exit;
    }
}

/* ── Flash messages ──────────────────────────────────────── */
function flash(string $key, string $msg): void {
    sessionStart();
    $_SESSION['flash'][$key] = $msg;
}

function getFlash(string $key): ?string {
    sessionStart();
    $msg = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $msg;
}

/* ── CSRF helpers ────────────────────────────────────────── */
function csrfToken(): string {
    sessionStart();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die('Invalid CSRF token.');
    }
}

/* ── Sanitize output ─────────────────────────────────────── */
function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

/* ── Redirect helper ─────────────────────────────────────── */
function redirect(string $url): never {
    header('Location: ' . $url);
    exit;
}

/* ── Initials from name ──────────────────────────────────── */
function initials(string $name): string {
    $parts = explode(' ', trim($name));
    $ini = strtoupper(substr($parts[0], 0, 1));
    if (isset($parts[1])) $ini .= strtoupper(substr($parts[1], 0, 1));
    return $ini;
}

/* ── Grade colour class ──────────────────────────────────── */
function gradeClass(int $grade): string {
    return $grade >= 75 ? 'green' : ($grade >= 50 ? 'orange' : 'red');
}
