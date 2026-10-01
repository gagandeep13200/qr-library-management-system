<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit();
}
include 'db_connect.php';
include_once 'library_config.php';

$user_id = $_SESSION['user_id'];

// Return ab sirf admin side se hota hai (manage_records.php).
// Student side se return disable kar diya hai — isliye ye POST handler bhi band hai.
/*
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['return_id'])) {
    $record_id = intval($_POST['return_id']);
    $return_date = date("Y-m-d");

    $stmt = $conn->prepare("SELECT br.due_date, br.copy_id, br.fine_paid,
            (SELECT COALESCE(SUM(fp.amount), 0) FROM fine_payments fp WHERE fp.record_id = br.record_id) AS paid_sum,
            (SELECT COUNT(*) FROM fine_payments fp2 WHERE fp2.record_id = br.record_id) AS pay_count
        FROM borrow_records br
        WHERE br.record_id=? AND br.user_id=? AND br.status='issued'");
    $stmt->bind_param("ii", $record_id, $user_id);
    $stmt->execute();
    $rec = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $msg = "Could not return this book";
    if ($rec) {
        $s = lib_fine_status($rec['due_date'], $return_date, $rec['fine_paid'], $rec['paid_sum'], $rec['pay_count']);
        $outstanding = $s['outstanding'];

        $conn->begin_transaction();

        $stmt = $conn->prepare("UPDATE borrow_records SET return_date=?, status='returned' WHERE record_id=? AND user_id=? AND status='issued'");
        $stmt->bind_param("sii", $return_date, $record_id, $user_id);
        $stmt->execute();
        $updated = $stmt->affected_rows;
        $stmt->close();

        // Copy ko wapas 'available' karo (purane records mein copy_id NULL ho sakta hai)
        if ($updated > 0 && !empty($rec['copy_id'])) {
            $stmt = $conn->prepare("UPDATE book_copies SET status='available' WHERE copy_id=?");
            $stmt->bind_param("s", $rec['copy_id']);
            $stmt->execute();
            $stmt->close();
        }

        if ($updated > 0) {
            $conn->commit();
            $msg = "Book returned successfully";
            if ($outstanding > 0) {
                $msg .= ". Fine due: ₹" . $outstanding . " (library desk par UPI se pay karein)";
            }
        } else {
            $conn->rollback();
        }
    }

    header("Location: my_books.php?msg=" . urlencode($msg));
    exit();
}
*/

