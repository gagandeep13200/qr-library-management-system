<?php
include 'db_connect.php';

// Test values - jo record abhi 'issued' status mein hai
$record_id = 1;  // borrow_records table ka record_id
$return_date = date("Y-m-d");  // aaj ki date

$sql = "UPDATE borrow_records 
        SET return_date = '$return_date', status = 'returned' 
        WHERE record_id = '$record_id'";

if ($conn->query($sql) === TRUE) {
    echo "Book returned successfully on " . $return_date;
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>