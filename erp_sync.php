<?php
// Yeh function fine ko ERP table mein sync karta hai.
// Abhi ERP table isi database mein hai (demo). Agar real ERP alag server pe ho,
// to niche wala $conn ek alag mysqli connection se replace kar dena.
function sync_fine_to_erp($conn, $erp_id, $fine_amount, $issue_id) {
    if ($fine_amount <= 0) {
        return true; // fine hi nahi hai to sync ki zaroorat nahi
    }

    try {
        $stmt = $conn->prepare("SELECT student_roll_no FROM erp_student_accounts WHERE student_roll_no=?");
        $stmt->bind_param("s", $erp_id);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($exists) {
            $stmt = $conn->prepare("UPDATE erp_student_accounts SET total_dues = total_dues + ? WHERE student_roll_no=?");
            $stmt->bind_param("ds", $fine_amount, $erp_id);
            $stmt->execute();
            $stmt->close();
        } else {
            $stmt = $conn->prepare("INSERT INTO erp_student_accounts (student_roll_no, student_name, total_dues) VALUES (?, 'Unknown', ?)");
            $stmt->bind_param("sd", $erp_id, $fine_amount);
            $stmt->execute();
            $stmt->close();
        }

        $stmt = $conn->prepare("UPDATE book_issues SET erp_sync_status='synced' WHERE issue_id=?");
        $stmt->bind_param("i", $issue_id);
        $stmt->execute();
        $stmt->close();

        return true;
    } catch (Exception $e) {
        $stmt = $conn->prepare("UPDATE book_issues SET erp_sync_status='failed' WHERE issue_id=?");
        $stmt->bind_param("i", $issue_id);
        $stmt->execute();
        $stmt->close();
        return false;
    }
}