<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}
include 'db_connect.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $erp_id = $_POST['erp_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    $check = $conn->query("SELECT * FROM users WHERE erp_id = '$erp_id' OR email = '$email'");

    if ($check->num_rows > 0) {
        $error = "ERP ID or Email already exists.";
    } else {
        $sql = "INSERT INTO users (erp_id, name, email, password, role) 
                VALUES ('$erp_id', '$name', '$email', '$password', 'student')";
        if ($conn->query($sql) === TRUE) {
            $success = "Student added successfully!";
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Student - Library System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5" style="max-width: 500px;">
    <h2 class="mb-4">🎓 Add New Student</h2>
    <a href="index.php" class="btn btn-secondary mb-3">← Back to Home</a>
    <?php if ($error) echo "<div class='alert alert-danger'>$error</div>"; ?>
    <?php if ($success) echo "<div class='alert alert-success'>$success</div>"; ?>
    <form method="POST">
        <input type="text" name="erp_id" class="form-control mb-2" placeholder="ERP ID (e.g. ERP1003)" required>
        <input type="text" name="name" class="form-control mb-2" placeholder="Full Name" required>
        <input type="email" name="email" class="form-control mb-2" placeholder="Email" required>
        <input type="password" name="password" class="form-control mb-3" placeholder="Password" required>
        <button type="submit" class="btn btn-success w-100">Add Student</button>
    </form>
</div>
</body>
</html>