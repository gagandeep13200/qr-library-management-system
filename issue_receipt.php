<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}
include 'db_connect.php';

$record_id = $_GET['record_id'] ?? null;
if (!$record_id) {
    header("Location: index.php");
    exit();
}

$stmt = $conn->prepare("SELECT br.record_id, br.copy_id, br.borrow_date, br.due_date, 
        u.name, u.erp_id, u.mobile_number, u.course, u.branch,
        b.title, b.author, b.isbn
        FROM borrow_records br
        JOIN users u ON br.user_id = u.user_id
        JOIN books b ON br.book_id = b.book_id
        WHERE br.record_id = ?");
$stmt->bind_param("i", $record_id);
$stmt->execute();
$record = $stmt->get_result()->fetch_assoc();

if (!$record) {
    header("Location: index.php");
    exit();
}
$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Issue Receipt - Library System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print { .no-print { display: none; } }
        .receipt-box { border: 2px dashed #333; padding: 25px; max-width: 500px; margin: 30px auto; }
    </style>
</head>
<body>
<div class="container">
    <div class="no-print mt-3 text-center">
        <a href="index.php" class="btn btn-secondary">← Back to Dashboard</a>
        <button onclick="window.print()" class="btn btn-primary">🖨️ Print Receipt</button>
    </div>
    <div class="receipt-box">
        <h4 class="text-center mb-3">📚 Library Book Issue Receipt</h4>
        <hr>
        <p><b>Receipt No:</b> #<?php echo $record['record_id']; ?></p>
        <p><b>Student Name:</b> <?php echo htmlspecialchars($record['name']); ?></p>
        <p><b>ERP ID:</b> <?php echo htmlspecialchars($record['erp_id']); ?></p>
        <p><b>Mobile Number:</b> <?php echo htmlspecialchars($record['mobile_number']); ?></p>
        <p><b>Course/Branch:</b> <?php echo htmlspecialchars($record['course'] . " " . $record['branch']); ?></p>
        <hr>
        <p><b>Book Title:</b> <?php echo htmlspecialchars($record['title']); ?></p>
        <p><b>Copy ID:</b> <?php echo htmlspecialchars($record['copy_id']); ?></p>
        <p><b>Author:</b> <?php echo htmlspecialchars($record['author']); ?></p>
        <hr>
        <p><b>Issue Date:</b> <?php echo $record['borrow_date']; ?></p>
        <p><b>Due Date:</b> <?php echo $record['due_date']; ?></p>
        <p class="text-center mt-3 text-muted" style="font-size:13px;">Please return the book on or before the due date.</p>
    </div>
</div>
</body>
</html>