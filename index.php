<?php
session_start();

// Agar login nahi kiya, toh login page pe bhej do
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>QR-Based Library Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial; text-align: center; margin-top: 50px; background: #f4f4f4; }
        h1 { color: #2c3e50; }
        .welcome { font-size: 18px; margin-bottom: 20px; color: #333; }
        a {
            display: block;
            width: 300px;
            margin: 15px auto;
            padding: 12px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 18px;
        }
        a:hover { background: #2980b9; }
        .logout { background: #e74c3c; }
        .logout:hover { background: #c0392b; }
    </style>
</head>
<body>
<div class="container text-center mt-5">
    <h1 class="mb-3">📚 QR-Based Library Management System</h1>
    <p class="lead">Welcome, <b><?php echo $_SESSION['name']; ?></b> (<?php echo $_SESSION['role']; ?>)</p>
    
    <div class="d-grid gap-2 col-4 mx-auto">
        <a href="view_books.php" class="btn btn-primary btn-lg">View All Books</a>
            <a href="my_books.php" class="btn btn-primary btn-lg">My Borrowed Books</a>
                <?php if ($_SESSION['role'] == 'admin') { ?>
        <a href="add_book.php" class="btn btn-warning btn-lg">Add New Book (Admin)</a>
    <?php } ?>
        <a href="generate_qr.php" class="btn btn-primary btn-lg">Generate QR Codes</a>
        <a href="scan_qr.php" class="btn btn-primary btn-lg">Scan QR to Issue Book</a>
        <a href="logout.php" class="btn btn-danger btn-lg">Logout</a>
    </div>
</div>
</body>
</html>