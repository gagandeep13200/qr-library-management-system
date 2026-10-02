<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}
include 'db_connect.php';

// LEFT JOIN so books with no copies registered yet still show up (0 total)
$sql = "SELECT b.book_id, b.title, b.course, b.branch,
               COUNT(bc.copy_id) AS total_copies,
               SUM(CASE WHEN bc.status='available' THEN 1 ELSE 0 END) AS available_count,
               SUM(CASE WHEN bc.status='issued'    THEN 1 ELSE 0 END) AS issued_count,
               SUM(CASE WHEN bc.status='lost'      THEN 1 ELSE 0 END) AS lost_count
        FROM books b
        LEFT JOIN book_copies bc ON bc.book_id = b.book_id
        GROUP BY b.book_id
        ORDER BY b.title";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>All Copies - Library System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h2 class="mb-3">📦 All Book Copies</h2>
    <a href="index.php" class="btn btn-secondary mb-3">← Back to Dashboard</a>

    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr><th>Title</th><th>Course/Branch</th><th>Total Copies</th><th>Issued</th><th>Lost</th><th>Available</th><th>Status</th></tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()) {
                $total = (int)$row['total_copies'];
                $available = (int)$row['available_count'];
                $issued = (int)$row['issued_count'];
            ?>
            <tr>
                <td><?php echo htmlspecialchars($row['title']); ?></td>
                <td><?php echo htmlspecialchars($row['course'] . " " . $row['branch']); ?></td>
                <td><?php echo $total; ?></td>
                <td><?php echo $issued; ?></td>
                <td><?php echo (int)$row['lost_count']; ?></td>
                <td><?php echo $available; ?></td>
                <td>
                    <?php if ($total == 0) { ?>
                        <span class="badge bg-secondary">No Copies Registered</span>
                    <?php } elseif ($available == 0) { ?>
                        <span class="badge bg-danger">All Issued</span>
                    <?php } elseif ($issued > 0) { ?>
                        <span class="badge bg-warning text-dark">Partially Issued</span>
                    <?php } else { ?>
                        <span class="badge bg-success">Fully Available</span>
                    <?php } ?>
                </td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</div>
</body>
</html>
<?php $conn->close(); ?>
