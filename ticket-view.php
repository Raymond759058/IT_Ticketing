<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT t.*, c.name category_name, d.name department_name,
    p.name priority_name, p.color priority_color,
    ru.name requester_name, ru.email requester_email, ru.phone requester_phone,
    au.name assignee_name
    FROM tickets t
    LEFT JOIN categories c ON c.id = t.category_id
    LEFT JOIN departments d ON d.id = t.department_id
    LEFT JOIN priorities p ON p.id = t.priority_id
    LEFT JOIN users ru ON ru.id = t.requester_id
    LEFT JOIN users au ON au.id = t.assigned_to
    WHERE t.id = ?");
$stmt->execute([$id]);
$ticket = $stmt->fetch();
if (!$ticket) { echo '<script>alert("Ticket not found");history.back();</script>'; exit; }

$role = $_SESSION['role'];
$uid = $_SESSION['user_id'];
$isAdminUser = in_array($role, ['super_admin', 'it_admin']);
$isTech = $role === 'technician';
$isOwner = $ticket['requester_id'] == $uid;
$isAssignee = $ticket['assigned_to'] == $uid;

// Access control: users may only view their own tickets; technicians only assigned/unassigned tickets
if (!$isAdminUser) {
    if ($role === 'user' && !$isOwner) { redirect('unauthorized.php'); }
    if ($isTech && !$isAssignee && $ticket['assigned_to'] !== null) { redirect('unauthorized.php'); }
}

$error = ''; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    // --- Assign ticket (admin only) ---
    if (isset($_POST['assign_ticket']) && $isAdminUser) {
        $techId = $_POST['assigned_to'] ?: null;
        $pdo->prepare("UPDATE tickets SET assigned_to = ?, status = IF(status='Open','In Progress',status) WHERE id = ?")
            ->execute([$techId, $id]);
        logAudit('Assigned ticket', "To user ID: $techId", $id);
        flash('success', 'Ticket assigned successfully.');
        redirect('ticket-view.php?id=' . $id);
    }

    // --- Technician accepts ticket ---
    if (isset($_POST['accept_ticket']) && $isTech) {
        $pdo->prepare("UPDATE tickets SET assigned_to = ?, status = 'In Progress' WHERE id = ?")->execute([$uid, $id]);
        logAudit('Technician accepted ticket', '', $id);
        flash('success', 'Ticket accepted.');
        redirect('ticket-view.php?id=' . $id);
    }

    // --- Update status (admin & assigned technician) ---
    if (isset($_POST['update_status']) && ($isAdminUser || $isAssignee)) {
        $newStatus = $_POST['status'];
        $valid = ['Open','Pending','In Progress','Resolved','Closed','Cancelled'];
        if (in_array($newStatus, $valid)) {
            $extra = '';
            if ($newStatus === 'Resolved') $extra = ', resolved_at = NOW()';
            if ($newStatus === 'Closed') $extra = ', closed_at = NOW()';
            $pdo->prepare("UPDATE tickets SET status = ? $extra WHERE id = ?")->execute([$newStatus, $id]);
            logAudit('Updated ticket status', "New status: $newStatus", $id);
            flash('success', "Status updated to $newStatus.");
        }
        redirect('ticket-view.php?id=' . $id);
    }

    // --- Close ticket (owner or admin) ---
    if (isset($_POST['close_ticket']) && ($isAdminUser || $isOwner)) {
        $pdo->prepare("UPDATE tickets SET status = 'Closed', closed_at = NOW() WHERE id = ?")->execute([$id]);
        logAudit('Closed ticket', '', $id);
        flash('success', 'Ticket closed.');
        redirect('ticket-view.php?id=' . $id);
    }

    // --- Add reply / work note ---
    if (isset($_POST['add_reply'])) {
        $message = trim($_POST['message']);
        $isInternal = isset($_POST['is_internal_note']) && ($isAdminUser || $isTech) ? 1 : 0;
        if ($message !== '') {
            try {
                $attachment = handleUpload('reply_attachment', 'replies');
                $pdo->prepare("INSERT INTO ticket_replies (ticket_id, user_id, message, is_internal_note, attachment)
                    VALUES (?, ?, ?, ?, ?)")->execute([$id, $uid, $message, $isInternal, $attachment]);
                $pdo->prepare("UPDATE tickets SET updated_at = NOW() WHERE id = ?")->execute([$id]);
                logAudit('Added reply/work note', $isInternal ? 'Internal note' : 'Reply', $id);
                flash('success', 'Reply added.');
            } catch (Exception $e) {
                flash('success', 'Error uploading attachment: ' . $e->getMessage());
            }
        }
        redirect('ticket-view.php?id=' . $id);
    }
}

