<?php
// Login Petugas
session_start();
if (isset($_SESSION['petugas'])) header('Location: dashboard.php');
include '../includes/db.php';
$message = '';
if (isset($_POST['login'])) {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];
    $res = $conn->query("SELECT * FROM users WHERE username='$username' AND role='petugas'");
    $data = $res->fetch_assoc();
    if ($data && password_verify($password, $data['password'])) {
        $_SESSION['petugas'] = $data;
        header('Location: dashboard.php');
        exit;
    } else {
        $message = '<div class="alert alert-danger">Username atau password salah!</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login Petugas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5" style="max-width:400px;">
    <h3 class="mb-4">Login Petugas</h3>
    <?= $message; ?>
    <form method="POST">
        <div class="mb-3">
            <input type="text" name="username" class="form-control" placeholder="Username" required>
        </div>
        <div class="mb-3">
            <input type="password" name="password" class="form-control" placeholder="Password" required>
        </div>
        <button type="submit" name="login" class="btn btn-primary w-100">Login</button>
    </form>
</div>
</body>
</html>
