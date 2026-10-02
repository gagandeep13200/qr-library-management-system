<?php
// run_notifications.php
// Sab students ke liye aaj ki notifications ek saath bana deta hai.
// Do tareeke se chalta hai:
//   1) Windows Task Scheduler se roz subah (CLI):
//        C:\xampp\php\php.exe C:\xampp\htdocs\library_system\run_notifications.php
//   2) Browser se, sirf admin login ke saath (demo / manual ke liye).
//
// Note: student jab my_books.php / notifications.php kholta hai tab uski aaj ki notification
// waise bhi ban jaati hai. Yeh script tab kaam aati hai jab sabko ek saath bhejna ho (aur baad mein email jodni ho).

chdir(__DIR__);

if (php_sapi_name() !== 'cli') {
    session_start();
    if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
        http_response_code(403);
        die('Forbidden');
    }
    header('Content-Type: text/plain; charset=utf-8');
}

include 'db_connect.php';
include_once 'notifications_lib.php';

$created = lib_generate_notifications($conn, null);
echo "Aaj ki nayi notifications bani: " . $created . "\n";

$conn->close();
