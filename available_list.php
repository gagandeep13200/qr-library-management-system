<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}
include 'db_connect.php';

$sql = "SELECT b.*, 
        (SELECT COUNT(*) FROM borrow_records br WHERE br.book_id = b.book_id AND br.status='issued') as issued_count
        FROM books b HAVING (b.quantity - issued_count) > 0 ORDER BY b.title";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Available Books - Library System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h2 class="mb-3">✅ Available Books</h2>
    <a href="index.php" class="btn btn-secondary mb-3">← Back to Dashboard</a>

    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr><th>Title</th><th>Author</th><th>Category</th><th>Course/Branch</th><th>Total</th><th>Issued</th><th>Available</th></tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()) { 
                $available = $row['quantity'] - $row['issued_count'];
            ?>
            <tr>
                <td><?php echo htmlspecialchars($row['title']); ?></td>
                <td><?php echo htmlspecialchars($row['author']); ?></td>
                <td><?php echo htmlspecialchars($row['category']); ?></td>
                <td><?php echo htmlspecialchars($row['course'] . " " . $row['branch']); ?></td>
                <td><?php echo $row['quantity']; ?></td>
                <td><?php echo $row['issued_count']; ?></td>
                <td><span class="badge bg-success"><?php echo $available; ?></span></td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</div>
</body>
</html>
<?php $conn->close(); ?>