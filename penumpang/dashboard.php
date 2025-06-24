<?php
// Dashboard Penumpang
session_start();
if (!isset($_SESSION['user']))
    header('Location: login.php');
include '../includes/db.php';

$user = $_SESSION['user'];
// Prioritas nama: nama lengkap > username > email (tanpa @domain)
$nama_display = '';
if (!empty($user['nama']) && $user['nama'] !== $user['email']) {
    $nama_display = $user['nama'];
} else if (!empty($user['username'])) {
    $nama_display = $user['username'];
} else {
    $nama_display = explode('@', $user['email'])[0];
}

// Ambil statistik penumpang dari database
$user_id = $_SESSION['user']['id'];
$penumpang = $conn->query("SELECT id FROM penumpang WHERE id_pengguna=$user_id")->fetch_assoc();
$id_penumpang = $penumpang ? $penumpang['id'] : 0;

// Statistik tiket
$total_tiket = $conn->query("SELECT COUNT(*) as total FROM tiket WHERE id_penumpang=$id_penumpang")->fetch_assoc()['total'];
$tiket_lunas = $conn->query("SELECT COUNT(*) as total FROM tiket WHERE id_penumpang=$id_penumpang AND status_tiket='Lunas'")->fetch_assoc()['total'];
$tiket_pending = $conn->query("SELECT COUNT(*) as total FROM tiket WHERE id_penumpang=$id_penumpang AND status_tiket='Pending'")->fetch_assoc()['total'];
$total_pembayaran = $conn->query("SELECT SUM(total_pembayaran) as total FROM tiket WHERE id_penumpang=$id_penumpang AND status_tiket='Lunas'")->fetch_assoc()['total'] ?? 0;

