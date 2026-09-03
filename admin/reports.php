<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole(['super_admin', 'it_admin']);

// Daily / Weekly / Monthly ticket counts
$daily = $pdo->query("SELECT DATE(created_at) d, COUNT(*) c FROM tickets
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY) GROUP BY DATE(created_at) ORDER BY d DESC")->fetchAll();

$weekly = $pdo->query("SELECT YEARWEEK(created_at,1) yw, MIN(DATE(created_at)) wk_start, COUNT(*) c FROM tickets
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 WEEK) GROUP BY YEARWEEK(created_at,1) ORDER BY yw DESC")->fetchAll();

$monthly = $pdo->query("SELECT DATE_FORMAT(created_at,'%Y-%m') ym, COUNT(*) c FROM tickets
    GROUP BY DATE_FORMAT(created_at,'%Y-%m') ORDER BY ym DESC LIMIT 12")->fetchAll();

// Status summary
$statusSummary = $pdo->query("SELECT status, COUNT(*) c FROM tickets GROUP BY status")->fetchAll();

// Technician performance
$techPerf = $pdo->query("SELECT u.name, 
    COUNT(t.id) total_assigned,
    SUM(CASE WHEN t.status IN ('Resolved','Closed') THEN 1 ELSE 0 END) completed,
    AVG(CASE WHEN t.resolved_at IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, t.created_at, t.resolved_at) END) avg_minutes
    FROM users u LEFT JOIN tickets t ON t.assigned_to = u.id
    WHERE u.role = 'technician' GROUP BY u.id, u.name ORDER BY total_assigned DESC")->fetchAll();

// Department stats
$deptStats = $pdo->query("SELECT d.name, COUNT(t.id) total,
    SUM(CASE WHEN t.status='Open' THEN 1 ELSE 0 END) open_count,
    SUM(CASE WHEN t.status IN ('Resolved','Closed') THEN 1 ELSE 0 END) resolved_count
    FROM departments d LEFT JOIN tickets t ON t.department_id = d.id
    GROUP BY d.id, d.name ORDER BY total DESC")->fetchAll();

$pageTitle = 'Reports';
include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content flex-grow-1">
  <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h3 class="mb-0"><i class="bi bi-bar-chart"></i> Reports</h3>
    <div class="btn-group no-print">
      <button onclick="printPage()" class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer"></i> Print</button>
      <a href="report-export.php?type=csv" class="btn btn-outline-success btn-sm"><i class="bi bi-file-earmark-excel"></i> Export Excel (CSV)</a>
      <button onclick="window.print()" class="btn btn-outline-danger btn-sm"><i class="bi bi-file-earmark-pdf"></i> Export PDF</button>
    </div>
  </div>

  <ul class="nav nav-tabs no-print mb-3">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#daily">Daily</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#weekly">Weekly</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#monthly">Monthly</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#status">Status Summary</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tech">Technician Performance</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#dept">Department Stats</a></li>
  </ul>

  <div class="tab-content">
    <div class="tab-pane fade show active" id="daily">
      <div class="card"><div class="card-header bg-white"><strong>Daily Tickets (Last 14 Days)</strong></div>
      <div class="table-responsive"><table class="table mb-0"><thead><tr><th>Date</th><th>Tickets Created</th></tr></thead><tbody>
      <?php foreach ($daily as $r): ?><tr><td><?= date('M j, Y (D)', strtotime($r['d'])) ?></td><td><?= $r['c'] ?></td></tr><?php endforeach; ?>
      </tbody></table></div></div>
    </div>

    <div class="tab-pane fade" id="weekly">
      <div class="card"><div class="card-header bg-white"><strong>Weekly Tickets (Last 12 Weeks)</strong></div>
      <div class="table-responsive"><table class="table mb-0"><thead><tr><th>Week Starting</th><th>Tickets Created</th></tr></thead><tbody>
      <?php foreach ($weekly as $r): ?><tr><td><?= date('M j, Y', strtotime($r['wk_start'])) ?></td><td><?= $r['c'] ?></td></tr><?php endforeach; ?>
      </tbody></table></div></div>
    </div>

    <div class="tab-pane fade" id="monthly">
      <div class="card"><div class="card-header bg-white"><strong>Monthly Tickets (Last 12 Months)</strong></div>
      <div class="table-responsive"><table class="table mb-0"><thead><tr><th>Month</th><th>Tickets Created</th></tr></thead><tbody>
      <?php foreach ($monthly as $r): ?><tr><td><?= date('F Y', strtotime($r['ym'].'-01')) ?></td><td><?= $r['c'] ?></td></tr><?php endforeach; ?>
      </tbody></table></div></div>
    </div>

    <div class="tab-pane fade" id="status">
      <div class="card"><div class="card-header bg-white"><strong>Ticket Status Summary</strong></div>
      <div class="table-responsive"><table class="table mb-0"><thead><tr><th>Status</th><th>Count</th></tr></thead><tbody>
      <?php foreach ($statusSummary as $r): ?><tr><td><?= statusBadge($r['status']) ?></td><td><?= $r['c'] ?></td></tr><?php endforeach; ?>
      </tbody></table></div></div>
    </div>

    <div class="tab-pane fade" id="tech">
      <div class="card"><div class="card-header bg-white"><strong>Technician Performance</strong></div>
      <div class="table-responsive"><table class="table mb-0"><thead><tr><th>Technician</th><th>Total Assigned</th><th>Completed</th><th>Completion Rate</th><th>Avg Resolution Time</th></tr></thead><tbody>
      <?php foreach ($techPerf as $r):
        $rate = $r['total_assigned'] > 0 ? round(($r['completed'] / $r['total_assigned']) * 100) : 0;
        $avgH = $r['avg_minutes'] ? floor($r['avg_minutes']/60) . 'h ' . ($r['avg_minutes']%60) . 'm' : '-';
      ?>
        <tr><td><?= e($r['name']) ?></td><td><?= $r['total_assigned'] ?></td><td><?= $r['completed'] ?></td><td><?= $rate ?>%</td><td><?= $avgH ?></td></tr>
      <?php endforeach; ?>
      <?php if (!$techPerf): ?><tr><td colspan="5" class="text-center text-muted py-3">No technicians found.</td></tr><?php endif; ?>
      </tbody></table></div></div>
    </div>

    <div class="tab-pane fade" id="dept">
      <div class="card"><div class="card-header bg-white"><strong>Department Statistics</strong></div>
      <div class="table-responsive"><table class="table mb-0"><thead><tr><th>Department</th><th>Total Tickets</th><th>Open</th><th>Resolved/Closed</th></tr></thead><tbody>
      <?php foreach ($deptStats as $r): ?>
        <tr><td><?= e($r['name']) ?></td><td><?= $r['total'] ?></td><td><?= $r['open_count'] ?></td><td><?= $r['resolved_count'] ?></td></tr>
      <?php endforeach; ?>
      </tbody></table></div></div>
    </div>
  </div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