// Reload ticket after POST
$stmt->execute([$id]);
$ticket = $stmt->fetch();

$repliesStmt = $pdo->prepare("SELECT r.*, u.name user_name, u.role user_role FROM ticket_replies r
    JOIN users u ON u.id = r.user_id WHERE r.ticket_id = ? ORDER BY r.created_at ASC");
$repliesStmt->execute([$id]);
$replies = $repliesStmt->fetchAll();
if (!$isAdminUser && !$isTech) {
    $replies = array_filter($replies, fn($r) => !$r['is_internal_note']);
}

$technicians = $pdo->query("SELECT id, name FROM users WHERE role = 'technician' AND status='active' ORDER BY name")->fetchAll();

$pageTitle = 'Ticket ' . $ticket['ticket_number'];
include __DIR__ . '/includes/header.php';
?>
<div class="d-flex">
<?php include __DIR__ . '/includes/sidebar.php'; ?>
<div class="main-content flex-grow-1">
  <?php if ($msg = flash('success')): ?><div class="alert alert-success alert-auto-dismiss"><?= e($msg) ?></div><?php endif; ?>

  <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
    <div>
      <h4 class="mb-1"><span class="ticket-number"><?= e($ticket['ticket_number']) ?></span> — <?= e($ticket['subject']) ?></h4>
      <?= statusBadge($ticket['status']) ?> <?= priorityBadge($ticket['priority_name'] ?? 'N/A', $ticket['priority_color']) ?>
    </div>
    <div class="btn-group no-print">
      <button onclick="printPage()" class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer"></i> Print</button>
      <?php if ($isAdminUser): ?>
        <a href="admin/ticket-form.php?id=<?= $id ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil"></i> Edit</a>
        <a href="admin/ticket-action.php?action=delete&id=<?= $id ?>&csrf=<?= csrfToken() ?>" class="btn btn-outline-danger btn-sm confirm-delete"><i class="bi bi-trash"></i> Delete</a>
      <?php endif; ?>
    </div>
  </div>

  <div class="row">
    <div class="col-lg-8">
      <div class="card mb-3">
        <div class="card-header bg-white"><strong>Description</strong></div>
        <div class="card-body">
          <p style="white-space:pre-wrap;"><?= e($ticket['description']) ?></p>
          <?php if ($ticket['attachment']): ?>
            <a href="uploads/<?= e($ticket['attachment']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
              <i class="bi bi-paperclip"></i> View Attachment
            </a>
          <?php endif; ?>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-header bg-white"><strong>Replies & Work Notes</strong></div>
        <div class="card-body">
          <?php if (!$replies): ?><p class="text-muted">No replies yet.</p><?php endif; ?>
          <?php foreach ($replies as $r): ?>
            <div class="border rounded p-3 mb-2 <?= $r['is_internal_note'] ? 'bg-light border-warning' : '' ?>">
              <div class="d-flex justify-content-between">
                <strong><?= e($r['user_name']) ?> <span class="badge bg-secondary"><?= e(roleLabel($r['user_role'])) ?></span>
                <?php if ($r['is_internal_note']): ?><span class="badge bg-warning text-dark">Internal Note</span><?php endif; ?>
                </strong>
                <small class="text-muted"><?= date('M j, Y g:i A', strtotime($r['created_at'])) ?></small>
              </div>
              <p class="mb-1 mt-2" style="white-space:pre-wrap;"><?= e($r['message']) ?></p>
              <?php if ($r['attachment']): ?>
                <a href="uploads/<?= e($r['attachment']) ?>" target="_blank" class="small"><i class="bi bi-paperclip"></i> Attachment</a>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>

          <?php if (!in_array($ticket['status'], ['Closed','Cancelled']) || $isAdminUser): ?>
          <form method="POST" enctype="multipart/form-data" class="mt-3 no-print">
            <?= csrfField() ?>
            <div class="mb-2">
              <textarea name="message" rows="3" class="form-control" placeholder="Write a reply..." required></textarea>
            </div>
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
              <div class="d-flex align-items-center gap-2">
                <input type="file" name="reply_attachment" class="form-control form-control-sm" style="max-width:220px;">
                <?php if ($isAdminUser || $isTech): ?>
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_internal_note" id="internalNote">
                    <label class="form-check-label small" for="internalNote">Internal note (staff only)</label>
                  </div>
                <?php endif; ?>
              </div>
              <button type="submit" name="add_reply" class="btn btn-primary btn-sm">Send Reply</button>
            </div>
          </form>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card mb-3">
        <div class="card-header bg-white"><strong>Ticket Details</strong></div>
        <ul class="list-group list-group-flush">
          <li class="list-group-item d-flex justify-content-between"><span>Category</span><strong><?= e($ticket['category_name'] ?? '-') ?></strong></li>
          <li class="list-group-item d-flex justify-content-between"><span>Department</span><strong><?= e($ticket['department_name'] ?? '-') ?></strong></li>
          <li class="list-group-item d-flex justify-content-between"><span>Requester</span><strong><?= e($ticket['requester_name']) ?></strong></li>
          <li class="list-group-item d-flex justify-content-between"><span>Contact</span><strong><?= e($ticket['contact_info'] ?: $ticket['requester_email']) ?></strong></li>
          <li class="list-group-item d-flex justify-content-between"><span>Technician</span><strong><?= e($ticket['assignee_name'] ?? 'Unassigned') ?></strong></li>
          <li class="list-group-item d-flex justify-content-between"><span>Created</span><strong><?= date('M j, Y g:i A', strtotime($ticket['created_at'])) ?></strong></li>
          <li class="list-group-item d-flex justify-content-between"><span>Last Updated</span><strong><?= date('M j, Y g:i A', strtotime($ticket['updated_at'])) ?></strong></li>
          <li class="list-group-item d-flex justify-content-between"><span>Resolution Time</span><strong><?= calcResolutionTime($ticket['created_at'], $ticket['resolved_at']) ?></strong></li>
        </ul>
      </div>

      <?php if ($isAdminUser): ?>
      <div class="card mb-3 no-print">
        <div class="card-header bg-white"><strong>Assign Ticket</strong></div>
        <div class="card-body">
          <form method="POST">
            <?= csrfField() ?>
            <select name="assigned_to" class="form-select mb-2">
              <option value="">-- Unassigned --</option>
              <?php foreach ($technicians as $t): ?>
                <option value="<?= $t['id'] ?>" <?= $ticket['assigned_to']==$t['id']?'selected':'' ?>><?= e($t['name']) ?></option>
              <?php endforeach; ?>
            </select>
            <button type="submit" name="assign_ticket" class="btn btn-sm btn-primary w-100">Assign</button>
          </form>
        </div>
      </div>
      <?php endif; ?>

      <?php if ($isTech && !$ticket['assigned_to']): ?>
      <div class="card mb-3 no-print">
        <div class="card-body">
          <form method="POST">
            <?= csrfField() ?>
            <button type="submit" name="accept_ticket" class="btn btn-success w-100"><i class="bi bi-check2"></i> Accept This Ticket</button>
          </form>
        </div>
      </div>
      <?php endif; ?>

      <?php if ($isAdminUser || $isAssignee): ?>
      <div class="card mb-3 no-print">
        <div class="card-header bg-white"><strong>Update Status</strong></div>
        <div class="card-body">
          <form method="POST">
            <?= csrfField() ?>
            <select name="status" class="form-select mb-2">
              <?php foreach (['Open','Pending','In Progress','Resolved','Closed','Cancelled'] as $s): ?>
                <option value="<?= $s ?>" <?= $ticket['status']==$s?'selected':'' ?>><?= $s ?></option>
              <?php endforeach; ?>
            </select>
            <button type="submit" name="update_status" class="btn btn-sm btn-primary w-100">Update Status</button>
          </form>
        </div>
      </div>
      <?php endif; ?>

      <?php if (($isOwner || $isAdminUser) && !in_array($ticket['status'], ['Closed','Cancelled'])): ?>
      <div class="card mb-3 no-print">
        <div class="card-body">
          <form method="POST" onsubmit="return confirm('Close this ticket?');">
            <?= csrfField() ?>
            <button type="submit" name="close_ticket" class="btn btn-outline-success w-100"><i class="bi bi-check-circle"></i> Close Ticket</button>
          </form>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
