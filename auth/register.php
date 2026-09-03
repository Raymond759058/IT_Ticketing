<?php
require_once __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) redirect(dashboardUrl());

if (getSetting('allow_registration', '1') != '1') {
    die('Public registration is currently disabled. Please contact your administrator.');
}

$error = '';
$success = '';
$departments = $pdo->query("SELECT * FROM departments WHERE status = 1 ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $role     = $_POST['role'] ?? 'user';
    $deptId   = $_POST['department_id'] ?: null;

    // Only allow self-registration for these roles from the public form.
    // Super Admin accounts should be created by an existing Super Admin.
    $allowedRoles = ['user', 'technician', 'it_admin'];

    if ($name === '' || $email === '' || $password === '') {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (!in_array($role, $allowedRoles)) {
        $error = 'Invalid role selected.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'An account with this email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password, role, department_id, status)
                VALUES (?, ?, ?, ?, ?, ?, 'active')");
            $stmt->execute([$name, $email, $phone, $hash, $role, $deptId]);
            logAudit('New user registered', "Role: $role, Email: $email");
            flash('success', 'Registration successful! You can now login.');
            redirect('auth/login.php');
        }
    }
}

$pageTitle = 'Register';
include __DIR__ . '/../includes/header.php';
?>
<div class="auth-wrapper">
  <div class="card auth-card" style="max-width:540px;">
    <div class="card-header">
      <i class="bi bi-person-plus fs-1"></i>
      <h4 class="mt-2 mb-0">Create an Account</h4>
      <small>Join the IT Ticketing System</small>
    </div>
    <div class="card-body p-4">
      <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

      <form method="POST" autocomplete="off">
        <?= csrfField() ?>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Full Name *</label>
            <input type="text" name="name" class="form-control" required value="<?= e($_POST['name'] ?? '') ?>">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" class="form-control" value="<?= e($_POST['phone'] ?? '') ?>">
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label">Email Address *</label>
          <input type="email" name="email" class="form-control" required value="<?= e($_POST['email'] ?? '') ?>">
        </div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Register As *</label>
            <select name="role" class="form-select" required>
              <option value="user">User (Requester)</option>
              <option value="technician">Technician</option>
              <option value="it_admin">IT Admin</option>
            </select>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Department</label>
            <select name="department_id" class="form-select">
              <option value="">-- Select --</option>
              <?php foreach ($departments as $d): ?>
                <option value="<?= $d['id'] ?>"><?= e($d['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Password *</label>
            <div class="input-group">
              <input type="password" name="password" id="password" class="form-control" required minlength="8">
              <span class="input-group-text password-toggle bi bi-eye" data-target="#password"></span>
            </div>
            <small class="text-muted">Minimum 8 characters</small>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Confirm Password *</label>
            <div class="input-group">
              <input type="password" name="confirm_password" id="confirm_password" class="form-control" required minlength="8">
              <span class="input-group-text password-toggle bi bi-eye" data-target="#confirm_password"></span>
            </div>
          </div>
        </div>
        <button type="submit" class="btn btn-primary w-100 py-2">Create Account</button>
      </form>
      <hr>
      <p class="text-center mb-0 small">Already have an account?
        <a href="<?= baseUrl('auth/login.php') ?>">Login here</a>
      </p>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
