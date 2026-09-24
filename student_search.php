<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}
include 'db_connect.php';
include 'erp_sync.php';

$fine_per_day = get_fine_per_day($conn);
$today = date("Y-m-d");

// ---------- Return button (isi page se) ----------
if (isset($_GET['return_id'])) {
    $issue_id = intval($_GET['return_id']);
    $return_date = date("Y-m-d");

    $stmt = $conn->prepare("SELECT copy_id, due_date FROM book_issues WHERE issue_id=? AND status='issued'");
    $stmt->bind_param("i", $issue_id);
    $stmt->execute();
    $rec = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($rec) {
        $overdue_days = 0;
        $due = new DateTime($rec['due_date']);
        $today_dt = new DateTime($return_date);
        if ($today_dt > $due) {
            $overdue_days = $today_dt->diff($due)->days;
        }
        $fine = $overdue_days * $fine_per_day;

        $stmt = $conn->prepare("UPDATE book_issues SET return_date=?, status='returned', fine_amount=? WHERE issue_id=?");
        $stmt->bind_param("sdi", $return_date, $fine, $issue_id);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("UPDATE book_copies SET status='available' WHERE copy_id=?");
        $stmt->bind_param("s", $rec['copy_id']);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("SELECT u.erp_id FROM book_issues bi JOIN users u ON bi.user_id = u.user_id WHERE bi.issue_id=?");
        $stmt->bind_param("i", $issue_id);
        $stmt->execute();
        $erp_row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($erp_row && $fine > 0) {
            sync_fine_to_erp($conn, $erp_row['erp_id'], $fine, $issue_id);
        }
    }

    $qs = $_GET;
    unset($qs['return_id']);
    $qs['msg'] = "Book marked as returned";
    header("Location: student_search.php?" . http_build_query($qs));
    exit();
}

// ---------- Search ----------
$query = trim($_GET['query'] ?? '');
$student = null;
$issued_books = [];
$active_count = 0;
$searched = ($query !== '');

if ($searched) {
    $stmt = $conn->prepare("SELECT user_id, name, erp_id, roll_number FROM users WHERE role='student' AND (erp_id = ? OR roll_number = ?)");
    $stmt->bind_param("ss", $query, $query);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($student) {
        $stmt = $conn->prepare("SELECT bi.issue_id, bi.copy_id, b.title, b.isbn, bi.issue_date, bi.due_date, bi.status
                                 FROM book_issues bi
                                 JOIN book_copies bc ON bi.copy_id = bc.copy_id
                                 JOIN books b ON bc.book_id = b.book_id
                                 WHERE bi.user_id = ? AND bi.status = 'issued'
                                 ORDER BY bi.issue_date DESC");
        $stmt->bind_param("i", $student['user_id']);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $issued_books[] = $row;
        }
        $stmt->close();
        $active_count = count($issued_books);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Student Search - Library System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body>
<?php include 'toast.php'; ?>

<div class="container mt-5">
    <h2 class="mb-4">🔍 Student Search</h2>
    <a href="index.php" class="btn btn-secondary mb-4">← Back to Home</a>

    <form method="GET" class="row g-2 mb-4">
        <div class="col-md-8">
            <input type="text" name="query" class="form-control" placeholder="Roll Number ya ERP ID daalo..." value="<?php echo htmlspecialchars($query); ?>" required>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">Search</button>
        </div>
    </form>

    <?php if ($searched && !$student) { ?>
        <div class="alert alert-warning">Student not found.</div>
    <?php } ?>

    <?php if ($student) { ?>
        <div class="card p-4 mb-4">
            <h4 class="mb-3">👤 <?php echo htmlspecialchars($student['name']); ?></h4>
            <div class="row">
                <div class="col-md-4"><strong>Roll Number:</strong> <?php echo htmlspecialchars($student['roll_number'] ?? '—'); ?></div>
                <div class="col-md-4"><strong>ERP ID:</strong> <?php echo htmlspecialchars($student['erp_id']); ?></div>
                <div class="col-md-4"><strong>Currently Issued Books:</strong> <span class="badge <?php echo $active_count >= 3 ? 'bg-danger' : 'bg-primary'; ?>"><?php echo $active_count; ?> / 3</span></div>
            </div>
        </div>

        <h5>📚 Currently Issued Books</h5>
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Book Title</th>
                    <th>ISBN</th>
                    <th>Copy ID</th>
                    <th>Issue Date</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($active_count == 0) { ?>
                    <tr><td colspan="7" class="text-center text-muted py-3">Is student ke paas abhi koi book issued nahi hai.</td></tr>
                <?php } ?>
                <?php foreach ($issued_books as $row) {
                    $is_overdue = ($row['due_date'] < $today);
                ?>
                <tr class="<?php echo $is_overdue ? 'table-danger' : ''; ?>">
                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                    <td><?php echo htmlspecialchars($row['isbn'] ?? '—'); ?></td>
                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($row['copy_id']); ?></span></td>
                    <td><?php echo $row['issue_date']; ?></td>
                    <td><?php echo $row['due_date']; ?></td>
                    <td>
                        <?php if ($is_overdue) { ?>
                            <span class="badge bg-danger">Overdue</span>
                        <?php } else { ?>
                            <span class="badge bg-warning text-dark">Issued</span>
                        <?php } ?>
                    </td>
                    <td>
                        <a href="student_search.php?query=<?php echo urlencode($query); ?>&return_id=<?php echo $row['issue_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Mark this book as returned?');">Mark Returned</a>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    <?php } ?>
</div>
</body>
</html>
<?php $conn->close(); ?>