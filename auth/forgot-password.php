<?php
require_once __DIR__ . '/../includes/functions.php';

$error = '';
$success = '';
$resetLink = ''; // shown on-screen since no SMTP is configured by default

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $email = trim($_POST['email'] ?? '');
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $u = $stmt->fetch();

    // Always show a generic success message to avoid leaking which emails exist
    $success = 'If an account with that email exists, a password reset link has been sent.';

    if ($u) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?")
            ->execute([$token, $expires, $u['id']]);

        $resetLink = baseUrl('auth/reset-password.php') . '?token=' . $token;

        $body = "Hello " . e($u['name']) . ",<br><br>
            You requested a password reset. Click the link below to set a new password (valid for 1 hour):<br>
            <a href='$resetLink'>$resetLink</a><br><br>
            If you did not request this, please ignore this email.";
        sendNotificationEmail($u['email'], 'Password Reset Request - IT Ticketing System', $body);
        logAudit('Password reset requested', "Email: $email");
    }
}

$pageTitle = 'Forgot Password';
include __DIR__ . '/../includes/header.php';
?>
<div class="auth-wrapper">
  <div class="card auth-card">
    <div class="card-header">
      <i class="bi bi-key fs-1"></i>
      <h4 class="mt-2 mb-0">Forgot Password</h4>
      <small>We'll help you reset it</small>
    </div>
    <div class="card-body p-4">
      <?php if ($success): ?>
        <div class="alert alert-success"><?= e($success) ?></div>
        <?php if ($resetLink): ?>
          <div class="alert alert-info small">
            <strong>Demo mode:</strong> No SMTP server configured, so here is your reset link directly:<br>
            <a href="<?= e($resetLink) ?>"><?= e($resetLink) ?></a>
          </div>
        <?php endif; ?>
      <?php else: ?>
        <p class="text-muted small">Enter your registered email address and we'll send you a link to reset your password.</p>
        <form method="POST">
          <?= csrfField() ?>
          <div class="mb-3">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" class="form-control" required autofocus>
          </div>
          <button type="submit" class="btn btn-primary w-100 py-2">Send Reset Link</button>
        </form>
      <?php endif; ?>
      <hr>
      <p class="text-center mb-0 small"><a href="<?= baseUrl('auth/login.php') ?>"><i class="bi bi-arrow-left"></i> Back to Login</a></p>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
