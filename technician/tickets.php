<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('technician');
$uid = $_SESSION['user_id'];

$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$view = $_GET['view'] ?? 'mine'; // mine | unassigned

$where = ["t.status NOT IN ('Closed','Cancelled')"];
$params = [];

if ($view === 'unassigned') {
    $where[] = "t.assigned_to IS NULL";
} else {
    $where[] = "t.assigned_to = ?";
    $params[] = $uid;
}

if ($search !== '') {
    $where[] = "(t.ticket_number LIKE ? OR t.subject LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%";
}
if ($statusFilter) { $where[] = "t.status = ?"; $params[] = $statusFilter; }

$sql = "SELECT t.*, c.name category_name, d.name department_name, p.name priority_name, p.color priority_color,
        ru.name requester_name FROM tickets t
        LEFT JOIN categories c ON c.id=t.category_id LEFT JOIN departments d ON d.id=t.department_id
        LEFT JOIN priorities p ON p.id=t.priority_id LEFT JOIN users ru ON ru.id=t.requester_id
        WHERE " . implode(' AND ', $where) . " ORDER BY t.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll();

$pageTitle = 'My Tickets';
include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content flex-grow-1">
  <h3 class="mb-4"><i class="bi bi-ticket-detailed"></i> Tickets</h3>

  <ul class="nav nav-pills mb-3">
    <li class="nav-item"><a class="nav-link <?= $view==='mine'?'active':'' ?>" href="?view=mine">My Assigned Tickets</a></li>
    <li class="nav-item"><a class="nav-link <?= $view==='unassigned'?'active':'' ?>" href="?view=unassigned">Unassigned Pool</a></li>
  </ul>

  <div class="card mb-3">
    <div class="card-body">
      <form method="GET" class="row g-2">
        <input type="hidden" name="view" value="<?= e($view) ?>">
        <div class="col-md-6"><input type="text" name="search" class="form-control" placeholder="Search ticket # or subject" value="<?= e($search) ?>"></div>
        <div class="col-md-4">
          <select name="status" class="form-select">
            <option value="">All Statuses</option>
            <?php foreach (['Open','Pending','In Progress'] as $s): ?>
              <option value="<?= $s ?>" <?= $statusFilter===$s?'selected':'' ?>><?= $s ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2"><button class="btn btn-primary w-100">Filter</button></div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead><tr><th>Ticket #</th><th>Subject</th><th>Requester</th><th>Category</th><th>Priority</th><th>Status</th><th>Created</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($tickets as $t): ?>
          <tr>
            <td class="ticket-number"><?= e($t['ticket_number']) ?></td>
            <td><?= e($t['subject']) ?></td>
            <td><?= e($t['requester_name']) ?></td>
            <td><?= e($t['category_name'] ?? '-') ?></td>
            <td><?= priorityBadge($t['priority_name'] ?? 'N/A', $t['priority_color']) ?></td>
            <td><?= statusBadge($t['status']) ?></td>
            <td><?= date('M j, Y', strtotime($t['created_at'])) ?></td>
            <td><a href="../ticket-view.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-primary">Open</a></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$tickets): ?><tr><td colspan="8" class="text-center text-muted py-4">No tickets found.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
