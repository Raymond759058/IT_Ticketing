<?php
require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ============================================================
   AUTH HELPERS
   ============================================================ */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function currentUser() {
    global $pdo;
    if (!isLoggedIn()) return null;
    static $user = null;
    if ($user === null) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
    }
    return $user;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . baseUrl('auth/login.php'));
        exit;
    }
}

function requireRole($roles) {
    requireLogin();
    $roles = is_array($roles) ? $roles : [$roles];
    if (!in_array($_SESSION['role'], $roles)) {
        header('Location: ' . baseUrl('unauthorized.php'));
        exit;
    }
}

function isAdmin() {
    return isLoggedIn() && in_array($_SESSION['role'], ['super_admin', 'it_admin']);
}

function isSuperAdmin() {
    return isLoggedIn() && $_SESSION['role'] === 'super_admin';
}

function isTechnician() {
    return isLoggedIn() && $_SESSION['role'] === 'technician';
}

function roleLabel($role) {
    $labels = [
        'super_admin' => 'Super Admin',
        'it_admin'    => 'IT Admin',
        'technician'  => 'Technician',
        'user'        => 'User',
    ];
    return $labels[$role] ?? ucfirst($role);
}

/**
 * Returns the dashboard path RELATIVE to the project root (no domain/base prefix).
 * Use dashboardUrl() with redirect() (which applies baseUrl() itself), or
 * wrap it in baseUrl(dashboardUrl()) when you need a full href for markup.
 */
function dashboardUrl($role = null) {
    $role = $role ?? ($_SESSION['role'] ?? 'user');
    if (in_array($role, ['super_admin', 'it_admin'])) return 'admin/dashboard.php';
    if ($role === 'technician') return 'technician/dashboard.php';
    return 'user/dashboard.php';
}

/* ============================================================
   PATHS
   ============================================================ */
function baseUrl($path = '') {
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    // Walk up to project root based on known subfolders
    $root = preg_replace('#/(admin|technician|user|auth|ajax)$#', '', $base);
    return $root . '/' . ltrim($path, '/');
}

/* ============================================================
   CSRF PROTECTION
   ============================================================ */
function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . csrfToken() . '">';
}

function verifyCsrf() {
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Invalid CSRF token. Please refresh the page and try again.');
    }
}

/* ============================================================
   TICKET HELPERS
   ============================================================ */
function generateTicketNumber() {
    global $pdo;
    $prefix = getSetting('ticket_prefix', 'TCK');
    $year = date('Y');
    $stmt = $pdo->query("SELECT COUNT(*) c FROM tickets WHERE YEAR(created_at) = " . intval($year));
    $count = $stmt->fetch()['c'] + 1;
    return sprintf('%s-%s-%05d', $prefix, $year, $count);
}

function statusBadge($status) {
    $map = [
        'Open'        => ['bg-danger',  '🔴'],
        'Pending'     => ['bg-warning text-dark', '🟡'],
        'In Progress' => ['bg-primary', '🔵'],
        'Resolved'    => ['bg-success', '🟢'],
        'Closed'      => ['bg-success', '🟢'],
        'Cancelled'   => ['bg-secondary', '⚫'],
    ];
    [$cls, $icon] = $map[$status] ?? ['bg-secondary', '⚫'];
    return '<span class="badge ' . $cls . ' status-badge">' . $icon . ' ' . htmlspecialchars($status) . '</span>';
}

function priorityBadge($name, $color = null) {
    $isHigh = in_array(strtolower($name), ['high', 'critical']);
    $style = $color ? "background-color:$color;color:#fff;" : '';
    $icon = $isHigh ? '🟣' : '⚪';
    return '<span class="badge priority-badge" style="' . $style . '">' . $icon . ' ' . htmlspecialchars($name) . '</span>';
}

function calcResolutionTime($created, $resolved) {
    if (!$resolved) return '-';
    $diff = strtotime($resolved) - strtotime($created);
    if ($diff < 0) return '-';
    $h = floor($diff / 3600);
    $m = floor(($diff % 3600) / 60);
    return $h . 'h ' . $m . 'm';
}

/* ============================================================
   SETTINGS
   ============================================================ */
function getSetting($key, $default = null) {
    global $pdo;
    static $cache = [];
    if (isset($cache[$key])) return $cache[$key];
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    $cache[$key] = $row ? $row['setting_value'] : $default;
    return $cache[$key];
}

function setSetting($key, $value) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    $stmt->execute([$key, $value]);
}

/* ============================================================
   AUDIT LOG
   ============================================================ */
function logAudit($action, $details = '', $ticketId = null) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, ticket_id, action, details, ip_address)
        VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $_SESSION['user_id'] ?? null,
        $ticketId,
        $action,
        $details,
        $_SERVER['REMOTE_ADDR'] ?? ''
    ]);
}

/* ============================================================
   FILE UPLOAD
   ============================================================ */
function handleUpload($fileField, $subDir = 'tickets') {
    if (empty($_FILES[$fileField]['name'])) return null;

    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'];
    $maxSize = 5 * 1024 * 1024; // 5MB

    $file = $_FILES[$fileField];
    if ($file['error'] !== UPLOAD_ERR_OK) return null;
    if ($file['size'] > $maxSize) {
        throw new Exception('File is too large. Max size is 5MB.');
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) {
        throw new Exception('File type not allowed.');
    }

    $uploadDir = __DIR__ . '/../uploads/' . $subDir . '/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $newName = uniqid('file_', true) . '.' . $ext;
    move_uploaded_file($file['tmp_name'], $uploadDir . $newName);

    return $subDir . '/' . $newName;
}

/* ============================================================
   EMAIL (stub - wire to PHPMailer/SMTP in production)
   ============================================================ */
function sendNotificationEmail($to, $subject, $body) {
    if (getSetting('email_notifications', '1') != '1') return;
    // In production, replace with PHPMailer + SMTP for cPanel/XAMPP.
    // mail() works out of the box on most cPanel hosts.
    $headers = "MIME-Version: 1.0\r\nContent-type:text/html;charset=UTF-8\r\n";
    $headers .= "From: " . getSetting('site_email', 'noreply@example.com') . "\r\n";
    @mail($to, $subject, $body, $headers);
}

/* ============================================================
   MISC
   ============================================================ */
function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function flash($key, $message = null) {
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return;
    }
    if (!empty($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}

function redirect($path) {
    header('Location: ' . baseUrl($path));
    exit;
}
