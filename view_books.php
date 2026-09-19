<?php
include 'db_connect.php';

$sql = "SELECT * FROM books";
$result = $conn->query($sql);

echo "<h2>Books in Library</h2>";
echo "<table border='1' cellpadding='8'>";
echo "<tr><th>ID</th><th>Title</th><th>Author</th><th>Category</th><th>ISBN</th><th>Quantity</th></tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['book_id'] . "</td>";
    echo "<td>" . $row['title'] . "</td>";
    echo "<td>" . $row['author'] . "</td>";
    echo "<td>" . $row['category'] . "</td>";
    echo "<td>" . $row['isbn'] . "</td>";
    echo "<td>" . $row['quantity'] . "</td>";
    echo "</tr>";
}

echo "</table>";

$conn->close();
?>