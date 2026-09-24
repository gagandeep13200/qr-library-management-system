<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    die("Unauthorized access.");
}
include 'db_connect.php';

$copy_id    = trim($_GET['copy_id'] ?? '');
$erp_id     = trim($_GET['erp_id'] ?? '');
$issue_date = trim($_GET['issue_date'] ?? '');
$due_date   = trim($_GET['due_date'] ?? '');

if ($copy_id === '' || $erp_id === '') {
    die("Copy ID aur ERP ID dono zaroori hain.");
}
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

try {
    $conn->begin_transaction();

    // Student dhundo ERP ID se
    $stmt = $conn->prepare("SELECT user_id, name FROM users WHERE erp_id = ? AND role = 'student'");
    $stmt->bind_param("s", $erp_id);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$student) {
        throw new Exception("Koi student is ERP ID se nahi mila: " . $erp_id);
    }

    // Copy dhundo, uska book_id aur current status lock karo
    $stmt = $conn->prepare("SELECT bc.status, bc.book_id, b.title
                             FROM book_copies bc
                             JOIN books b ON bc.book_id = b.book_id
                             WHERE bc.copy_id = ? FOR UPDATE");
    $stmt->bind_param("s", $copy_id);
    $stmt->execute();
    $copy = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$copy) {
        throw new Exception("Yeh Copy ID system mein nahi mila: " . $copy_id);
    }
    if ($copy['status'] != 'available') {
        throw new Exception("Yeh copy (" . $copy_id . ") abhi available nahi hai. Current status: " . $copy['status']);
    }

    // Same table jo manage_records.php / issued_list.php / my_books.php sab use karte hain
    $stmt = $conn->prepare("INSERT INTO borrow_records (user_id, book_id, copy_id, borrow_date, due_date, status)
                             VALUES (?, ?, ?, ?, ?, 'issued')");
    $stmt->bind_param("iisss", $student['user_id'], $copy['book_id'], $copy_id, $issue_date, $due_date);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("UPDATE book_copies SET status='issued' WHERE copy_id=?");
    $stmt->bind_param("s", $copy_id);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    echo "✅ '" . htmlspecialchars($copy['title']) . "' (Copy: " . htmlspecialchars($copy_id) . ") issued to " . htmlspecialchars($student['name']) . ". Issue: " . htmlspecialchars($issue_date) . " | Due: " . htmlspecialchars($due_date);
} catch (Throwable $e) {
    $conn->rollback();
    echo "❌ Error: " . htmlspecialchars($e->getMessage());
}

$conn->close();