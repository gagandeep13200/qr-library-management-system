<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}
include 'db_connect.php';

$sql = "SELECT bc.copy_id, bc.status, b.title, b.author
        FROM book_copies bc
        JOIN books b ON bc.book_id = b.book_id
        ORDER BY b.title, bc.copy_id";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>QR Codes - Library System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5 text-center">
    <h2 class="mb-4">📷 Book Copy QR Codes</h2>
    <a href="index.php" class="btn btn-secondary mb-4">← Back to Home</a>
    <div class="d-flex flex-wrap justify-content-center gap-3">
        <?php while ($row = $result->fetch_assoc()) { 
            $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($row['copy_id']);
        ?>
        <div class="card p-3" style="width: 200px;">
            <img src="<?php echo $qr_url; ?>" class="mx-auto">
            <p class="mt-2 mb-0"><b><?php echo htmlspecialchars($row['title']); ?></b></p>
            <small>Copy ID: <?php echo htmlspecialchars($row['copy_id']); ?></small><br>
            <?php if ($row['status'] == 'available') { ?>
                <span class="badge bg-success mt-1">Available</span>
            <?php } elseif ($row['status'] == 'issued') { ?>
                <span class="badge bg-warning text-dark mt-1">Issued</span>
            <?php } else { ?>
                <span class="badge bg-secondary mt-1">Lost</span>
            <?php } ?>
        </div>
        <?php } ?>
    </div>
</div>
</body>
</html>
<?php $conn->close(); ?>