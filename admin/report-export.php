<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole(['super_admin', 'it_admin']);

$stmt = $pdo->query("SELECT t.ticket_number, t.subject, c.name category, d.name department, p.name priority,
    t.status, ru.name requester, au.name technician, t.created_at, t.updated_at, t.resolved_at
    FROM tickets t
    LEFT JOIN categories c ON c.id = t.category_id
    LEFT JOIN departments d ON d.id = t.department_id
    LEFT JOIN priorities p ON p.id = t.priority_id
    LEFT JOIN users ru ON ru.id = t.requester_id
    LEFT JOIN users au ON au.id = t.assigned_to
    ORDER BY t.created_at DESC");
$rows = $stmt->fetchAll();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="ticket_report_' . date('Y-m-d') . '.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['Ticket Number','Subject','Category','Department','Priority','Status','Requester','Technician','Created','Last Updated','Resolved At']);
foreach ($rows as $r) {
    fputcsv($out, [
        $r['ticket_number'], $r['subject'], $r['category'], $r['department'], $r['priority'],
        $r['status'], $r['requester'], $r['technician'] ?: 'Unassigned',
        $r['created_at'], $r['updated_at'], $r['resolved_at'] ?: '-'
    ]);
}
fclose($out);
exit;
