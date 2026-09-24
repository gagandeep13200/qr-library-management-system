<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}
include 'db_connect.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$sql = "SELECT br.record_id, u.name, u.erp_id, u.course, u.branch, b.title, br.borrow_date, br.due_date
        FROM borrow_records br
        JOIN users u ON br.user_id = u.user_id
        JOIN books b ON br.book_id = b.book_id
        WHERE br.status = 'issued'";
$params = [];
$types = "";

if ($search != '') {
    $sql .= " AND (u.name LIKE ? OR u.erp_id LIKE ? OR b.title LIKE ?)";
    $like = "%$search%";
    $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= "sss";
}
$sql .= " ORDER BY br.due_date";

$stmt = $conn->prepare($sql);
if (count($params) > 0) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Issued Books - Library System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h2 class="mb-3">📕 Currently Issued Books</h2>
    <a href="index.php" class="btn btn-secondary mb-3">← Back to Dashboard</a>

    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-6">
            <input type="text" name="search" class="form-control" placeholder="Search student name, ERP ID, or book title" value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">Search</button>
        </div>
    </form>

    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr><th>Student</th><th>ERP ID</th><th>Course/Branch</th><th>Book</th><th>Borrow Date</th><th>Due Date</th></tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()) { ?>
            <tr>
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td><?php echo htmlspecialchars($row['erp_id']); ?></td>
                <td><?php echo htmlspecialchars($row['course'] . " " . $row['branch']); ?></td>
                <td><?php echo htmlspecialchars($row['title']); ?></td>
                <td><?php echo $row['borrow_date']; ?></td>
                <td><?php echo $row['due_date']; ?></td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</div>
</body>
</html>
<?php $conn->close(); ?>