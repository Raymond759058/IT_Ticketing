<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole(['super_admin', 'it_admin']);

$id = $_GET['id'] ?? null;
$ticket = null;
$error = '';

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM tickets WHERE id = ?");
    $stmt->execute([$id]);
    $ticket = $stmt->fetch();
    if (!$ticket) redirect('admin/tickets.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $subject = trim($_POST['subject']);
    $description = trim($_POST['description']);
    $categoryId = $_POST['category_id'] ?: null;
    $departmentId = $_POST['department_id'] ?: null;
    $priorityId = $_POST['priority_id'] ?: null;
    $requesterId = $_POST['requester_id'];
    $contact = trim($_POST['contact_info']);

    if ($subject === '' || $description === '' || !$requesterId) {
        $error = 'Please fill in all required fields.';
    } else {
        try {
            $attachment = handleUpload('attachment') ?? ($ticket['attachment'] ?? null);

            if ($ticket) {
                $stmt = $pdo->prepare("UPDATE tickets SET subject=?, description=?, category_id=?, department_id=?,
                    priority_id=?, requester_id=?, contact_info=?, attachment=? WHERE id=?");
                $stmt->execute([$subject, $description, $categoryId, $departmentId, $priorityId, $requesterId, $contact, $attachment, $ticket['id']]);
                logAudit('Updated ticket', $ticket['ticket_number'], $ticket['id']);
                flash('success', 'Ticket updated successfully.');
            } else {
                $ticketNumber = generateTicketNumber();
                $stmt = $pdo->prepare("INSERT INTO tickets (ticket_number, subject, description, category_id,
                    department_id, priority_id, status, requester_id, contact_info, attachment)
                    VALUES (?, ?, ?, ?, ?, ?, 'Open', ?, ?, ?)");
                $stmt->execute([$ticketNumber, $subject, $description, $categoryId, $departmentId, $priorityId, $requesterId, $contact, $attachment]);
                $newId = $pdo->lastInsertId();
                logAudit('Created ticket', $ticketNumber, $newId);
                flash('success', "Ticket $ticketNumber created successfully.");
            }
            redirect('admin/tickets.php');
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

$categories = $pdo->query("SELECT * FROM categories WHERE status=1 ORDER BY name")->fetchAll();
$departments = $pdo->query("SELECT * FROM departments WHERE status=1 ORDER BY name")->fetchAll();
$priorities = $pdo->query("SELECT * FROM priorities WHERE status=1 ORDER BY level")->fetchAll();
$requesters = $pdo->query("SELECT id, name, email FROM users ORDER BY name")->fetchAll();

$pageTitle = $ticket ? 'Edit Ticket' : 'Create Ticket';
include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content flex-grow-1">
  <h3 class="mb-4"><i class="bi bi-<?= $ticket ? 'pencil' : 'plus-circle' ?>"></i> <?= $ticket ? 'Edit Ticket' : 'Create Ticket' ?></h3>
  <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

  <div class="card">
    <div class="card-body">
      <form method="POST" enctype="multipart/form-data">
        <?= csrfField() ?>
        <div class="mb-3">
          <label class="form-label">Ticket Subject *</label>
          <input type="text" name="subject" class="form-control" required value="<?= e($ticket['subject'] ?? '') ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Issue Description *</label>
          <textarea name="description" rows="5" class="form-control" required><?= e($ticket['description'] ?? '') ?></textarea>
        </div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Requester *</label>
            <select name="requester_id" class="form-select" required>
              <option value="">-- Select --</option>
              <?php foreach ($requesters as $r): ?>
                <option value="<?= $r['id'] ?>" <?= ($ticket['requester_id'] ?? null)==$r['id']?'selected':'' ?>><?= e($r['name']) ?> (<?= e($r['email']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Contact Information</label>
            <input type="text" name="contact_info" class="form-control" value="<?= e($ticket['contact_info'] ?? '') ?>">
          </div>
        </div>
        <div class="row">
          <div class="col-md-4 mb-3">
            <label class="form-label">Category</label>
            <select name="category_id" class="form-select">
              <option value="">-- Select --</option>
              <?php foreach ($categories as $c): ?>
                <option value="<?= $c['id'] ?>" <?= ($ticket['category_id'] ?? null)==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label">Department</label>
            <select name="department_id" class="form-select">
              <option value="">-- Select --</option>
              <?php foreach ($departments as $d): ?>
                <option value="<?= $d['id'] ?>" <?= ($ticket['department_id'] ?? null)==$d['id']?'selected':'' ?>><?= e($d['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label">Priority</label>
            <select name="priority_id" class="form-select">
              <option value="">-- Select --</option>
              <?php foreach ($priorities as $p): ?>
                <option value="<?= $p['id'] ?>" <?= ($ticket['priority_id'] ?? null)==$p['id']?'selected':'' ?>><?= e($p['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label">Attachment (Image/PDF)</label>
          <input type="file" name="attachment" class="form-control" accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx">
          <?php if (!empty($ticket['attachment'])): ?>
            <small class="text-muted">Current file: <?= e(basename($ticket['attachment'])) ?></small>
          <?php endif; ?>
        </div>
        <button type="submit" class="btn btn-primary"><?= $ticket ? 'Update Ticket' : 'Create Ticket' ?></button>
        <a href="tickets.php" class="btn btn-outline-secondary">Cancel</a>
      </form>
    </div>
  </div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
