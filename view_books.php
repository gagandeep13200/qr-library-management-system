<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include 'db_connect.php';

$sql = "SELECT * FROM books";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Books - Library System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2 class="mb-4">📚 Books in Library</h2>
    <a href="index.php" class="btn btn-secondary mb-3">← Back to Home</a>
    <table class="table table-striped table-bordered">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Author</th>
                <th>Category</th>
                <th>ISBN</th>
                <th>Quantity</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()) { ?>
            <tr>
                <td><?php echo $row['book_id']; ?></td>
                <td><?php echo $row['title']; ?></td>
                <td><?php echo $row['author']; ?></td>
                <td><?php echo $row['category']; ?></td>
                <td><?php echo $row['isbn']; ?></td>
                <td><?php echo $row['quantity']; ?></td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</div>
</body>
</html>
<?php $conn->close(); ?>