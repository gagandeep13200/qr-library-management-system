<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}
include 'db_connect.php';
include 'csrf.php';
include_once 'pagination_lib.php';

// ---- Return handling (POST only) ----
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['return_id'])) {
    csrf_verify();
    $record_id   = (int)$_POST['return_id'];
    $return_date = date("Y-m-d");
    try {
        $conn->begin_transaction();
        $sel = $conn->prepare("SELECT copy_id, due_date FROM borrow_records WHERE record_id=? AND status='issued' FOR UPDATE");
        $sel->bind_param("i", $record_id);
        $sel->execute();
        $rec = $sel->get_result()->fetch_assoc();
        if (!$rec) {
            throw new Exception("Yeh record pehle se returned hai ya mila nahi.");
        }

        $upd = $conn->prepare("UPDATE borrow_records SET return_date=?, status='returned' WHERE record_id=?");
        $upd->bind_param("si", $return_date, $record_id);
        $upd->execute();

        if (!empty($rec['copy_id'])) {
            $cp = $conn->prepare("UPDATE book_copies SET status='available' WHERE copy_id=?");
            $cp->bind_param("s", $rec['copy_id']);
            $cp->execute();
        }

        $conn->commit();
        if ($return_date > $rec['due_date']) {
            header("Location: overdue_list.php?pay=" . $record_id
                 . "&msg=" . urlencode("Book return ho gayi. Late hai, fine collect karo.")
                 . "&t=warning");
            exit();
        }
        $_SESSION['flash'] = ["success", "Book return ho gayi."];
    } catch (Throwable $e) {
        $conn->rollback();
        $_SESSION['flash'] = ["danger", "Error: " . $e->getMessage()];
    }
    header("Location: manage_records.php");
    exit();
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$show_all = (isset($_GET['show']) && $_GET['show'] === 'all');
$where = $show_all ? "" : "WHERE br.status = 'issued'";

$total_records = (int)$conn->query("SELECT COUNT(*) AS c FROM borrow_records br $where")->fetch_assoc()['c'];
$pg = lib_paginate($total_records, ['show' => $show_all ? 'all' : '']);

$sql = "SELECT br.record_id, br.copy_id, u.name, u.erp_id, b.title, br.borrow_date, br.due_date, br.status
        FROM borrow_records br
        JOIN users u ON br.user_id = u.user_id
        JOIN books b ON br.book_id = b.book_id
        $where
        ORDER BY br.borrow_date DESC
        LIMIT {$pg['per_page']} OFFSET {$pg['offset']}";
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
<div class="container mt-5">
    <h2 class="mb-4">📋 Manage All Borrowing Records</h2>
    <a href="index.php" class="btn btn-secondary mb-3">← Back to Home</a>

    <div class="mb-2">
        <?php if ($show_all) { ?>
            <a href="manage_records.php" class="btn btn-sm btn-outline-secondary">Sirf Issued dikhao</a>
        <?php } else { ?>
            <a href="manage_records.php?show=all" class="btn btn-sm btn-outline-secondary">Poori history dikhao</a>
        <?php } ?>
    </div>

    <?php if ($flash) { ?>
        <div class="alert alert-<?php echo $flash[0]; ?>"><?php echo htmlspecialchars($flash[1]); ?></div>
    <?php } ?>

    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>Student</th><th>ERP ID</th><th>Book</th><th>Copy ID</th><th>Borrow Date</th><th>Due Date</th><th>Status</th><th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($total_records == 0) { ?>
            <tr><td colspan="8" class="text-center text-muted py-4">Koi record nahi mila.</td></tr>
            <?php } else { while ($row = $result->fetch_assoc()) {
                $is_issued  = ($row['status'] == 'issued');
                $is_overdue = ($is_issued && $row['due_date'] < $today);
            ?>
            <tr>
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td><?php echo htmlspecialchars($row['erp_id']); ?></td>
                <td><?php echo htmlspecialchars($row['title']); ?></td>
                <td><?php echo $row['copy_id'] ? htmlspecialchars($row['copy_id']) : '—'; ?></td>
                <td><?php echo htmlspecialchars($row['borrow_date']); ?></td>
                <td><?php echo htmlspecialchars($row['due_date']); ?></td>
                <td>
                    <?php if ($is_overdue) { ?>
                        <span class="badge bg-danger">Overdue</span>
                    <?php } elseif ($is_issued) { ?>
                        <span class="badge bg-warning text-dark">Issued</span>
                    <?php } else { ?>
                        <span class="badge bg-success">Returned</span>
                    <?php } ?>
                </td>
                <td>
                    <?php if ($is_issued) { ?>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Is book ko returned mark karna hai?');">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="return_id" value="<?php echo (int)$row['record_id']; ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Mark Returned</button>
                        </form>
                    <?php } else { ?>
                        —
                    <?php } ?>
                </td>
            </tr>
            <?php } } ?>
        </tbody>
    </table>

    <?php echo lib_pagination_controls($pg); ?>
</div>
</body>
</html>
<?php $conn->close(); ?>