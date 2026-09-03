<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('technician');

$uid = $_SESSION['user_id'];

$assigned = $pdo->prepare("SELECT COUNT(*) c FROM tickets WHERE assigned_to = ?");
$assigned->execute([$uid]);
$assignedCount = $assigned->fetch()['c'];

$statuses = ['Open','Pending','In Progress','Resolved','Closed'];
$counts = [];
foreach ($statuses as $s) {
    $stmt = $pdo->prepare("SELECT COUNT(*) c FROM tickets WHERE assigned_to = ? AND status = ?");
    $stmt->execute([$uid, $s]);
    $counts[$s] = $stmt->fetch()['c'];
}

$unassignedCount = $pdo->query("SELECT COUNT(*) c FROM tickets WHERE assigned_to IS NULL AND status NOT IN ('Closed','Cancelled')")->fetch()['c'];

$myTickets = $pdo->prepare("SELECT t.*, p.name priority_name, p.color priority_color, ru.name requester_name
    FROM tickets t LEFT JOIN priorities p ON p.id=t.priority_id LEFT JOIN users ru ON ru.id = t.requester_id
    WHERE t.assigned_to = ? ORDER BY t.updated_at DESC LIMIT 8");
$myTickets->execute([$uid]);
$myTickets = $myTickets->fetchAll();

$pageTitle = 'Technician Dashboard';
include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content flex-grow-1">
  <h3 class="mb-4"><i class="bi bi-speedometer2"></i> Technician Dashboard</h3>

  <div class="row g-3 mb-4">
    <div class="col-6 col-lg-2">
      <div class="stat-card bg-grad-blue"><div class="stat-value"><?= $assignedCount ?></div><div>My Tickets</div></div>
    </div>
    <div class="col-6 col-lg-2">
      <div class="stat-card bg-grad-red"><div class="stat-value"><?= $counts['Open'] ?></div><div>🔴 Open</div></div>
    </div>
    <div class="col-6 col-lg-2">
      <div class="stat-card bg-grad-yellow"><div class="stat-value"><?= $counts['Pending'] ?></div><div>🟡 Pending</div></div>
    </div>
    <div class="col-6 col-lg-2">
      <div class="stat-card bg-grad-cyan"><div class="stat-value"><?= $counts['In Progress'] ?></div><div>🔵 In Progress</div></div>
    </div>
    <div class="col-6 col-lg-2">
      <div class="stat-card bg-grad-green"><div class="stat-value"><?= $counts['Resolved']+$counts['Closed'] ?></div><div>🟢 Resolved</div></div>
    </div>
    <div class="col-6 col-lg-2">
      <div class="stat-card bg-grad-gray"><div class="stat-value"><?= $unassignedCount ?></div><div>Unassigned Pool</div></div>
    </div>
  </div>

  <div class="card">
    <div class="card-header bg-white d-flex justify-content-between">
      <strong>My Recent Tickets</strong>
      <a href="tickets.php" class="small">View All →</a>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead><tr><th>Ticket #</th><th>Subject</th><th>Requester</th><th>Priority</th><th>Status</th><th>Updated</th></tr></thead>
        <tbody>
        <?php foreach ($myTickets as $t): ?>
          <tr onclick="window.location='../ticket-view.php?id=<?= $t['id'] ?>'" style="cursor:pointer;">
            <td class="ticket-number"><?= e($t['ticket_number']) ?></td>
            <td><?= e($t['subject']) ?></td>
            <td><?= e($t['requester_name']) ?></td>
            <td><?= priorityBadge($t['priority_name'] ?? 'N/A', $t['priority_color']) ?></td>
            <td><?= statusBadge($t['status']) ?></td>
            <td><?= date('M j, g:i A', strtotime($t['updated_at'])) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$myTickets): ?><tr><td colspan="6" class="text-center text-muted py-4">No tickets assigned yet.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
