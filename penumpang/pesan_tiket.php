<?php
// Pesan Tiket (Cari Jadwal)
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

$jadwal = [];
$message = '';
$search_performed = false;

// Ambil id_penumpang dari id_pengguna
$user_id = $_SESSION['user']['id'];
$penumpang_query = $conn->query("SELECT id FROM penumpang WHERE id_pengguna=$user_id");
$penumpang = $penumpang_query ? $penumpang_query->fetch_assoc() : null;
$id_penumpang = $penumpang ? $penumpang['id'] : 0;

// Proses pencarian jadwal hanya jika form di-submit
if (isset($_GET['cari']) && !empty($_GET['stasiun_awal']) && !empty($_GET['stasiun_tujuan']) && !empty($_GET['tanggal'])) {
    $stasiun_awal = $conn->real_escape_string($_GET['stasiun_awal']);
    $stasiun_tujuan = $conn->real_escape_string($_GET['stasiun_tujuan']);
    $tanggal = $conn->real_escape_string($_GET['tanggal']);
    $search_performed = true;

    if ($stasiun_awal === $stasiun_tujuan) {
        $message = "Stasiun asal dan tujuan tidak boleh sama!";
    } else {
        // Query jadwal kereta dengan informasi lengkap dari database
        $query = "SELECT jk.*, k.nama_kereta, k.jenis_kereta, k.kelas, k.kapasitas,
                         (SELECT COUNT(*) FROM tiket t WHERE t.id_jadwal = jk.id) as kursi_terpesan,
                         (k.kapasitas - (SELECT COUNT(*) FROM tiket t WHERE t.id_jadwal = jk.id)) as kursi_tersisa
                  FROM jadwal_kereta jk 
                  JOIN kereta k ON jk.id_kereta = k.id 
                  WHERE jk.stasiun_awal = '$stasiun_awal' 
                    AND jk.stasiun_tujuan = '$stasiun_tujuan' 
                    AND jk.tanggal = '$tanggal' 
                    AND jk.status = 'Tersedia'
                    AND jk.tanggal >= CURDATE()
                  ORDER BY jk.waktu_berangkat ASC";

        $result = $conn->query($query);

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $jadwal[] = $row;
            }
        } else {
            $message = "Tidak ada jadwal kereta yang tersedia untuk rute dan tanggal yang dipilih.";
        }
    }
}

// Ambil daftar stasiun untuk dropdown
$stasiun_query = "SELECT DISTINCT stasiun_awal as nama_stasiun FROM jadwal_kereta 
                  UNION 
                  SELECT DISTINCT stasiun_tujuan as nama_stasiun FROM jadwal_kereta 
                  ORDER BY nama_stasiun";
$stasiun_result = $conn->query($stasiun_query);

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
            return 'badge-primary';
    }
}

// Function untuk mendapatkan icon berdasarkan jenis kereta
function getTrainIcon($jenis)
{
    switch (strtolower($jenis)) {
        case 'express':
            return 'bi-lightning-charge';
        case 'reguler':
            return 'bi-train-front';
        case 'eksekutif':
            return 'bi-gem';
        default:
            return 'bi-train-front';
    }
}

