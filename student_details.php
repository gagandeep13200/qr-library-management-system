<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}
include 'db_connect.php';
$today = date("Y-m-d");

$user_id = intval($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT * FROM users WHERE user_id=? AND role='student'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    die("Student not found.");
}

$stmt = $conn->prepare("SELECT bi.issue_id, bi.copy_id, b.title, b.isbn, bi.issue_date, bi.due_date, bi.return_date, bi.status, bi.fine_amount
                         FROM book_issues bi
                         JOIN book_copies bc ON bi.copy_id = bc.copy_id
                         JOIN books b ON bc.book_id = b.book_id
                         WHERE bi.user_id = ?
                         ORDER BY bi.issue_date DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$records = $stmt->get_result();

$active_count = 0;
$records_arr = [];
while ($row = $records->fetch_assoc()) {
    if ($row['status'] == 'issued') $active_count++;
    $records_arr[] = $row;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Student Details - Library System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2 class="mb-4">👤 Student Details</h2>
    <a href="student_list.php" class="btn btn-secondary mb-4">← Back to Student List</a>

    <div class="card p-4 mb-4">
        <h4 class="mb-3"><?php echo htmlspecialchars($student['name']); ?></h4>
        <div class="row g-2">
            <div class="col-md-4"><strong>Roll Number:</strong> <?php echo htmlspecialchars($student['roll_number'] ?? '—'); ?></div>
            <div class="col-md-4"><strong>ERP ID:</strong> <?php echo htmlspecialchars($student['erp_id']); ?></div>
            <div class="col-md-4"><strong>Email:</strong> <?php echo htmlspecialchars($student['email'] ?? '—'); ?></div>
            <div class="col-md-4"><strong>Course:</strong> <?php echo htmlspecialchars($student['course'] ?? '—'); ?></div>
            <div class="col-md-4"><strong>Branch:</strong> <?php echo htmlspecialchars($student['branch'] ?? '—'); ?></div>
            <div class="col-md-4"><strong>Year:</strong> <?php echo htmlspecialchars($student['year'] ?? '—'); ?></div>
            <div class="col-md-4"><strong>Semester:</strong> <?php echo htmlspecialchars($student['semester'] ?? '—'); ?></div>
            <div class="col-md-4"><strong>Contact:</strong> <?php echo htmlspecialchars($student['contact'] ?? '—'); ?></div>
            <div class="col-md-4"><strong>Currently Issued Books:</strong> <span class="badge <?php echo $active_count >= 3 ? 'bg-danger' : 'bg-primary'; ?>"><?php echo $active_count; ?> / 3</span></div>
        </div>
    </div>

    <h5>📚 Book Records</h5>
    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>Book Title</th>
                <th>ISBN</th>
                <th>Copy ID</th>
                <th>Issue Date</th>
                <th>Due Date</th>
                <th>Return Date</th>
                <th>Status</th>
                <th>Fine</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($records_arr) == 0) { ?>
                <tr><td colspan="8" class="text-center text-muted py-3">Koi book record nahi hai.</td></tr>
            <?php } ?>
            <?php foreach ($records_arr as $row) {
                $is_overdue = ($row['status'] == 'issued' && $row['due_date'] < $today);
            ?>
            <tr class="<?php echo $is_overdue ? 'table-danger' : ''; ?>">
                <td><?php echo htmlspecialchars($row['title']); ?></td>
                <td><?php echo htmlspecialchars($row['isbn'] ?? '—'); ?></td>
                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($row['copy_id']); ?></span></td>
                <td><?php echo $row['issue_date']; ?></td>
                <td><?php echo $row['due_date']; ?></td>
                <td><?php echo $row['return_date'] ?? '—'; ?></td>
                <td>
                    <?php if ($is_overdue) { ?>
                        <span class="badge bg-danger">Overdue</span>
                    <?php } elseif ($row['status'] == 'issued') { ?>
                        <span class="badge bg-warning text-dark">Issued</span>
                    <?php } else { ?>
                        <span class="badge bg-success">Returned</span>
                    <?php } ?>
                </td>
                <td><?php echo $row['fine_amount'] > 0 ? '₹' . number_format($row['fine_amount'], 2) : '—'; ?></td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</div>
</body>
</html>
<?php $conn->close(); ?>