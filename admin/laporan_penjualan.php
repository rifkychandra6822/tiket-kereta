<?php
// Laporan Penjualan
session_start();
if (!isset($_SESSION['admin'])) header('Location: login.php');
include '../includes/db.php';
$laporan = $conn->query("SELECT k.nama_kereta, jk.tanggal, jk.waktu_berangkat, COUNT(t.id) as jumlah_tiket, SUM(jk.tarif) as total_pendapatan FROM tiket t JOIN jadwal_kereta jk ON t.id_jadwal=jk.id JOIN kereta k ON jk.id_kereta=k.id WHERE t.status_tiket='Lunas' GROUP BY t.id_jadwal ORDER BY jk.tanggal DESC, jk.waktu_berangkat DESC");

// Calculate totals
$total_tiket = 0;
$total_pendapatan = 0;
$laporan_data = [];
while ($row = $laporan->fetch_assoc()) {
    $laporan_data[] = $row;
    $total_tiket += $row['jumlah_tiket'];
    $total_pendapatan += $row['total_pendapatan'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Keuangan - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/laporan_keuangan.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-card">
            <div class="header">
                <div class="header-left">
                    <div class="brand-logo">
                        📊
                    </div>
                    <div class="header-title">
                        <h1>Laporan Keuangan</h1>
                        <p>Laporan penjualan tiket kereta api</p>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?= $total_tiket ?></div>
                    <div class="stat-label">Total Tiket Terjual</div>
                </div>
                <div class="stat-card revenue">
                    <div class="stat-value">Rp<?= number_format($total_pendapatan, 0, ',', '.') ?></div>
                    <div class="stat-label">Total Pendapatan</div>
                </div>
            </div>

            <!-- Data Table -->
            <div class="table-container">
                <div class="table-header">
                    <h3>Detail Penjualan per Jadwal</h3>
                </div>
                
                <?php if (count($laporan_data) > 0): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nama Kereta</th>
                                <th>Tanggal</th>
                                <th>Waktu Berangkat</th>
                                <th>Jumlah Tiket</th>
                                <th>Total Pendapatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($laporan_data as $row): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($row['nama_kereta']); ?></strong></td>
                                    <td><?= date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                                    <td><?= date('H:i', strtotime($row['waktu_berangkat'])); ?></td>
                                    <td><?= $row['jumlah_tiket']; ?> tiket</td>
                                    <td class="revenue-cell">Rp<?= number_format($row['total_pendapatan'], 0, ',', '.'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📈</div>
                        <h4>Belum Ada Data Penjualan</h4>
                        <p>Tidak ada tiket yang terjual dengan status lunas</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Action Buttons -->
            <div class="actions">
                <a href="dashboard.php" class="btn-secondary">
                    ← Kembali ke Dashboard
                </a>
                <a href="#" onclick="window.print()" class="btn-primary">
                    🖨️ Cetak Laporan
                </a>
            </div>
        </div>
    </div>

    <script>
        // Add print styles
        const printStyles = `
            <style media="print">
                body { background: white !important; }
                .dashboard-container { max-width: none !important; margin: 0 !important; padding: 0 !important; }
                .dashboard-card { box-shadow: none !important; border-radius: 0 !important; }
                .actions { display: none !important; }
                .stats-grid { page-break-inside: avoid; }
                .table-container { page-break-inside: avoid; }
            </style>
        `;
        document.head.insertAdjacentHTML('beforeend', printStyles);
    </script>
</body>
</html>