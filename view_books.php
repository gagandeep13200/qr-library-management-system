<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include 'db_connect.php';
$role = $_SESSION['role'];

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sql = "SELECT b.*, 
        (SELECT COUNT(*) FROM borrow_records br WHERE br.book_id = b.book_id AND br.status='issued') as issued_count
        FROM books b WHERE 1=1";
$params = [];
$types = "";
if ($search != '') {
    $sql .= " AND (title LIKE ? OR author LIKE ?)";
    $like = "%$search%";
    $params[] = $like; $params[] = $like;
    $types .= "ss";
}
$sql .= " ORDER BY title";
$stmt = $conn->prepare($sql);
if (count($params) > 0) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$result = $stmt->get_result();
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

    <form method="GET" class="mb-3">
        <input type="text" name="search" class="form-control" placeholder="Search by title or author..." value="<?php echo htmlspecialchars($search); ?>">
    </form>

    <table class="table table-striped table-bordered">
        <thead class="table-dark">
            <tr>
                <th>Title</th><th>Author</th><th>Category</th><th>Course/Branch</th><th>ISBN</th><th>Total</th><th>Available</th>
                <?php if ($role == 'admin') echo "<th>Actions</th>"; ?>
            </tr>
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
                <td><?php echo htmlspecialchars($row['isbn']); ?></td>
                <td><?php echo $row['quantity']; ?></td>
                <td>
                    <?php if ($available > 0) { ?>
                        <span class="badge bg-success"><?php echo $available; ?></span>
                    <?php } else { ?>
                        <span class="badge bg-danger">0</span>
                    <?php } ?>
                </td>
                <?php if ($role == 'admin') { ?>
                <td>
                    <a href="edit_book.php?id=<?php echo $row['book_id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                    <a href="delete_book.php?id=<?php echo $row['book_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this book?');">Delete</a>
                </td>
                <?php } ?>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</div>
</body>
</html>
<?php $conn->close(); ?>