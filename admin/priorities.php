<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole(['super_admin', 'it_admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $name = trim($_POST['name']);
    $level = (int)$_POST['level'];
    $color = trim($_POST['color']);
    $sla = (int)$_POST['sla_hours'];
    $id = $_POST['id'] ?? null;
    if ($name !== '') {
        if ($id) {
            $pdo->prepare("UPDATE priorities SET name=?, level=?, color=?, sla_hours=? WHERE id=?")->execute([$name, $level, $color, $sla, $id]);
            flash('success', 'Priority updated.');
        } else {
            $pdo->prepare("INSERT INTO priorities (name, level, color, sla_hours) VALUES (?, ?, ?, ?)")->execute([$name, $level, $color, $sla]);
            flash('success', 'Priority added.');
        }
        logAudit('Saved priority', $name);
    }
    redirect('admin/priorities.php');
}
if (isset($_GET['delete']) && hash_equals($_SESSION['csrf_token'] ?? '', $_GET['csrf'] ?? '')) {
    $pdo->prepare("DELETE FROM priorities WHERE id = ?")->execute([$_GET['delete']]);
    flash('success', 'Priority deleted.');
    redirect('admin/priorities.php');
}

$priorities = $pdo->query("SELECT p.*, (SELECT COUNT(*) FROM tickets t WHERE t.priority_id=p.id) ticket_count FROM priorities p ORDER BY p.level")->fetchAll();
$pageTitle = 'Manage Priorities';
include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content flex-grow-1">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="mb-0"><i class="bi bi-flag"></i> Manage Priorities</h3>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#prModal" onclick="resetForm()"><i class="bi bi-plus-circle"></i> Add Priority</button>
  </div>
  <?php if ($msg = flash('success')): ?><div class="alert alert-success alert-auto-dismiss"><?= e($msg) ?></div><?php endif; ?>

  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead><tr><th>Name</th><th>Level</th><th>Color</th><th>SLA (hrs)</th><th>Tickets</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($priorities as $p): ?>
          <tr>
            <td><?= priorityBadge($p['name'], $p['color']) ?></td>
            <td><?= $p['level'] ?></td>
            <td><span class="badge" style="background:<?= e($p['color']) ?>">&nbsp;&nbsp;&nbsp;</span> <?= e($p['color']) ?></td>
            <td><?= $p['sla_hours'] ?>h</td>
            <td><span class="badge bg-secondary"><?= $p['ticket_count'] ?></span></td>
            <td>
              <div class="btn-group btn-group-sm">
                <button class="btn btn-outline-secondary" onclick='editModal(<?= json_encode($p) ?>)'><i class="bi bi-pencil"></i></button>
                <a href="?delete=<?= $p['id'] ?>&csrf=<?= csrfToken() ?>" class="btn btn-outline-danger confirm-delete"><i class="bi bi-trash"></i></a>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</div>

<div class="modal fade" id="prModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="id" id="p_id">
        <div class="modal-header"><h5 class="modal-title" id="p_title">Add Priority</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" id="p_name" class="form-control" required></div>
          <div class="mb-3"><label class="form-label">Level (1=Low ... 4=Critical)</label><input type="number" min="1" max="10" name="level" id="p_level" class="form-control" required></div>
          <div class="mb-3"><label class="form-label">Color</label><input type="color" name="color" id="p_color" class="form-control form-control-color"></div>
          <div class="mb-3"><label class="form-label">SLA (hours to resolve)</label><input type="number" min="1" name="sla_hours" id="p_sla" class="form-control" required></div>
        </div>
        <div class="modal-footer"><button type="submit" class="btn btn-primary">Save</button></div>
      </form>
    </div>
  </div>
</div>
<script>
function resetForm(){document.getElementById('p_title').textContent='Add Priority';document.getElementById('p_id').value='';document.getElementById('p_name').value='';document.getElementById('p_level').value=1;document.getElementById('p_color').value='#6c757d';document.getElementById('p_sla').value=72;}
function editModal(p){document.getElementById('p_title').textContent='Edit Priority';document.getElementById('p_id').value=p.id;document.getElementById('p_name').value=p.name;document.getElementById('p_level').value=p.level;document.getElementById('p_color').value=p.color;document.getElementById('p_sla').value=p.sla_hours;new bootstrap.Modal(document.getElementById('prModal')).show();}
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
