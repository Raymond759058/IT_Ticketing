<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole(['super_admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    setSetting('site_name', trim($_POST['site_name']));
    setSetting('site_email', trim($_POST['site_email']));
    setSetting('ticket_prefix', trim($_POST['ticket_prefix']));
    setSetting('allow_registration', isset($_POST['allow_registration']) ? '1' : '0');
    setSetting('email_notifications', isset($_POST['email_notifications']) ? '1' : '0');
    logAudit('Updated system settings');
    flash('success', 'Settings saved successfully.');
    redirect('admin/settings.php');
}

$pageTitle = 'System Settings';
include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content flex-grow-1">
  <h3 class="mb-4"><i class="bi bi-gear"></i> System Settings</h3>
  <?php if ($msg = flash('success')): ?><div class="alert alert-success alert-auto-dismiss"><?= e($msg) ?></div><?php endif; ?>

  <div class="card" style="max-width:640px;">
    <div class="card-body">
      <form method="POST">
        <?= csrfField() ?>
        <div class="mb-3">
          <label class="form-label">Site Name</label>
          <input type="text" name="site_name" class="form-control" value="<?= e(getSetting('site_name')) ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Support Email</label>
          <input type="email" name="site_email" class="form-control" value="<?= e(getSetting('site_email')) ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Ticket Number Prefix</label>
          <input type="text" name="ticket_prefix" class="form-control" value="<?= e(getSetting('ticket_prefix')) ?>" maxlength="10">
        </div>
        <div class="form-check mb-3">
          <input class="form-check-input" type="checkbox" name="allow_registration" id="ar" <?= getSetting('allow_registration')=='1'?'checked':'' ?>>
          <label class="form-check-label" for="ar">Allow Public User Registration</label>
        </div>
        <div class="form-check mb-3">
          <input class="form-check-input" type="checkbox" name="email_notifications" id="en" <?= getSetting('email_notifications')=='1'?'checked':'' ?>>
          <label class="form-check-label" for="en">Enable Email Notifications</label>
        </div>
        <button type="submit" class="btn btn-primary">Save Settings</button>
      </form>
    </div>
  </div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
