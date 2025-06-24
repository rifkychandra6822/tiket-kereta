<?php
// Konfirmasi Pesanan Makanan
session_start();
if (!isset($_SESSION['admin'])) header('Location: login.php');
include '../includes/db.php';
$message = '';
if (isset($_GET['konfirmasi'])) {
    $id = intval($_GET['konfirmasi']);
    $conn->query("UPDATE pesanan_makanan SET konfirmasi=1 WHERE id=$id");
    $message = '<div class="alert alert-success">Pesanan dikonfirmasi/dikirim!</div>';
}
$pesanan = $conn->query("SELECT pm.id, p.id as id_penumpang, u.username, m.nama, m.harga, pm.konfirmasi FROM pesanan_makanan pm JOIN tiket t ON pm.id_tiket=t.id JOIN penumpang p ON t.id_penumpang=p.id JOIN pengguna u ON p.id_pengguna=u.id JOIN makanan m ON m.id=pm.id_tiket ORDER BY pm.id DESC");

// Calculate statistics
$total_pesanan = 0;
$pesanan_dikirim = 0;
$pesanan_pending = 0;
$total_pendapatan = 0;
$pesanan_data = [];

while ($row = $pesanan->fetch_assoc()) {
    $pesanan_data[] = $row;
    $total_pesanan++;
    if ($row['konfirmasi']) {
        $pesanan_dikirim++;
        $total_pendapatan += $row['harga'];
    } else {
        $pesanan_pending++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Pesanan Makanan - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/konfirmasi_pesanan_makanan.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-card">
            <div class="header">
                <div class="header-left">
                    <div class="brand-logo">
                        🍽️
                    </div>
                    <div class="header-title">
                        <h1>Konfirmasi Pesanan Makanan</h1>
                        <p>Kelola dan konfirmasi pesanan makanan penumpang</p>
                    </div>
                </div>
            </div>

            <!-- Alert Messages -->
            <?= $message; ?>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card total">
                    <div class="stat-value"><?= $total_pesanan ?></div>
                    <div class="stat-label">Total Pesanan</div>
                </div>
                <div class="stat-card pending">
                    <div class="stat-value"><?= $pesanan_pending ?></div>
                    <div class="stat-label">Menunggu Konfirmasi</div>
                </div>
                <div class="stat-card completed">
                    <div class="stat-value"><?= $pesanan_dikirim ?></div>
                    <div class="stat-label">Pesanan Dikirim</div>
                </div>
                <div class="stat-card revenue">
                    <div class="stat-value">Rp<?= number_format($total_pendapatan, 0, ',', '.') ?></div>
                    <div class="stat-label">Total Pendapatan</div>
                </div>
            </div>

            <!-- Data Table -->
            <div class="table-container">
                <div class="table-header">
                    <h3>Daftar Pesanan Makanan</h3>
                </div>
                
                <?php if (count($pesanan_data) > 0): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Penumpang</th>
                                <th>Menu Makanan</th>
                                <th>Harga</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pesanan_data as $row): ?>
                                <tr>
                                    <td class="username-cell"><?= htmlspecialchars($row['username']); ?></td>
                                    <td class="menu-cell"><?= htmlspecialchars($row['nama']); ?></td>
                                    <td class="price-cell">Rp<?= number_format($row['harga'], 0, ',', '.'); ?></td>
                                    <td>
                                        <?php if ($row['konfirmasi']): ?>
                                            <span class="status-badge status-completed">Dikirim</span>
                                        <?php else: ?>
                                            <span class="status-badge status-pending">Dipesan</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!$row['konfirmasi']): ?>
                                            <a href="?konfirmasi=<?= $row['id']; ?>" class="btn-success" onclick="return confirm('Konfirmasi pesanan ini sebagai dikirim?')">
                                                ✓ Konfirmasi/Dikirim
                                            </a>
                                        <?php else: ?>
                                            <span style="color: var(--success); font-weight: 600;">✓ Selesai</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">🍽️</div>
                        <h4>Belum Ada Pesanan Makanan</h4>
                        <p>Tidak ada pesanan makanan yang perlu dikonfirmasi</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Action Buttons -->
            <div class="actions">
                <a href="dashboard.php" class="btn-secondary">
                    ← Kembali ke Dashboard
                </a>
                <a href="#" onclick="window.location.reload()" class="btn-primary">
                    🔄 Refresh Data
                </a>
            </div>
        </div>
    </div>

    <script>
        // Auto refresh every 30 seconds for real-time updates
        setInterval(function() {
            // Only refresh if there are pending orders
            <?php if ($pesanan_pending > 0): ?>
                console.log('Checking for new orders...');
            <?php endif; ?>
        }, 30000);

        // Add confirmation dialog for actions
        document.querySelectorAll('.btn-success').forEach(button => {
            button.addEventListener('click', function(e) {
                if (!confirm('Apakah Anda yakin ingin mengkonfirmasi pesanan ini sebagai dikirim?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>