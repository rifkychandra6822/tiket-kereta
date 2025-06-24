<?php
// Riwayat Pemesanan Penumpang
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

$user_id = $_SESSION['user']['id'];

// Ambil id_penumpang dari id_pengguna
$penumpang_query = $conn->query("SELECT id FROM penumpang WHERE id_pengguna=$user_id");
$penumpang = $penumpang_query ? $penumpang_query->fetch_assoc() : null;
$id_penumpang = $penumpang ? $penumpang['id'] : 0;

// Filter berdasarkan status dan tanggal
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : '';

// Query dengan filter dan informasi lengkap sesuai database asli
$where_clause = "WHERE t.id_penumpang=$id_penumpang";
if ($filter_status) {
    $where_clause .= " AND t.status_tiket = '" . $conn->real_escape_string($filter_status) . "'";
}
if ($filter_tanggal) {
    $where_clause .= " AND jk.tanggal = '" . $conn->real_escape_string($filter_tanggal) . "'";
}

$tiket_query = "SELECT t.*, jk.tanggal, jk.waktu_berangkat, jk.waktu_tiba, 
                jk.stasiun_awal, jk.stasiun_tujuan, jk.tarif,
                k.nama_kereta, k.jenis_kereta, k.kelas,
                ks.nomor_kursi, g.nomor_gerbong,
                p.status as payment_status, p.metode as payment_method, p.tanggal as payment_date,
                pm.total as makanan_total, pm.konfirmasi as makanan_konfirmasi
                FROM tiket t 
                JOIN jadwal_kereta jk ON t.id_jadwal=jk.id 
                JOIN kereta k ON jk.id_kereta=k.id
                LEFT JOIN kursi ks ON t.id_kursi=ks.id
                LEFT JOIN gerbong g ON ks.id_gerbong=g.id
                LEFT JOIN pembayaran p ON t.id=p.id_tiket
                LEFT JOIN pesanan_makanan pm ON t.id=pm.id_tiket
                $where_clause
                ORDER BY t.id DESC";

$tiket_result = $conn->query($tiket_query);

// Statistik tiket sesuai database asli
$stats_query = "SELECT 
                COUNT(*) as total_tiket,
                SUM(CASE WHEN t.status_tiket = 'Lunas' THEN 1 ELSE 0 END) as tiket_lunas,
                SUM(CASE WHEN t.status_tiket = 'Pending' THEN 1 ELSE 0 END) as tiket_pending,
                SUM(CASE WHEN t.status_refund = 1 THEN 1 ELSE 0 END) as tiket_refund,
                SUM(CASE WHEN t.status_tiket = 'Lunas' THEN t.total_pembayaran ELSE 0 END) as total_pengeluaran
                FROM tiket t
                WHERE t.id_penumpang=$id_penumpang";

$stats = $conn->query($stats_query)->fetch_assoc();

// Function untuk menghitung durasi perjalanan
function hitungDurasi($waktu_berangkat, $waktu_tiba)
{
    $start = new DateTime($waktu_berangkat);
    $end = new DateTime($waktu_tiba);
    $interval = $start->diff($end);
    return $interval->format('%h jam %i menit');
}

// Function untuk mendapatkan class badge berdasarkan kelas kereta
function getClassBadge($kelas)
{
    switch (strtolower($kelas)) {
        case 'eksekutif':
            return 'badge-eksekutif';
        case 'bisnis':
            return 'badge-bisnis';
        case 'ekonomi':
            return 'badge-ekonomi';
        default:
            return 'badge-secondary';
    }
}

// Function untuk mendapatkan icon berdasarkan status
function getStatusIcon($status)
{
    switch (strtolower($status)) {
        case 'lunas':
            return 'bi-check-circle-fill';
        case 'pending':
            return 'bi-clock-fill';
        case 'batal':
            return 'bi-x-circle-fill';
        default:
            return 'bi-circle';
    }
}

