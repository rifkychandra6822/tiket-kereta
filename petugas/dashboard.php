<?php
// Dashboard Petugas
session_start();
if (!isset($_SESSION['petugas'])) header('Location: login.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Petugas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Petugas Dashboard</h2>
    <a href="update_status.php" class="btn btn-primary">Update Status Perjalanan</a>
    <a href="validasi_tiket.php" class="btn btn-success">Validasi Tiket</a>
    <a href="logout.php" class="btn btn-danger">Logout</a>
</div>
</body>
</html>
