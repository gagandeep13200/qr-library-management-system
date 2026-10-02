<?php
include 'db_connect.php';

$res = $conn->query("SELECT user_id, erp_id, password FROM users");
$upd = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
$done = 0; $skipped = 0;

while ($u = $res->fetch_assoc()) {
    // Pehle se hashed ($2y$...) ho to chhod do
    if (strpos($u['password'], '$2y$') === 0) { $skipped++; continue; }
    $h = password_hash($u['password'], PASSWORD_DEFAULT);
    $id = (int)$u['user_id'];
    $upd->bind_param("si", $h, $id);
    $upd->execute();
    $done++;
}
echo "Hashed: $done | Already hashed (skipped): $skipped";