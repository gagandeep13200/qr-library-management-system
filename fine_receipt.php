<?php
// fine_receipt.php?id=<fine_payments.id>
// Library fine ki receipt, HRIT University ki fee receipt jaise format mein.
// Admin kisi ki bhi dekh sakta hai, student sirf apni.

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include 'db_connect.php';
include_once 'library_config.php';

// ---- Yahan badal sakte ho ----
$INSTITUTE_NAME    = 'HRIT University';
$INSTITUTE_ADDRESS = '8th Km Delhi-Meerut Road, Morta, Ghaziabad';
$LOGO_FILE         = 'logo.png'; // isi folder mein rakho; file na ho to logo nahi dikhega

function rcpt_h($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

// Rupaye ko shabdon mein (Indian system: Crore / Lakh / Thousand)
function rcpt_words($n) {
    $n = (int)$n;
    if ($n <= 0) {
        return 'Zero';
    }
    $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven',
             'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
    $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
    $two = function ($x) use ($ones, $tens) {
        if ($x < 20) {
            return $ones[$x];
        }
        return trim($tens[intdiv($x, 10)] . ' ' . $ones[$x % 10]);
    };

    $out   = '';
    $crore = intdiv($n, 10000000); $n %= 10000000;
    $lakh  = intdiv($n, 100000);   $n %= 100000;
    $thou  = intdiv($n, 1000);     $n %= 1000;
    $hund  = intdiv($n, 100);
    $rest  = $n % 100;

    if ($crore) $out .= $two($crore) . ' Crore ';
    if ($lakh)  $out .= $two($lakh) . ' Lakh ';
    if ($thou)  $out .= $two($thou) . ' Thousand ';
    if ($hund)  $out .= $ones[$hund] . ' Hundred ';
    if ($rest)  $out .= $two($rest);
    return trim($out);
}

function rcpt_year_label($y) {
    $map = [1 => 'First Year', 2 => 'Second Year', 3 => 'Third Year', 4 => 'Fourth Year', 5 => 'Fifth Year'];
    $y = trim((string)$y);
    if ($y === '') {
        return '';
    }
    return $map[(int)$y] ?? $y;
}

function rcpt_money($n) {
    return number_format((float)$n, 2, '.', '');
}

$pid          = (int)($_GET['id'] ?? 0);
$is_admin     = (($_SESSION['role'] ?? '') === 'admin');
$session_user = (int)$_SESSION['user_id'];

if ($pid <= 0) {
    http_response_code(400);
    die("Invalid receipt.");
}

// u.* pehle rakha hai taaki roll number, course, branch, year, batch, father ka naam (jo bhi column ho) mil jaaye.
// Baaki fields alias ke saath hain taaki naam na takraayein.
$stmt = $conn->prepare("SELECT u.*,
        fp.id AS payment_id, fp.record_id AS rec_id, fp.amount AS paid_amount, fp.utr AS pay_utr, fp.paid_at AS pay_time,
        adm.name AS admin_name,
        br.borrow_date AS br_borrow, br.due_date AS br_due, br.return_date AS br_return, br.status AS br_status,
        b.title AS book_title, b.author AS book_author
    FROM fine_payments fp
    JOIN borrow_records br ON fp.record_id = br.record_id
    JOIN users u ON br.user_id = u.user_id
    JOIN books b ON br.book_id = b.book_id
    LEFT JOIN users adm ON fp.received_by = adm.user_id
    WHERE fp.id = ?");
$stmt->bind_param("i", $pid);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    http_response_code(404);
    die("Receipt nahi mili.");
}
if (!$is_admin && (int)$row['user_id'] !== $session_user) {
    http_response_code(403);
    die("Yeh receipt aapki nahi hai.");
}

// Is payment tak kitna pay hua aur kitna baaki
$stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) AS s FROM fine_payments WHERE record_id = ? AND id <= ?");
$stmt->bind_param("ii", $row['rec_id'], $pid);
$stmt->execute();
$paid_upto = (int)$stmt->get_result()->fetch_assoc()['s'];
$stmt->close();

