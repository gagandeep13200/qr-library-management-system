<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include 'db_connect.php';

$user_id = $_SESSION['user_id'];

// Return button dabane par
if (isset($_GET['return_id'])) {
    $record_id = $_GET['return_id'];
    $return_date = date("Y-m-d");
    $conn->query("UPDATE borrow_records SET return_date='$return_date', status='returned' WHERE record_id='$record_id'");
}

$sql = "SELECT br.record_id, b.title, br.borrow_date, br.due_date, br.status 
        FROM borrow_records br
        JOIN books b ON br.book_id = b.book_id
        WHERE br.user_id = '$user_id'
        ORDER BY br.borrow_date DESC";
$result = $conn->query($sql);
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
    <table class="table table-bordered">
        <thead class="table-dark">
            <tr>
                <th>Book Title</th>
                <th>Borrow Date</th>
                <th>Due Date</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()) { ?>
            <tr>
                <td><?php echo $row['title']; ?></td>
                <td><?php echo $row['borrow_date']; ?></td>
                <td><?php echo $row['due_date']; ?></td>
                <td>
                    <?php if ($row['status'] == 'issued') { ?>
                        <span class="badge bg-warning text-dark">Issued</span>
                    <?php } else { ?>
                        <span class="badge bg-success">Returned</span>
                    <?php } ?>
                </td>
                <td>
                    <?php if ($row['status'] == 'issued') { ?>
                        <a href="my_books.php?return_id=<?php echo $row['record_id']; ?>" class="btn btn-sm btn-danger">Return</a>
                    <?php } else { ?>
                        —
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