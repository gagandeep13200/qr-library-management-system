<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}
include 'db_connect.php';

$id = $_GET['id'] ?? ($_POST['id'] ?? null);
if (!$id) {
    header("Location: students_list.php");
    exit();
}

$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $father_name = $_POST['father_name'];
    $mobile_number = $_POST['mobile_number'];
    $email = $_POST['email'];
    $erp_id = $_POST['erp_id'];
    $roll_number = $_POST['roll_number'];
    $course = $_POST['course'];
    $branch = $_POST['branch'];
    $year = $_POST['year'];

    $stmt = $conn->prepare("UPDATE users SET name=?, father_name=?, mobile_number=?, email=?, erp_id=?, roll_number=?, course=?, branch=?, year=? WHERE user_id=? AND role='student'");
    $stmt->bind_param("sssssssssi", $name, $father_name, $mobile_number, $email, $erp_id, $roll_number, $course, $branch, $year, $id);
    if ($stmt->execute()) {
        $success = "Student updated successfully!";
    }
}

$stmt2 = $conn->prepare("SELECT * FROM users WHERE user_id = ? AND role='student'");
$stmt2->bind_param("i", $id);
$stmt2->execute();
$student = $stmt2->get_result()->fetch_assoc();

if (!$student) {
    header("Location: students_list.php");
    exit();
}
$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Student - Library System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5" style="max-width: 550px;">
    <h2 class="mb-4">✏️ Edit Student</h2>
    <a href="students_list.php" class="btn btn-secondary mb-3">← Back to Students List</a>
    <?php if ($success) echo "<div class='alert alert-success'>$success</div>"; ?>
    <form method="POST">
        <input type="hidden" name="id" value="<?php echo $student['user_id']; ?>">
        <label class="form-label">Full Name</label>
        <input type="text" name="name" class="form-control mb-2" value="<?php echo htmlspecialchars($student['name']); ?>" required>
        <label class="form-label">Father's Name</label>
        <input type="text" name="father_name" class="form-control mb-2" value="<?php echo htmlspecialchars($student['father_name']); ?>">
        <label class="form-label">Mobile Number</label>
        <input type="text" name="mobile_number" class="form-control mb-2" value="<?php echo htmlspecialchars($student['mobile_number']); ?>">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control mb-2" value="<?php echo htmlspecialchars($student['email']); ?>" required>
        <label class="form-label">ERP ID</label>
        <input type="text" name="erp_id" class="form-control mb-2" value="<?php echo htmlspecialchars($student['erp_id']); ?>" required>
        <label class="form-label">Roll Number</label>
        <input type="text" name="roll_number" class="form-control mb-2" value="<?php echo htmlspecialchars($student['roll_number']); ?>">
        <label class="form-label">Course</label>
        <input type="text" name="course" class="form-control mb-2" value="<?php echo htmlspecialchars($student['course']); ?>">
        <label class="form-label">Branch</label>
        <input type="text" name="branch" class="form-control mb-2" value="<?php echo htmlspecialchars($student['branch']); ?>">
        <label class="form-label">Year</label>
        <select name="year" class="form-select mb-3">
            <option value="">Select Year</option>
            <?php for ($i=1;$i<=4;$i++) { 
                $sel = ($student['year'] == $i) ? 'selected' : '';
                echo "<option value='$i' $sel>Year $i</option>";
            } ?>
        </select>
        <button type="submit" class="btn btn-success w-100">Save Changes</button>
    </form>
</div>
</body>
</html>