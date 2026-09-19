<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$role = $_SESSION['role'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>QR-Based Library Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container text-center mt-5">
    <h1 class="mb-3">📚 QR-Based Library Management System</h1>

    <?php if ($role == 'admin') { ?>
        <p class="lead">Welcome, <b><?php echo $_SESSION['name']; ?></b> (Admin/Librarian)</p>
        <div class="d-grid gap-2 col-4 mx-auto">
            <a href="view_books.php" class="btn btn-primary btn-lg">View All Books</a>
            <a href="add_book.php" class="btn btn-warning btn-lg">Add New Book</a>
            <a href="add_student.php" class="btn btn-warning btn-lg">Add New Student</a>
            <a href="generate_qr.php" class="btn btn-primary btn-lg">Generate QR Codes</a>
            <a href="scan_qr.php" class="btn btn-primary btn-lg">Scan QR to Issue Book</a>
            <a href="manage_records.php" class="btn btn-info btn-lg">Manage Borrowing Records</a>
                        <a href="future_scope.php" class="btn btn-outline-dark btn-lg">Future Scope (ERP Integration)</a>
            <a href="logout.php" class="btn btn-danger btn-lg">Logout</a>
        </div>
    <?php } else { ?>
        <p class="lead">Welcome, <b><?php echo $_SESSION['name']; ?></b> (<?php echo $_SESSION['erp_id']; ?>)</p>
        <div class="d-grid gap-2 col-4 mx-auto">
            <a href="view_books.php" class="btn btn-primary btn-lg">View All Books</a>
            <a href="my_books.php" class="btn btn-primary btn-lg">My Borrowed Books</a>
            <a href="logout.php" class="btn btn-danger btn-lg">Logout</a>
        </div>
    <?php } ?>
</div>
</body>
</html>