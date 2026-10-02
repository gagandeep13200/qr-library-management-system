<?php
include 'db_connect.php';
include 'jwt.php';
header('Content-Type: application/json');

$erp = $_POST['erp_id'] ?? '';
$pw  = $_POST['password'] ?? '';

$st = $conn->prepare("SELECT user_id, role, password FROM users WHERE erp_id=?");
$st->bind_param("s", $erp);
$st->execute();
$u = $st->get_result()->fetch_assoc();

if (!$u || !password_verify($pw, $u['password'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid login']);
    exit;
}
echo json_encode(['token' => jwt_create(['sub' => $u['user_id'], 'role' => $u['role']])]);