// Function untuk mendapatkan icon berdasarkan jenis kereta
function getTrainIcon($jenis)
{
    switch (strtolower($jenis)) {
        case 'eksekutif':
            return 'bi-gem';
        case 'bisnis':
            return 'bi-lightning-charge';
        case 'ekonomi':
            return 'bi-train-front';
        default:
            return 'bi-train-front';
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Riwayat Pemesanan - Kereta Connect</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <style>
        /* Additional styles for history page - consistent dengan dashboard */
        .filter-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .history-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .history-card::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: var(--gradient-primary);
            border-radius: 0 4px 4px 0;
        }

        .history-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }

        .history-card.refund::before {
            background: linear-gradient(135deg, #ef4444, #f87171);
        }

        .history-card.pending::before {
            background: linear-gradient(135deg, #f59e0b, #fbbf24);
        }

        .route-timeline {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 1.5rem 0;
            position: relative;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid #e2e8f0;
        }

        .route-point {
            text-align: center;
            flex: 1;
        }

        .route-time {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-blue);
            margin-bottom: 0.5rem;
        }

        .route-station {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.25rem;
        }

        .route-date {
            font-size: 0.875rem;
            color: var(--text-light);
        }

        .route-connector {
            flex: 2;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 1rem;
        }

        .route-line {
            flex: 1;
            height: 2px;
            background: linear-gradient(90deg, var(--primary-blue), var(--secondary-blue));
            border-radius: 1px;
        }

        .route-train-icon {
            margin: 0 1rem;
            color: var(--primary-blue);
            font-size: 1.5rem;
            animation: float 2s ease-in-out infinite;
        }

        .ticket-details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin: 1.5rem 0;
        }

        .detail-item {
            text-align: center;
            padding: 1rem;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }

        .detail-icon {
            color: var(--primary-blue);
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            display: block;
        }

        .detail-value {
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.25rem;
        }

        .detail-label {
            font-size: 0.875rem;
            color: var(--text-light);
        }

        .ticket-actions {
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
            flex-wrap: wrap;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e2e8f0;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.875rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-lunas {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
            border: 1px solid #10b981;
        }

        .status-pending {
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
            border: 1px solid #f59e0b;
        }

        .status-refund {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: 1px solid #ef4444;
        }

        .train-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .train-info h5 {
            color: var(--primary-blue);
            margin-bottom: 0.5rem;
        }

        .train-badges {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .badge-eksekutif {
            background: linear-gradient(135deg, #7c3aed, #a855f7);
            color: white;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
        }

        .badge-bisnis {
            background: linear-gradient(135deg, #059669, #10b981);
            color: white;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
        }

        .badge-ekonomi {
            background: linear-gradient(135deg, #ea580c, #f97316);
            color: white;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
        }

        .badge-info {
            background: linear-gradient(135deg, #0ea5e9, #06b6d4);
            color: white;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--text-light);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .btn-pesan-tiket {
            background: var(--gradient-primary);
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-pesan-tiket:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            color: white;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .route-timeline {
                flex-direction: column;
                gap: 1rem;
            }

            .route-connector {
                transform: rotate(90deg);
                margin: 0.5rem 0;
            }

            .ticket-details-grid {
                grid-template-columns: 1fr;
            }

            .train-header {
                flex-direction: column;
                gap: 1rem;
            }

            .ticket-actions {
                justify-content: center;
            }

            .history-card {
                padding: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <!-- Navbar - sama dengan dashboard -->
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
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-house me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pesan_tiket.php">
                            <i class="bi bi-plus-circle me-1"></i>Pesan Tiket
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="riwayat.php">
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
            <!-- Header dengan train icon sama dengan dashboard -->
            <div class="welcome-card">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="fw-bold mb-2">
                            <i class="bi bi-clock-history me-2"></i>Riwayat Pemesanan Tiket
                        </h1>
                        <p class="mb-0 opacity-90">
                            Lihat dan kelola semua riwayat pemesanan tiket kereta Anda.
                            Akses tiket digital, informasi perjalanan, dan status pembayaran.
                        </p>
                    </div>
                    <div class="col-md-4 text-end d-none d-md-block">
                        <!-- Train Icon yang sama dengan dashboard -->
                        <div class="welcome-train-svg">
                            <svg viewBox="0 0 280 180" xmlns="http://www.w3.org/2000/svg">
                                <ellipse cx="140" cy="165" rx="100" ry="12" fill="rgba(30, 64, 175, 0.1)" />
                                <rect x="40" y="80" width="200" height="70" rx="16" fill="#1e40af" />
                                <rect x="60" y="60" width="160" height="25" rx="12" fill="#3b82f6" />
                                <rect x="70" y="95" width="25" height="20" rx="4" fill="rgba(255,255,255,0.95)"
                                    stroke="rgba(255,255,255,0.8)" stroke-width="1.5" />
                                <rect x="105" y="95" width="25" height="20" rx="4" fill="rgba(255,255,255,0.95)"
                                    stroke="rgba(255,255,255,0.8)" stroke-width="1.5" />
                                <rect x="140" y="95" width="25" height="20" rx="4" fill="rgba(255,255,255,0.95)"
                                    stroke="rgba(255,255,255,0.8)" stroke-width="1.5" />
                                <rect x="175" y="95" width="25" height="20" rx="4" fill="rgba(255,255,255,0.95)"
                                    stroke="rgba(255,255,255,0.8)" stroke-width="1.5" />
                                <path d="M240 80 Q260 80 260 115 Q260 150 240 150" fill="#1f2937" />
                                <g class="wheel-group">
                                    <circle cx="90" cy="165" r="16" fill="#374151" />
                                    <circle cx="90" cy="165" r="12" fill="#6b7280" />
                                    <circle cx="90" cy="165" r="6" fill="#9ca3af" />
                                </g>
                                <g class="wheel-group">
                                    <circle cx="140" cy="165" r="16" fill="#374151" />
                                    <circle cx="140" cy="165" r="12" fill="#6b7280" />
                                    <circle cx="140" cy="165" r="6" fill="#9ca3af" />
                                </g>
                                <g class="wheel-group">
                                    <circle cx="190" cy="165" r="16" fill="#374151" />
                                    <circle cx="190" cy="165" r="12" fill="#6b7280" />
                                    <circle cx="190" cy="165" r="6" fill="#9ca3af" />
                                </g>
                                <circle cx="260" cy="100" r="8" fill="#fbbf24" opacity="0.9" />
                                <rect x="70" y="125" width="35" height="4" fill="rgba(255,255,255,0.6)" rx="2" />
                                <rect x="115" y="125" width="35" height="4" fill="rgba(255,255,255,0.6)" rx="2" />
                                <rect x="160" y="125" width="35" height="4" fill="rgba(255,255,255,0.6)" rx="2" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards - sama dengan dashboard -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stats-card">
                        <div class="stats-icon stats-primary">
                            <i class="bi bi-ticket-perforated"></i>
                        </div>
                        <h3 class="fw-bold text-primary"><?= $stats['total_tiket']; ?></h3>
                        <p class="text-muted mb-0">Total Tiket</p>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stats-card">
                        <div class="stats-icon stats-success">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <h3 class="fw-bold text-success"><?= $stats['tiket_lunas']; ?></h3>
                        <p class="text-muted mb-0">Tiket Lunas</p>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stats-card">
                        <div class="stats-icon stats-warning">
                            <i class="bi bi-clock"></i>
                        </div>
                        <h3 class="fw-bold text-warning"><?= $stats['tiket_pending']; ?></h3>
                        <p class="text-muted mb-0">Tiket Pending</p>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stats-card">
                        <div class="stats-icon stats-info">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                        <h3 class="fw-bold text-info">Rp<?= number_format($stats['total_pengeluaran'], 0, ',', '.'); ?>
                        </h3>
                        <p class="text-muted mb-0">Total Pengeluaran</p>
                    </div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <h5 class="fw-bold mb-3">
                    <i class="bi bi-funnel me-2"></i>Filter Riwayat
                </h5>
                <form method="GET" action="">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="status" class="form-label fw-semibold">
                                <i class="bi bi-check-circle me-1"></i>Status Tiket
                            </label>
                            <select name="status" id="status" class="form-select">
                                <option value="">Semua Status</option>
                                <option value="Lunas" <?= ($filter_status == 'Lunas') ? 'selected' : ''; ?>>Lunas</option>
                                <option value="Pending" <?= ($filter_status == 'Pending') ? 'selected' : ''; ?>>Pending
                                </option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="tanggal" class="form-label fw-semibold">
                                <i class="bi bi-calendar me-1"></i>Tanggal Perjalanan
                            </label>
                            <input type="date" name="tanggal" id="tanggal" class="form-select"
                                value="<?= $filter_tanggal; ?>">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="bi bi-search me-2"></i>Filter
                            </button>
                            <a href="riwayat.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-clockwise me-2"></i>Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Ticket History -->
            <?php if ($tiket_result && $tiket_result->num_rows > 0): ?>
                <div class="results-section">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="fw-bold">
                            <i class="bi bi-list-ul me-2"></i>Riwayat Tiket
                        </h4>
                        <span class="badge bg-primary fs-6"><?= $tiket_result->num_rows; ?> tiket ditemukan</span>
                    </div>

                    <?php while ($row = $tiket_result->fetch_assoc()): ?>
                        <div
                            class="history-card <?= strtolower($row['status_tiket']); ?> <?= $row['status_refund'] ? 'refund' : ''; ?>">
                            <!-- Train Header -->
                            <div class="train-header">
                                <div class="train-info">
                                    <h5 class="fw-bold">
                                        <i class="<?= getTrainIcon($row['jenis_kereta']); ?> me-2"></i>
                                        <?= htmlspecialchars($row['nama_kereta']); ?>
                                    </h5>
                                    <div class="train-badges">
                                        <span class="<?= getClassBadge($row['kelas']); ?>">
                                            <?= ucfirst($row['kelas']); ?>
                                        </span>
                                        <span class="badge-info">
                                            <?= htmlspecialchars($row['jenis_kereta']); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="status-badge status-<?= strtolower($row['status_tiket']); ?>">
                                    <i class="<?= getStatusIcon($row['status_tiket']); ?>"></i>
                                    <?= $row['status_refund'] ? 'Refund' : ucfirst($row['status_tiket']); ?>
                                </div>
                            </div>

                            <!-- Route Timeline -->
                            <div class="route-timeline">
                                <div class="route-point">
                                    <div class="route-time"><?= date('H:i', strtotime($row['waktu_berangkat'])); ?></div>
                                    <div class="route-station"><?= htmlspecialchars($row['stasiun_awal']); ?></div>
                                    <div class="route-date"><?= date('d/m/Y', strtotime($row['tanggal'])); ?></div>
                                </div>

                                <div class="route-connector">
                                    <div class="route-line"></div>
                                    <i class="bi bi-train-front route-train-icon"></i>
                                    <div class="route-line"></div>
                                </div>

                                <div class="route-point">
                                    <div class="route-time"><?= date('H:i', strtotime($row['waktu_tiba'])); ?></div>
                                    <div class="route-station"><?= htmlspecialchars($row['stasiun_tujuan']); ?></div>
                                    <div class="route-date"><?= date('d/m/Y', strtotime($row['tanggal'])); ?></div>
                                </div>
                            </div>

                            <!-- Ticket Details -->
                            <div class="ticket-details-grid">
                                <div class="detail-item">
                                    <i class="bi bi-clock detail-icon"></i>
                                    <div class="detail-value"><?= hitungDurasi($row['waktu_berangkat'], $row['waktu_tiba']); ?>
                                    </div>
                                    <div class="detail-label">Durasi Perjalanan</div>
                                </div>

                                <?php if ($row['nomor_kursi']): ?>
                                    <div class="detail-item">
                                        <i class="bi bi-bookmark detail-icon"></i>
                                        <div class="detail-value">Kursi <?= htmlspecialchars($row['nomor_kursi']); ?></div>
                                        <div class="detail-label">
                                            <?= $row['nomor_gerbong'] ? 'Gerbong ' . htmlspecialchars($row['nomor_gerbong']) : 'Nomor Kursi'; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="detail-item">
                                    <i class="bi bi-calendar2-date detail-icon"></i>
                                    <div class="detail-value"><?= date('d/m/Y', strtotime($row['tanggal_pesan'])); ?></div>
                                    <div class="detail-label">Tanggal Pesan</div>
                                </div>

                                <div class="detail-item">
                                    <i class="bi bi-cash detail-icon"></i>
                                    <div class="detail-value">Rp<?= number_format($row['total_pembayaran'], 0, ',', '.'); ?>
                                    </div>
                                    <div class="detail-label">Total Pembayaran</div>
                                </div>

                                <?php if ($row['makanan_total']): ?>
                                    <div class="detail-item">
                                        <i class="bi bi-cup-hot detail-icon"></i>
                                        <div class="detail-value">Rp<?= number_format($row['makanan_total'], 0, ',', '.'); ?></div>
                                        <div class="detail-label">Makanan
                                            <?= $row['makanan_konfirmasi'] ? '(Dikonfirmasi)' : '(Pending)'; ?></div>
                                    </div>
                                <?php endif; ?>

                                <?php if ($row['payment_method']): ?>
                                    <div class="detail-item">
                                        <i class="bi bi-credit-card detail-icon"></i>
                                        <div class="detail-value"><?= ucfirst($row['payment_method']); ?></div>
                                        <div class="detail-label">Metode Pembayaran</div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Ticket Actions -->
                            <div class="ticket-actions">
                                <?php if ($row['status_tiket'] == 'Lunas' && $row['validasi_tiket']): ?>
                                    <a href="cetak_tiket.php?id=<?= $row['id']; ?>" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-printer me-1"></i>Cetak Tiket
                                    </a>
                                <?php endif; ?>

                                <?php if ($row['status_tiket'] == 'Lunas' && !$row['status_refund']): ?>
                                    <?php
                                    $jadwal_datetime = strtotime($row['tanggal'] . ' ' . $row['waktu_berangkat']);
                                    $can_refund = ($jadwal_datetime - time()) > 3600; // > 1 jam
                                    ?>
                                    <?php if ($can_refund): ?>
                                        <a href="refund.php?id=<?= $row['id']; ?>" class="btn btn-outline-danger btn-sm"
                                            onclick="return confirm('Yakin ingin melakukan refund tiket ini?')">
                                            <i class="bi bi-arrow-return-left me-1"></i>Refund
                                        </a>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php if ($row['status_tiket'] == 'Pending'): ?>
                                    <a href="bayar.php?id=<?= $row['id']; ?>" class="btn btn-success btn-sm">
                                        <i class="bi bi-credit-card me-1"></i>Bayar Sekarang
                                    </a>
                                <?php endif; ?>

                                <?php if ($row['status_tiket'] == 'Lunas' && !$row['makanan_total']): ?>
                                    <a href="pesan_makanan.php?tiket_id=<?= $row['id']; ?>" class="btn btn-outline-warning btn-sm">
                                        <i class="bi bi-cup-hot me-1"></i>Pesan Makanan
                                    </a>
                                <?php endif; ?>

                                <a href="detail_tiket.php?id=<?= $row['id']; ?>" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-eye me-1"></i>Detail
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>

            <?php else: ?>
                <!-- Empty State -->
                <div class="empty-state">
                    <i class="bi bi-ticket"></i>
                    <h5 class="mt-3">Belum Ada Riwayat Pemesanan</h5>
                    <p>Anda belum pernah memesan tiket. Mulai perjalanan pertama Anda!</p>
                    <a href="pesan_tiket.php" class="btn-pesan-tiket">
                        <i class="bi bi-plus-circle me-2"></i>Pesan Tiket Pertama
                    </a>
                </div>
            <?php endif; ?>

            <!-- Back Button -->
            <div class="text-center mt-4">
                <a href="dashboard.php" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-left me-2"></i>Kembali ke Dashboard
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Page load animation yang sama dengan dashboard
        window.addEventListener('load', function () {
            const cards = document.querySelectorAll('.stats-card, .history-card, .filter-section, .welcome-card');
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

        // Form enhancement
        document.addEventListener('DOMContentLoaded', function () {
            const statusFilter = document.getElementById('status');
            const tanggalFilter = document.getElementById('tanggal');

            // Optional: Auto-submit on filter change
            // statusFilter.addEventListener('change', function() {
            //     this.form.submit();
            // });
        });
    </script>
</body>

</html>