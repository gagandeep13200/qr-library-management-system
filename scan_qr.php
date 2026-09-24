<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
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
    <h2 class="mb-4">📷 Scan Book Copy QR</h2>
    <a href="index.php" class="btn btn-secondary mb-4">← Back to Home</a>

    <div id="reader" style="width:400px; margin: 0 auto;"></div>

    <div id="scannedInfo" class="mt-4" style="display:none;">
        <div class="alert alert-info">
            Scanned Copy ID: <strong id="copyIdDisplay"></strong>
        </div>
        <div style="max-width: 350px; margin: 0 auto;" class="text-start">
            <label class="form-label">Student ERP ID</label>
            <input type="text" id="erpInput" class="form-control mb-3" placeholder="e.g. ERP1001">

            <label class="form-label">Issue Date</label>
            <input type="date" id="issueDateInput" class="form-control mb-3" required>

            <label class="form-label">Due Date</label>
            <input type="date" id="dueDateInput" class="form-control mb-3" required>

            <button onclick="issueBook()" class="btn btn-success w-100">Issue This Copy</button>
        </div>
    </div>

    <p id="result" class="fs-5 fw-bold mt-3"></p>

    <button onclick="location.reload()" class="btn btn-outline-secondary mt-3">🔄 Scan Another</button>
</div>

<script>
    let scannedCopyId = "";

    function onScanSuccess(decodedText, decodedResult) {
        scannedCopyId = decodedText;
        document.getElementById("copyIdDisplay").innerText = decodedText;
        document.getElementById("scannedInfo").style.display = "block";

        // Issue Date field ko aaj ki date se pre-fill karo (sirf default, editable hai)
        const today = new Date().toISOString().split('T')[0];
        document.getElementById("issueDateInput").value = today;
        // Due Date jaan-boojh kar khaali/blank hai — admin manually select karega, koi auto-calculation nahi

        html5QrcodeScanner.clear();
    }

    function issueBook() {
        const erp = document.getElementById("erpInput").value.trim();
        const issueDate = document.getElementById("issueDateInput").value;
        const dueDate = document.getElementById("dueDateInput").value;

        if (!erp) {
            alert("Please enter the student's ERP ID.");
            return;
        }
        if (!issueDate) {
            alert("Please select the Issue Date.");
            return;
        }
        if (!dueDate) {
            alert("Please select the Due Date.");
            return;
        }

        document.getElementById("result").innerText = "Processing...";
        fetch("issue_scanned_book.php"
            + "?copy_id=" + encodeURIComponent(scannedCopyId)
            + "&erp_id=" + encodeURIComponent(erp)
            + "&issue_date=" + encodeURIComponent(issueDate)
            + "&due_date=" + encodeURIComponent(dueDate))
            .then(response => response.text())
            .then(data => {
                document.getElementById("result").innerText = data;
            });
    }

    var html5QrcodeScanner = new Html5QrcodeScanner(
        "reader", { fps: 10, qrbox: 250 });
    html5QrcodeScanner.render(onScanSuccess);
</script>
</body>
</html>