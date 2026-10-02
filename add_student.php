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
    $father_name = $_POST['father_name'];
    $mobile_number = $_POST['mobile_number'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $course = $_POST['course'];
    $branch = $_POST['branch'];
    $year = $_POST['year'];
    $roll_number = $_POST['roll_number'];
    $batch = trim($_POST['batch'] ?? '');

    $stmt = $conn->prepare("SELECT * FROM users WHERE erp_id = ? OR email = ?");
    $stmt->bind_param("ss", $erp_id, $email);
    $stmt->execute();
    $check = $stmt->get_result();

    if ($check->num_rows > 0) {
        $error = "ERP ID or Email already exists.";
    } else {
                $stmt2 = $conn->prepare("INSERT INTO users (erp_id, name, father_name, mobile_number, email, password, role, course, branch, year, roll_number, batch) VALUES (?, ?, ?, ?, ?, ?, 'student', ?, ?, ?, ?, ?)");
        $stmt2->bind_param("ssssssssiss", $erp_id, $name, $father_name, $mobile_number, $email, $password, $course, $branch, $year, $roll_number, $batch);
        if ($stmt2->execute()) {
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
<div class="container mt-5" style="max-width: 550px;">
    <h2 class="mb-4">🎓 Add New Student</h2>
    <a href="index.php" class="btn btn-secondary mb-3">← Back to Home</a>
    <?php if ($error) echo "<div class='alert alert-danger'>$error</div>"; ?>
    <?php if ($success) echo "<div class='alert alert-success'>$success</div>"; ?>
    <form method="POST">
        <input type="text" name="erp_id" class="form-control mb-2" placeholder="ERP ID (e.g. ERP1003)" required>
        <input type="text" name="father_name" class="form-control mb-2" placeholder="Father's Name">
        <input type="text" name="mobile_number" class="form-control mb-2" placeholder="Mobile Number">
        <input type="text" name="name" class="form-control mb-2" placeholder="Full Name" required>
        <input type="email" name="email" class="form-control mb-2" placeholder="Email" required>
        <input type="password" name="password" class="form-control mb-2" placeholder="Password" required>
        <input type="text" name="roll_number" class="form-control mb-2" placeholder="Roll Number">
        <select name="course" class="form-select mb-2" required>
            <option value="">Select Course</option>
            <option value="B.Tech">B.Tech</option>
            <option value="BCA">BCA</option>
            <option value="MCA">MCA</option>
            <option value="MBA">MBA</option>
        </select>
        <select name="branch" class="form-select mb-2" required>
            <option value="">Select Branch</option>
            <option value="CSE">CSE</option>
            <option value="ECE">ECE</option>
            <option value="ME">ME</option>
            <option value="EE">EE</option>
        </select>
        <select name="year" class="form-select mb-2" required>
            <option value="">Select Year</option>
            <option value="1">1st Year</option>
            <option value="2">2nd Year</option>
            <option value="3">3rd Year</option>
            <option value="4">4th Year</option>
        </select>
        <input type="text" name="batch" class="form-control mb-3" placeholder="Batch / Session (e.g. 2022-2026)">
        <button type="submit" class="btn btn-success w-100">Add Student</button>
    </form>
</div>
</body>
</html>