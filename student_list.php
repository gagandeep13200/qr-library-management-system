<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}
include 'db_connect.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$course_filter = isset($_GET['course']) ? trim($_GET['course']) : '';
$branch_filter = isset($_GET['branch']) ? trim($_GET['branch']) : '';
$year_filter = isset($_GET['year']) ? trim($_GET['year']) : '';

$sql = "SELECT * FROM users WHERE role='student'";
$params = [];
$types = "";

if ($search != '') {
    $sql .= " AND (name LIKE ? OR erp_id LIKE ? OR roll_number LIKE ?)";
    $like = "%$search%";
    $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= "sss";
}
if ($course_filter != '') {
    $sql .= " AND course = ?";
    $params[] = $course_filter;
    $types .= "s";
}
if ($branch_filter != '') {
    $sql .= " AND branch = ?";
    $params[] = $branch_filter;
    $types .= "s";
}
if ($year_filter != '') {
    $sql .= " AND year = ?";
    $params[] = $year_filter;
    $types .= "i";
}
$sql .= " ORDER BY name";

$stmt = $conn->prepare($sql);
if (count($params) > 0) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$courses = $conn->query("SELECT DISTINCT course FROM users WHERE course IS NOT NULL AND course != ''");
$branches = $conn->query("SELECT DISTINCT branch FROM users WHERE branch IS NOT NULL AND branch != ''");
?>
<!DOCTYPE html>
<html>
<head>
    <title>All Students - Library System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h2 class="mb-3">🎓 All Students</h2>
    <a href="index.php" class="btn btn-secondary mb-3">← Back to Dashboard</a>

    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-4">
            <input type="text" name="search" class="form-control" placeholder="Search name, ERP ID, roll no." value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="col-md-3">
            <select name="course" class="form-select">
                <option value="">All Courses</option>
                <?php while ($c = $courses->fetch_assoc()) { 
                    $sel = ($c['course'] == $course_filter) ? 'selected' : '';
                    echo "<option value='{$c['course']}' $sel>{$c['course']}</option>";
                } ?>
            </select>
        </div>
        <div class="col-md-3">
            <select name="branch" class="form-select">
                <option value="">All Branches</option>
                <?php while ($b = $branches->fetch_assoc()) { 
                    $sel = ($b['branch'] == $branch_filter) ? 'selected' : '';
                    echo "<option value='{$b['branch']}' $sel>{$b['branch']}</option>";
                } ?>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">Filter</button>
        </div>
    </form>

    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>Name</th><th>ERP ID</th><th>Roll No.</th><th>Course</th><th>Branch</th><th>Year</th><th>Email</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()) { ?>
            <tr>
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td><?php echo htmlspecialchars($row['erp_id']); ?></td>
                <td><?php echo htmlspecialchars($row['roll_number']); ?></td>
                <td><?php echo htmlspecialchars($row['course']); ?></td>
                <td><?php echo htmlspecialchars($row['branch']); ?></td>
                <td><?php echo htmlspecialchars($row['year']); ?></td>
                <td><?php echo htmlspecialchars($row['email']); ?></td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</div>
</body>
</html>
<?php $conn->close(); ?>