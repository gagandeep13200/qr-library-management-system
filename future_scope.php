<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Future Scope - Library System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5" style="max-width: 700px;">
    <h2 class="mb-4">🚀 Future Scope</h2>
    <a href="index.php" class="btn btn-secondary mb-3">← Back to Home</a>
    <div class="card p-4">
        <p>This is currently a <b>standalone mini-project</b> built for demonstration purposes, using its own database of students and books.</p>
        <p>In a real-world college deployment, this system could be extended to <b>integrate directly with the college ERP</b>, so that:</p>
        <ul>
            <li>Student records (ERP ID, name, department) sync automatically from the ERP database — no manual entry needed.</li>
            <li>Login credentials could reuse the ERP's existing authentication (Single Sign-On).</li>
            <li>Library fine/due-date data could reflect back into the student's ERP profile.</li>
        </ul>
        <p class="mb-0"><b>Note:</b> This ERP integration is <u>not implemented</u> in the current mini-project — it is proposed as future scope only. The current system is fully functional and self-contained for demo purposes.</p>
    </div>
</div>
</body>
</html>