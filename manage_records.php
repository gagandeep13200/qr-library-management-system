<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}
include 'db_connect.php';

// Return button dabane par
if (isset($_GET['return_id'])) {
    $record_id = intval($_GET['return_id']);
    $return_date = date("Y-m-d");
    $stmt = $conn->prepare("UPDATE borrow_records SET return_date=?, status='returned' WHERE record_id=?");
    $stmt->bind_param("si", $return_date, $record_id);
    $stmt->execute();
    $stmt->close();
    header("Location: manage_records.php?msg=" . urlencode("Book marked as returned"));
    exit();
}

$sql = "SELECT br.record_id, u.name, u.erp_id, b.title, br.borrow_date, br.due_date, br.status 
        FROM borrow_records br
        JOIN users u ON br.user_id = u.user_id
        JOIN books b ON br.book_id = b.book_id
        ORDER BY br.borrow_date DESC";
$result = $conn->query($sql);
$today = date("Y-m-d");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Records - Library System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'toast.php'; ?>

<div class="container mt-5">
    <h2 class="mb-4">📋 Manage All Borrowing Records</h2>
    <a href="index.php" class="btn btn-secondary mb-3">← Back to Home</a>

    <div class="mb-3">
        <input type="text" id="searchBox" class="form-control" placeholder="🔍 Search by student name, ERP ID, or book...">
    </div>

    <table class="table table-bordered table-striped" id="recordsTable">
        <thead class="table-dark">
            <tr>
                <th>Student</th>
                <th>ERP ID</th>
                <th>Book</th>
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
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td><?php echo htmlspecialchars($row['erp_id']); ?></td>
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
                        <a href="manage_records.php?return_id=<?php echo $row['record_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Mark this book as returned?');">Mark Returned</a>
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
    const rows = document.querySelectorAll('#recordsTable tbody tr');
    rows.forEach(row => {
        const name = row.cells[0].textContent.toLowerCase();
        const erp = row.cells[1].textContent.toLowerCase();
        const book = row.cells[2].textContent.toLowerCase();
        row.style.display = (name.includes(query) || erp.includes(query) || book.includes(query)) ? '' : 'none';
    });
});
</script>
</body>
</html>
<?php $conn->close(); ?>