<?php
include 'db_connect.php';

// Test values - baad mein yeh QR scan se aayenge
$user_id = 2;   // John Doe
$book_id = 1;   // The Alchemist
$borrow_date = date("Y-m-d");           // aaj ki date
$due_date = date("Y-m-d", strtotime("+14 days")); // 14 din baad

$sql = "INSERT INTO borrow_records (user_id, book_id, borrow_date, due_date, status) 
        VALUES ('$user_id', '$book_id', '$borrow_date', '$due_date', 'issued')";

if ($conn->query($sql) === TRUE) {
    echo "Book issued successfully! Due date: " . $due_date;
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>