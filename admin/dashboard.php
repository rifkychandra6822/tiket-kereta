<?php
// Dashboard Admin
session_start();
if (!isset($_SESSION['admin'])) header('Location: login.php');
include '../includes/db.php';

// Get comprehensive statistics
$stats = [];

// Total kereta
$kereta_result = $conn->query("SELECT COUNT(*) as total, COUNT(CASE WHEN status='Aktif' THEN 1 END) as aktif FROM kereta");
$kereta_data = $kereta_result->fetch_assoc();
$stats['kereta'] = $kereta_data;

// Total jadwal
$jadwal_result = $conn->query("SELECT COUNT(*) as total, COUNT(CASE WHEN status='Tersedia' THEN 1 END) as tersedia FROM jadwal_kereta");
$jadwal_data = $jadwal_result->fetch_assoc();
$stats['jadwal'] = $jadwal_data;

// Total tiket dan pendapatan
$tiket_result = $conn->query("SELECT 
    COUNT(*) as total_tiket,
    COUNT(CASE WHEN status_tiket='Lunas' THEN 1 END) as tiket_lunas,
    SUM(CASE WHEN status_tiket='Lunas' THEN total_pembayaran ELSE 0 END) as total_pendapatan
    FROM tiket");
$tiket_data = $tiket_result->fetch_assoc();
$stats['tiket'] = $tiket_data;

// Total menu makanan
$menu_result = $conn->query("SELECT COUNT(*) as total, COUNT(CASE WHEN status=1 THEN 1 END) as aktif FROM menu_makanan");
$menu_data = $menu_result->fetch_assoc();
$stats['menu'] = $menu_data;

// Total pesanan makanan
$pesanan_result = $conn->query("SELECT 
    COUNT(*) as total_pesanan,
    COUNT(CASE WHEN konfirmasi=1 THEN 1 END) as pesanan_selesai,
    SUM(total) as total_pendapatan_makanan
    FROM pesanan_makanan");
$pesanan_data = $pesanan_result->fetch_assoc();
$stats['pesanan'] = $pesanan_data;

// Total pengguna
$pengguna_result = $conn->query("SELECT COUNT(*) as total FROM pengguna");
$pengguna_data = $pengguna_result->fetch_assoc();
$stats['pengguna'] = $pengguna_data;

// Recent activities - Jadwal terbaru
$recent_jadwal = $conn->query("SELECT jk.*, k.nama_kereta FROM jadwal_kereta jk JOIN kereta k ON jk.id_kereta = k.id ORDER BY jk.id DESC LIMIT 5");

// Recent tickets
$recent_tiket = $conn->query("SELECT t.*, jk.stasiun_awal, jk.stasiun_tujuan, k.nama_kereta, pg.nama as nama_penumpang 
    FROM tiket t 
    JOIN jadwal_kereta jk ON t.id_jadwal = jk.id 
    JOIN kereta k ON jk.id_kereta = k.id 
    JOIN penumpang p ON t.id_penumpang = p.id 
    JOIN pengguna pg ON p.id_pengguna = pg.id 
    ORDER BY t.tanggal_pesan DESC LIMIT 5");

// Calculate total revenue
$total_revenue = ($stats['tiket']['total_pendapatan'] ?? 0) + ($stats['pesanan']['total_pendapatan_makanan'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Sistem Tiket Kereta</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/dashboardadmin.css" rel="stylesheet">

</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-card">
            <!-- Header -->
            <div class="header">
                <div class="header-left">
                    <div class="brand-logo">
                        🚊
                    </div>
                    <div class="header-title">
                        <h1>Admin Dashboard</h1>
                        <p>Sistem Manajemen Tiket Kereta Api</p>
                    </div>
                </div>
                <div class="header-actions">
                    <div class="admin-info">
                        👨‍💼 Admin System
                    </div>
                    <a href="logout.php" class="btn-logout" onclick="return confirm('Yakin ingin logout?')">
                        🚪 Logout
                    </a>
                </div>
            </div>

            <!-- Statistics Overview -->
            <div class="stats-grid">
                <div class="stat-card revenue">
                    <div class="stat-icon">💰</div>
                    <div class="stat-value">Rp<?= number_format($total_revenue, 0, ',', '.') ?></div>
                    <div class="stat-label">Total Pendapatan</div>
                    <div class="stat-subtitle">Tiket + Makanan</div>
                </div>
                <div class="stat-card trains">
                    <div class="stat-icon">🚂</div>
                    <div class="stat-value"><?= $stats['kereta']['total'] ?></div>
                    <div class="stat-label">Total Kereta</div>
                    <div class="stat-subtitle"><?= $stats['kereta']['aktif'] ?> Aktif</div>
                </div>
                <div class="stat-card schedules">
                    <div class="stat-icon">🕒</div>
                    <div class="stat-value"><?= $stats['jadwal']['total'] ?></div>
                    <div class="stat-label">Total Jadwal</div>
                    <div class="stat-subtitle"><?= $stats['jadwal']['tersedia'] ?> Tersedia</div>
                </div>
                <div class="stat-card tickets">
                    <div class="stat-icon">🎫</div>
                    <div class="stat-value"><?= $stats['tiket']['total_tiket'] ?></div>
                    <div class="stat-label">Total Tiket</div>
                    <div class="stat-subtitle"><?= $stats['tiket']['tiket_lunas'] ?> Lunas</div>
                </div>
            </div>

            <!-- Navigation Grid -->
            <div class="navigation-grid">
                <a href="kelola_jadwal.php" class="nav-card schedules">
                    <div class="nav-icon">🕒</div>
                    <div class="nav-title">Kelola Jadwal</div>
                    <div class="nav-description">Atur jadwal perjalanan kereta api dan tarif</div>
                </a>
                
                <a href="kelola_kereta.php" class="nav-card trains">
                    <div class="nav-icon">🚂</div>
                    <div class="nav-title">Kelola Data Kereta</div>
                    <div class="nav-description">Manajemen armada dan spesifikasi kereta</div>
                </a>
                
                <a href="laporan_keuangan.php" class="nav-card reports">
                    <div class="nav-icon">📊</div>
                    <div class="nav-title">Laporan Keuangan</div>
                    <div class="nav-description">Laporan penjualan dan analisis pendapatan</div>
                </a>
                
                <a href="kelola_menu_makanan.php" class="nav-card food">
                    <div class="nav-icon">🍽️</div>
                    <div class="nav-title">Kelola Menu Makanan</div>
                    <div class="nav-description">Atur menu makanan dan minuman</div>
                </a>
                
                <a href="konfirmasi_pesanan_makanan.php" class="nav-card orders">
                    <div class="nav-icon">✅</div>
                    <div class="nav-title">Konfirmasi Pesanan</div>
                    <div class="nav-description">Kelola pesanan makanan penumpang</div>
                </a>
                
                <a href="kelola_tarif.php" class="nav-card tariff">
                    <div class="nav-icon">💰</div>
                    <div class="nav-title">Kelola Tarif</div>
                    <div class="nav-description">Pengaturan tarif perjalanan kereta</div>
                </a>
            </div>

            <!-- Content Grid -->
            <div class="content-grid">
                <!-- Recent Schedules -->
                <div class="recent-section">
                    <h3 class="section-title">
                        🕒 Jadwal Terbaru
                    </h3>
                    <?php if ($recent_jadwal->num_rows > 0): ?>
                        <?php while ($jadwal = $recent_jadwal->fetch_assoc()): ?>
                            <div class="recent-item">
                                <div class="recent-info">
                                    <h4><?= htmlspecialchars($jadwal['nama_kereta']) ?></h4>
                                    <p><?= htmlspecialchars($jadwal['stasiun_awal']) ?> → <?= htmlspecialchars($jadwal['stasiun_tujuan']) ?></p>
                                </div>
                                <div class="recent-meta">
                                    <div><?= date('d/m/Y', strtotime($jadwal['tanggal'])) ?></div>
                                    <div><?= date('H:i', strtotime($jadwal['waktu_berangkat'])) ?></div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="color: var(--text-light); text-align: center; padding: 20px;">Belum ada jadwal</p>
                    <?php endif; ?>
                </div>

                <!-- Quick Stats & Recent Tickets -->
                <div>
                    <!-- Quick Stats -->
                    <div class="recent-section" style="margin-bottom: 20px;">
                        <h3 class="section-title">📈 Statistik Cepat</h3>
                        <div class="quick-stats">
                            <div class="quick-stat">
                                <div class="quick-stat-value"><?= $stats['pengguna']['total'] ?></div>
                                <div class="quick-stat-label">Total Pengguna</div>
                            </div>
                            <div class="quick-stat">
                                <div class="quick-stat-value"><?= $stats['menu']['total'] ?></div>
                                <div class="quick-stat-label">Menu Makanan</div>
                            </div>
                            <div class="quick-stat">
                                <div class="quick-stat-value"><?= $stats['pesanan']['total_pesanan'] ?></div>
                                <div class="quick-stat-label">Pesanan Makanan</div>
                            </div>
                            <div class="quick-stat">
                                <div class="quick-stat-value">Rp<?= number_format($stats['pesanan']['total_pendapatan_makanan'] ?? 0, 0, ',', '.') ?></div>
                                <div class="quick-stat-label">Pendapatan F&B</div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Tickets -->
                    <div class="recent-section">
                        <h3 class="section-title">
                            🎫 Tiket Terbaru
                        </h3>
                        <?php if ($recent_tiket->num_rows > 0): ?>
                            <?php while ($tiket = $recent_tiket->fetch_assoc()): ?>
                                <div class="recent-item">
                                    <div class="recent-info">
                                        <h4><?= htmlspecialchars($tiket['nama_penumpang']) ?></h4>
                                        <p><?= htmlspecialchars($tiket['nama_kereta']) ?> - <?= htmlspecialchars($tiket['stasiun_awal']) ?> → <?= htmlspecialchars($tiket['stasiun_tujuan']) ?></p>
                                    </div>
                                    <div class="recent-meta">
                                        <div style="color: var(--success); font-weight: 600;"><?= $tiket['status_tiket'] ?></div>
                                        <div>Rp<?= number_format($tiket['total_pembayaran'], 0, ',', '.') ?></div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p style="color: var(--text-light); text-align: center; padding: 20px;">Belum ada tiket</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-refresh statistics every 30 seconds
        setInterval(function() {
            console.log('Dashboard auto-refresh...');
            // You can implement AJAX refresh here if needed
        }, 30000);

        // Add smooth scrolling for navigation
        document.querySelectorAll('.nav-card').forEach(card => {
            card.addEventListener('click', function(e) {
                // Add loading effect
                this.style.opacity = '0.7';
                setTimeout(() => {
                    this.style.opacity = '1';
                }, 200);
            });
        });

        // Welcome message
        console.log('🚊 Admin Dashboard Loaded Successfully!');
        console.log('📊 Statistics: Trains(<?= $stats['kereta']['total'] ?>), Schedules(<?= $stats['jadwal']['total'] ?>), Tickets(<?= $stats['tiket']['total_tiket'] ?>)');
    </script>
</body>
</html>