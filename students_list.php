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
$year_filter   = isset($_GET['year'])   ? trim($_GET['year'])   : '';

$sql = "SELECT * FROM users WHERE role='student'";
$params = [];
$types  = "";

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
    $sql .= " AND `year` = ?";
    $params[] = (int)$year_filter;
    $types .= "i";
}
$sql .= " ORDER BY name";

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

$courses  = $conn->query("SELECT DISTINCT course FROM users WHERE role='student' AND course IS NOT NULL AND course != '' ORDER BY course");
$branches = $conn->query("SELECT DISTINCT branch FROM users WHERE role='student' AND branch IS NOT NULL AND branch != '' ORDER BY branch");
$years    = $conn->query("SELECT DISTINCT `year` FROM users WHERE role='student' AND `year` IS NOT NULL AND `year` != '' ORDER BY `year`");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>All Students - Library System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h2 class="mb-3">🎓 All Students <small class="text-muted fs-6">(<?php echo $total; ?> found)</small></h2>
    <a href="index.php" class="btn btn-secondary mb-3">← Back to Dashboard</a>

    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-3">
            <input type="text" name="search" class="form-control" placeholder="Search name, ERP ID, roll no." value="<?php echo htmlspecialchars($search); ?>">
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
        <div class="col-md-2">
            <select name="branch" class="form-select">
                <option value="">All Branches</option>
                <?php while ($b = $branches->fetch_assoc()) {
                    $val = htmlspecialchars($b['branch']);
                    $sel = ($b['branch'] == $branch_filter) ? 'selected' : '';
                    echo "<option value=\"$val\" $sel>$val</option>";
                } ?>
            </select>
        </div>
        <div class="col-md-2">
            <select name="year" class="form-select">
                <option value="">All Years</option>
                <?php while ($y = $years->fetch_assoc()) {
                    $val = htmlspecialchars($y['year']);
                    $sel = ((string)$y['year'] === $year_filter) ? 'selected' : '';
                    echo "<option value=\"$val\" $sel>Year $val</option>";
                } ?>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary w-100">Filter</button>
            <a href="students_list.php" class="btn btn-outline-secondary">Reset</a>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Name</th><th>Father's Name</th><th>Mobile</th><th>ERP ID</th><th>Roll No.</th><th>Course</th><th>Branch</th><th>Year</th><th>Batch</th><th>Email</th><th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($total == 0) { ?>
                <tr><td colspan="11" class="text-center text-muted py-4">No students found.</td></tr>
                <?php } else { while ($row = $result->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['father_name'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['mobile_number'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['erp_id']); ?></td>
                    <td><?php echo htmlspecialchars($row['roll_number']); ?></td>
                    <td><?php echo htmlspecialchars($row['course']); ?></td>
                    <td><?php echo htmlspecialchars($row['branch']); ?></td>
                    <td><?php echo htmlspecialchars($row['year']); ?></td>
                    <td><?php echo htmlspecialchars($row['batch'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td><a href="edit_student.php?id=<?php echo (int)$row['user_id']; ?>" class="btn btn-sm btn-primary">Edit</a></td>
                </tr>
                <?php } } ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
<?php $stmt->close(); $conn->close(); ?>