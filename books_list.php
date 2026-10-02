<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}
include 'db_connect.php';

$search        = isset($_GET['search']) ? trim($_GET['search']) : '';
$course_filter = isset($_GET['course']) ? trim($_GET['course']) : '';
$branch_filter = isset($_GET['branch']) ? trim($_GET['branch']) : '';

$sql = "SELECT b.*,
        (SELECT COUNT(*) FROM borrow_records br
         WHERE br.book_id = b.book_id AND br.status = 'issued') AS issued_count
        FROM books b WHERE 1=1";
$params = [];
$types  = "";

if ($search != '') {
    $sql .= " AND (b.title LIKE ? OR b.author LIKE ? OR b.isbn LIKE ? OR b.category LIKE ?)";
    $like = "%$search%";
    $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= "ssss";
}
if ($course_filter != '') {
    $sql .= " AND b.course = ?";
    $params[] = $course_filter;
    $types .= "s";
}
if ($branch_filter != '') {
    $sql .= " AND b.branch = ?";
    $params[] = $branch_filter;
    $types .= "s";
}
$sql .= " ORDER BY b.title";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Query error: " . htmlspecialchars($conn->error));
}
if (count($params) > 0) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$total  = $result->num_rows;

$courses  = $conn->query("SELECT DISTINCT course FROM books WHERE course IS NOT NULL AND course != '' ORDER BY course");
$branches = $conn->query("SELECT DISTINCT branch FROM books WHERE branch IS NOT NULL AND branch != '' ORDER BY branch");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>All Books - Library System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h2 class="mb-3">📚 All Books <small class="text-muted fs-6">(<?php echo $total; ?> found)</small></h2>
    <a href="index.php" class="btn btn-secondary mb-3">← Back to Dashboard</a>

    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-4">
            <input type="text" name="search" class="form-control" placeholder="Search title, author, ISBN, category" value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="col-md-3">
            <select name="course" class="form-select">
                <option value="">All Courses</option>
                <?php while ($c = $courses->fetch_assoc()) {
                    $val = htmlspecialchars($c['course']);
                    $sel = ($c['course'] == $course_filter) ? 'selected' : '';
                    echo "<option value=\"$val\" $sel>$val</option>";
                } ?>
            </select>
        </div>
        <div class="col-md-3">
            <select name="branch" class="form-select">
                <option value="">All Branches</option>
                <?php while ($b = $branches->fetch_assoc()) {
                    $val = htmlspecialchars($b['branch']);
                    $sel = ($b['branch'] == $branch_filter) ? 'selected' : '';
                    echo "<option value=\"$val\" $sel>$val</option>";
                } ?>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary w-100">Filter</button>
            <a href="books_list.php" class="btn btn-outline-secondary">Reset</a>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Title</th><th>Author</th><th>Category</th><th>Course</th><th>Branch</th><th>ISBN</th><th>Total</th><th>Issued</th><th>Available</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($total == 0) { ?>
                <tr><td colspan="9" class="text-center text-muted py-4">No books found.</td></tr>
                <?php } else { while ($row = $result->fetch_assoc()) {
                    $qty       = (int)$row['quantity'];
                    $issued    = (int)$row['issued_count'];
                    $available = max(0, $qty - $issued);
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                    <td><?php echo htmlspecialchars($row['author']); ?></td>
                    <td><?php echo htmlspecialchars($row['category']); ?></td>
                    <td><?php echo htmlspecialchars($row['course']); ?></td>
                    <td><?php echo htmlspecialchars($row['branch']); ?></td>
                    <td><?php echo htmlspecialchars($row['isbn']); ?></td>
                    <td><?php echo $qty; ?></td>
                    <td><?php echo $issued; ?></td>
                    <td>
                        <?php if ($available > 0) { ?>
                            <span class="badge bg-success"><?php echo $available; ?> Available</span>
                        <?php } else { ?>
                            <span class="badge bg-danger">0 Available</span>
                        <?php } ?>
                    </td>
                </tr>
                <?php } } ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
<?php $stmt->close(); $conn->close(); ?>