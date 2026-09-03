<?php
require_once __DIR__ . '/../includes/functions.php';

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$error = '';
$success = '';

$stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_expires > NOW()");
$stmt->execute([$token]);
$u = $stmt->fetch();

if (!$u) {
    $error = 'This password reset link is invalid or has expired. Please request a new one.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?")
            ->execute([$hash, $u['id']]);
        logAudit('Password reset completed', "User ID: {$u['id']}");
        flash('success', 'Your password has been reset successfully. Please login.');
        redirect('auth/login.php');
    }
}

$pageTitle = 'Reset Password';
include __DIR__ . '/../includes/header.php';
?>
<div class="auth-wrapper">
  <div class="card auth-card">
    <div class="card-header">
      <i class="bi bi-shield-lock fs-1"></i>
      <h4 class="mt-2 mb-0">Reset Password</h4>
    </div>
    <div class="card-body p-4">
      <?php if ($error): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
        <p class="text-center"><a href="<?= baseUrl('auth/forgot-password.php') ?>">Request a new link</a></p>
      <?php else: ?>
        <form method="POST">
          <?= csrfField() ?>
          <input type="hidden" name="token" value="<?= e($token) ?>">
          <div class="mb-3">
            <label class="form-label">New Password</label>
            <div class="input-group">
              <input type="password" name="password" id="password" class="form-control" required minlength="8">
              <span class="input-group-text password-toggle bi bi-eye" data-target="#password"></span>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Confirm New Password</label>
            <div class="input-group">
              <input type="password" name="confirm_password" id="confirm_password" class="form-control" required minlength="8">
              <span class="input-group-text password-toggle bi bi-eye" data-target="#confirm_password"></span>
            </div>
          </div>
          <button type="submit" class="btn btn-primary w-100 py-2">Reset Password</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
