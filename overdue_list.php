<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}
include 'db_connect.php';
include_once 'library_config.php';

$FINE_PER_DAY = lib_fine_per_day();
$today        = date("Y-m-d");
$admin_id     = (int)$_SESSION['user_id'];
$show_all     = (isset($_GET['show']) && $_GET['show'] === 'all');

function back_with($text, $type) {
    header("Location: overdue_list.php?msg=" . urlencode($text) . "&t=" . urlencode($type));
    exit();
}

// ---- Confirm payment (POST) ----
// Sirf RETURNED book par, aur sirf tab jab admin ne UPI app mein paisa dekhkar UTR daala ho.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_payment'])) {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $record_id = (int)($_POST['record_id'] ?? 0);
    $utr       = strtoupper(trim($_POST['utr'] ?? ''));
    $amount    = (int)($_POST['amount'] ?? 0);

    if ($record_id <= 0) {
        back_with("Invalid record.", "danger");
    }
    if (!preg_match('/^[A-Z0-9]{8,25}$/', $utr)) {
        back_with("UTR / transaction ID sahi nahi hai (8 se 25 letters/numbers chahiye).", "danger");
    }

    try {
        $conn->begin_transaction();

        // Record lock karo aur fine server par dobara calculate karo (browser ke amount par bharosa nahi)
        $stmt = $conn->prepare("SELECT br.due_date, br.return_date, br.status, br.fine_paid,
                (SELECT COALESCE(SUM(fp.amount), 0) FROM fine_payments fp WHERE fp.record_id = br.record_id) AS paid_sum,
                (SELECT COUNT(*) FROM fine_payments fp2 WHERE fp2.record_id = br.record_id) AS pay_count
            FROM borrow_records br
            WHERE br.record_id = ? FOR UPDATE");
        $stmt->bind_param("i", $record_id);
        $stmt->execute();
        $rec = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$rec) {
            $conn->rollback();
            back_with("Record nahi mila.", "danger");
        }
        if ($rec['status'] !== 'returned') {
            $conn->rollback();
            back_with("Pehle book return karo, uske baad hi fine payment lo.", "warning");
        }
        if (empty($rec['return_date'])) {
            $conn->rollback();
            back_with("Is record ki return date save nahi hai, isliye fine nahi nikal sakta.", "danger");
        }

        $s           = lib_fine_status($rec['due_date'], $rec['return_date'], $rec['fine_paid'], $rec['paid_sum'], $rec['pay_count']);
        $outstanding = $s['outstanding'];

        if ($outstanding <= 0) {
            $conn->rollback();
            back_with("Is record par koi fine baaki nahi hai.", "warning");
        }
        if ($amount < 1 || $amount > $outstanding) {
            $conn->rollback();
            back_with("Amount 1 se ₹" . $outstanding . " ke beech hona chahiye.", "danger");
        }

        $paid_at = date('Y-m-d H:i:s');

        // UNIQUE(utr): same UTR dobara aaya to yahin error aayega
        $stmt = $conn->prepare("INSERT INTO fine_payments (record_id, amount, utr, received_by, paid_at) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisis", $record_id, $amount, $utr, $admin_id, $paid_at);
        $stmt->execute();
        $stmt->close();

        if ($amount >= $outstanding) {
            $stmt = $conn->prepare("UPDATE borrow_records SET fine_paid = 'paid' WHERE record_id = ?");
            $stmt->bind_param("i", $record_id);
            $stmt->execute();
            $stmt->close();
        }

        $conn->commit();
        // Receipt seedha khol do (UTR unique hai, isliye usi se payment id mil jaati hai)
        $stmt = $conn->prepare("SELECT id FROM fine_payments WHERE utr = ?");
        $stmt->bind_param("s", $utr);
        $stmt->execute();
        $new_pay = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($new_pay) {
            header("Location: fine_receipt.php?id=" . (int)$new_pay['id']);
            exit();
        }
        back_with("₹" . $amount . " payment record ho gaya (UTR " . $utr . ").", "success");
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        if ((int)$e->getCode() === 1062) {
            back_with("Yeh UTR pehle hi use ho chuka hai. Har payment ka UTR alag hota hai.", "danger");
        }
        error_log('[overdue_list] ' . $e->getMessage());
        back_with("Payment save nahi hua. Check karo ki fine_payments table bani hui hai.", "danger");
    }
}

