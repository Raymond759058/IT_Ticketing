<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('user');
$uid = $_SESSION['user_id'];

$statuses = ['Open','Pending','In Progress','Resolved','Closed'];
$counts = [];
foreach ($statuses as $s) {
    $stmt = $pdo->prepare("SELECT COUNT(*) c FROM tickets WHERE requester_id = ? AND status = ?");
    $stmt->execute([$uid, $s]);
    $counts[$s] = $stmt->fetch()['c'];
}
$total = array_sum($counts);

$recent = $pdo->prepare("SELECT t.*, p.name priority_name, p.color priority_color, au.name assignee_name
    FROM tickets t LEFT JOIN priorities p ON p.id=t.priority_id LEFT JOIN users au ON au.id=t.assigned_to
    WHERE t.requester_id = ? ORDER BY t.created_at DESC LIMIT 8");
$recent->execute([$uid]);
$recent = $recent->fetchAll();

$pageTitle = 'My Dashboard';
include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content flex-grow-1">
  <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h3 class="mb-0"><i class="bi bi-speedometer2"></i> My Dashboard</h3>
    <a href="ticket-create.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Create Ticket</a>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-6 col-lg-2">
      <div class="stat-card bg-grad-blue"><div class="stat-value"><?= $total ?></div><div>Total Tickets</div></div>
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
  </div>

  <div class="card">
    <div class="card-header bg-white d-flex justify-content-between">
      <strong>My Recent Tickets</strong>
      <a href="tickets.php" class="small">View All →</a>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead><tr><th>Ticket #</th><th>Subject</th><th>Priority</th><th>Status</th><th>Technician</th><th>Created</th></tr></thead>
        <tbody>
        <?php foreach ($recent as $t): ?>
          <tr onclick="window.location='../ticket-view.php?id=<?= $t['id'] ?>'" style="cursor:pointer;">
            <td class="ticket-number"><?= e($t['ticket_number']) ?></td>
            <td><?= e($t['subject']) ?></td>
            <td><?= priorityBadge($t['priority_name'] ?? 'N/A', $t['priority_color']) ?></td>
            <td><?= statusBadge($t['status']) ?></td>
            <td><?= e($t['assignee_name'] ?? 'Unassigned') ?></td>
            <td><?= date('M j, Y', strtotime($t['created_at'])) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$recent): ?><tr><td colspan="6" class="text-center text-muted py-4">You haven't created any tickets yet. <a href="ticket-create.php">Create one now</a>.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
