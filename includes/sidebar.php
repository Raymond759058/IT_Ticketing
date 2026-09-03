<?php
$role = $_SESSION['role'] ?? 'user';
$current = basename($_SERVER['PHP_SELF']);

function navLink($url, $icon, $label, $current, $match) {
    $active = ($current === $match) ? 'active' : '';
    echo '<a href="' . $url . '" class="nav-link ' . $active . '"><i class="bi ' . $icon . '"></i> <span>' . $label . '</span></a>';
}
?>
<div class="sidebar" id="appSidebar">
  <div class="sidebar-inner">
    <?php if (in_array($role, ['super_admin','it_admin'])): ?>
        <?php navLink(baseUrl('admin/dashboard.php'), 'bi-speedometer2', 'Dashboard', $current, 'dashboard.php'); ?>
        <?php navLink(baseUrl('admin/tickets.php'), 'bi-ticket-detailed', 'All Tickets', $current, 'tickets.php'); ?>
        <?php navLink(baseUrl('admin/ticket-form.php'), 'bi-plus-circle', 'Create Ticket', $current, 'ticket-form.php'); ?>
        <?php navLink(baseUrl('admin/users.php'), 'bi-people', 'Manage Users', $current, 'users.php'); ?>
        <?php navLink(baseUrl('admin/reports.php'), 'bi-bar-chart', 'Reports', $current, 'reports.php'); ?>
        <?php navLink(baseUrl('admin/audit-logs.php'), 'bi-shield-check', 'Audit Logs', $current, 'audit-logs.php'); ?>
        <?php if ($role === 'super_admin'): ?>
            <?php navLink(baseUrl('admin/departments.php'), 'bi-building', 'Departments', $current, 'departments.php'); ?>
            <?php navLink(baseUrl('admin/categories.php'), 'bi-tags', 'Categories', $current, 'categories.php'); ?>
            <?php navLink(baseUrl('admin/priorities.php'), 'bi-flag', 'Priorities', $current, 'priorities.php'); ?>
            <?php navLink(baseUrl('admin/settings.php'), 'bi-gear', 'System Settings', $current, 'settings.php'); ?>
        <?php endif; ?>

    <?php elseif ($role === 'technician'): ?>
        <?php navLink(baseUrl('technician/dashboard.php'), 'bi-speedometer2', 'Dashboard', $current, 'dashboard.php'); ?>
        <?php navLink(baseUrl('technician/tickets.php'), 'bi-ticket-detailed', 'Assigned Tickets', $current, 'tickets.php'); ?>
        <?php navLink(baseUrl('technician/completed.php'), 'bi-check2-circle', 'Completed Tickets', $current, 'completed.php'); ?>

    <?php else: ?>
        <?php navLink(baseUrl('user/dashboard.php'), 'bi-speedometer2', 'Dashboard', $current, 'dashboard.php'); ?>
        <?php navLink(baseUrl('user/ticket-create.php'), 'bi-plus-circle', 'Create Ticket', $current, 'ticket-create.php'); ?>
        <?php navLink(baseUrl('user/tickets.php'), 'bi-ticket-detailed', 'My Tickets', $current, 'tickets.php'); ?>
    <?php endif; ?>
  </div>
</div>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