// ---- Page data ----
$flash = isset($_GET['msg']) ? $_GET['msg'] : '';
$flash_type = (isset($_GET['t']) && in_array($_GET['t'], ['success', 'danger', 'warning'], true)) ? $_GET['t'] : 'info';

// Data-check: returned records jinki return_date save nahi hui
$missing_return_date = (int)$conn->query("SELECT COUNT(*) AS c FROM borrow_records WHERE status = 'returned' AND return_date IS NULL")->fetch_assoc()['c'];

// A) Abhi overdue (book wapas nahi aayi) -> running fine, payment nahi
$stmtA = $conn->prepare("SELECT br.record_id, u.name, u.erp_id, u.course, u.branch, b.title, br.borrow_date, br.due_date
        FROM borrow_records br
        JOIN users u ON br.user_id = u.user_id
        JOIN books b ON br.book_id = b.book_id
        WHERE br.status = 'issued' AND br.due_date < ?
        ORDER BY br.due_date");
$stmtA->bind_param("s", $today);
$stmtA->execute();
$resultA = $stmtA->get_result();

// B) Late return ho chuki books -> yahin fine collect hota hai
$stmtB = $conn->prepare("SELECT br.record_id, u.name, u.erp_id, u.course, u.branch, b.title, br.due_date, br.return_date, br.fine_paid,
               (SELECT GROUP_CONCAT(fp4.id ORDER BY fp4.id) FROM fine_payments fp4 WHERE fp4.record_id = br.record_id) AS pay_ids,
               (SELECT COALESCE(SUM(fp.amount), 0) FROM fine_payments fp WHERE fp.record_id = br.record_id) AS paid_sum,
               (SELECT COUNT(*) FROM fine_payments fp2 WHERE fp2.record_id = br.record_id) AS pay_count
        FROM borrow_records br
        JOIN users u ON br.user_id = u.user_id
        JOIN books b ON br.book_id = b.book_id
        WHERE br.status = 'returned' AND br.return_date IS NOT NULL AND br.return_date > br.due_date
        ORDER BY br.return_date DESC");
$stmtB->execute();
$resultB = $stmtB->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Overdue Books - Library System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h2 class="mb-3">⚠️ Overdue Books &amp; Fines</h2>
    <a href="index.php" class="btn btn-secondary mb-3">← Back to Dashboard</a>
    <p class="text-muted">Fine rate: ₹<?php echo $FINE_PER_DAY; ?> per day. Fine book return hone ki date par ruk jaata hai. Payment sirf return ke baad lo, aur tabhi confirm karo jab apne UPI app mein paisa aaya dikh jaaye.</p>

    <?php if ($flash != '') { ?>
        <div class="alert alert-<?php echo $flash_type; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($flash); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php } ?>

    <?php if ($missing_return_date > 0) { ?>
        <div class="alert alert-danger">
            ⚠️ <?php echo $missing_return_date; ?> returned record(s) mein <b>return_date save nahi hai</b>, isliye unka fine sahi nahi nikal raha.
            Jis file se book return hoti hai (jaise manage_records.php) wahan return_date set hona chahiye.
        </div>
    <?php } ?>

    <!-- A) Abhi overdue -->
    <h4 class="mt-4">📕 Abhi overdue (book wapas nahi aayi)</h4>
    <div class="table-responsive">
    <table class="table table-bordered table-striped align-middle">
        <thead class="table-dark">
            <tr><th>Student</th><th>ERP ID</th><th>Course/Branch</th><th>Book</th><th>Borrow Date</th><th>Due Date</th><th>Days Overdue</th><th>Running Fine</th><th>Payment</th></tr>
        </thead>
        <tbody>
            <?php
            $rowsA = 0;
            $total_running = 0;
            while ($row = $resultA->fetch_assoc()) {
                $rowsA++;
                $days = lib_fine_days($row['due_date'], $today);
                $fine = $days * $FINE_PER_DAY;
                $total_running += $fine;
            ?>
            <tr>
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td><?php echo htmlspecialchars($row['erp_id']); ?></td>
                <td><?php echo htmlspecialchars($row['course'] . " " . $row['branch']); ?></td>
                <td><?php echo htmlspecialchars($row['title']); ?></td>
                <td><?php echo htmlspecialchars($row['borrow_date']); ?></td>
                <td><?php echo htmlspecialchars($row['due_date']); ?></td>
                <td><span class="badge bg-danger"><?php echo $days; ?> days</span></td>
                <td><strong>₹<?php echo $fine; ?></strong> <span class="small text-muted">(running)</span></td>
                <td><span class="badge bg-secondary">Pehle book return karo</span></td>
            </tr>
            <?php } ?>
        </tbody>
        <?php if ($rowsA > 0) { ?>
        <tfoot>
            <tr>
                <th colspan="7" class="text-end">Total running fine (abhi tak)</th>
                <th colspan="2">₹<?php echo $total_running; ?></th>
            </tr>
        </tfoot>
        <?php } ?>
    </table>
    </div>
    <?php if ($rowsA == 0) echo "<p class='text-muted'>Abhi koi overdue book nahi hai. 🎉</p>"; ?>

    <!-- B) Return ho chuki, fine collect karna hai -->
    <div class="d-flex justify-content-between align-items-center mt-5">
        <h4 class="mb-0">💰 Return ho chuki books, fine <?php echo $show_all ? '(sab)' : 'baaki'; ?></h4>
        <?php if ($show_all) { ?>
            <a href="overdue_list.php" class="btn btn-sm btn-outline-secondary">Sirf baaki dikhao</a>
        <?php } else { ?>
            <a href="overdue_list.php?show=all" class="btn btn-sm btn-outline-secondary">Paid bhi dikhao</a>
        <?php } ?>
    </div>
    <div class="table-responsive mt-2">
    <table class="table table-bordered table-striped align-middle">
        <thead class="table-dark">
            <tr><th>Student</th><th>ERP ID</th><th>Course/Branch</th><th>Book</th><th>Due Date</th><th>Returned On</th><th>Days Late</th><th>Fine</th><th>Payment</th></tr>
        </thead>
        <tbody>
            <?php
            $rowsB = 0;
            $total_pending = 0;
            while ($row = $resultB->fetch_assoc()) {
                $s           = lib_fine_status($row['due_date'], $row['return_date'], $row['fine_paid'], $row['paid_sum'], $row['pay_count']);
                $outstanding = $s['outstanding'];
                if ($outstanding <= 0 && !$show_all) {
                    continue;
                }
                $rowsB++;
                $paid_so_far = (int)$row['paid_sum'];
                $total_pending += $outstanding;
                $rid = (int)$row['record_id'];

                if ($outstanding > 0) {
                    $upi_note = "Fine RecordID " . $rid;
                    // rawurlencode (urlencode nahi): space %20 banta hai, "+" nahi
                    $upi_link = "upi://pay?pa=" . UPI_ID
                              . "&pn=" . rawurlencode(UPI_PAYEE_NAME)
                              . "&am=" . rawurlencode((string)$outstanding)
                              . "&cu=INR&tn=" . rawurlencode($upi_note);
                    $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=" . urlencode($upi_link);
                }
            ?>
            <tr>
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td><?php echo htmlspecialchars($row['erp_id']); ?></td>
                <td><?php echo htmlspecialchars($row['course'] . " " . $row['branch']); ?></td>
                <td><?php echo htmlspecialchars($row['title']); ?></td>
                <td><?php echo htmlspecialchars($row['due_date']); ?></td>
                <td><?php echo htmlspecialchars($row['return_date']); ?></td>
                <td><span class="badge bg-secondary"><?php echo $s['days']; ?> days</span></td>
                <td><strong>₹<?php echo $s['fine']; ?></strong></td>
                <td>
                    <?php if ($outstanding <= 0) { ?>
                        <span class="badge bg-success">Paid ✓</span>
                        <?php foreach (array_filter(explode(',', (string)$row['pay_ids'])) as $rcpt_id) { ?>
                            <a href="fine_receipt.php?id=<?php echo (int)$rcpt_id; ?>" target="_blank" class="btn btn-sm btn-outline-secondary ms-1">🧾 Receipt #<?php echo (int)$rcpt_id; ?></a>
                        <?php } ?>
                    <?php } else { ?>
                        <?php if ($paid_so_far > 0) { ?>
                            <div class="small text-muted mb-1">Paid ₹<?php echo $paid_so_far; ?> · Balance ₹<?php echo $outstanding; ?>
                                <?php foreach (array_filter(explode(',', (string)$row['pay_ids'])) as $rcpt_id) { ?>
                                    <a href="fine_receipt.php?id=<?php echo (int)$rcpt_id; ?>" target="_blank">🧾 #<?php echo (int)$rcpt_id; ?></a>
                                <?php } ?>
                            </div>
                        <?php } ?>
                        <button class="btn btn-sm btn-outline-primary" type="button"
                                onclick="document.getElementById('pay-<?php echo $rid; ?>').classList.toggle('d-none')">
                            Collect ₹<?php echo $outstanding; ?>
                        </button>
                        <div id="pay-<?php echo $rid; ?>" class="d-none mt-2 p-2 border rounded" style="width:230px;">
                            <div class="text-center">
                                <img src="<?php echo $qr_url; ?>" alt="UPI QR" class="mb-1" width="160" height="160">
                                <div class="small text-muted mb-2">Scan to pay ₹<?php echo $outstanding; ?></div>
                            </div>
                            <form method="POST"
                                  onsubmit="return confirm('UPI app mein ₹' + this.amount.value + ' aaya hua dekh liya? (UTR: ' + this.utr.value + ')');">
                                <input type="hidden" name="confirm_payment" value="1">
                                <input type="hidden" name="record_id" value="<?php echo $rid; ?>">

                                <label class="form-label small mb-0">UTR / Transaction ID</label>
                                <input type="text" name="utr" class="form-control form-control-sm mb-1" required
                                       minlength="8" maxlength="25" pattern="[A-Za-z0-9]{8,25}"
                                       placeholder="UPI app se copy karo" autocomplete="off">

                                <label class="form-label small mb-0">Amount received (₹)</label>
                                <input type="number" name="amount" class="form-control form-control-sm mb-2" required
                                       min="1" max="<?php echo $outstanding; ?>" value="<?php echo $outstanding; ?>">

                                <button type="submit" class="btn btn-sm btn-success w-100">Confirm payment received</button>
                            </form>
                        </div>
                    <?php } ?>
                </td>
            </tr>
            <?php } ?>
        </tbody>
        <?php if ($rowsB > 0) { ?>
        <tfoot>
            <tr>
                <th colspan="7" class="text-end">Total fine baaki</th>
                <th colspan="2">₹<?php echo $total_pending; ?></th>
            </tr>
        </tfoot>
        <?php } ?>
    </table>
    </div>
    <?php if ($rowsB == 0) echo "<p class='text-muted'>Kisi return ho chuki book ka fine baaki nahi hai. ✅</p>"; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $stmtA->close(); $stmtB->close(); $conn->close(); ?>