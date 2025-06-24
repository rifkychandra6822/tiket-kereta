<?php
// Update Status Perjalanan
session_start();
if (!isset($_SESSION['petugas'])) header('Location: login.php');
include '../includes/db.php';
$message = '';
if (isset($_POST['update'])) {
    $jadwal_id = intval($_POST['jadwal_id']);
    $status = $conn->real_escape_string($_POST['status']);
    $waktu = date('Y-m-d H:i:s');
    $conn->query("INSERT INTO status_perjalanan (id_jadwal, status, waktu) VALUES ($jadwal_id, '$status', '$waktu')");
    $message = '<div class="alert alert-success">Status perjalanan berhasil diupdate!</div>';
}
$jadwal = $conn->query("SELECT jk.*, k.nama_kereta FROM jadwal_kereta jk JOIN kereta k ON jk.id_kereta=k.id ORDER BY jk.tanggal DESC, jk.waktu_berangkat DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Status Perjalanan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5" style="max-width:600px;">
    <h3 class="mb-4">Update Status Perjalanan</h3>
    <?= $message; ?>
    <form method="POST" class="mb-4">
        <div class="mb-3">
            <label>Pilih Jadwal</label>
            <select name="jadwal_id" class="form-control" required>
                <option value="">Pilih Jadwal</option>
                <?php while ($row = $jadwal->fetch_assoc()): ?>
                    <option value="<?= $row['id']; ?>">[<?= $row['nama_kereta']; ?>] <?= $row['stasiun_awal']; ?> - <?= $row['stasiun_tujuan']; ?>, <?= $row['tanggal']; ?> <?= $row['waktu_berangkat']; ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="mb-3">
            <label>Status Perjalanan</label>
            <input type="text" name="status" class="form-control" placeholder="Contoh: Berangkat, Tiba di Stasiun X, dll" required>
        </div>
        <button type="submit" name="update" class="btn btn-success w-100">Update Status</button>
    </form>
    <a href="dashboard.php" class="btn btn-secondary">Kembali</a>
</div>
</body>
</html>
