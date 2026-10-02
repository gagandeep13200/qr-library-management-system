<?php
include 'db_connect.php';
include 'jwt.php';
header('Content-Type: application/json');

// Header kisi bhi tareeke se mile, wahan se utha lo
$headers = function_exists('getallheaders') ? getallheaders() : [];
$auth = $_SERVER['HTTP_AUTHORIZATION']
     ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
     ?? ($headers['Authorization'] ?? $headers['authorization'] ?? '');

$token = trim(preg_replace('/^Bearer\s+/i', '', $auth));

// Token verify karo (ye hissa hatana nahi hai)
$user = jwt_verify($token);
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Token invalid ya expired']);
    exit;
}

$uid = (int)$user['sub'];
$st = $conn->prepare("SELECT br.record_id, b.title, br.borrow_date, br.due_date, br.return_date, br.status
                      FROM borrow_records br
                      JOIN books b ON br.book_id = b.book_id
                      WHERE br.user_id = ?
                      ORDER BY br.borrow_date DESC");
$st->bind_param("i", $uid);
$st->execute();
echo json_encode(['books' => $st->get_result()->fetch_all(MYSQLI_ASSOC)]);