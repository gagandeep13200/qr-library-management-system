<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}
include 'db_connect.php';

$success = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $author = $_POST['author'];
    $category = $_POST['category'];
    $isbn = $_POST['isbn'];
    $quantity = $_POST['quantity'];

    $sql = "INSERT INTO books (title, author, category, isbn, quantity) 
            VALUES ('$title', '$author', '$category', '$isbn', '$quantity')";
    if ($conn->query($sql) === TRUE) {
        $success = "Book added successfully!";
    } else {
        $success = "Error: " . $conn->error;
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
<div class="container mt-5" style="max-width: 500px;">
    <h2 class="mb-4">➕ Add New Book</h2>
    <a href="index.php" class="btn btn-secondary mb-3">← Back to Home</a>
    <?php if ($success) echo "<div class='alert alert-info'>$success</div>"; ?>
    <form method="POST">
        <input type="text" name="title" class="form-control mb-2" placeholder="Book Title" required>
        <input type="text" name="author" class="form-control mb-2" placeholder="Author" required>
        <input type="text" name="category" class="form-control mb-2" placeholder="Category">
        <input type="text" name="isbn" class="form-control mb-2" placeholder="ISBN">
        <input type="number" name="quantity" class="form-control mb-3" placeholder="Quantity" value="1" required>
        <button type="submit" class="btn btn-success w-100">Add Book</button>
    </form>
</div>
</body>
</html>