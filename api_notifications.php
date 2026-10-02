<?php
include 'api_auth.php';
$user = require_user();
$uid = (int)$user['sub'];

$st = $conn->prepare("SELECT id, type, message, notify_date, is_read
                      FROM fine_notifications WHERE user_id = ?
                      ORDER BY notify_date DESC, id DESC LIMIT 50");
$st->bind_param("i", $uid);
$st->execute();
echo json_encode(['notifications' => $st->get_result()->fetch_all(MYSQLI_ASSOC)]);