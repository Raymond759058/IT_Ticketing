<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole(['super_admin', 'it_admin']);

$error = ''; $success = '';
$editUser = null;

if (!empty($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editUser = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    if (isset($_POST['save_user'])) {
        $userId = $_POST['user_id'] ?? null;
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $role = $_POST['role'];
        $deptId = $_POST['department_id'] ?: null;
        $status = $_POST['status'];
        $password = $_POST['password'] ?? '';

        // Fetch existing role if updating
        $existingRole = null;
        if ($userId) {
            $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $existingRole = $stmt->fetchColumn();
        }

        // Only Super Admin can create/edit other Super Admins
        if ($role === 'super_admin' && !isSuperAdmin()) {
            $error = 'Only a Super Admin can assign the Super Admin role.';
        } elseif ($existingRole === 'super_admin' && !isSuperAdmin()) {
            $error = 'Only a Super Admin can edit another Super Admin.';
        } elseif ($name === '' || $email === '') {
            $error = 'Name and email are required.';
        } else {
            if ($userId) {
                if ($password !== '') {
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $pdo->prepare("UPDATE users SET name=?, email=?, phone=?, role=?, department_id=?, status=?, password=? WHERE id=?")
                        ->execute([$name, $email, $phone, $role, $deptId, $status, $hash, $userId]);
                } else {
                    $pdo->prepare("UPDATE users SET name=?, email=?, phone=?, role=?, department_id=?, status=? WHERE id=?")
                        ->execute([$name, $email, $phone, $role, $deptId, $status, $userId]);
                }
                logAudit('Updated user', "Email: $email");
                flash('success', 'User updated successfully.');
            } else {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error = 'A user with this email already exists.';
                } else {
                    $pass = $password !== '' ? $password : bin2hex(random_bytes(4));
                    $hash = password_hash($pass, PASSWORD_BCRYPT);
                    $pdo->prepare("INSERT INTO users (name, email, phone, password, role, department_id, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?)")->execute([$name, $email, $phone, $hash, $role, $deptId, $status]);
                    logAudit('Created user', "Email: $email, Role: $role");
                    flash('success', 'User created successfully.');
                }
            }
            if (!$error) redirect('admin/users.php');
        }
    }
}

if (isset($_GET['delete']) && hash_equals($_SESSION['csrf_token'] ?? '', $_GET['csrf'] ?? '')) {
    if ($_GET['delete'] != $_SESSION['user_id']) {
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
        $targetRole = $stmt->fetchColumn();

        if ($targetRole === 'super_admin' && !isSuperAdmin()) {
            flash('error', 'Only a Super Admin can delete a Super Admin.');
        } else {
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$_GET['delete']]);
            logAudit('Deleted user', "ID: {$_GET['delete']}");
            flash('success', 'User deleted.');
        }
    }
    redirect('admin/users.php');
}

$users = $pdo->query("SELECT u.*, d.name department_name FROM users u
    LEFT JOIN departments d ON d.id = u.department_id ORDER BY u.created_at DESC")->fetchAll();
$departments = $pdo->query("SELECT * FROM departments WHERE status=1 ORDER BY name")->fetchAll();

$pageTitle = 'Manage Users';
include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content flex-grow-1">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="mb-0"><i class="bi bi-people"></i> Manage Users</h3>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal" onclick="resetForm()"><i class="bi bi-plus-circle"></i> Add User</button>
  </div>
  <?php if ($msg = flash('success')): ?><div class="alert alert-success alert-auto-dismiss"><?= e($msg) ?></div><?php endif; ?>
  <?php if ($msg = flash('error')): ?><div class="alert alert-danger alert-auto-dismiss"><?= e($msg) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Department</th><th>Status</th><th>Last Login</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
          <tr>
            <td><?= e($u['name']) ?></td>
            <td><?= e($u['email']) ?></td>
            <td><?= e($u['phone'] ?: '-') ?></td>
            <td><span class="badge bg-info text-dark"><?= e(roleLabel($u['role'])) ?></span></td>
            <td><?= e($u['department_name'] ?? '-') ?></td>
            <td><span class="badge bg-<?= $u['status']==='active'?'success':'secondary' ?>"><?= e(ucfirst($u['status'])) ?></span></td>
            <td><?= $u['last_login'] ? date('M j, Y g:i A', strtotime($u['last_login'])) : 'Never' ?></td>
            <td>
              <div class="btn-group btn-group-sm">
                <?php $canEditThis = ($u['role'] !== 'super_admin' || isSuperAdmin()); ?>
                <?php if ($canEditThis): ?>
                <button class="btn btn-outline-secondary" onclick='editUserModal(<?= json_encode($u) ?>)'><i class="bi bi-pencil"></i></button>
                <?php endif; ?>
                <?php if ($u['id'] != $_SESSION['user_id'] && $canEditThis): ?>
                <a href="?delete=<?= $u['id'] ?>&csrf=<?= csrfToken() ?>" class="btn btn-outline-danger confirm-delete"><i class="bi bi-trash"></i></a>
                <?php endif; ?>
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

<!-- User Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="user_id" id="user_id">
        <div class="modal-header">
          <h5 class="modal-title" id="modalTitle">Add User</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3"><label class="form-label">Full Name</label><input type="text" name="name" id="f_name" class="form-control" required></div>
          <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" id="f_email" class="form-control" required></div>
          <div class="mb-3"><label class="form-label">Phone</label><input type="text" name="phone" id="f_phone" class="form-control"></div>
          <div class="row">
            <div class="col-6 mb-3">
              <label class="form-label">Role</label>
              <select name="role" id="f_role" class="form-select">
                <option value="user">User</option>
                <option value="technician">Technician</option>
                <option value="it_admin">IT Admin</option>
                <?php if (isSuperAdmin()): ?><option value="super_admin">Super Admin</option><?php endif; ?>
              </select>
            </div>
            <div class="col-6 mb-3">
              <label class="form-label">Status</label>
              <select name="status" id="f_status" class="form-select">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="suspended">Suspended</option>
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Department</label>
            <select name="department_id" id="f_department" class="form-select">
              <option value="">-- None --</option>
              <?php foreach ($departments as $d): ?><option value="<?= $d['id'] ?>"><?= e($d['name']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Password <small class="text-muted">(leave blank to keep unchanged / auto-generate)</small></label>
            <div class="input-group">
              <input type="password" name="password" id="f_password" class="form-control">
              <span class="input-group-text password-toggle bi bi-eye" data-target="#f_password"></span>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="save_user" class="btn btn-primary">Save User</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function resetForm() {
  document.getElementById('modalTitle').textContent = 'Add User';
  document.getElementById('user_id').value = '';
  ['f_name','f_email','f_phone','f_password'].forEach(id => document.getElementById(id).value = '');
  document.getElementById('f_role').value = 'user';
  document.getElementById('f_status').value = 'active';
  document.getElementById('f_department').value = '';
}
function editUserModal(u) {
  document.getElementById('modalTitle').textContent = 'Edit User';
  document.getElementById('user_id').value = u.id;
  document.getElementById('f_name').value = u.name;
  document.getElementById('f_email').value = u.email;
  document.getElementById('f_phone').value = u.phone || '';
  document.getElementById('f_role').value = u.role;
  document.getElementById('f_status').value = u.status;
  document.getElementById('f_department').value = u.department_id || '';
  document.getElementById('f_password').value = '';
  new bootstrap.Modal(document.getElementById('userModal')).show();
}
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
