<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}
include 'db_connect.php';

$sql = "SELECT bc.copy_id, bc.status, b.title, b.author 
        FROM book_copies bc
        JOIN books b ON bc.book_id = b.book_id
        ORDER BY b.title ASC, bc.copy_id ASC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>QR Codes - Library System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none; }
            .card { break-inside: avoid; }
        }
    </style>
</head>
<body>
<div class="container mt-5 text-center">
    <h2 class="mb-4">📷 Book Copy QR Codes</h2>
    <div class="no-print mb-4">
        <a href="index.php" class="btn btn-secondary">← Back to Home</a>
        <button onclick="window.print()" class="btn btn-primary">🖨️ Print All QR Codes</button>
    </div>
    <div class="d-flex flex-wrap justify-content-center gap-3">
        <?php if ($result->num_rows == 0) { ?>
            <p class="text-muted">Abhi koi book copy nahi hai. Pehle "Add Book" se book add karo.</p>
        <?php } ?>
        <?php while ($row = $result->fetch_assoc()) {
            $copy_id = $row['copy_id'];
            $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($copy_id);
        ?>
        <div class="card p-3" style="width: 200px;">
            <img src="<?php echo $qr_url; ?>" class="mx-auto" alt="QR for <?php echo htmlspecialchars($copy_id); ?>">
            <p class="mt-2 mb-0"><b><?php echo htmlspecialchars($row['title']); ?></b></p>
            <small class="text-muted"><?php echo htmlspecialchars($row['author']); ?></small>
            <p class="mb-1 mt-1"><strong>Copy ID: <?php echo htmlspecialchars($copy_id); ?></strong></p>
            <?php
                $badge_class = $row['status'] == 'available' ? 'bg-success' : ($row['status'] == 'issued' ? 'bg-warning text-dark' : 'bg-secondary');
            ?>
            <span class="badge <?php echo $badge_class; ?>"><?php echo ucfirst($row['status']); ?></span>
        </div>
        <?php } ?>
    </div>
</div>
</body>
</html>
<?php $conn->close(); ?>