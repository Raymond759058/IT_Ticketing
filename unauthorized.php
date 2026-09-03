<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Access Denied';
include __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-column align-items-center justify-content-center text-center" style="min-height:80vh;">
  <i class="bi bi-shield-exclamation text-danger" style="font-size:4rem;"></i>
  <h2 class="mt-3">Access Denied</h2>
  <p class="text-muted">You do not have permission to view this page.</p>
  <a href="<?= isLoggedIn() ? baseUrl(dashboardUrl()) : baseUrl('auth/login.php') ?>" class="btn btn-primary mt-2">Go Back</a>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
