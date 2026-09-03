<?php
require_once __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) {
    logAudit('User logged out');
    // Clear remember-me token
    if (!empty($_COOKIE['remember_token'])) {
        $pdo->prepare("UPDATE users SET remember_token = NULL WHERE id = ?")->execute([$_SESSION['user_id']]);
        setcookie('remember_token', '', time() - 3600, '/');
    }
}

$_SESSION = [];
session_destroy();
redirect('auth/login.php');
