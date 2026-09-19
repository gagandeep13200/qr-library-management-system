<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include 'db_connect.php';

$sql = "SELECT * FROM books";
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
    <h2 class="mb-4">📷 Book QR Codes</h2>
    <a href="index.php" class="btn btn-secondary mb-4">← Back to Home</a>
    <div class="d-flex flex-wrap justify-content-center gap-3">
        <?php while ($row = $result->fetch_assoc()) { 
            $book_id = $row['book_id'];
            $title = $row['title'];
            $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . $book_id;
        ?>
        <div class="card p-3" style="width: 200px;">
            <img src="<?php echo $qr_url; ?>" class="mx-auto">
            <p class="mt-2 mb-0"><b><?php echo $title; ?></b></p>
            <small>Book ID: <?php echo $book_id; ?></small>
        </div>
        <?php } ?>
    </div>
</div>
</body>
</html>
<?php $conn->close(); ?>