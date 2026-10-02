<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: index.php");
    exit();
}
include 'db_connect.php';

$user_id = $_SESSION['user_id'];
$today = date("Y-m-d");

$stmt = $conn->prepare("SELECT br.record_id, b.title, br.borrow_date, br.due_date, br.status 
        FROM borrow_records br
        JOIN books b ON br.book_id = b.book_id
        WHERE br.user_id = ?
        ORDER BY br.borrow_date DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Books - Library System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2 class="mb-4">📖 My Borrowed Books</h2>
    <a href="index.php" class="btn btn-secondary mb-3">← Back to Home</a>
    <p class="text-muted" style="font-size:14px;">Note: Only the Admin/Librarian can mark a book as returned. Please return your physical book at the library counter.</p>
    <table class="table table-bordered">
        <thead class="table-dark">
            <tr>
                <th>Book Title</th>
                <th>Borrow Date</th>
                <th>Due Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()) { ?>
            <tr>
                <td><?php echo htmlspecialchars($row['title']); ?></td>
                <td><?php echo $row['borrow_date']; ?></td>
                <td><?php echo $row['due_date']; ?></td>
                <td>
                    <?php if ($row['status'] == 'issued') { 
                        if ($row['due_date'] < $today) { ?>
                            <span class="badge bg-danger">Overdue</span>
                        <?php } else { ?>
                            <span class="badge bg-warning text-dark">Issued</span>
                        <?php }
                    } else { ?>
                        <span class="badge bg-success">Returned</span>
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