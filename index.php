<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$role = $_SESSION['role'];
include 'db_connect.php';
$user_id = $_SESSION['user_id'];

if ($role == 'admin') {
    $total_students = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='student'")->fetch_assoc()['c'];
    $total_books = $conn->query("SELECT COUNT(*) as c FROM books")->fetch_assoc()['c'];
    $issued_books = $conn->query("SELECT COUNT(*) as c FROM borrow_records WHERE status='issued'")->fetch_assoc()['c'];
    $available_books = $conn->query("SELECT SUM(quantity) as c FROM books")->fetch_assoc()['c'];

    $recent = $conn->query("SELECT br.record_id, u.name, u.erp_id, b.title, br.borrow_date, br.due_date, br.status 
                             FROM borrow_records br
                             JOIN users u ON br.user_id = u.user_id
                             JOIN books b ON br.book_id = b.book_id
                             ORDER BY br.record_id DESC LIMIT 5");
} else {
    $today = date("Y-m-d");
    $stmt = $conn->prepare("SELECT COUNT(*) as c FROM borrow_records WHERE user_id=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $total_borrowed = $stmt->get_result()->fetch_assoc()['c'];
    $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(*) as c FROM borrow_records WHERE user_id=? AND status='issued'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $currently_issued = $stmt->get_result()->fetch_assoc()['c'];
    $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(*) as c FROM borrow_records WHERE user_id=? AND status='issued' AND due_date < ?");
    $stmt->bind_param("is", $user_id, $today);
    $stmt->execute();
    $overdue_count = $stmt->get_result()->fetch_assoc()['c'];
    $stmt->close();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>QR-Based Library Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: #f4f6f9; }
        .sidebar {
            width: 240px; min-height: 100vh; background: #212529; color: white;
            position: fixed; top: 0; left: 0; padding-top: 20px;
        }
        .sidebar a {
            display: block; padding: 12px 20px; color: #ccc; text-decoration: none; font-size: 15px;
        }
        .sidebar a:hover, .sidebar a.active { background: #343a40; color: white; }
        .main-content { margin-left: 240px; padding: 30px; }
        .stat-card {
            border-radius: 12px; padding: 20px; color: white; box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .stat-card h2 { font-size: 32px; margin: 0; }
        .stat-card p { margin: 0; opacity: 0.9; }
        .quick-card {
            border-radius: 12px; padding: 25px; text-align: center; text-decoration: none;
            color: #333; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.08); transition: 0.2s;
            display: block;
        }
        .quick-card:hover { transform: translateY(-4px); box-shadow: 0 6px 14px rgba(0,0,0,0.12); color: #333; }
        .quick-card i { font-size: 32px; display: block; margin-bottom: 10px; }
        @media (max-width: 768px) {
            .sidebar { width: 100%; min-height: auto; position: relative; }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>

<?php if ($role == 'admin') { ?>
<div class="sidebar">
    <h4 class="text-center mb-4">📚 Library Admin</h4>
    <a href="index.php" class="active"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <a href="view_books.php"><i class="bi bi-book"></i> View Books</a>
    <a href="add_book.php"><i class="bi bi-plus-square"></i> Add Book</a>
    <a href="add_student.php"><i class="bi bi-person-plus"></i> Add Student</a>
    <a href="generate_qr.php"><i class="bi bi-qr-code"></i> Generate QR</a>
    <a href="scan_qr.php"><i class="bi bi-camera"></i> Scan QR / Issue</a>
    <a href="manage_records.php"><i class="bi bi-clipboard-data"></i> Borrow Records</a>
    <a href="future_scope.php"><i class="bi bi-rocket"></i> Future Scope</a>
    <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
</div>

<div class="main-content">
    <h2 class="mb-1">Welcome, <?php echo $_SESSION['name']; ?> 👋</h2>
    <p class="text-muted">Admin / Librarian Dashboard</p>

    <div class="row g-3 mt-2">
        <div class="col-md-3">
            <div class="stat-card" style="background:#3498db;">
                <p>Total Students</p>
                <h2><?php echo $total_students; ?></h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card" style="background:#9b59b6;">
                <p>Total Books</p>
                <h2><?php echo $total_books; ?></h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card" style="background:#e67e22;">
                <p>Issued Books</p>
                <h2><?php echo $issued_books; ?></h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card" style="background:#27ae60;">
                <p>Total Copies</p>
                <h2><?php echo $available_books; ?></h2>
            </div>
        </div>
    </div>

    <div class="card mt-4 p-3">
        <h5>Recent Borrow Records</h5>
        <table class="table table-hover mt-2">
            <thead>
                <tr><th>Student</th><th>ERP ID</th><th>Book</th><th>Borrow Date</th><th>Due Date</th><th>Status</th></tr>
            </thead>
            <tbody>
                <?php while ($row = $recent->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['erp_id']); ?></td>
                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                    <td><?php echo $row['borrow_date']; ?></td>
                    <td><?php echo $row['due_date']; ?></td>
                    <td>
                        <?php if ($row['status'] == 'issued') { ?>
                            <span class="badge bg-warning text-dark">Issued</span>
                        <?php } else { ?>
                            <span class="badge bg-success">Returned</span>
                        <?php } ?>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<?php } else { ?>

<div class="container mt-5">
    <h2 class="mb-1">📚 Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?> 👋</h2>
    <p class="text-muted">ERP ID: <?php echo htmlspecialchars($_SESSION['erp_id']); ?></p>

    <div class="row g-3 mt-2">
        <div class="col-md-4">
            <div class="stat-card" style="background:#3498db;">
                <p>Total Books Borrowed</p>
                <h2><?php echo $total_borrowed; ?></h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card" style="background:#e67e22;">
                <p>Currently Issued</p>
                <h2><?php echo $currently_issued; ?></h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card" style="background:<?php echo $overdue_count > 0 ? '#e74c3c' : '#27ae60'; ?>;">
                <p>Overdue Books</p>
                <h2><?php echo $overdue_count; ?></h2>
            </div>
        </div>
    </div>

    <?php if ($overdue_count > 0) { ?>
        <div class="alert alert-danger mt-4">
            ⚠️ Aapke paas <?php echo $overdue_count; ?> overdue book(s) hai — kripya jald return karein.
        </div>
    <?php } ?>

    <div class="row g-3 mt-3">
        <div class="col-md-6">
            <a href="view_books.php" class="quick-card">
                <i class="bi bi-book text-primary"></i>
                <h5>View All Books</h5>
                <p class="text-muted mb-0">Library catalog browse karo</p>
            </a>
        </div>
        <div class="col-md-6">
            <a href="my_books.php" class="quick-card">
                <i class="bi bi-journal-bookmark text-success"></i>
                <h5>My Borrowed Books</h5>
                <p class="text-muted mb-0">Apni issued books dekho, return karo</p>
            </a>
        </div>
    </div>

    <a href="logout.php" class="btn btn-outline-danger mt-4">Logout</a>
</div>

<?php } ?>
</body>
</html>
<?php $conn->close(); ?>