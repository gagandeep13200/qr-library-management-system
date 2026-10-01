<?php
session_start();
include 'db_connect.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login_id = $_POST['login_id'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? OR erp_id = ?");
    $stmt->bind_param("ss", $login_id, $login_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['erp_id'] = $user['erp_id'];
            header("Location: index.php");
            exit();
        } else {
            $error = "Invalid login ID or password.";
        }
    } else {
        $error = "Invalid login ID or password.";
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - Library System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container d-flex justify-content-center align-items-center" style="height:100vh;">
    <div class="card p-4 shadow" style="width: 380px;">
        <h3 class="text-center mb-1">📚 Library Login</h3>
        <p class="text-center text-muted" style="font-size:14px;">Admin: use Email &nbsp;|&nbsp; Student: use ERP ID</p>
        <?php if ($error) echo "<div class='alert alert-danger py-2'>$error</div>"; ?>
        <form method="POST">
            <input type="text" name="login_id" class="form-control mb-3" placeholder="Email (Admin) or ERP ID (Student)" required>
            <div class="input-group mb-3">
                <input type="password" name="password" id="pwd" class="form-control" placeholder="Password" required>
                <button class="btn btn-outline-secondary" type="button" onclick="togglePwd()">👁</button>
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
    </div>
</div>
<script>
function togglePwd() {
    var x = document.getElementById("pwd");
    x.type = (x.type === "password") ? "text" : "password";
}
</script>
</body>
</html>