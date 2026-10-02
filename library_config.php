<?php
// library_config.php
// Fine rate, UPI details aur fine ka hisaab ek jagah. overdue_list.php aur my_books.php dono yahin se lete hain.
//
// RULE: fine due_date se RETURN date tak ginta hai (book abhi issued hai to aaj tak, running).
//       Return hote hi days/fine wahin ruk jaate hain. Payment sirf returned book par li jaati hai.

date_default_timezone_set('Asia/Kolkata');

// ---- Fine ----
define('FINE_PER_DAY', 5); // ₹ per day overdue

// ---- UPI (QR isi se banta hai) ----
define('UPI_ID', '8445457565@upi');      // apne UPI app ke My QR / Profile mein exact ID check karo
define('UPI_PAYEE_NAME', 'College Library');

// ---- Helpers (naam "lib_" se shuru taaki db_connect.php ke functions se na takraye) ----

function lib_fine_per_day() {
    return (int)FINE_PER_DAY;
}

// due_date se end_date tak kitne din late (0 agar late nahi)
function lib_fine_days($due_date, $end_date) {
    $due = new DateTime($due_date);
    $end = new DateTime($end_date);
    if ($end <= $due) {
        return 0;
    }
    return (int)$end->diff($due)->days;
}

// Fine kis date tak ginna hai:
//  - returned book  => return_date (agar return_date save nahi hui to due_date, yani fine 0; overdue_list par warning aayegi)
//  - issued book    => aaj (running)
function lib_fine_end_date($status, $return_date, $due_date, $today) {
    if ($status === 'returned') {
        return !empty($return_date) ? $return_date : $due_date;
    }
    return $today;
}

// Fine ka status.
//  $paid_sum  : fine_payments mein is record ke payments ka total
//  $pay_count : is record ke kitne payments hue
// Returns: days, fine, outstanding (sab int), legacy (bool)
//   legacy = purana record jo pehle manual "Mark as Paid" se paid hua tha (koi payment record nahi) => baaki 0 maana jaata hai
function lib_fine_status($due_date, $end_date, $fine_paid, $paid_sum, $pay_count) {
    $days      = lib_fine_days($due_date, $end_date);
    $fine      = $days * lib_fine_per_day();
    $paid_sum  = (int)$paid_sum;
    $pay_count = (int)$pay_count;

    if ($pay_count === 0 && $fine_paid === 'paid') {
        return ['days' => $days, 'fine' => $fine, 'outstanding' => 0, 'legacy' => true];
    }
    return ['days' => $days, 'fine' => $fine, 'outstanding' => max(0, $fine - $paid_sum), 'legacy' => false];
}
