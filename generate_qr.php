<?php
include 'db_connect.php';

$sql = "SELECT * FROM books";
$result = $conn->query($sql);

echo "<h2>Book QR Codes</h2>";

while ($row = $result->fetch_assoc()) {
    $book_id = $row['book_id'];
    $title = $row['title'];
    
    // QR code image generate karne ke liye free API
    $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . $book_id;
    
    echo "<div style='display:inline-block; text-align:center; margin:15px; border:1px solid #ccc; padding:10px;'>";
    echo "<img src='" . $qr_url . "'><br>";
    echo "<b>" . $title . "</b><br>";
    echo "Book ID: " . $book_id;
    echo "</div>";
}

$conn->close();
?>