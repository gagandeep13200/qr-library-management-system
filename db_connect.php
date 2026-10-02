<?php
date_default_timezone_set('Asia/Kolkata');
$host = "127.0.0.1";
$username = "root";
$password = "Gagandeep@13";
$database = "library_management";
$port = 3306;

$conn = new mysqli($host, $username, $password, $database, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

?>