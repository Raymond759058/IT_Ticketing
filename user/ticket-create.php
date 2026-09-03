<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('user');

$error = '';
$user = currentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $subject = trim($_POST['subject']);
    $description = trim($_POST['description']);
    $categoryId = $_POST['category_id'] ?: null;
    $departmentId = $_POST['department_id'] ?: null;
    $priorityId = $_POST['priority_id'] ?: null;
    $contact = trim($_POST['contact_info']);

    if ($subject === '' || $description === '') {
        $error = 'Please fill in the subject and description.';
    } else {
        try {
            $attachment = handleUpload('attachment');
            $ticketNumber = generateTicketNumber();
            $stmt = $pdo->prepare("INSERT INTO tickets (ticket_number, subject, description, category_id,
                department_id, priority_id, status, requester_id, contact_info, attachment)
                VALUES (?, ?, ?, ?, ?, ?, 'Open', ?, ?, ?)");
            $stmt->execute([$ticketNumber, $subject, $description, $categoryId, $departmentId, $priorityId,
                $user['id'], $contact, $attachment]);
            $newId = $pdo->lastInsertId();
            logAudit('Created ticket', $ticketNumber, $newId);
            sendNotificationEmail(getSetting('site_email'), "New Ticket: $ticketNumber", "A new ticket was submitted: " . e($subject));
            flash('success', "Your ticket $ticketNumber has been submitted successfully.");
            redirect('user/tickets.php');
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

$categories = $pdo->query("SELECT * FROM categories WHERE status=1 ORDER BY name")->fetchAll();
$departments = $pdo->query("SELECT * FROM departments WHERE status=1 ORDER BY name")->fetchAll();
$priorities = $pdo->query("SELECT * FROM priorities WHERE status=1 ORDER BY level")->fetchAll();

$pageTitle = 'Create Ticket';
include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content flex-grow-1">
  <h3 class="mb-4"><i class="bi bi-plus-circle"></i> Create New Ticket</h3>
  <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

  <div class="card" style="max-width:760px;">
    <div class="card-body">
      <form method="POST" enctype="multipart/form-data">
        <?= csrfField() ?>
        <div class="mb-3">
          <label class="form-label">Ticket Subject *</label>
          <input type="text" name="subject" class="form-control" required placeholder="Brief summary of the issue">
        </div>
        <div class="mb-3">
          <label class="form-label">Issue Description *</label>
          <textarea name="description" rows="5" class="form-control" required placeholder="Describe the issue in detail..."></textarea>
        </div>
        <div class="row">
          <div class="col-md-4 mb-3">
            <label class="form-label">Category</label>
            <select name="category_id" class="form-select">
              <option value="">-- Select --</option>
              <?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label">Department</label>
            <select name="department_id" class="form-select">
              <option value="">-- Select --</option>
              <?php foreach ($departments as $d): ?><option value="<?= $d['id'] ?>"><?= e($d['name']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label">Priority</label>
            <select name="priority_id" class="form-select">
              <option value="">-- Select --</option>
              <?php foreach ($priorities as $p): ?><option value="<?= $p['id'] ?>"><?= e($p['name']) ?></option><?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label">Contact Information</label>
          <input type="text" name="contact_info" class="form-control" placeholder="Phone or alternate email" value="<?= e($user['phone'] ?: $user['email']) ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Attachment (Image/PDF)</label>
          <input type="file" name="attachment" class="form-control" accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx">
          <small class="text-muted">Max 5MB. Accepted: JPG, PNG, GIF, PDF, DOC, DOCX</small>
        </div>
        <button type="submit" class="btn btn-primary"><i class="bi bi-send"></i> Submit Ticket</button>
      </form>
    </div>
  </div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
