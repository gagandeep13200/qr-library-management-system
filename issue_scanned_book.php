<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    die("Unauthorized access.");
}
include 'db_connect.php';

$book_id = $_GET['book_id'];
$user_id = 2; // abhi fixed rakha hai, baad mein login system se aayega
$borrow_date = date("Y-m-d");
$due_date = date("Y-m-d", strtotime("+14 days"));

$sql = "INSERT INTO borrow_records (user_id, book_id, borrow_date, due_date, status) 
        VALUES ('$user_id', '$book_id', '$borrow_date', '$due_date', 'issued')";

if ($conn->query($sql) === TRUE) {
    echo "Book Issued! Due: " . $due_date;
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>