<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}
include 'db_connect.php';

$id = intval($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $author = trim($_POST['author']);
    $category = trim($_POST['category']);
    $isbn = trim($_POST['isbn']);

    $stmt = $conn->prepare("UPDATE books SET title=?, author=?, category=?, isbn=? WHERE book_id=?");
    $stmt->bind_param("ssssi", $title, $author, $category, $isbn, $id);
    $stmt->execute();
    $stmt->close();

    header("Location: view_books.php?msg=" . urlencode("Book updated successfully"));
    exit();
}

$stmt = $conn->prepare("SELECT * FROM books WHERE book_id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$book) {
    header("Location: view_books.php?msg=" . urlencode("Book not found"));
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Book</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5" style="max-width: 500px;">
    <h3 class="mb-4">✏️ Edit Book</h3>
    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($book['title']); ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Author</label>
            <input type="text" name="author" class="form-control" value="<?php echo htmlspecialchars($book['author']); ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Category</label>
            <input type="text" name="category" class="form-control" value="<?php echo htmlspecialchars($book['category']); ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">ISBN</label>
            <input type="text" name="isbn" class="form-control" value="<?php echo htmlspecialchars($book['isbn']); ?>">
        </div>
        <p class="text-muted small">Book Code: <strong><?php echo htmlspecialchars($book['book_code']); ?></strong> (QR prefix hai, isliye edit nahi kar sakte)</p>
        <p class="text-muted small">Copies add karni ho to "Add Book" se naya entry na banao — yeh feature abhi available nahi hai, future improvement ke roop mein add kar sakte hain.</p>
        <button type="submit" class="btn btn-success">Save Changes</button>
        <a href="view_books.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>
</body>
</html>