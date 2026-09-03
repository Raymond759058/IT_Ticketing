<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole(['super_admin', 'it_admin']);

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? 0;
$csrf = $_GET['csrf'] ?? '';

if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
    die('Invalid request token.');
}

if ($action === 'delete' && $id) {
    $stmt = $pdo->prepare("SELECT ticket_number FROM tickets WHERE id = ?");
    $stmt->execute([$id]);
    $t = $stmt->fetch();
    if ($t) {
        $pdo->prepare("DELETE FROM tickets WHERE id = ?")->execute([$id]);
        logAudit('Deleted ticket', $t['ticket_number']);
        flash('success', "Ticket {$t['ticket_number']} deleted.");
    }
}

redirect('admin/tickets.php');