// Tiket terdekat
$tiket_terdekat = $conn->query("
    SELECT t.*, jk.tanggal, jk.waktu_berangkat, jk.stasiun_awal, jk.stasiun_tujuan, k.nama_kereta, ks.nomor_kursi 
    FROM tiket t 
    JOIN jadwal_kereta jk ON t.id_jadwal=jk.id 
    JOIN kereta k ON jk.id_kereta=k.id 
    JOIN kursi ks ON t.id_kursi=ks.id 
    WHERE t.id_penumpang=$id_penumpang AND jk.tanggal >= CURDATE() 
    ORDER BY jk.tanggal ASC, jk.waktu_berangkat ASC 
    LIMIT 3
");

// Jadwal kereta hari ini
$jadwal_hari_ini = $conn->query("
    SELECT jk.*, k.nama_kereta 
    FROM jadwal_kereta jk 
    JOIN kereta k ON jk.id_kereta=k.id 
    WHERE jk.tanggal = CURDATE() AND jk.status = 'Tersedia'
    ORDER BY jk.waktu_berangkat ASC 
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Dashboard - Kereta Connect</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-custom sticky-top">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-train-front me-2"></i>Kereta Connect
            </a>

            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="bi bi-house me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pesan_tiket.php">
                            <i class="bi bi-plus-circle me-1"></i>Pesan Tiket
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="riwayat.php">
                            <i class="bi bi-clock-history me-1"></i>Riwayat
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pesan_makanan.php">
                            <i class="bi bi-cup-hot me-1"></i>Pesan Makanan
                        </a>
                    </li>
                </ul>

                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button"
                            data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-2"></i>
                            <?= htmlspecialchars($nama_display); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profil.php">
                                    <i class="bi bi-person me-2"></i>Profil Saya</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item text-danger" href="../logout.php">
                                    <i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="container">
            <!-- Welcome Section -->
            <div class="welcome-card">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="fw-bold mb-2">
                            <i class="bi bi-sun me-2"></i>Selamat Datang Kembali!
                        </h1>
                        <h3 class="fw-semibold mb-3"><?= htmlspecialchars($nama_display); ?></h3>
                        <p class="mb-0 opacity-90">
                            Kelola perjalanan kereta Anda dengan mudah dan nyaman.
                            Nikmati pengalaman pemesanan tiket yang modern dan efisien.
                        </p>
                    </div>
                    <div class="col-md-4 text-end d-none d-md-block">
                        <!-- Train Icon dengan ukuran yang lebih besar dan animasi -->
                        <div class="welcome-train-svg">
                            <svg viewBox="0 0 280 180" xmlns="http://www.w3.org/2000/svg">
                                <!-- Train shadow -->
                                <ellipse cx="140" cy="165" rx="100" ry="12" fill="rgba(30, 64, 175, 0.1)"
                                    class="train-shadow" />

                                <!-- Train body dengan warna biru navbar -->
                                <rect x="40" y="80" width="200" height="70" rx="16" fill="#1e40af"
                                    class="train-main-body" />

                                <!-- Train roof -->
                                <rect x="60" y="60" width="160" height="25" rx="12" fill="#3b82f6" class="train-roof" />

                                <!-- Windows -->
                                <rect x="70" y="95" width="25" height="20" rx="4" fill="rgba(255,255,255,0.95)"
                                    stroke="rgba(255,255,255,0.8)" stroke-width="1.5" class="train-window" />
                                <rect x="105" y="95" width="25" height="20" rx="4" fill="rgba(255,255,255,0.95)"
                                    stroke="rgba(255,255,255,0.8)" stroke-width="1.5" class="train-window" />
                                <rect x="140" y="95" width="25" height="20" rx="4" fill="rgba(255,255,255,0.95)"
                                    stroke="rgba(255,255,255,0.8)" stroke-width="1.5" class="train-window" />
                                <rect x="175" y="95" width="25" height="20" rx="4" fill="rgba(255,255,255,0.95)"
                                    stroke="rgba(255,255,255,0.8)" stroke-width="1.5" class="train-window" />

                                <!-- Front of train -->
                                <path d="M240 80 Q260 80 260 115 Q260 150 240 150" fill="#1f2937" class="train-front" />

                                <!-- Wheels dengan animasi putar -->
                                <g class="wheel-group">
                                    <circle cx="90" cy="165" r="16" fill="#374151" />
                                    <circle cx="90" cy="165" r="12" fill="#6b7280" class="wheel" />
                                    <circle cx="90" cy="165" r="6" fill="#9ca3af" />
                                    <line x1="90" y1="155" x2="90" y2="175" stroke="#374151" stroke-width="2"
                                        class="wheel-spoke" />
                                    <line x1="80" y1="165" x2="100" y2="165" stroke="#374151" stroke-width="2"
                                        class="wheel-spoke" />
                                </g>

                                <g class="wheel-group">
                                    <circle cx="140" cy="165" r="16" fill="#374151" />
                                    <circle cx="140" cy="165" r="12" fill="#6b7280" class="wheel" />
                                    <circle cx="140" cy="165" r="6" fill="#9ca3af" />
                                    <line x1="140" y1="155" x2="140" y2="175" stroke="#374151" stroke-width="2"
                                        class="wheel-spoke" />
                                    <line x1="130" y1="165" x2="150" y2="165" stroke="#374151" stroke-width="2"
                                        class="wheel-spoke" />
                                </g>

                                <g class="wheel-group">
                                    <circle cx="190" cy="165" r="16" fill="#374151" />
                                    <circle cx="190" cy="165" r="12" fill="#6b7280" class="wheel" />
                                    <circle cx="190" cy="165" r="6" fill="#9ca3af" />
                                    <line x1="190" y1="155" x2="190" y2="175" stroke="#374151" stroke-width="2"
                                        class="wheel-spoke" />
                                    <line x1="180" y1="165" x2="200" y2="165" stroke="#374151" stroke-width="2"
                                        class="wheel-spoke" />
                                </g>

                                <!-- Front light -->
                                <circle cx="260" cy="100" r="8" fill="#fbbf24" opacity="0.9" class="train-light" />

                                <!-- Details -->
                                <rect x="70" y="125" width="35" height="4" fill="rgba(255,255,255,0.6)" rx="2" />
                                <rect x="115" y="125" width="35" height="4" fill="rgba(255,255,255,0.6)" rx="2" />
                                <rect x="160" y="125" width="35" height="4" fill="rgba(255,255,255,0.6)" rx="2" />

                                <!-- Smoke effects -->
                                <g class="smoke-effects">
                                    <circle cx="245" cy="50" r="4" fill="rgba(255,255,255,0.3)" class="smoke-puff"
                                        style="animation: smokeFloat 2s ease-in-out infinite;" />
                                    <circle cx="250" cy="45" r="3.5" fill="rgba(255,255,255,0.25)" class="smoke-puff"
                                        style="animation: smokeFloat 2s ease-in-out infinite 0.3s;" />
                                    <circle cx="255" cy="40" r="3" fill="rgba(255,255,255,0.2)" class="smoke-puff"
                                        style="animation: smokeFloat 2s ease-in-out infinite 0.6s;" />
                                </g>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stats-card">
                        <div class="stats-icon stats-primary">
                            <i class="bi bi-ticket-perforated"></i>
                        </div>
                        <h3 class="fw-bold text-primary"><?= $total_tiket; ?></h3>
                        <p class="text-muted mb-0">Total Tiket</p>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stats-card">
                        <div class="stats-icon stats-success">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <h3 class="fw-bold text-success"><?= $tiket_lunas; ?></h3>
                        <p class="text-muted mb-0">Tiket Lunas</p>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stats-card">
                        <div class="stats-icon stats-warning">
                            <i class="bi bi-clock"></i>
                        </div>
                        <h3 class="fw-bold text-warning"><?= $tiket_pending; ?></h3>
                        <p class="text-muted mb-0">Tiket Pending</p>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stats-card">
                        <div class="stats-icon stats-info">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                        <h3 class="fw-bold text-info">Rp<?= number_format($total_pembayaran, 0, ',', '.'); ?></h3>
                        <p class="text-muted mb-0">Total Pengeluaran</p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <h4 class="section-title">
                <i class="bi bi-lightning-charge"></i>Aksi Cepat
            </h4>
            <div class="row mb-5">
                <div class="col-lg-4 col-md-6 mb-3">
                    <div class="quick-action-card">
                        <i class="bi bi-plus-circle-fill quick-action-icon"></i>
                        <h5 class="fw-bold mb-3">Pesan Tiket Baru</h5>
                        <p class="text-muted mb-4">Cari dan pesan tiket kereta untuk perjalanan Anda</p>
                        <a href="pesan_tiket.php" class="btn btn-primary-custom">
                            <i class="bi bi-search me-2"></i>Mulai Pencarian
                        </a>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6 mb-3">
                    <div class="quick-action-card">
                        <i class="bi bi-clock-history quick-action-icon"></i>
                        <h5 class="fw-bold mb-3">Riwayat Perjalanan</h5>
                        <p class="text-muted mb-4">Lihat semua tiket dan riwayat perjalanan Anda</p>
                        <a href="riwayat.php" class="btn btn-outline-primary">
                            <i class="bi bi-list-ul me-2"></i>Lihat Riwayat
                        </a>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6 mb-3">
                    <div class="quick-action-card">
                        <i class="bi bi-person-gear quick-action-icon"></i>
                        <h5 class="fw-bold mb-3">Kelola Profil</h5>
                        <p class="text-muted mb-4">Update informasi personal dan preferensi akun</p>
                        <a href="profil.php" class="btn btn-outline-secondary">
                            <i class="bi bi-person me-2"></i>Edit Profil
                        </a>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Tiket Terdekat -->
                <div class="col-lg-6 mb-4">
                    <h4 class="section-title">
                        <i class="bi bi-calendar-event"></i>Perjalanan Mendatang
                    </h4>
                    <?php if ($tiket_terdekat && $tiket_terdekat->num_rows > 0): ?>
                        <?php while ($tiket = $tiket_terdekat->fetch_assoc()): ?>
                            <div class="ticket-card">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="fw-bold text-primary mb-0"><?= htmlspecialchars($tiket['nama_kereta']); ?></h6>
                                    <span class="badge badge-<?= strtolower($tiket['status_tiket']); ?> badge-status">
                                        <?= ucfirst($tiket['status_tiket']); ?>
                                    </span>
                                </div>
                                <p class="mb-1">
                                    <i class="bi bi-geo-alt me-1 text-primary"></i>
                                    <?= htmlspecialchars($tiket['stasiun_awal']); ?> →
                                    <?= htmlspecialchars($tiket['stasiun_tujuan']); ?>
                                </p>
                                <p class="mb-1">
                                    <i class="bi bi-calendar me-1 text-success"></i>
                                    <?= date('d/m/Y', strtotime($tiket['tanggal'])); ?> • <?= $tiket['waktu_berangkat']; ?>
                                </p>
                                <p class="mb-2">
                                    <i class="bi bi-bookmark me-1 text-warning"></i>
                                    Kursi <?= $tiket['nomor_kursi']; ?>
                                </p>
                                <div class="d-flex gap-2">
                                    <a href="cetak_tiket.php?id=<?= $tiket['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-printer me-1"></i>Cetak
                                    </a>
                                    <span class="text-success fw-semibold">
                                        Rp<?= number_format($tiket['total_pembayaran'], 0, ',', '.'); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-calendar-x" style="font-size: 3rem; color: var(--text-light);"></i>
                            <h6 class="mt-3 text-muted">Belum ada perjalanan mendatang</h6>
                            <p class="text-muted">Pesan tiket sekarang untuk perjalanan Anda</p>
                            <a href="pesan_tiket.php" class="btn-pesan-tiket">
                                <i class="bi bi-plus"></i> Pesan Tiket
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Jadwal Hari Ini -->
                <div class="col-lg-6 mb-4">
                    <h4 class="section-title">
                        <i class="bi bi-train-front"></i>Jadwal Hari Ini
                    </h4>
                    <?php if ($jadwal_hari_ini && $jadwal_hari_ini->num_rows > 0): ?>
                        <?php while ($jadwal = $jadwal_hari_ini->fetch_assoc()): ?>
                            <div class="schedule-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="fw-bold mb-1"><?= htmlspecialchars($jadwal['nama_kereta']); ?></h6>
                                        <p class="text-muted mb-1 small">
                                            <?= htmlspecialchars($jadwal['stasiun_awal']); ?> →
                                            <?= htmlspecialchars($jadwal['stasiun_tujuan']); ?>
                                        </p>
                                        <p class="mb-0 small">
                                            <i class="bi bi-clock me-1"></i>
                                            <?= $jadwal['waktu_berangkat']; ?> - <?= $jadwal['waktu_tiba']; ?>
                                        </p>
                                    </div>
                                    <div class="text-end">
                                        <p class="fw-bold text-success mb-1">
                                            Rp<?= number_format($jadwal['tarif'], 0, ',', '.'); ?>
                                        </p>
                                        <a href="pesan_tiket.php" class="btn btn-sm btn-outline-primary">
                                            Pesan
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-train-front" style="font-size: 3rem; color: var(--text-light);"></i>
                            <h6 class="mt-3 text-muted">Tidak ada jadwal hari ini</h6>
                            <p class="text-muted">Cek jadwal untuk tanggal lain</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Page load animation
        window.addEventListener('load', function () {
            const cards = document.querySelectorAll('.stats-card, .quick-action-card, .ticket-card, .schedule-item');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';

                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });

        // Real-time clock (optional)
        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('id-ID');
            const dateString = now.toLocaleDateString('id-ID');

            // You can add a clock element if needed
        }

        setInterval(updateClock, 1000);
    </script>
</body>

</html>