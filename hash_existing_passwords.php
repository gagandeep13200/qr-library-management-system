<?php
include 'db_connect.php';

// Sabhi users ka data lo
$result = $conn->query("SELECT user_id, password FROM users");

while ($row = $result->fetch_assoc()) {
    $id = $row['user_id'];
    $plain_password = $row['password'];
    
    // Agar already hashed hai toh skip karo (hashed passwords lambe hote hain aur $2y$ se start hote hain)
    if (strpos($plain_password, '$2y$') === 0) {
        continue;
    }
    
    $hashed = password_hash($plain_password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
    $stmt->bind_param("si", $hashed, $id);
    $stmt->execute();
}

echo "All passwords hashed successfully!";
$conn->close();
?>