$end_date   = lib_fine_end_date($row['br_status'], $row['br_return'], $row['br_due'], date('Y-m-d'));
$days_late  = lib_fine_days($row['br_due'], $end_date);
$total_fine = $days_late * lib_fine_per_day();
$balance    = max(0, $total_fine - $paid_upto);
$paid_now   = (int)$row['paid_amount'];

$father = '';
foreach (['father_name', 'fathers_name', 'father'] as $k) {
    if (!empty($row[$k])) {
        $father = $row[$k];
        break;
    }
}
$batch = '';
foreach (['batch', 'batch_year', 'session'] as $k) {
    if (!empty($row[$k])) {
        $batch = $row[$k];
        break;
    }
}

$course_text = trim(($row['course'] ?? '') . ' ' . ($row['branch'] ?? ''));
$receipt_no  = str_pad((string)$row['payment_id'], 4, '0', STR_PAD_LEFT);
$paid_date   = date('d/m/Y', strtotime($row['pay_time']));
$back_url    = $is_admin ? 'overdue_list.php' : 'my_books.php';
$show_logo   = file_exists(__DIR__ . '/' . $LOGO_FILE);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Receipt <?php echo rcpt_h($receipt_no); ?></title>
    <style>
        body { font-family: "Times New Roman", Times, serif; font-size: 16px; color: #000; background: #f2f2f2; margin: 0; padding: 16px; }
        .toolbar { max-width: 760px; margin: 0 auto 12px; display: flex; gap: 8px; }
        .btn { font-family: Arial, sans-serif; font-size: 14px; padding: 8px 14px; border: 1px solid #555; background: #fff; border-radius: 4px; cursor: pointer; text-decoration: none; color: #000; }
        .btn.primary { background: #0d6efd; border-color: #0d6efd; color: #fff; }
        .sheet { max-width: 760px; margin: 0 auto; background: #fff; border: 1px solid #000; padding: 14px 16px 18px; }
        .head { display: flex; align-items: center; gap: 14px; padding-bottom: 6px; }
        .head img { width: 84px; height: 84px; object-fit: contain; }
        .head .title { flex: 1; text-align: center; margin-right: 84px; line-height: 1.3; }
        .head .title b { font-size: 18px; }
        .head.nologo .title { margin-right: 0; }
        hr { border: 0; border-top: 1px solid #000; margin: 4px 0 6px; }
        .rtitle { text-align: center; font-weight: bold; font-size: 18px; margin-bottom: 8px; }
        .line { margin: 5px 0; }
        .two { display: flex; justify-content: space-between; gap: 16px; margin: 5px 0; }
        .two > div { width: 50%; }
        .two > div:last-child { text-align: left; }
        .two.dates > div:last-child { text-align: right; width: auto; }
        table.fee { width: calc(100% - 28px); margin: 12px 14px; border-collapse: collapse; font-family: Arial, sans-serif; font-size: 15px; }
        table.fee th, table.fee td { border: 1px solid #000; padding: 6px 8px; }
        table.fee th { text-align: center; font-size: 17px; }
        table.fee td.num { text-align: right; width: 140px; }
        table.fee td.sr { text-align: left; width: 70px; }
        table.fee tr.total td { font-weight: bold; font-size: 17px; text-align: center; }
        table.fee tr.total td.num { text-align: right; }
        .notes { font-size: 13px; margin-top: 6px; line-height: 1.35; }
        .sign { text-align: right; margin-top: 26px; font-weight: bold; padding-right: 40px; }
        .small { font-size: 13px; }
        @media print {
            body { background: #fff; padding: 0; }
            .toolbar { display: none !important; }
            .sheet { border: 1px solid #000; }
            @page { margin: 10mm; }
        }
    </style>
</head>
<body>

<div class="toolbar">
    <a href="<?php echo $back_url; ?>" class="btn">← Back</a>
    <button type="button" class="btn primary" onclick="window.print()">🖨️ Print / Save as PDF</button>
</div>

<div class="sheet">
    <div class="head <?php echo $show_logo ? '' : 'nologo'; ?>">
        <?php if ($show_logo) { ?>
            <img src="<?php echo rcpt_h($LOGO_FILE); ?>" alt="Logo">
        <?php } ?>
        <div class="title">
            <b><?php echo rcpt_h($INSTITUTE_NAME); ?></b><br>
            <?php echo rcpt_h($INSTITUTE_ADDRESS); ?>
        </div>
    </div>
    <hr>
    <div class="rtitle">RECEIPT <span class="small" style="font-weight:normal;">(Library Fine)</span></div>

    <div class="two dates">
        <div><b>No.:</b> <?php echo rcpt_h($receipt_no); ?></div>
        <div><b>Date:</b> <?php echo rcpt_h($paid_date); ?></div>
    </div>

    <div class="line"><b>Student Name:</b> <?php echo rcpt_h($row['name'] ?? ''); ?></div>
    <div class="line"><b>ERP ID:</b> <?php echo rcpt_h($row['erp_id'] ?? ''); ?></div>
    <div class="line"><b>Father's Name:</b> <?php echo $father !== '' ? rcpt_h($father) : '—'; ?></div>

    <div class="two">
        <div><b>Roll No.:</b> <?php echo rcpt_h($row['roll_number'] ?? ''); ?></div>
        <div><b>Course:</b> <?php echo $course_text !== '' ? rcpt_h($course_text) : '—'; ?></div>
    </div>
    <div class="two">
        <div><b>Year:</b> <?php echo rcpt_h(rcpt_year_label($row['year'] ?? '')); ?></div>
        <div><b>Batch:</b> <?php echo $batch !== '' ? rcpt_h($batch) : '—'; ?></div>
    </div>

    <table class="fee">
        <tr>
            <th style="width:70px;">Sr.No.</th>
            <th>Particulars</th>
            <th style="width:140px;">Amount</th>
        </tr>
        <tr>
            <td class="sr">1</td>
            <td>
                Library Fine &mdash; <?php echo rcpt_h($row['book_title']); ?><br>
                <span class="small">Due: <?php echo rcpt_h($row['br_due']); ?>
                <?php echo !empty($row['br_return']) ? ' | Returned: ' . rcpt_h($row['br_return']) : ' | Not returned yet'; ?>
                | <?php echo $days_late; ?> day(s) late &times; &#8377;<?php echo lib_fine_per_day(); ?>/day</span>
            </td>
            <td class="num"><?php echo rcpt_money($total_fine); ?></td>
        </tr>
        <tr class="total">
            <td></td>
            <td>Total Amount</td>
            <td class="num"><?php echo rcpt_money($total_fine); ?></td>
        </tr>
    </table>

    <div class="two">
        <div><b>UPI / UTR No.:</b> <?php echo rcpt_h($row['pay_utr']); ?></div>
        <div><b>Payment Mode:</b> UPI</div>
    </div>
    <div class="two">
        <div><b>Dated:</b> <?php echo rcpt_h($paid_date); ?></div>
        <div><b>Amount Rs.:</b> <?php echo rcpt_money($paid_now); ?></div>
    </div>
    <div class="line"><b>Mode:</b> ONLINE</div>
    <div class="line"><b>Total Paid Amount in words Rs.:</b> <?php echo rcpt_h(rcpt_words($paid_now)); ?> Rupees Only</div>
    <div class="line">
        <b>Balance Rs.:</b> <?php echo $balance > 0 ? rcpt_money($balance) : 'Nil (fully paid)'; ?>
        <span class="small">&nbsp;|&nbsp; Total paid till now: <?php echo rcpt_money($paid_upto); ?></span>
    </div>

    <div class="notes">
        1. Subject to realisation of UPI payment<br>
        2. No refund in any case<br>
        3. Generated by Computer<br>
        <span>Received by: <?php echo rcpt_h($row['admin_name'] ?? '—'); ?></span>
    </div>

    <div class="sign">Authorized Signature</div>
</div>

</body>
</html>
<?php $conn->close(); ?>