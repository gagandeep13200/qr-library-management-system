<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}
include 'db_connect.php';

$today = date("Y-m-d");
$sql = "SELECT br.record_id, u.name, u.erp_id, u.course, u.branch, b.title, br.borrow_date, br.due_date
        FROM borrow_records br
        JOIN users u ON br.user_id = u.user_id
        JOIN books b ON br.book_id = b.book_id
        WHERE br.status = 'issued' AND br.due_date < ?
        ORDER BY br.due_date";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $today);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Overdue Books - Library System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h2 class="mb-3">⚠️ Overdue Books</h2>
    <a href="index.php" class="btn btn-secondary mb-3">← Back to Dashboard</a>

    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr><th>Student</th><th>ERP ID</th><th>Course/Branch</th><th>Book</th><th>Borrow Date</th><th>Due Date</th><th>Days Overdue</th></tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()) { 
                $days_overdue = (strtotime($today) - strtotime($row['due_date'])) / 86400;
            ?>
            <tr>
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td><?php echo htmlspecialchars($row['erp_id']); ?></td>
                <td><?php echo htmlspecialchars($row['course'] . " " . $row['branch']); ?></td>
                <td><?php echo htmlspecialchars($row['title']); ?></td>
                <td><?php echo $row['borrow_date']; ?></td>
                <td><?php echo $row['due_date']; ?></td>
                <td><span class="badge bg-danger"><?php echo (int)$days_overdue; ?> days</span></td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
    <?php if ($result->num_rows == 0) echo "<p class='text-muted'>No overdue books right now. 🎉</p>"; ?>
</div>
</body>
</html>
<?php $conn->close(); ?>