<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}
include 'db_connect.php';

// Count directly from book_copies.status — same source of truth used by
// generate_qr.php, manage_records.php and issue_scanned_book.php
$sql = "SELECT b.book_id, b.title, b.author, b.category, b.course, b.branch,
               COUNT(bc.copy_id) AS total_copies,
               SUM(CASE WHEN bc.status='available' THEN 1 ELSE 0 END) AS available_count,
               SUM(CASE WHEN bc.status='issued'    THEN 1 ELSE 0 END) AS issued_count,
               SUM(CASE WHEN bc.status='lost'      THEN 1 ELSE 0 END) AS lost_count
        FROM books b
        JOIN book_copies bc ON bc.book_id = b.book_id
        GROUP BY b.book_id
        HAVING available_count > 0
        ORDER BY b.title";
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
            <tr><th>Title</th><th>Author</th><th>Category</th><th>Course/Branch</th><th>Total Copies</th><th>Issued</th><th>Lost</th><th>Available</th></tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()) { ?>
            <tr>
                <td><?php echo htmlspecialchars($row['title']); ?></td>
                <td><?php echo htmlspecialchars($row['author']); ?></td>
                <td><?php echo htmlspecialchars($row['category']); ?></td>
                <td><?php echo htmlspecialchars($row['course'] . " " . $row['branch']); ?></td>
                <td><?php echo $row['total_copies']; ?></td>
                <td><?php echo $row['issued_count']; ?></td>
                <td><?php echo $row['lost_count']; ?></td>
                <td><span class="badge bg-success"><?php echo $row['available_count']; ?></span></td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
    <?php if ($result->num_rows == 0) echo "<p class='text-muted'>Abhi koi copy available nahi hai.</p>"; ?>
</div>
</body>
</html>
<?php $conn->close(); ?>