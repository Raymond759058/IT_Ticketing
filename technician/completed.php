<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('technician');
$uid = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT t.*, p.name priority_name, p.color priority_color, ru.name requester_name
    FROM tickets t LEFT JOIN priorities p ON p.id=t.priority_id LEFT JOIN users ru ON ru.id=t.requester_id
    WHERE t.assigned_to = ? AND t.status IN ('Resolved','Closed') ORDER BY t.resolved_at DESC");
$stmt->execute([$uid]);
$tickets = $stmt->fetchAll();

$pageTitle = 'Completed Tickets';
include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content flex-grow-1">
  <h3 class="mb-4"><i class="bi bi-check2-circle"></i> Completed Tickets</h3>
  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead><tr><th>Ticket #</th><th>Subject</th><th>Requester</th><th>Priority</th><th>Status</th><th>Resolution Time</th></tr></thead>
        <tbody>
        <?php foreach ($tickets as $t): ?>
          <tr onclick="window.location='../ticket-view.php?id=<?= $t['id'] ?>'" style="cursor:pointer;">
            <td class="ticket-number"><?= e($t['ticket_number']) ?></td>
            <td><?= e($t['subject']) ?></td>
            <td><?= e($t['requester_name']) ?></td>
            <td><?= priorityBadge($t['priority_name'] ?? 'N/A', $t['priority_color']) ?></td>
            <td><?= statusBadge($t['status']) ?></td>
            <td><?= calcResolutionTime($t['created_at'], $t['resolved_at']) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$tickets): ?><tr><td colspan="6" class="text-center text-muted py-4">No completed tickets yet.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
