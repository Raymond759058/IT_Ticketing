<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('user');
$uid = $_SESSION['user_id'];

$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';

$where = ["t.requester_id = ?"];
$params = [$uid];
if ($search !== '') { $where[] = "(t.ticket_number LIKE ? OR t.subject LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($statusFilter) { $where[] = "t.status = ?"; $params[] = $statusFilter; }

$sql = "SELECT t.*, c.name category_name, p.name priority_name, p.color priority_color, au.name assignee_name
        FROM tickets t
        LEFT JOIN categories c ON c.id = t.category_id
        LEFT JOIN priorities p ON p.id = t.priority_id
        LEFT JOIN users au ON au.id = t.assigned_to
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
  <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h3 class="mb-0"><i class="bi bi-ticket-detailed"></i> My Tickets (<?= count($tickets) ?>)</h3>
    <a href="ticket-create.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Create Ticket</a>
  </div>

  <div class="card mb-3">
    <div class="card-body">
      <form method="GET" class="row g-2">
        <div class="col-md-6"><input type="text" name="search" class="form-control" placeholder="Search by ticket # or subject" value="<?= e($search) ?>"></div>
        <div class="col-md-4">
          <select name="status" class="form-select">
            <option value="">All Statuses</option>
            <?php foreach (['Open','Pending','In Progress','Resolved','Closed','Cancelled'] as $s): ?>
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
        <thead><tr><th>Ticket #</th><th>Subject</th><th>Category</th><th>Priority</th><th>Status</th><th>Technician</th><th>Created</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($tickets as $t): ?>
          <tr>
            <td class="ticket-number"><?= e($t['ticket_number']) ?></td>
            <td><?= e($t['subject']) ?></td>
            <td><?= e($t['category_name'] ?? '-') ?></td>
            <td><?= priorityBadge($t['priority_name'] ?? 'N/A', $t['priority_color']) ?></td>
            <td><?= statusBadge($t['status']) ?></td>
            <td><?= e($t['assignee_name'] ?? 'Unassigned') ?></td>
            <td><?= date('M j, Y', strtotime($t['created_at'])) ?></td>
            <td><a href="../ticket-view.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-primary">View</a></td>
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
