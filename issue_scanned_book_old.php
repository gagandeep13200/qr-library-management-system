<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    die("Unauthorized access.");
}
include 'db_connect.php';

$copy_id = trim($_GET['copy_id'] ?? '');
$erp_id = trim($_GET['erp_id'] ?? '');
$issue_date = trim($_GET['issue_date'] ?? '');
$due_date = trim($_GET['due_date'] ?? '');

if ($copy_id === '' || $erp_id === '') {
    die("Copy ID aur ERP ID dono zaroori hain.");
}

// ---------- Issue Date aur Due Date dono manual, independent validation ----------
if ($issue_date === '' || $due_date === '') {
    die("Issue Date aur Due Date dono zaroori hain.");
}

$d1 = DateTime::createFromFormat('Y-m-d', $issue_date);
if (!$d1 || $d1->format('Y-m-d') !== $issue_date) {
    die("Issue Date invalid hai.");
}

$d2 = DateTime::createFromFormat('Y-m-d', $due_date);
if (!$d2 || $d2->format('Y-m-d') !== $due_date) {
    die("Due Date invalid hai.");
}

if ($d2 < $d1) {
    die("Due Date, Issue Date se pehle nahi ho sakti.");
}

// Student dhundo ERP ID se
$stmt = $conn->prepare("SELECT user_id, name FROM users WHERE erp_id = ? AND role = 'student'");
$stmt->bind_param("s", $erp_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    die("Koi student is ERP ID se nahi mila: " . htmlspecialchars($erp_id));
}

// Copy dhundo aur uska current status check karo
$stmt = $conn->prepare("SELECT bc.status, b.title FROM book_copies bc JOIN books b ON bc.book_id = b.book_id WHERE bc.copy_id = ?");
$stmt->bind_param("s", $copy_id);
$stmt->execute();
$copy = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$copy) {
    die("Yeh Copy ID system mein nahi mila: " . htmlspecialchars($copy_id));
}

if ($copy['status'] != 'available') {
    die("Yeh copy (" . htmlspecialchars($copy_id) . ") abhi available nahi hai. Current status: " . htmlspecialchars($copy['status']));
}

// Sab sahi hai — issue karo (Issue Date aur Due Date dono admin ne manually diye)
$stmt = $conn->prepare("INSERT INTO book_issues (copy_id, user_id, issue_date, due_date, status) VALUES (?, ?, ?, ?, 'issued')");
$stmt->bind_param("siss", $copy_id, $student['user_id'], $issue_date, $due_date);
$stmt->execute();
$stmt->close();

$stmt = $conn->prepare("UPDATE book_copies SET status='issued' WHERE copy_id=?");
$stmt->bind_param("s", $copy_id);
$stmt->execute();
$stmt->close();

echo "✅ '" . htmlspecialchars($copy['title']) . "' (Copy: " . htmlspecialchars($copy_id) . ") issued to " . htmlspecialchars($student['name']) . ". Issue: " . $issue_date . " | Due: " . $due_date;

$conn->close();
?>