<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit();
}
include 'db_connect.php';

$user_id = $_SESSION['user_id'];

// Return button dabane par
if (isset($_GET['return_id'])) {
    $record_id = intval($_GET['return_id']);
    $return_date = date("Y-m-d");
    $stmt = $conn->prepare("UPDATE borrow_records SET return_date=?, status='returned' WHERE record_id=? AND user_id=? AND status='issued'");
    $stmt->bind_param("sii", $return_date, $record_id, $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: my_books.php?msg=" . urlencode("Book returned successfully"));
    exit();
}

$stmt = $conn->prepare("SELECT br.record_id, b.title, br.borrow_date, br.due_date, br.status 
        FROM borrow_records br
        JOIN books b ON br.book_id = b.book_id
        WHERE br.user_id = ?
        ORDER BY br.borrow_date DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$today = date("Y-m-d");
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Books - Library System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'toast.php'; ?>

<div class="container mt-5">
    <h2 class="mb-4">📖 My Borrowed Books</h2>
    <a href="index.php" class="btn btn-secondary mb-3">← Back to Home</a>

    <div class="mb-3">
        <input type="text" id="searchBox" class="form-control" placeholder="🔍 Search by book title...">
    </div>

    <table class="table table-bordered" id="myBooksTable">
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
            <?php while ($row = $result->fetch_assoc()) {
                $is_overdue = ($row['status'] == 'issued' && $row['due_date'] < $today);
            ?>
            <tr class="<?php echo $is_overdue ? 'table-danger' : ''; ?>">
                <td><?php echo htmlspecialchars($row['title']); ?></td>
                <td><?php echo $row['borrow_date']; ?></td>
                <td><?php echo $row['due_date']; ?></td>
                <td>
                    <?php if ($is_overdue) { ?>
                        <span class="badge bg-danger">Overdue</span>
                    <?php } elseif ($row['status'] == 'issued') { ?>
                        <span class="badge bg-warning text-dark">Issued</span>
                    <?php } else { ?>
                        <span class="badge bg-success">Returned</span>
                    <?php } ?>
                </td>
                <td>
                    <?php if ($row['status'] == 'issued') { ?>
                        <a href="my_books.php?return_id=<?php echo $row['record_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Return this book?');">Return</a>
                    <?php } else { ?>
                        —
                    <?php } ?>
                </td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<script>
document.getElementById('searchBox').addEventListener('keyup', function() {
    const query = this.value.toLowerCase();
    const rows = document.querySelectorAll('#myBooksTable tbody tr');
    rows.forEach(row => {
        const title = row.cells[0].textContent.toLowerCase();
        row.style.display = title.includes(query) ? '' : 'none';
    });
});
</script>
</body>
</html>
<?php $stmt->close(); $conn->close(); ?>