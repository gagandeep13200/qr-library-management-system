<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}
include 'db_connect.php';

$id = intval($_GET['id'] ?? 0);

// Prevent deleting a book that's currently issued
$check = $conn->prepare("SELECT COUNT(*) as c FROM borrow_records WHERE book_id=? AND status='issued'");
$check->bind_param("i", $id);
$check->execute();
$issued = $check->get_result()->fetch_assoc()['c'];
$check->close();

if ($issued > 0) {
    header("Location: view_books.php?msg=" . urlencode("Book deleted successfully"));
}

$stmt = $conn->prepare("DELETE FROM books WHERE book_id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

header("Location: view_books.php?msg=Book deleted successfully");
exit();