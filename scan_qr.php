<!DOCTYPE html>
<html>
<head>
    <title>Scan QR to Issue Book</title>
    <script src="https://unpkg.com/html5-qrcode"></script>
</head>
<body>
    <h2>Scan Book QR Code</h2>
    <div id="reader" style="width:400px;"></div>
    <p id="result" style="font-size:20px; font-weight:bold;"></p>

    <script>
        function onScanSuccess(decodedText, decodedResult) {
            document.getElementById("result").innerText = "Scanned Book ID: " + decodedText;
            
            // Backend ko book_id bhejna, book issue karne ke liye
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