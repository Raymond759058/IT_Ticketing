<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole(['super_admin', 'it_admin']);

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 40;
$offset = ($page - 1) * $perPage;

$total = $pdo->query("SELECT COUNT(*) c FROM audit_logs")->fetch()['c'];
$stmt = $pdo->prepare("SELECT a.*, u.name user_name, t.ticket_number FROM audit_logs a
    LEFT JOIN users u ON u.id = a.user_id
    LEFT JOIN tickets t ON t.id = a.ticket_id
    ORDER BY a.created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute();
$logs = $stmt->fetchAll();
$totalPages = ceil($total / $perPage);

$pageTitle = 'Audit Logs';
include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content flex-grow-1">
  <h3 class="mb-4"><i class="bi bi-shield-check"></i> Audit Logs</h3>
  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead><tr><th>Date/Time</th><th>User</th><th>Action</th><th>Details</th><th>Ticket</th><th>IP Address</th></tr></thead>
        <tbody>
        <?php foreach ($logs as $l): ?>
          <tr>
            <td><?= date('M j, Y g:i A', strtotime($l['created_at'])) ?></td>
            <td><?= e($l['user_name'] ?? 'System') ?></td>
            <td><?= e($l['action']) ?></td>
            <td class="small text-muted"><?= e($l['details']) ?></td>
            <td><?= $l['ticket_number'] ? '<span class="ticket-number">'.e($l['ticket_number']).'</span>' : '-' ?></td>
            <td class="small"><?= e($l['ip_address']) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$logs): ?><tr><td colspan="6" class="text-center text-muted py-4">No log entries found.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php if ($totalPages > 1): ?>
  <nav class="mt-3">
    <ul class="pagination">
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <li class="page-item <?= $i==$page?'active':'' ?>"><a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a></li>
      <?php endfor; ?>
    </ul>
  </nav>
  <?php endif; ?>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
