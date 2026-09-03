<?php
require_once __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) redirect(dashboardUrl());

$error = '';

// Handle "remember me" auto-login via cookie
if (!isLoggedIn() && !empty($_COOKIE['remember_token'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE remember_token = ? AND status = 'active'");
    $stmt->execute([$_COOKIE['remember_token']]);
    $u = $stmt->fetch();
    if ($u) {
        $_SESSION['user_id'] = $u['id'];
        $_SESSION['role'] = $u['role'];
        $_SESSION['name'] = $u['name'];
        redirect(dashboardUrl($u['role']));
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if ($email === '' || $password === '') {
        $error = 'Please enter both email and password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $u = $stmt->fetch();

        if (!$u || !password_verify($password, $u['password'])) {
            $error = 'Invalid email or password.';
            logAudit('Failed login attempt', "Email: $email");
        } elseif ($u['status'] !== 'active') {
            $error = 'Your account is ' . $u['status'] . '. Please contact the administrator.';
        } else {
            $_SESSION['user_id'] = $u['id'];
            $_SESSION['role'] = $u['role'];
            $_SESSION['name'] = $u['name'];

            $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$u['id']]);

            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?")->execute([$token, $u['id']]);
                setcookie('remember_token', $token, time() + (86400 * 30), '/', '', false, true);
            }

            logAudit('User logged in');
            redirect(dashboardUrl($u['role']));
        }
    }
}

$pageTitle = 'Login';
include __DIR__ . '/../includes/header.php';
?>
<div class="auth-wrapper">
  <div class="card auth-card">
    <div class="card-header">
      <i class="bi bi-life-preserver fs-1"></i>
      <h4 class="mt-2 mb-0">IT Ticketing System</h4>
      <small>Sign in to your account</small>
    </div>
    <div class="card-body p-4">
      <?php if ($error): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
      <?php endif; ?>
      <?php if ($msg = flash('success')): ?>
        <div class="alert alert-success"><?= e($msg) ?></div>
      <?php endif; ?>

      <form method="POST" autocomplete="off">
        <?= csrfField() ?>
        <div class="mb-3">
          <label class="form-label">Email Address</label>
          <input type="email" name="email" class="form-control" required autofocus value="<?= e($_POST['email'] ?? '') ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Password</label>
          <div class="input-group">
            <input type="password" name="password" id="password" class="form-control" required>
            <span class="input-group-text password-toggle bi bi-eye" data-target="#password"></span>
          </div>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="remember" id="remember">
            <label class="form-check-label" for="remember">Remember Me</label>
          </div>
          <a href="<?= baseUrl('auth/forgot-password.php') ?>" class="small">Forgot Password?</a>
        </div>
        <button type="submit" class="btn btn-primary w-100 py-2">Login</button>
      </form>

      <hr>
      <p class="text-center mb-0 small">Don't have an account?
        <a href="<?= baseUrl('auth/register.php') ?>">Register here</a>
      </p>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
