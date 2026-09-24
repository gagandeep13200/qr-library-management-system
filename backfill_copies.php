<?php
// RUN ONCE from the browser, then DELETE this file.
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}
include 'db_connect.php';

$books = $conn->query("SELECT book_id, title, book_code, quantity FROM books ORDER BY book_id");
// INSERT IGNORE: copies that already exist (e.g. DBMS-001) are left untouched
$ins = $conn->prepare("INSERT IGNORE INTO book_copies (copy_id, book_id) VALUES (?, ?)");

echo "<h3>Backfill report</h3>";
while ($b = $books->fetch_assoc()) {
    $title = htmlspecialchars($b['title']);
    if (empty($b['book_code'])) {
        echo "SKIPPED (book_code khali hai): book_id {$b['book_id']} - $title<br>";
        continue;
    }
    $added = 0;
    for ($i = 1; $i <= (int)$b['quantity']; $i++) {
        $copy_id = $b['book_code'] . '-' . str_pad($i, 3, '0', STR_PAD_LEFT);
        $bid = (int)$b['book_id'];
        $ins->bind_param("si", $copy_id, $bid);
        $ins->execute();
        $added += $ins->affected_rows > 0 ? 1 : 0;
    }
    echo "book_id {$b['book_id']} - $title ({$b['book_code']}): $added new copies added<br>";
}
echo "<br><b>Done. Ab is file ko delete kar do.</b>";
$conn->close();