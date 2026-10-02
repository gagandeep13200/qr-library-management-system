<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}
include 'db_connect.php';

$id = intval($_GET['id'] ?? 0);

// Prevent deleting a book agar uski koi copy abhi issued hai
$check = $conn->prepare("SELECT COUNT(*) as c FROM book_copies bc JOIN book_issues bi ON bc.copy_id = bi.copy_id WHERE bc.book_id=? AND bi.status='issued'");
$check->bind_param("i", $id);
$check->execute();
$issued = $check->get_result()->fetch_assoc()['c'];
$check->close();

if ($issued > 0) {
    header("Location: view_books.php?error=" . urlencode("Cannot delete — is book ki ek ya zyada copies abhi kisi student ko issued hain"));
    exit();
}

$stmt = $conn->prepare("DELETE FROM books WHERE book_id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

header("Location: view_books.php?msg=" . urlencode("Book deleted successfully"));
exit();
?>