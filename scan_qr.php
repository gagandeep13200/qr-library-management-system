<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Scan QR - Library System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/html5-qrcode"></script>
</head>
<body>
<div class="container mt-5 text-center">
    <h2 class="mb-4">📷 Scan Book QR to Issue</h2>
    <a href="index.php" class="btn btn-secondary mb-4">← Back to Home</a>
    <div id="reader" style="width:400px; margin: 0 auto;"></div>
    <p id="result" class="fs-5 fw-bold mt-3"></p>
</div>

<script>
    function onScanSuccess(decodedText, decodedResult) {
        document.getElementById("result").innerText = "Scanned Book ID: " + decodedText;
        
        fetch("issue_scanned_book.php?book_id=" + decodedText)
            .then(response => response.text())
            .then(data => {
                document.getElementById("result").innerText += " | " + data;
            });

        html5QrcodeScanner.clear();
    }

    var html5QrcodeScanner = new Html5QrcodeScanner(
        "reader", { fps: 10, qrbox: 250 });
    html5QrcodeScanner.render(onScanSuccess);
</script>
</body>
</html>