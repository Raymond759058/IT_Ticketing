<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$user = currentUser();
$error = ''; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    if (isset($_POST['update_profile'])) {
        $name = trim($_POST['name']);
        $phone = trim($_POST['phone']);
        $pdo->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?")->execute([$name, $phone, $user['id']]);
        $_SESSION['name'] = $name;
        $success = 'Profile updated successfully.';
        logAudit('Updated own profile');
    }

    if (isset($_POST['change_password'])) {
        $current = $_POST['current_password'];
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];

        if (!password_verify($current, $user['password'])) {
            $error = 'Current password is incorrect.';
        } elseif (strlen($new) < 8) {
            $error = 'New password must be at least 8 characters.';
        } elseif ($new !== $confirm) {
            $error = 'New passwords do not match.';
        } else {
            $hash = password_hash($new, PASSWORD_BCRYPT);
            $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hash, $user['id']]);
            $success = 'Password changed successfully.';
            logAudit('Changed own password');
        }
    }
    $user = currentUser();
}

$pageTitle = 'My Profile';
include __DIR__ . '/includes/header.php';
?>
<div class="d-flex">
<?php include __DIR__ . '/includes/sidebar.php'; ?>
<div class="main-content flex-grow-1">
  <h3 class="mb-4"><i class="bi bi-person-circle"></i> My Profile</h3>
  <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

  <div class="row">
    <div class="col-lg-6 mb-4">
      <div class="card">
        <div class="card-header bg-white"><strong>Profile Information</strong></div>
        <div class="card-body">
          <form method="POST">
            <?= csrfField() ?>
            <div class="mb-3">
              <label class="form-label">Full Name</label>
              <input type="text" name="name" class="form-control" value="<?= e($user['name']) ?>" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Email</label>
              <input type="email" class="form-control" value="<?= e($user['email']) ?>" disabled>
            </div>
            <div class="mb-3">
              <label class="form-label">Phone</label>
              <input type="text" name="phone" class="form-control" value="<?= e($user['phone']) ?>">
            </div>
            <div class="mb-3">
              <label class="form-label">Role</label>
              <input type="text" class="form-control" value="<?= e(roleLabel($user['role'])) ?>" disabled>
            </div>
            <button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button>
          </form>
        </div>
      </div>
    </div>

    <div class="col-lg-6 mb-4">
      <div class="card">
        <div class="card-header bg-white"><strong>Change Password</strong></div>
        <div class="card-body">
          <form method="POST">
            <?= csrfField() ?>
            <div class="mb-3">
              <label class="form-label">Current Password</label>
              <div class="input-group">
                <input type="password" name="current_password" id="cp" class="form-control" required>
                <span class="input-group-text password-toggle bi bi-eye" data-target="#cp"></span>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label">New Password</label>
              <div class="input-group">
                <input type="password" name="new_password" id="np" class="form-control" required minlength="8">
                <span class="input-group-text password-toggle bi bi-eye" data-target="#np"></span>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label">Confirm New Password</label>
              <div class="input-group">
                <input type="password" name="confirm_password" id="ncp" class="form-control" required minlength="8">
                <span class="input-group-text password-toggle bi bi-eye" data-target="#ncp"></span>
              </div>
            </div>
            <button type="submit" name="change_password" class="btn btn-warning">Change Password</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
