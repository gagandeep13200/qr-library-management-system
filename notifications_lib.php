<?php
// notifications_lib.php
// Fine ki notifications banane aur padhne ke functions.
//
// Rule (due date = D):
//   D    -> reminder     : "Aaj due date hai, kal se fine lagega"
//   D+1  -> fine_started : "Fine shuru ho gaya: ₹5"
//   D+2+ -> fine_daily   : "N din late, fine ab ₹X ho gaya"
// Sirf issued (abhi wapas nahi aayi) books ke liye. Return hote hi in teeno ki notifications band.
//
// returned_unpaid -> book return ho chuki hai, lekin fine abhi bhi baaki hai (daily reminder).
//
// Har function try/catch mein hai: agar fine_notifications table abhi nahi bani to page crash nahi hoga.

include_once __DIR__ . '/library_config.php';

// Aaj ki notifications banata hai (jo pehle se bani hain unhe chhod deta hai).
// $user_id = null  => sab students ke liye (daily script), warna sirf us student ke liye.
// Returns: kitni nayi notifications bani.
function lib_generate_notifications($conn, $user_id = null) {
    try {
        $today   = date('Y-m-d');
        $rate    = lib_fine_per_day();
        $created = 0;

        // ---- Part A: Abhi issued books (due ho chuki / hone wali) ----
        if ($user_id === null) {
            $stmt = $conn->prepare("SELECT br.record_id, br.user_id, br.due_date, b.title
                                    FROM borrow_records br
                                    JOIN books b ON br.book_id = b.book_id
                                    WHERE br.status = 'issued' AND br.due_date <= ?");
            $stmt->bind_param("s", $today);
        } else {
            $uid = (int)$user_id;
            $stmt = $conn->prepare("SELECT br.record_id, br.user_id, br.due_date, b.title
                                    FROM borrow_records br
                                    JOIN books b ON br.book_id = b.book_id
                                    WHERE br.status = 'issued' AND br.due_date <= ? AND br.user_id = ?");
            $stmt->bind_param("si", $today, $uid);
        }
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        if ($rows) {
            $ins = $conn->prepare("INSERT IGNORE INTO fine_notifications (user_id, record_id, notify_date, type, message)
                                   VALUES (?, ?, ?, ?, ?)");

            foreach ($rows as $r) {
                $days  = lib_fine_days($r['due_date'], $today);
                $fine  = $days * $rate;
                $title = mb_substr($r['title'], 0, 80);

                if ($days === 0) {
                    $type = 'reminder';
                    $msg  = "⏰ '" . $title . "' ki due date aaj hai. Aaj wapas kar do, kal se ₹" . $rate . " per din fine lagega.";
                } elseif ($days === 1) {
                    $type = 'fine_started';
                    $msg  = "⚠️ '" . $title . "' overdue ho gayi. Fine shuru ho gaya: ₹" . $fine . " (₹" . $rate . " per din). Book wapas karte hi fine ruk jaayega.";
                } else {
                    $type = 'fine_daily';
                    $msg  = "⚠️ '" . $title . "' " . $days . " din late hai. Fine ab ₹" . $fine . " ho gaya. Book wapas karte hi fine ruk jaayega.";
                }

                $uid_row = (int)$r['user_id'];
                $rid_row = (int)$r['record_id'];
                $ins->bind_param("iisss", $uid_row, $rid_row, $today, $type, $msg);
                $ins->execute();
                if ($ins->affected_rows > 0) {
                    $created++;
                }
            }
            $ins->close();
        }

        // ---- Part B: Return ho chuki books jinka fine abhi baaki hai ----
        if ($user_id === null) {
            $stmt2 = $conn->prepare("SELECT br.record_id, br.user_id, br.due_date, br.return_date, br.fine_paid, b.title,
                        (SELECT COALESCE(SUM(fp.amount), 0) FROM fine_payments fp WHERE fp.record_id = br.record_id) AS paid_sum,
                        (SELECT COUNT(*) FROM fine_payments fp2 WHERE fp2.record_id = br.record_id) AS pay_count
                    FROM borrow_records br
                    JOIN books b ON br.book_id = b.book_id
                    WHERE br.status = 'returned' AND br.return_date IS NOT NULL AND br.return_date > br.due_date");
        } else {
            $uid = (int)$user_id;
            $stmt2 = $conn->prepare("SELECT br.record_id, br.user_id, br.due_date, br.return_date, br.fine_paid, b.title,
                        (SELECT COALESCE(SUM(fp.amount), 0) FROM fine_payments fp WHERE fp.record_id = br.record_id) AS paid_sum,
                        (SELECT COUNT(*) FROM fine_payments fp2 WHERE fp2.record_id = br.record_id) AS pay_count
                    FROM borrow_records br
                    JOIN books b ON br.book_id = b.book_id
                    WHERE br.status = 'returned' AND br.return_date IS NOT NULL AND br.return_date > br.due_date AND br.user_id = ?");
            $stmt2->bind_param("i", $uid);
        }
        $stmt2->execute();
        $rows2 = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt2->close();

        if ($rows2) {
            $ins2 = $conn->prepare("INSERT IGNORE INTO fine_notifications (user_id, record_id, notify_date, type, message)
                                    VALUES (?, ?, ?, ?, ?)");

            foreach ($rows2 as $r) {
                $s = lib_fine_status($r['due_date'], $r['return_date'], $r['fine_paid'], $r['paid_sum'], $r['pay_count']);
                $outstanding = $s['outstanding'];

                if ($outstanding <= 0) {
                    continue;
                }

                $title = mb_substr($r['title'], 0, 80);
                $type  = 'returned_unpaid';
                $msg   = "💰 '" . $title . "' return ho chuki hai, lekin ₹" . $outstanding . " ka fine abhi baaki hai. Library desk par UPI se pay kar do.";

                $uid_row = (int)$r['user_id'];
                $rid_row = (int)$r['record_id'];
                $ins2->bind_param("iisss", $uid_row, $rid_row, $today, $type, $msg);
                $ins2->execute();
                if ($ins2->affected_rows > 0) {
                    $created++;
                }
            }
            $ins2->close();
        }

        return $created;
    } catch (Throwable $e) {
        error_log('[notifications_lib] generate: ' . $e->getMessage());
        return 0;
    }
}

// Student ki unread notifications (naya pehle)
function lib_unread_notifications($conn, $user_id, $limit = 3) {
    try {
        $uid   = (int)$user_id;
        $limit = max(1, (int)$limit);
        $stmt  = $conn->prepare("SELECT id, type, message, notify_date
                                 FROM fine_notifications
                                 WHERE user_id = ? AND is_read = 0
                                 ORDER BY notify_date DESC, id DESC
                                 LIMIT " . $limit);
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    } catch (Throwable $e) {
        error_log('[notifications_lib] unread list: ' . $e->getMessage());
        return [];
    }
}

function lib_unread_count($conn, $user_id) {
    try {
        $uid  = (int)$user_id;
        $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM fine_notifications WHERE user_id = ? AND is_read = 0");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $c = (int)$stmt->get_result()->fetch_assoc()['c'];
        $stmt->close();
        return $c;
    } catch (Throwable $e) {
        error_log('[notifications_lib] unread count: ' . $e->getMessage());
        return 0;
    }
}