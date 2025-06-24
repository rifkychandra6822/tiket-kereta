<?php
session_start();
if (!isset($_SESSION['user'])) header('Location: login.php');
include '../includes/db.php';

// Ambil data dari GET
$jadwal_id = isset($_GET['jadwal_id']) ? intval($_GET['jadwal_id']) : 0;
$seat_ids = isset($_GET['seat_ids']) ? $_GET['seat_ids'] : '';

if ($jadwal_id == 0 || empty($seat_ids)) {
    echo '<div style="padding:2rem;text-align:center;color:red;">Data tidak valid. Silakan ulangi pemesanan.</div>';
    exit;
}

// Ambil info jadwal
$jadwal = $conn->query("SELECT jk.*, k.nama_kereta, k.kelas FROM jadwal_kereta jk JOIN kereta k ON jk.id_kereta = k.id WHERE jk.id = $jadwal_id")->fetch_assoc();

// Ambil info kursi yang dipilih
$kursi_list = [];
$kursi_ids = array_map('intval', explode(',', $seat_ids));
if (count($kursi_ids) > 0) {
    $ids_str = implode(',', $kursi_ids);
    $result = $conn->query("SELECT k.nomor_kursi, g.nomor_gerbong FROM kursi k JOIN gerbong g ON k.id_gerbong = g.id WHERE k.id IN ($ids_str)");
    while ($row = $result->fetch_assoc()) {
        $kursi_list[] = $row;
    }
}

// Tampilkan ringkasan pemesanan
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Konfirmasi Pemesanan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2 class="mb-4">Konfirmasi Pemesanan Tiket</h2>
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title mb-3">Jadwal: <?= htmlspecialchars($jadwal['nama_kereta']) ?> (<?= htmlspecialchars($jadwal['kelas']) ?>)</h5>
            <p class="mb-1"><strong>Rute:</strong> <?= htmlspecialchars($jadwal['stasiun_awal']) ?> → <?= htmlspecialchars($jadwal['stasiun_tujuan']) ?></p>
            <p class="mb-1"><strong>Tanggal:</strong> <?= htmlspecialchars($jadwal['tanggal']) ?></p>
            <p class="mb-1"><strong>Jam:</strong> <?= htmlspecialchars($jadwal['waktu_berangkat']) ?> - <?= htmlspecialchars($jadwal['waktu_tiba']) ?></p>
            <p class="mb-1"><strong>Kursi Dipilih:</strong> 
                <?php foreach ($kursi_list as $k) {
                    echo 'Gerbong ' . htmlspecialchars($k['nomor_gerbong']) . ' - Kursi ' . htmlspecialchars($k['nomor_kursi']) . '; ';
                } ?>
            </p>
            <p class="mb-1"><strong>Total Harga:</strong> Rp<?= number_format($jadwal['tarif'] * count($kursi_list), 0, ',', '.') ?></p>
        </div>
    </div>
    <form method="post" action="bayar.php">
        <input type="hidden" name="jadwal_id" value="<?= $jadwal_id ?>">
        <input type="hidden" name="seat_ids" value="<?= htmlspecialchars($seat_ids) ?>">
        <button type="submit" class="btn btn-success">Lanjut ke Pembayaran</button>
        <a href="pesan_tiket.php" class="btn btn-secondary ms-2">Batal</a>
    </form>
</div>
</body>
</html>
