<?php
// Validasi Tiket
session_start();
if (!isset($_SESSION['petugas'])) header('Location: login.php');
include '../includes/db.php';
$message = '';
if (isset($_POST['validasi'])) {
    $tiket_id = intval($_POST['tiket_id']);
    $tiket = $conn->query("SELECT t.*, p.id as id_penumpang, u.username FROM tiket t JOIN penumpang p ON t.id_penumpang=p.id JOIN pengguna u ON p.id_pengguna=u.id WHERE t.id=$tiket_id")->fetch_assoc();
    if ($tiket && $tiket['status_tiket'] == 'Lunas' && $tiket['validasi_tiket'] != 1) {
        $conn->query("UPDATE tiket SET validasi_tiket=1 WHERE id=$tiket_id");
        $message = '<div class="alert alert-success">Tiket valid dan sudah digunakan.</div>';
    } else {
        $message = '<div class="alert alert-danger">Tiket tidak valid, sudah digunakan, atau sudah refund.</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Validasi Tiket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5" style="max-width:500px;">
    <h3 class="mb-4">Validasi Tiket Penumpang</h3>
    <?= $message; ?>
    <form method="POST" class="mb-4">
        <div class="mb-3">
            <label>ID Tiket</label>
            <input type="number" name="tiket_id" class="form-control" placeholder="Masukkan ID Tiket" required>
        </div>
        <button type="submit" name="validasi" class="btn btn-success w-100">Validasi</button>
    </form>
    <a href="dashboard.php" class="btn btn-secondary">Kembali</a>
</div>
</body>
</html>
