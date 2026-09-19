<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include 'db_connect.php';
$role = $_SESSION['role'];

// Fetch books with issued count for availability
$sql = "SELECT b.*, 
        (SELECT COUNT(*) FROM borrow_records br WHERE br.book_id = b.book_id AND br.status='issued') as issued_count
        FROM books b ORDER BY b.title ASC";
$books = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>View Books</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: #f4f6f9; padding: 30px; }
        .card { border-radius: 12px; }
    </style>
</head>
<body>
<?php include 'toast.php'; ?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>📚 All Books</h3>
        <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
    </div>

    <div class="mb-3">
        <input type="text" id="searchBox" class="form-control" placeholder="🔍 Search by title or author...">
    </div>

    <div class="card p-3">
        <table class="table table-hover align-middle" id="booksTable">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Total Copies</th>
                    <th>Available</th>
                    <th>Status</th>
                    <?php if ($role == 'admin') { ?><th>Actions</th><?php } ?>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $books->fetch_assoc()) {
                    $available = $row['quantity'] - $row['issued_count'];
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                    <td><?php echo htmlspecialchars($row['author']); ?></td>
                    <td><?php echo $row['quantity']; ?></td>
                    <td><?php echo $available; ?></td>
                    <td>
                        <?php if ($available > 0) { ?>
                            <span class="badge bg-success">Available</span>
                        <?php } else { ?>
                            <span class="badge bg-danger">Out of Stock</span>
                        <?php } ?>
                    </td>
                    <?php if ($role == 'admin') { ?>
                    <td>
                        <a href="edit_book.php?id=<?php echo $row['book_id']; ?>" class="btn btn-sm btn-primary"><i class="bi bi-pencil"></i></a>
                        <a href="delete_book.php?id=<?php echo $row['book_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this book? This cannot be undone.');"><i class="bi bi-trash"></i></a>
                    </td>
                    <?php } ?>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.getElementById('searchBox').addEventListener('keyup', function() {
    const query = this.value.toLowerCase();
    const rows = document.querySelectorAll('#booksTable tbody tr');
    rows.forEach(row => {
        const title = row.cells[0].textContent.toLowerCase();
        const author = row.cells[1].textContent.toLowerCase();
        row.style.display = (title.includes(query) || author.includes(query)) ? '' : 'none';
    });
});
</script>
</body>
</html>
<?php $conn->close(); ?>