<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole(['super_admin', 'it_admin']);

// Stats
$total = $pdo->query("SELECT COUNT(*) c FROM tickets")->fetch()['c'];
$statuses = ['Open','Pending','In Progress','Resolved','Closed','Cancelled'];
$statusCounts = [];
foreach ($statuses as $s) {
    $stmt = $pdo->prepare("SELECT COUNT(*) c FROM tickets WHERE status = ?");
    $stmt->execute([$s]);
    $statusCounts[$s] = $stmt->fetch()['c'];
}

// Priority breakdown (for chart)
$priorityRows = $pdo->query("SELECT p.name, COUNT(t.id) c FROM priorities p
    LEFT JOIN tickets t ON t.priority_id = p.id GROUP BY p.id, p.name ORDER BY p.level")->fetchAll();

// Tickets over last 7 days
$last7 = $pdo->query("SELECT DATE(created_at) d, COUNT(*) c FROM tickets
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(created_at) ORDER BY d")->fetchAll();
$last7map = [];
foreach ($last7 as $r) $last7map[$r['d']] = $r['c'];
$days = []; $dayCounts = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i day"));
    $days[] = date('M j', strtotime($d));
    $dayCounts[] = $last7map[$d] ?? 0;
}

// Recent tickets
$recent = $pdo->query("SELECT t.*, p.name priority_name, p.color priority_color, u.name requester_name
    FROM tickets t
    LEFT JOIN priorities p ON p.id = t.priority_id
    LEFT JOIN users u ON u.id = t.requester_id
    ORDER BY t.created_at DESC LIMIT 8")->fetchAll();

$totalUsers = $pdo->query("SELECT COUNT(*) c FROM users")->fetch()['c'];
$totalTechs = $pdo->query("SELECT COUNT(*) c FROM users WHERE role = 'technician'")->fetch()['c'];

$pageTitle = 'Admin Dashboard';
include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content flex-grow-1">
  <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h3 class="mb-0"><i class="bi bi-speedometer2"></i> Admin Dashboard</h3>
    <a href="ticket-form.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Create Ticket</a>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-6 col-lg-2">
      <div class="stat-card bg-grad-blue">
        <div class="d-flex justify-content-between"><div><div class="stat-value"><?= $total ?></div><div>Total Tickets</div></div><i class="bi bi-ticket-detailed stat-icon"></i></div>
      </div>
    </div>
    <div class="col-6 col-lg-2">
      <div class="stat-card bg-grad-red">
        <div class="d-flex justify-content-between"><div><div class="stat-value"><?= $statusCounts['Open'] ?></div><div>🔴 Open</div></div><i class="bi bi-exclamation-circle stat-icon"></i></div>
      </div>
    </div>
    <div class="col-6 col-lg-2">
      <div class="stat-card bg-grad-yellow">
        <div class="d-flex justify-content-between"><div><div class="stat-value"><?= $statusCounts['Pending'] ?></div><div>🟡 Pending</div></div><i class="bi bi-hourglass-split stat-icon"></i></div>
      </div>
    </div>
    <div class="col-6 col-lg-2">
      <div class="stat-card bg-grad-cyan">
        <div class="d-flex justify-content-between"><div><div class="stat-value"><?= $statusCounts['In Progress'] ?></div><div>🔵 In Progress</div></div><i class="bi bi-arrow-repeat stat-icon"></i></div>
      </div>
    </div>
    <div class="col-6 col-lg-2">
      <div class="stat-card bg-grad-green">
        <div class="d-flex justify-content-between"><div><div class="stat-value"><?= $statusCounts['Resolved'] + $statusCounts['Closed'] ?></div><div>🟢 Resolved/Closed</div></div><i class="bi bi-check-circle stat-icon"></i></div>
      </div>
    </div>
    <div class="col-6 col-lg-2">
      <div class="stat-card bg-grad-gray">
        <div class="d-flex justify-content-between"><div><div class="stat-value"><?= $statusCounts['Cancelled'] ?></div><div>⚫ Cancelled</div></div><i class="bi bi-x-circle stat-icon"></i></div>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-lg-8">
      <div class="card h-100">
        <div class="card-header bg-white"><strong>Tickets Created — Last 7 Days</strong></div>
        <div class="card-body"><canvas id="weeklyChart" height="110"></canvas></div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="card h-100">
        <div class="card-header bg-white"><strong>Status Breakdown</strong></div>
        <div class="card-body"><canvas id="statusChart" height="110"></canvas></div>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-lg-6">
      <div class="card h-100">
        <div class="card-header bg-white"><strong>Priority Distribution</strong></div>
        <div class="card-body"><canvas id="priorityChart" height="130"></canvas></div>
      </div>
    </div>
    <div class="col-lg-3 col-6">
      <div class="card h-100 text-center">
        <div class="card-body">
          <i class="bi bi-people fs-1 text-primary"></i>
          <h3 class="mt-2"><?= $totalUsers ?></h3>
          <p class="text-muted mb-0">Total Registered Users</p>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-6">
      <div class="card h-100 text-center">
        <div class="card-body">
          <i class="bi bi-person-workspace fs-1 text-success"></i>
          <h3 class="mt-2"><?= $totalTechs ?></h3>
          <p class="text-muted mb-0">Active Technicians</p>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
      <strong>Recent Tickets</strong>
      <a href="tickets.php" class="small">View All →</a>
    </div>
    <div class="table-responsive">
      <table class="table table-hover mb-0 align-middle">
        <thead><tr><th>Ticket #</th><th>Subject</th><th>Requester</th><th>Priority</th><th>Status</th><th>Created</th></tr></thead>
        <tbody>
        <?php foreach ($recent as $t): ?>
          <tr onclick="window.location='../ticket-view.php?id=<?= $t['id'] ?>'" style="cursor:pointer;">
            <td class="ticket-number"><?= e($t['ticket_number']) ?></td>
            <td><?= e($t['subject']) ?></td>
            <td><?= e($t['requester_name']) ?></td>
            <td><?= priorityBadge($t['priority_name'] ?? 'N/A', $t['priority_color']) ?></td>
            <td><?= statusBadge($t['status']) ?></td>
            <td><?= date('M j, Y', strtotime($t['created_at'])) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$recent): ?><tr><td colspan="6" class="text-center text-muted py-4">No tickets yet.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</div>

<script>
new Chart(document.getElementById('weeklyChart'), {
  type: 'line',
  data: {
    labels: <?= json_encode($days) ?>,
    datasets: [{ label: 'Tickets Created', data: <?= json_encode($dayCounts) ?>, borderColor: '#2563eb', backgroundColor: 'rgba(37,99,235,.15)', fill: true, tension: .35 }]
  },
  options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
});

new Chart(document.getElementById('statusChart'), {
  type: 'doughnut',
  data: {
    labels: <?= json_encode(array_keys($statusCounts)) ?>,
    datasets: [{ data: <?= json_encode(array_values($statusCounts)) ?>,
      backgroundColor: ['#dc3545','#ffc107','#0d6efd','#198754','#20c997','#6c757d'] }]
  },
  options: { plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } } }
});

new Chart(document.getElementById('priorityChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_column($priorityRows, 'name')) ?>,
    datasets: [{ label: 'Tickets', data: <?= json_encode(array_map('intval', array_column($priorityRows, 'c'))) ?>,
      backgroundColor: <?= json_encode(array_column($priorityRows, 'color')) ?> }]
  },
  options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
