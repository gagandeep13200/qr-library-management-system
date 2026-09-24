<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fine_per_day = floatval($_POST['fine_per_day']);
    if ($fine_per_day < 0) $fine_per_day = 0;
    $stmt = $conn->prepare("UPDATE settings SET setting_value=? WHERE setting_key='fine_per_day'");
    $stmt->bind_param("s", $fine_per_day);
    $stmt->execute();
    $stmt->close();
    header("Location: settings.php?msg=" . urlencode("Fine per day updated successfully"));
    exit();
}

$current_fine = get_fine_per_day($conn);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Settings - Library System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: #f4f6f9; }
        .sidebar {
            width: 240px; min-height: 100vh; background: #212529; color: white;
            position: fixed; top: 0; left: 0; padding-top: 20px;
        }
        .sidebar a { display: block; padding: 12px 20px; color: #ccc; text-decoration: none; font-size: 15px; }
        .sidebar a:hover, .sidebar a.active { background: #343a40; color: white; }
        .main-content { margin-left: 240px; padding: 30px; }
        @media (max-width: 768px) {
            .sidebar { width: 100%; min-height: auto; position: relative; }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>
<?php include 'toast.php'; ?>
<div class="sidebar">
    <h4 class="text-center mb-4">📚 Library Admin</h4>
    <a href="index.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <a href="view_books.php"><i class="bi bi-book"></i> View Books</a>
    <a href="add_book.php"><i class="bi bi-plus-square"></i> Add Book</a>
    <a href="add_student.php"><i class="bi bi-person-plus"></i> Add Student</a>
    <a href="generate_qr.php"><i class="bi bi-qr-code"></i> Generate QR</a>
    <a href="scan_qr.php"><i class="bi bi-camera"></i> Scan QR / Issue</a>
    <a href="manage_records.php"><i class="bi bi-clipboard-data"></i> Borrow Records</a>
    <a href="settings.php" class="active"><i class="bi bi-gear"></i> Settings</a>
    <a href="future_scope.php"><i class="bi bi-rocket"></i> Future Scope</a>
    <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
</div>
<div class="main-content">
    <h3 class="mb-4">⚙️ System Settings</h3>
    <div class="card p-4" style="max-width: 420px;">
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Fine Per Day (₹)</label>
                <input type="number" step="0.01" min="0" name="fine_per_day" class="form-control" value="<?php echo htmlspecialchars($current_fine); ?>" required>
                <small class="text-muted">Har overdue din ke liye yeh amount charge hoga.</small>
            </div>
            <button type="submit" class="btn btn-success">Save Settings</button>
        </form>
    </div>
</div>
</body>
</html>
<?php $conn->close(); ?>