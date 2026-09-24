<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}
include 'db_connect.php';

$success = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title     = trim($_POST['title']);
    $author    = trim($_POST['author']);
    $category  = trim($_POST['category']);
    $isbn      = trim($_POST['isbn']);
    if ($isbn == "") {
        $isbn = null;
    }
    $course    = trim($_POST['course']);
    $branch    = trim($_POST['branch']);
    $semester  = trim($_POST['semester']);
    $quantity  = max(1, (int)$_POST['quantity']);
    // Copy IDs will look like DBMS-001, so keep the code short and clean
    $book_code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $_POST['book_code']));

    if ($book_code == "") {
        $success = "Error: Book Code required hai (e.g. DBMS).";
    } else {
        try {
            $conn->begin_transaction();

            $stmt = $conn->prepare("INSERT INTO books (title, author, category, isbn, quantity, course, branch, semester, book_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssissss", $title, $author, $category, $isbn, $quantity, $course, $branch, $semester, $book_code);
            if (!$stmt->execute()) {
                throw new Exception($conn->error);
            }
            $new_book_id = $conn->insert_id;

            // One row per physical copy; status defaults to 'available'
            $copy_stmt = $conn->prepare("INSERT INTO book_copies (copy_id, book_id) VALUES (?, ?)");
            for ($i = 1; $i <= $quantity; $i++) {
                $copy_id = $book_code . '-' . str_pad($i, 3, '0', STR_PAD_LEFT);
                $copy_stmt->bind_param("si", $copy_id, $new_book_id);
                if (!$copy_stmt->execute()) {
                    throw new Exception($conn->error);
                }
            }

            $conn->commit();
            $success = "Book added successfully with $quantity copies! QR codes generate_qr.php mein dikhenge.";
        } catch (Throwable $e) {
            $conn->rollback();
            if ($e->getCode() == 1062) {
                $success = "Error: Book Code ya ISBN pehle se exist karta hai. Koi unique code use karo.";
            } else {
                $success = "Error: " . $e->getMessage();
            }
        }
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Book - Library System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5" style="max-width: 550px;">
    <h2 class="mb-4">➕ Add New Book</h2>
    <a href="index.php" class="btn btn-secondary mb-3">← Back to Home</a>
    <?php if ($success) echo "<div class='alert alert-info'>" . htmlspecialchars($success) . "</div>"; ?>
    <form method="POST">
        <input type="text" name="title" class="form-control mb-2" placeholder="Book Title" required>
        <input type="text" name="author" class="form-control mb-2" placeholder="Author" required>
        <input type="text" name="book_code" class="form-control mb-2" placeholder="Book Code for QR (e.g. DBMS, OS, CN)" maxlength="12" required>
        <input type="text" name="category" class="form-control mb-2" placeholder="Category / Subject">
        <input type="text" name="isbn" class="form-control mb-2" placeholder="ISBN / Book ID">
        <input type="text" name="course" class="form-control mb-2" placeholder="Course (e.g. B.Tech, BCA, MBA)">
        <input type="text" name="branch" class="form-control mb-2" placeholder="Branch (e.g. CSE, ECE, Civil)">
        <input type="text" name="semester" class="form-control mb-2" placeholder="Semester (e.g. 3rd Semester)">
        <input type="number" name="quantity" class="form-control mb-3" placeholder="Total Copies" value="1" min="1" required>
        <button type="submit" class="btn btn-success w-100">Add Book</button>
    </form>
</div>
</body>
</html>