<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole(['super_admin', 'it_admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $name = trim($_POST['name']);
    $desc = trim($_POST['description']);
    $id = $_POST['id'] ?? null;
    if ($name !== '') {
        if ($id) {
            $pdo->prepare("UPDATE departments SET name=?, description=? WHERE id=?")->execute([$name, $desc, $id]);
            flash('success', 'Department updated.');
        } else {
            $pdo->prepare("INSERT INTO departments (name, description) VALUES (?, ?)")->execute([$name, $desc]);
            flash('success', 'Department added.');
        }
        logAudit('Saved department', $name);
    }
    redirect('admin/departments.php');
}

if (isset($_GET['toggle']) && hash_equals($_SESSION['csrf_token'] ?? '', $_GET['csrf'] ?? '')) {
    $pdo->prepare("UPDATE departments SET status = 1 - status WHERE id = ?")->execute([$_GET['toggle']]);
    redirect('admin/departments.php');
}
if (isset($_GET['delete']) && hash_equals($_SESSION['csrf_token'] ?? '', $_GET['csrf'] ?? '')) {
    $pdo->prepare("DELETE FROM departments WHERE id = ?")->execute([$_GET['delete']]);
    flash('success', 'Department deleted.');
    redirect('admin/departments.php');
}

$departments = $pdo->query("SELECT d.*, (SELECT COUNT(*) FROM tickets t WHERE t.department_id=d.id) ticket_count FROM departments d ORDER BY d.name")->fetchAll();
$pageTitle = 'Manage Departments';
include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content flex-grow-1">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="mb-0"><i class="bi bi-building"></i> Manage Departments</h3>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#deptModal" onclick="resetForm()"><i class="bi bi-plus-circle"></i> Add Department</button>
  </div>
  <?php if ($msg = flash('success')): ?><div class="alert alert-success alert-auto-dismiss"><?= e($msg) ?></div><?php endif; ?>

  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead><tr><th>Name</th><th>Description</th><th>Tickets</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($departments as $d): ?>
          <tr>
            <td><?= e($d['name']) ?></td>
            <td><?= e($d['description']) ?></td>
            <td><span class="badge bg-secondary"><?= $d['ticket_count'] ?></span></td>
            <td><span class="badge bg-<?= $d['status']?'success':'secondary' ?>"><?= $d['status']?'Active':'Inactive' ?></span></td>
            <td>
              <div class="btn-group btn-group-sm">
                <button class="btn btn-outline-secondary" onclick='editModal(<?= json_encode($d) ?>)'><i class="bi bi-pencil"></i></button>
                <a href="?toggle=<?= $d['id'] ?>&csrf=<?= csrfToken() ?>" class="btn btn-outline-warning"><i class="bi bi-toggle2-<?= $d['status']?'on':'off' ?>"></i></a>
                <a href="?delete=<?= $d['id'] ?>&csrf=<?= csrfToken() ?>" class="btn btn-outline-danger confirm-delete"><i class="bi bi-trash"></i></a>
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

<div class="modal fade" id="deptModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="id" id="d_id">
        <div class="modal-header"><h5 class="modal-title" id="d_title">Add Department</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" id="d_name" class="form-control" required></div>
          <div class="mb-3"><label class="form-label">Description</label><textarea name="description" id="d_desc" class="form-control"></textarea></div>
        </div>
        <div class="modal-footer"><button type="submit" class="btn btn-primary">Save</button></div>
      </form>
    </div>
  </div>
</div>
<script>
function resetForm(){document.getElementById('d_title').textContent='Add Department';document.getElementById('d_id').value='';document.getElementById('d_name').value='';document.getElementById('d_desc').value='';}
function editModal(d){document.getElementById('d_title').textContent='Edit Department';document.getElementById('d_id').value=d.id;document.getElementById('d_name').value=d.name;document.getElementById('d_desc').value=d.description||'';new bootstrap.Modal(document.getElementById('deptModal')).show();}
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
