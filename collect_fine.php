<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}
include 'db_connect.php';
include_once 'library_config.php';

$record_id = $_GET['record_id'] ?? null;
if (!$record_id) {
    header("Location: overdue_list.php");
    exit();
}

$stmt = $conn->prepare("SELECT br.*, u.name, u.erp_id, b.title 
        FROM borrow_records br
        JOIN users u ON br.user_id = u.user_id
        JOIN books b ON br.book_id = b.book_id
        WHERE br.record_id = ?");
$stmt->bind_param("i", $record_id);
$stmt->execute();
$record = $stmt->get_result()->fetch_assoc();

if (!$record) {
    header("Location: overdue_list.php");
    exit();
}

$today = date("Y-m-d");
$end_date = lib_fine_end_date($record['status'], $record['return_date'], $record['due_date'], $today);
$days_late = lib_fine_days($record['due_date'], $end_date);
$total_fine = $days_late * lib_fine_per_day();

$stmt2 = $conn->prepare("SELECT COALESCE(SUM(amount),0) as s FROM fine_payments WHERE record_id = ?");
$stmt2->bind_param("i", $record_id);
$stmt2->execute();
$paid_so_far = $stmt2->get_result()->fetch_assoc()['s'];
$balance = $total_fine - $paid_so_far;

$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $amount = $_POST['amount'];
    $utr = $_POST['utr'];
    $admin_id = $_SESSION['user_id'];

    if ($amount <= 0 || $amount > $balance) {
        $error = "Invalid amount. Balance due is ₹$balance.";
    } else {
        $stmt3 = $conn->prepare("INSERT INTO fine_payments (record_id, amount, utr, received_by) VALUES (?, ?, ?, ?)");
        $stmt3->bind_param("idsi", $record_id, $amount, $utr, $admin_id);
        $stmt3->execute();
        $payment_id = $stmt3->insert_id;
        header("Location: fine_receipt.php?id=" . $payment_id);
        exit();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Collect Fine - Library System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5" style="max-width: 500px;">
    <h2 class="mb-4">💰 Collect Library Fine</h2>
    <a href="overdue_list.php" class="btn btn-secondary mb-3">← Back</a>
    <div class="card p-3 mb-3">
        <p><b>Student:</b> <?php echo htmlspecialchars($record['name']); ?> (<?php echo htmlspecialchars($record['erp_id']); ?>)</p>
        <p><b>Book:</b> <?php echo htmlspecialchars($record['title']); ?></p>
        <p><b>Due Date:</b> <?php echo $record['due_date']; ?></p>
        <p><b>Days Late:</b> <?php echo $days_late; ?> | <b>Total Fine:</b> ₹<?php echo $total_fine; ?></p>
        <p><b>Already Paid:</b> ₹<?php echo $paid_so_far; ?> | <b>Balance Due:</b> ₹<?php echo $balance; ?></p>
    </div>
    <?php if ($error) echo "<div class='alert alert-danger'>$error</div>"; ?>
    <?php if ($balance <= 0) { ?>
        <div class="alert alert-success">This record has no pending fine.</div>
    <?php } else { ?>
    <form method="POST">
        <label class="form-label">Amount Received (₹)</label>
        <input type="number" step="0.01" name="amount" class="form-control mb-2" value="<?php echo $balance; ?>" max="<?php echo $balance; ?>" required>
        <label class="form-label">UPI/UTR Number</label>
        <input type="text" name="utr" class="form-control mb-3" placeholder="e.g. 758964586325">
        <button type="submit" class="btn btn-success w-100">Collect & Generate Receipt</button>
    </form>
    <?php } ?>
</div>
</body>
</html>