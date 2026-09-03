<?php
require_once __DIR__ . '/functions.php';
$user = currentUser();
$pageTitle = $pageTitle ?? 'IT Ticketing System';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?> | IT Ticketing System</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?= baseUrl('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>
<?php if (isLoggedIn()): ?>
<nav class="navbar navbar-expand-lg navbar-dark app-navbar sticky-top">
  <div class="container-fluid">
    <button class="btn btn-sm btn-outline-light d-lg-none me-2" id="sidebarToggle"><i class="bi bi-list"></i></button>
    <a class="navbar-brand fw-bold" href="<?= baseUrl(dashboardUrl()) ?>"><i class="bi bi-life-preserver"></i> IT Ticketing</a>
    <div class="ms-auto d-flex align-items-center gap-3">
      <span class="badge bg-light text-dark d-none d-md-inline"><?= e(roleLabel($_SESSION['role'])) ?></span>
      <div class="dropdown">
        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
          <i class="bi bi-person-circle fs-4 me-1"></i> <?= e($user['name'] ?? '') ?>
        </a>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item" href="<?= baseUrl('profile.php') ?>"><i class="bi bi-person me-2"></i>My Profile</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item text-danger" href="<?= baseUrl('auth/logout.php') ?>"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
        </ul>
      </div>
    </div>
  </div>
</nav>
<?php endif; ?>
