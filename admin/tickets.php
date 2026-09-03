<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole(['super_admin', 'it_admin']);

// Build dynamic filter query
$where = [];
$params = [];

$search = trim($_GET['search'] ?? '');
if ($search !== '') {
    $where[] = "(t.ticket_number LIKE ? OR t.subject LIKE ? OR ru.name LIKE ? OR au.name LIKE ?)";
    $like = "%$search%";
    array_push($params, $like, $like, $like, $like);
}

if (!empty($_GET['status'])) { $where[] = "t.status = ?"; $params[] = $_GET['status']; }
if (!empty($_GET['priority'])) { $where[] = "t.priority_id = ?"; $params[] = $_GET['priority']; }
if (!empty($_GET['department'])) { $where[] = "t.department_id = ?"; $params[] = $_GET['department']; }
if (!empty($_GET['category'])) { $where[] = "t.category_id = ?"; $params[] = $_GET['category']; }

$dateFilter = $_GET['date_filter'] ?? '';
if ($dateFilter === 'today') $where[] = "DATE(t.created_at) = CURDATE()";
elseif ($dateFilter === 'week') $where[] = "t.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
elseif ($dateFilter === 'month') $where[] = "t.created_at >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT t.*, c.name category_name, d.name department_name, p.name priority_name, p.color priority_color,
        ru.name requester_name, au.name assignee_name
        FROM tickets t
        LEFT JOIN categories c ON c.id = t.category_id
        LEFT JOIN departments d ON d.id = t.department_id
        LEFT JOIN priorities p ON p.id = t.priority_id
        LEFT JOIN users ru ON ru.id = t.requester_id
        LEFT JOIN users au ON au.id = t.assigned_to
        $whereSql
        ORDER BY t.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll();

$departments = $pdo->query("SELECT * FROM departments WHERE status=1 ORDER BY name")->fetchAll();
$categories = $pdo->query("SELECT * FROM categories WHERE status=1 ORDER BY name")->fetchAll();
$priorities = $pdo->query("SELECT * FROM priorities WHERE status=1 ORDER BY level")->fetchAll();

$pageTitle = 'All Tickets';
include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content flex-grow-1">
  <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h3 class="mb-0"><i class="bi bi-ticket-detailed"></i> All Tickets (<?= count($tickets) ?>)</h3>
    <a href="ticket-form.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Create Ticket</a>
  </div>

  <div class="card mb-3">
    <div class="card-body">
      <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-3">
          <label class="form-label small">Search</label>
          <input type="text" name="search" class="form-control" placeholder="Ticket #, subject, user, technician..." value="<?= e($search) ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label small">Status</label>
          <select name="status" class="form-select">
            <option value="">All</option>
            <?php foreach (['Open','Pending','In Progress','Resolved','Closed','Cancelled'] as $s): ?>
              <option value="<?= $s ?>" <?= ($_GET['status'] ?? '')===$s?'selected':'' ?>><?= $s ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label small">Priority</label>
          <select name="priority" class="form-select">
            <option value="">All</option>
            <?php foreach ($priorities as $p): ?>
              <option value="<?= $p['id'] ?>" <?= ($_GET['priority'] ?? '')==$p['id']?'selected':'' ?>><?= e($p['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label small">Department</label>
          <select name="department" class="form-select">
            <option value="">All</option>
            <?php foreach ($departments as $d): ?>
              <option value="<?= $d['id'] ?>" <?= ($_GET['department'] ?? '')==$d['id']?'selected':'' ?>><?= e($d['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label small">Category</label>
          <select name="category" class="form-select">
            <option value="">All</option>
            <?php foreach ($categories as $c): ?>
              <option value="<?= $c['id'] ?>" <?= ($_GET['category'] ?? '')==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-1">
          <button class="btn btn-primary w-100"><i class="bi bi-search"></i></button>
        </div>
        <div class="col-12">
          <div class="btn-group btn-group-sm mt-1" role="group">
            <a href="?date_filter=today" class="btn btn-outline-secondary <?= $dateFilter==='today'?'active':'' ?>">Today's Tickets</a>
            <a href="?date_filter=week" class="btn btn-outline-secondary <?= $dateFilter==='week'?'active':'' ?>">Past 7 Days</a>
            <a href="?date_filter=month" class="btn btn-outline-secondary <?= $dateFilter==='month'?'active':'' ?>">Monthly Records</a>
            <a href="tickets.php" class="btn btn-outline-danger">Clear Filters</a>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead>
          <tr>
            <th>Ticket #</th><th>Subject</th><th>Category</th><th>Department</th><th>Priority</th>
            <th>Status</th><th>Requester</th><th>Technician</th><th>Created</th><th>Resolution Time</th><th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($tickets as $t): ?>
          <tr>
            <td class="ticket-number"><?= e($t['ticket_number']) ?></td>
            <td><?= e($t['subject']) ?></td>
            <td><?= e($t['category_name'] ?? '-') ?></td>
            <td><?= e($t['department_name'] ?? '-') ?></td>
            <td><?= priorityBadge($t['priority_name'] ?? 'N/A', $t['priority_color']) ?></td>
            <td><?= statusBadge($t['status']) ?></td>
            <td><?= e($t['requester_name']) ?></td>
            <td><?= e($t['assignee_name'] ?? 'Unassigned') ?></td>
            <td><?= date('M j, Y', strtotime($t['created_at'])) ?></td>
            <td><?= calcResolutionTime($t['created_at'], $t['resolved_at']) ?></td>
            <td>
              <div class="btn-group btn-group-sm">
                <a href="../ticket-view.php?id=<?= $t['id'] ?>" class="btn btn-outline-primary" title="View"><i class="bi bi-eye"></i></a>
                <a href="ticket-form.php?id=<?= $t['id'] ?>" class="btn btn-outline-secondary" title="Edit"><i class="bi bi-pencil"></i></a>
                <a href="ticket-action.php?action=delete&id=<?= $t['id'] ?>&csrf=<?= csrfToken() ?>" class="btn btn-outline-danger confirm-delete" title="Delete"><i class="bi bi-trash"></i></a>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$tickets): ?><tr><td colspan="11" class="text-center text-muted py-4">No tickets found.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
