<?php
include 'api_auth.php';
require_user('admin');
$today = date('Y-m-d');

$st = $conn->prepare("SELECT br.record_id, u.name, u.erp_id, b.title, br.due_date,
                      DATEDIFF(?, br.due_date) AS days_overdue
                      FROM borrow_records br
                      JOIN users u ON br.user_id = u.user_id
                      JOIN books b ON br.book_id = b.book_id
                      WHERE br.status = 'issued' AND br.due_date < ?
                      ORDER BY br.due_date");
$st->bind_param("ss", $today, $today);
$st->execute();
echo json_encode(['overdue' => $st->get_result()->fetch_all(MYSQLI_ASSOC)]);