$stmt = $conn->prepare("SELECT br.record_id, b.title, br.borrow_date, br.due_date, br.return_date, br.status, br.fine_paid,
                               (SELECT COALESCE(SUM(fp.amount), 0) FROM fine_payments fp WHERE fp.record_id = br.record_id) AS paid_sum,
                               (SELECT COUNT(*) FROM fine_payments fp2 WHERE fp2.record_id = br.record_id) AS pay_count
        FROM borrow_records br
        JOIN books b ON br.book_id = b.book_id
        WHERE br.user_id = ?
        ORDER BY br.borrow_date DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$total = $result->num_rows;
$today = date("Y-m-d");
$flash = isset($_GET['msg']) ? $_GET['msg'] : '';

// Aaj ki notifications banao (agar pehle se nahi bani) aur unread dikhane ke liye lo
include_once 'notifications_lib.php';
lib_generate_notifications($conn, (int)$user_id);
$unread       = lib_unread_notifications($conn, (int)$user_id, 3);
$unread_total = lib_unread_count($conn, (int)$user_id);
$any_unpaid = false;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Books - Library System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2 class="mb-4">📖 My Borrowed Books</h2>
    <a href="index.php" class="btn btn-secondary mb-3">← Back to Home</a>
    <a href="notifications.php" class="btn btn-outline-primary mb-3 ms-2">🔔 Notifications<?php echo $unread_total > 0 ? ' (' . $unread_total . ')' : ''; ?></a>

    <?php foreach ($unread as $n) { ?>
        <div class="alert alert-<?php echo $n['type'] === 'reminder' ? 'warning' : 'danger'; ?> py-2">
            <?php echo htmlspecialchars($n['message']); ?>
            <div class="small text-muted"><?php echo htmlspecialchars($n['notify_date']); ?></div>
        </div>
    <?php } ?>

    <?php if ($flash != '') { ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($flash); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php } ?>

    <div class="mb-3">
        <input type="text" id="searchBox" class="form-control" placeholder="🔍 Search by book title...">
    </div>

    <div class="table-responsive">
        <table class="table table-bordered" id="myBooksTable">
            <thead class="table-dark">
                <tr>
                    <th>Book Title</th>
                    <th>Borrow Date</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Fine</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($total == 0) { ?>
                <tr><td colspan="6" class="text-center text-muted py-4">You haven't borrowed any books yet.</td></tr>
                <?php } else { while ($row = $result->fetch_assoc()) {
                    $returned    = ($row['status'] == 'returned');
                    $end_date    = lib_fine_end_date($row['status'], $row['return_date'], $row['due_date'], $today);
                    $s           = lib_fine_status($row['due_date'], $end_date, $row['fine_paid'], $row['paid_sum'], $row['pay_count']);
                    $fine        = $s['fine'];
                    $outstanding = $s['outstanding'];
                    $paid_so_far = (int)$row['paid_sum'];
                    $past_due    = (!$returned && $row['due_date'] < $today);
                    $rid         = (int)$row['record_id'];
                    if ($returned && $outstanding > 0) { $any_unpaid = true; }
                ?>
                <tr class="<?php echo $past_due ? 'table-danger' : ''; ?>">
                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                    <td><?php echo htmlspecialchars($row['borrow_date']); ?></td>
                    <td><?php echo htmlspecialchars($row['due_date']); ?></td>
                    <td>
                        <?php if ($past_due) { ?>
                            <span class="badge bg-danger">Overdue</span>
                        <?php } elseif (!$returned) { ?>
                            <span class="badge bg-warning text-dark">Issued</span>
                        <?php } else { ?>
                            <span class="badge bg-success">Returned</span>
                        <?php } ?>
                    </td>
                    <td>
                        <?php if ($fine <= 0) { ?>
                            —
                        <?php } else { ?>
                            <span class="<?php echo ($returned && $outstanding > 0) ? 'text-danger' : ''; ?>">₹<?php echo number_format($fine, 2); ?><?php echo !$returned ? ' (running)' : ''; ?></span>
                            <?php if ($returned) { ?>
                                <?php if ($outstanding <= 0) { ?>
                                    <span class="badge bg-success">Paid</span>
                                <?php } elseif ($paid_so_far > 0) { ?>
                                    <span class="badge bg-warning text-dark">Balance ₹<?php echo $outstanding; ?></span>
                                <?php } else { ?>
                                    <span class="badge bg-danger">Unpaid</span>
                                <?php } ?>
                            <?php } ?>
                        <?php } ?>
                    </td>
                    <td>—</td>
                </tr>
                <?php } } ?>
            </tbody>
        </table>
    </div>

    <?php if ($any_unpaid) { ?>
        <div class="alert alert-warning">
            ℹ️ Fine library desk par UPI se bharna hota hai. Librarian payment confirm karega, uske baad yahan <b>Paid</b> dikhega. Fine book return hone ki date par ruk chuka hai.
        </div>
    <?php } ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('searchBox').addEventListener('input', function() {
    const query = this.value.toLowerCase();
    document.querySelectorAll('#myBooksTable tbody tr').forEach(row => {
        if (row.cells.length < 2) return;
        row.style.display = row.cells[0].textContent.toLowerCase().includes(query) ? '' : 'none';
    });
});
</script>
</body>
</html>
<?php $stmt->close(); $conn->close(); ?>