// Function untuk menghitung durasi perjalanan
function hitungDurasi($waktu_berangkat, $waktu_tiba)
{
    $start = new DateTime($waktu_berangkat);
    $end = new DateTime($waktu_tiba);
    $interval = $start->diff($end);
    return $interval->format('%h jam %i menit');
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Pesan Tiket - Kereta Connect</title>
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
        /* Custom styles untuk schedule cards yang rapi */
        .schedule-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .schedule-card::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: var(--gradient-primary);
            border-radius: 0 4px 4px 0;
        }

        .schedule-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }

        .train-header {
            display: flex;
            justify-content: between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
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

        .journey-section {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-radius: 12px;
            padding: 1.5rem;
            margin: 1rem 0;
            border: 1px solid #e2e8f0;
        }

        .route-timeline {
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
        }

        .route-point {
            text-align: center;
            flex: 1;
        }

        .route-time {
            font-size: 1.5rem;
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

        .duration-info {
            text-align: center;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
        }

        .duration-text {
            color: var(--text-light);
            font-size: 0.875rem;
        }

        .price-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
        }

        .price-info {
            display: flex;
            flex-direction: column;
        }

        .price-amount {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--success-green);
            margin-bottom: 0.25rem;
        }

        .price-label {
            font-size: 0.875rem;
            color: var(--text-light);
        }

        .availability-info {
            text-align: center;
            margin: 0 1rem;
        }

        .availability-indicator {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .availability-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }

        .availability-high {
            background: var(--success-green);
        }

        .availability-medium {
            background: var(--warning-yellow);
        }

        .availability-low {
            background: var(--danger-red);
        }

        .availability-text {
            font-size: 0.875rem;
            font-weight: 600;
        }

        .seats-remaining {
            font-size: 0.75rem;
            color: var(--text-light);
        }

        .btn-pilih-kursi {
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

        .btn-pilih-kursi:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .btn-sold-out {
            background: #6b7280;
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            cursor: not-allowed;
            opacity: 0.7;
        }

        /* Search form styling - consistent with dashboard */
        .search-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .form-control,
        .form-select {
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.1);
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

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .schedule-card {
                padding: 1rem;
            }

            .route-timeline {
                flex-direction: column;
                gap: 1rem;
            }

            .route-connector {
                transform: rotate(90deg);
                margin: 0.5rem 0;
            }

            .price-section {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .train-header {
                flex-direction: column;
                gap: 1rem;
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
                        <a class="nav-link active" href="pesan_tiket.php">
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
            <!-- Header dengan train icon -->
            <div class="welcome-card">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="fw-bold mb-2">
                            <i class="bi bi-search me-2"></i>Cari & Pesan Tiket Kereta
                        </h1>
                        <p class="mb-0 opacity-90">
                            Temukan jadwal kereta yang sesuai dengan perjalanan Anda.
                            Pilih dari berbagai kelas dan rute yang tersedia.
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

            <!-- Form Pencarian -->
            <div class="search-section">
                <h5 class="fw-bold mb-3">
                    <i class="bi bi-search me-2"></i>Cari Jadwal Kereta
                </h5>
                <form method="GET" action="">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="stasiun_awal" class="form-label fw-semibold">
                                <i class="bi bi-geo-alt me-1"></i>Stasiun Asal
                            </label>
                            <select class="form-select" id="stasiun_awal" name="stasiun_awal" required>
                                <option value="">Pilih Stasiun Asal</option>
                                <?php if ($stasiun_result): ?>
                                    <?php $stasiun_result->data_seek(0); ?>
                                    <?php while ($stasiun = $stasiun_result->fetch_assoc()): ?>
                                        <option value="<?= htmlspecialchars($stasiun['nama_stasiun']); ?>"
                                            <?= (isset($_GET['stasiun_awal']) && $_GET['stasiun_awal'] == $stasiun['nama_stasiun']) ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($stasiun['nama_stasiun']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="stasiun_tujuan" class="form-label fw-semibold">
                                <i class="bi bi-geo me-1"></i>Stasiun Tujuan
                            </label>
                            <select class="form-select" id="stasiun_tujuan" name="stasiun_tujuan" required>
                                <option value="">Pilih Stasiun Tujuan</option>
                                <?php if ($stasiun_result): ?>
                                    <?php $stasiun_result->data_seek(0); ?>
                                    <?php while ($stasiun = $stasiun_result->fetch_assoc()): ?>
                                        <option value="<?= htmlspecialchars($stasiun['nama_stasiun']); ?>"
                                            <?= (isset($_GET['stasiun_tujuan']) && $_GET['stasiun_tujuan'] == $stasiun['nama_stasiun']) ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($stasiun['nama_stasiun']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="tanggal" class="form-label fw-semibold">
                                <i class="bi bi-calendar me-1"></i>Tanggal Keberangkatan
                            </label>
                            <input type="date" class="form-control" id="tanggal" name="tanggal"
                                min="<?= date('Y-m-d'); ?>"
                                value="<?= isset($_GET['tanggal']) ? htmlspecialchars($_GET['tanggal']) : ''; ?>"
                                required>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" name="cari" class="btn btn-primary w-100">
                                <i class="bi bi-search me-2"></i>Cari Jadwal
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Hasil Pencarian -->
            <?php if (!empty($message) && $search_performed): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <i class="bi bi-info-circle me-2"></i>
                    <?= htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($jadwal)): ?>
                <div class="results-section">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="fw-bold">
                            <i class="bi bi-list-ul me-2"></i>Jadwal Tersedia
                        </h4>
                        <span class="badge bg-primary fs-6"><?= count($jadwal); ?> jadwal ditemukan</span>
                    </div>

                    <div class="schedules-grid">
                        <?php foreach ($jadwal as $j): ?>
                            <?php
                            $availability_percentage = ($j['kursi_tersisa'] / $j['kapasitas']) * 100;
                            $availability_class = '';
                            $availability_text = '';

                            if ($availability_percentage > 70) {
                                $availability_class = 'availability-high';
                                $availability_text = 'Tersedia';
                            } elseif ($availability_percentage > 30) {
                                $availability_class = 'availability-medium';
                                $availability_text = 'Terbatas';
                            } else {
                                $availability_class = 'availability-low';
                                $availability_text = 'Hampir Penuh';
                            }
                            ?>
                            <div class="schedule-card">
                                <!-- Train Header -->
                                <div class="train-header">
                                    <div class="train-info">
                                        <h5 class="fw-bold">
                                            <i class="<?= getTrainIcon($j['jenis_kereta']); ?> me-2"></i>
                                            <?= htmlspecialchars($j['nama_kereta']); ?>
                                        </h5>
                                        <div class="train-badges">
                                            <span class="badge-<?= strtolower($j['kelas']); ?>">
                                                <?= ucfirst($j['kelas']); ?>
                                            </span>
                                            <span class="badge-info">
                                                <?= htmlspecialchars($j['jenis_kereta']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Journey Section -->
                                <div class="journey-section">
                                    <div class="route-timeline">
                                        <div class="route-point">
                                            <div class="route-time"><?= date('H:i', strtotime($j['waktu_berangkat'])); ?></div>
                                            <div class="route-station"><?= htmlspecialchars($j['stasiun_awal']); ?></div>
                                            <div class="route-date"><?= date('d/m/Y', strtotime($j['tanggal'])); ?></div>
                                        </div>

                                        <div class="route-connector">
                                            <div class="route-line"></div>
                                            <i class="bi bi-train-front route-train-icon"></i>
                                            <div class="route-line"></div>
                                        </div>

                                        <div class="route-point">
                                            <div class="route-time"><?= date('H:i', strtotime($j['waktu_tiba'])); ?></div>
                                            <div class="route-station"><?= htmlspecialchars($j['stasiun_tujuan']); ?></div>
                                            <div class="route-date"><?= date('d/m/Y', strtotime($j['tanggal'])); ?></div>
                                        </div>
                                    </div>

                                    <div class="duration-info">
                                        <div class="duration-text">
                                            <i class="bi bi-clock me-1"></i>
                                            Durasi: <?= hitungDurasi($j['waktu_berangkat'], $j['waktu_tiba']); ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Price and Action Section -->
                                <div class="price-section">
                                    <div class="price-info">
                                        <div class="price-amount">Rp<?= number_format($j['tarif'], 0, ',', '.'); ?></div>
                                        <div class="price-label">per penumpang</div>
                                    </div>

                                    <div class="availability-info">
                                        <div class="availability-indicator">
                                            <div class="availability-dot <?= $availability_class; ?>"></div>
                                            <span class="availability-text"><?= $availability_text; ?></span>
                                        </div>
                                        <div class="seats-remaining"><?= $j['kursi_tersisa']; ?> dari <?= $j['kapasitas']; ?>
                                            kursi tersisa</div>
                                    </div>

                                    <div class="action-section">
                                        <?php if ($j['kursi_tersisa'] > 0): ?>
                                            <a href="pilih_kursi.php?jadwal_id=<?= $j['id']; ?>" class="btn-pilih-kursi">
                                                <i class="bi bi-person-plus"></i>
                                                Pilih Kursi
                                            </a>
                                        <?php else: ?>
                                            <button class="btn-sold-out" disabled>
                                                <i class="bi bi-x-circle"></i>
                                                Kursi Habis
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php elseif ($search_performed): ?>
                <div class="empty-state">
                    <i class="bi bi-search"></i>
                    <h5>Tidak ada jadwal ditemukan</h5>
                    <p>Maaf, tidak ada jadwal kereta yang tersedia untuk rute dan tanggal yang Anda pilih.</p>
                    <p class="small">Coba ubah kriteria pencarian atau pilih tanggal lain.</p>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="bi bi-calendar-search"></i>
                    <h5>Cari Jadwal Kereta</h5>
                    <p>Gunakan form pencarian di atas untuk menemukan jadwal kereta yang sesuai dengan perjalanan Anda.</p>
                    <p class="small">Isi stasiun asal, tujuan, dan tanggal keberangkatan untuk melihat jadwal yang tersedia.
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-submit form on date change
        document.getElementById('tanggal').addEventListener('change', function () {
            if (document.getElementById('stasiun_awal').value && document.getElementById('stasiun_tujuan').value) {
                document.querySelector('form').submit();
            }
        });

        // Prevent same station selection
        document.getElementById('stasiun_awal').addEventListener('change', function () {
            const tujuan = document.getElementById('stasiun_tujuan');
            if (this.value === tujuan.value && tujuan.value !== '') {
                alert('Stasiun asal dan tujuan tidak boleh sama!');
                this.value = '';
            }
        });

        document.getElementById('stasiun_tujuan').addEventListener('change', function () {
            const asal = document.getElementById('stasiun_awal');
            if (this.value === asal.value && asal.value !== '') {
                alert('Stasiun asal dan tujuan tidak boleh sama!');
                this.value = '';
            }
        });

        // Page load animation
        window.addEventListener('load', function () {
            const cards = document.querySelectorAll('.schedule-card, .search-section, .welcome-card');
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
    </script>
</body>

</html>