<?php
include 'api_auth.php';
include_once 'library_config.php';
$user = require_user();
$uid = (int)$user['sub'];
$today = date('Y-m-d');
$rate = lib_fine_per_day();

$st = $conn->prepare("SELECT br.record_id, b.title, br.due_date, br.return_date, br.status, br.fine_paid,
    (SELECT COALESCE(SUM(fp.amount),0) FROM fine_payments fp WHERE fp.record_id = br.record_id) AS paid_sum,
    (SELECT COUNT(*) FROM fine_payments fp2 WHERE fp2.record_id = br.record_id) AS pay_count
    FROM borrow_records br JOIN books b ON br.book_id = b.book_id
    WHERE br.user_id = ?");
$st->bind_param("i", $uid);
$st->execute();
$res = $st->get_result();

$out = []; $total = 0;
while ($r = $res->fetch_assoc()) {
    if ($r['status'] === 'issued' && $r['due_date'] < $today) {
        $days = lib_fine_days($r['due_date'], $today);
        $out[] = ['record_id'=>$r['record_id'], 'title'=>$r['title'], 'type'=>'running',
                  'days_late'=>$days, 'fine'=>$days*$rate, 'outstanding'=>0];
    } elseif ($r['status'] === 'returned' && $r['return_date'] && $r['return_date'] > $r['due_date']) {
        $s = lib_fine_status($r['due_date'], $r['return_date'], $r['fine_paid'], $r['paid_sum'], $r['pay_count']);
        $total += $s['outstanding'];
        $out[] = ['record_id'=>$r['record_id'], 'title'=>$r['title'], 'type'=>'returned',
                  'days_late'=>$s['days'], 'fine'=>$s['fine'], 'outstanding'=>$s['outstanding']];
    }
}
echo json_encode(['fines' => $out, 'total_outstanding' => $total]);