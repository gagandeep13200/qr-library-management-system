<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit();
}
include 'db_connect.php';
include_once 'notifications_lib.php';

$user_id = (int)$_SESSION['user_id'];
$error   = '';
$items   = [];

// Aaj ki notifications (agar abhi tak nahi bani) bana do
lib_generate_notifications($conn, $user_id);

try {
    $stmt = $conn->prepare("SELECT id, type, message, notify_date, is_read
                            FROM fine_notifications
                            WHERE user_id = ?
                            ORDER BY notify_date DESC, id DESC
                            LIMIT 100");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Page khulte hi sab "read" ho jaati hain (is baar "New" tag dikh jaayega)
    $stmt = $conn->prepare("UPDATE fine_notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
} catch (Throwable $e) {
    error_log('[notifications.php] ' . $e->getMessage());
    $error = "Notifications abhi load nahi ho paayin. (notifications.sql chalayi hai?)";
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Notifications - Library System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5" style="max-width: 800px;">
    <h2 class="mb-4">🔔 Notifications</h2>
    <a href="my_books.php" class="btn btn-secondary mb-3">← My Books</a>

    <?php if ($error != '') { ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php } elseif (count($items) == 0) { ?>
        <p class="text-muted">Abhi koi notification nahi hai. 🎉</p>
    <?php } else { ?>
        <div class="list-group">
            <?php foreach ($items as $n) {
                $cls = ($n['type'] === 'reminder') ? 'list-group-item-warning' : 'list-group-item-danger';
            ?>
            <div class="list-group-item <?php echo $cls; ?>">
                <div class="d-flex justify-content-between">
                    <div><?php echo htmlspecialchars($n['message']); ?></div>
                    <?php if ((int)$n['is_read'] === 0) { ?>
                        <span class="badge bg-primary ms-2 align-self-start">New</span>
                    <?php } ?>
                </div>
                <div class="small text-muted mt-1"><?php echo htmlspecialchars($n['notify_date']); ?></div>
            </div>
            <?php } ?>
        </div>
    <?php } ?>
</div>
</body>
</html>
<?php $conn->close(); ?>
