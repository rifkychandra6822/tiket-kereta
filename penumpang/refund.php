<?php
// Refund Tiket
session_start();
if (!isset($_SESSION['user']))
    header('Location: login.php');
include '../includes/db.php';
if (!isset($_GET['id']))
    header('Location: riwayat.php');

$id = intval($_GET['id']);
$user_id = $_SESSION['user']['id'];

// Ambil id_penumpang dari id_pengguna
$penumpang = $conn->query("SELECT id FROM penumpang WHERE id_pengguna=$user_id")->fetch_assoc();
$id_penumpang = $penumpang ? $penumpang['id'] : 0;

// Ambil detail tiket lengkap dengan relasi
$tiket_query = "SELECT t.*, jk.tanggal, jk.waktu_berangkat, jk.waktu_tiba, 
                jk.stasiun_awal, jk.stasiun_tujuan, k.nama_kereta, k.jenis_kereta, 
                ks.nomor_kursi, g.nomor_gerbong, p.status as payment_status
                FROM tiket t 
                JOIN jadwal_kereta jk ON t.id_jadwal=jk.id 
                JOIN kereta k ON jk.id_kereta=k.id
                JOIN kursi ks ON t.id_kursi=ks.id
                JOIN gerbong g ON ks.id_gerbong=g.id
                LEFT JOIN pembayaran p ON t.id=p.id_tiket
                WHERE t.id=$id AND t.id_penumpang=$id_penumpang";

$tiket = $conn->query($tiket_query)->fetch_assoc();

if (!$tiket) {
    header('Location: riwayat.php?error=tiket_not_found');
    exit;
}

$jadwal_datetime = strtotime($tiket['tanggal'] . ' ' . $tiket['waktu_berangkat']);
$now = time();
$time_difference = $jadwal_datetime - $now;
$boleh_refund = $time_difference > 3600; // >1 jam sebelum berangkat
$dapat_refund = $boleh_refund && $tiket['status_tiket'] == 'Lunas' && !$tiket['status_refund'];

$message = '';
$refund_processed = false;

// Proses refund
if (isset($_POST['process_refund']) && $dapat_refund) {
    $alasan_refund = $conn->real_escape_string($_POST['alasan_refund']);

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Update status tiket
        $conn->query("UPDATE tiket SET status_tiket='Refund', status_refund=1 WHERE id=$id");

        // Update status kursi menjadi tersedia
        $conn->query("UPDATE kursi SET status='Tersedia' WHERE id={$tiket['id_kursi']}");

        // Update status pembayaran jika ada
        if ($tiket['payment_status']) {
            $conn->query("UPDATE pembayaran SET status='Refund', refund=1 WHERE id_tiket=$id");
        }

        // Insert notifikasi refund
        $tanggal_now = date('Y-m-d H:i:s');
        $judul_notif = "Refund Tiket Berhasil";
        $pesan_notif = "Tiket {$tiket['nama_kereta']} untuk tanggal " . date('d/m/Y', strtotime($tiket['tanggal'])) . " telah berhasil direfund. Alasan: $alasan_refund";

        $conn->query("INSERT INTO notifikasi (id_pengguna, judul, pesan, tanggal, status, dari_sistem) 
                      VALUES ($user_id, '$judul_notif', '$pesan_notif', '$tanggal_now', 0, 1)");

        $conn->commit();
        $refund_processed = true;

        $message = '<div class="alert alert-success alert-custom">
                        <i class="bi bi-check-circle me-2"></i>
                        Tiket berhasil direfund! Dana akan dikembalikan dalam 3-5 hari kerja.
                    </div>';
    } catch (Exception $e) {
        $conn->rollback();
        $message = '<div class="alert alert-danger alert-custom">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Terjadi kesalahan saat memproses refund. Silakan coba lagi.
                    </div>';
    }
} elseif (isset($_POST['process_refund']) && !$dapat_refund) {
    if (!$boleh_refund) {
        $message = '<div class="alert alert-danger alert-custom">
                        <i class="bi bi-clock me-2"></i>
                        Refund hanya dapat dilakukan minimal 1 jam sebelum keberangkatan.
                    </div>';
    } else {
        $message = '<div class="alert alert-warning alert-custom">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Tiket ini tidak dapat direfund karena sudah direfund atau belum lunas.
                    </div>';
    }
}

// Calculate refund amount (bisa disesuaikan dengan kebijakan)
$refund_percentage = 100;
if ($time_difference < 86400) { // < 24 jam
    $refund_percentage = 50;
} elseif ($time_difference < 172800) { // < 48 jam
    $refund_percentage = 75;
}
$refund_amount = ($tiket['total_pembayaran'] * $refund_percentage) / 100;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Refund Tiket - Kereta Connect</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
        }

        .refund-container {
            min-height: 100vh;
            padding-top: 20px;
        }

        .ticket-detail-card {
            background: var(--white);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-soft);
            border: none;
        }

        .ticket-header {
            background: var(--gradient-primary);
            color: white;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 25px;
            position: relative;
            overflow: hidden;
        }

        .ticket-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 1px, transparent 1px);
            background-size: 30px 30px;
            animation: float 20s infinite linear;
        }

        .refund-form-card {
            background: var(--white);
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--shadow-soft);
            border: none;
        }

        .refund-policy {
            background: rgba(96, 165, 250, 0.1);
            border-left: 4px solid var(--accent-blue);
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }

        .refund-amount {
            background: rgba(16, 185, 129, 0.1);
            border: 2px solid var(--success);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }

        .warning-box {
            background: rgba(245, 158, 11, 0.1);
            border: 2px solid var(--warning);
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
        }

        .danger-box {
            background: rgba(239, 68, 68, 0.1);
            border: 2px solid var(--danger);
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
        }

        .ticket-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .info-item {
            text-align: center;
            padding: 15px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
        }

        .countdown {
            font-family: 'Courier New', monospace;
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--warning);
        }

        .navbar-custom {
            background: var(--white);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }

        .navbar-brand {
            color: var(--primary-blue) !important;
            font-weight: 700;
            font-size: 1.5rem;
        }

        .nav-link {
            color: var(--text-dark) !important;
            font-weight: 500;
        }

        .nav-link:hover {
            color: var(--primary-blue) !important;
        }

        .section-title {
            color: var(--text-dark);
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        @media (max-width: 768px) {

            .ticket-detail-card,
            .refund-form-card {
                padding: 20px;
                border-radius: 16px;
            }

            .ticket-header {
                padding: 20px;
                text-align: center;
            }
        }
    </style>
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
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-house me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="riwayat.php">
                            <i class="bi bi-clock-history me-1"></i>Riwayat
                        </a>
                    </li>
                </ul>

                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button"
                            data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-2"></i>
                            <?= htmlspecialchars($_SESSION['user']['nama'] ?? explode('@', $_SESSION['user']['email'])[0]); ?>
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

    <div class="refund-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <!-- Page Header -->
                    <h2 class="section-title mb-4">
                        <i class="bi bi-arrow-return-left"></i>Refund Tiket
                    </h2>

                    <?= $message; ?>

                    <!-- Ticket Details -->
                    <div class="ticket-detail-card">
                        <div class="ticket-header">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h4 class="fw-bold mb-2">
                                        <i class="bi bi-ticket-detailed me-2"></i>
                                        <?= htmlspecialchars($tiket['nama_kereta']); ?>
                                    </h4>
                                    <p class="mb-1 opacity-90">
                                        <i class="bi bi-geo-alt me-2"></i>
                                        <?= htmlspecialchars($tiket['stasiun_awal']); ?> →
                                        <?= htmlspecialchars($tiket['stasiun_tujuan']); ?>
                                    </p>
                                    <p class="mb-0 opacity-90">
                                        <i class="bi bi-calendar me-2"></i>
                                        <?= date('d F Y', strtotime($tiket['tanggal'])); ?> •
                                        <?= $tiket['waktu_berangkat']; ?>
                                    </p>
                                </div>
                                <div class="col-md-4 text-end d-none d-md-block">
                                    <div class="h4 fw-bold mb-1">
                                        Rp<?= number_format($tiket['total_pembayaran'], 0, ',', '.'); ?></div>
                                    <small class="opacity-90">Total Pembayaran</small>
                                </div>
                            </div>
                        </div>

                        <div class="ticket-info-grid">
                            <div class="info-item">
                                <i class="bi bi-bookmark text-primary" style="font-size: 1.5rem;"></i>
                                <h6 class="fw-bold mt-2 mb-1">Kursi <?= htmlspecialchars($tiket['nomor_kursi']); ?></h6>
                                <small class="text-muted">Gerbong
                                    <?= htmlspecialchars($tiket['nomor_gerbong']); ?></small>
                            </div>

                            <div class="info-item">
                                <i class="bi bi-star text-warning" style="font-size: 1.5rem;"></i>
                                <h6 class="fw-bold mt-2 mb-1"><?= htmlspecialchars($tiket['jenis_kereta']); ?></h6>
                                <small class="text-muted">Jenis Kereta</small>
                            </div>

                            <div class="info-item">
                                <i class="bi bi-shield-check text-success" style="font-size: 1.5rem;"></i>
                                <h6 class="fw-bold mt-2 mb-1"><?= ucfirst($tiket['status_tiket']); ?></h6>
                                <small class="text-muted">Status Tiket</small>
                            </div>

                            <div class="info-item">
                                <i class="bi bi-clock text-info" style="font-size: 1.5rem;"></i>
                                <h6 class="fw-bold mt-2 mb-1 countdown" id="countdown">--:--:--</h6>
                                <small class="text-muted">Waktu Tersisa</small>
                            </div>
                        </div>
                    </div>

                    <?php if (!$refund_processed): ?>
                        <!-- Refund Form -->
                        <div class="refund-form-card">
                            <h5 class="section-title">
                                <i class="bi bi-cash-stack"></i>Proses Refund
                            </h5>

                            <?php if ($dapat_refund): ?>
                                <!-- Refund Amount Display -->
                                <div class="refund-amount">
                                    <h6 class="fw-bold text-success mb-2">
                                        <i class="bi bi-cash me-2"></i>Jumlah Refund
                                    </h6>
                                    <div class="h3 fw-bold text-success">
                                        Rp<?= number_format($refund_amount, 0, ',', '.'); ?>
                                    </div>
                                    <small class="text-muted">
                                        (<?= $refund_percentage; ?>% dari total pembayaran)
                                    </small>
                                </div>

                                <!-- Refund Policy -->
                                <div class="refund-policy">
                                    <h6 class="fw-bold text-primary mb-3">
                                        <i class="bi bi-info-circle me-2"></i>Kebijakan Refund
                                    </h6>
                                    <ul class="mb-0">
                                        <li>Refund 100% jika dibatalkan lebih dari 48 jam sebelum keberangkatan</li>
                                        <li>Refund 75% jika dibatalkan 24-48 jam sebelum keberangkatan</li>
                                        <li>Refund 50% jika dibatalkan 1-24 jam sebelum keberangkatan</li>
                                        <li>Dana akan dikembalikan dalam 3-5 hari kerja</li>
                                    </ul>
                                </div>

                                <!-- Refund Form -->
                                <form method="POST" id="refundForm">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">
                                            <i class="bi bi-chat-text text-primary me-1"></i>
                                            Alasan Refund <span class="text-danger">*</span>
                                        </label>
                                        <textarea name="alasan_refund" class="form-control form-control-enhanced" rows="4"
                                            required placeholder="Jelaskan alasan Anda mengajukan refund..."></textarea>
                                    </div>

                                    <div class="form-check mb-4">
                                        <input type="checkbox" class="form-check-input" id="agreement" required>
                                        <label class="form-check-label" for="agreement">
                                            Saya telah membaca dan menyetujui kebijakan refund di atas
                                        </label>
                                    </div>

                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <a href="riwayat.php" class="btn btn-outline-secondary me-md-2">
                                            <i class="bi bi-arrow-left me-2"></i>Kembali
                                        </a>
                                        <button type="submit" name="process_refund" class="btn btn-danger"
                                            onclick="return confirm('Apakah Anda yakin ingin melakukan refund? Tindakan ini tidak dapat dibatalkan.')">
                                            <i class="bi bi-arrow-return-left me-2"></i>Proses Refund
                                        </button>
                                    </div>
                                </form>

                            <?php elseif (!$boleh_refund): ?>
                                <div class="danger-box">
                                    <h6 class="fw-bold text-danger mb-3">
                                        <i class="bi bi-x-circle me-2"></i>Refund Tidak Diizinkan
                                    </h6>
                                    <p class="mb-2">
                                        <strong>Alasan:</strong> Waktu refund telah terlewati. Refund hanya dapat dilakukan
                                        minimal 1 jam sebelum keberangkatan.
                                    </p>
                                    <p class="mb-0">
                                        <strong>Waktu Keberangkatan:</strong>
                                        <?= date('d F Y, H:i', strtotime($tiket['tanggal'] . ' ' . $tiket['waktu_berangkat'])); ?>
                                        WIB
                                    </p>
                                </div>

                            <?php else: ?>
                                <div class="warning-box">
                                    <h6 class="fw-bold text-warning mb-3">
                                        <i class="bi bi-exclamation-triangle me-2"></i>Tiket Tidak Dapat Direfund
                                    </h6>
                                    <p class="mb-0">
                                        Tiket ini tidak dapat direfund karena:
                                        <?php if ($tiket['status_tiket'] != 'Lunas'): ?>
                                            Status tiket bukan "Lunas"
                                        <?php elseif ($tiket['status_refund']): ?>
                                            Tiket sudah pernah direfund
                                        <?php endif; ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>

                    <?php else: ?>
                        <!-- Success Message -->
                        <div class="refund-form-card text-center">
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                            <h4 class="fw-bold text-success mt-3 mb-3">Refund Berhasil Diproses!</h4>
                            <p class="text-muted mb-4">
                                Dana sebesar <strong>Rp<?= number_format($refund_amount, 0, ',', '.'); ?></strong>
                                akan dikembalikan ke rekening Anda dalam 3-5 hari kerja.
                            </p>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                                <a href="riwayat.php" class="btn btn-primary-custom">
                                    <i class="bi bi-list-ul me-2"></i>Lihat Riwayat
                                </a>
                                <a href="dashboard.php" class="btn btn-outline-primary">
                                    <i class="bi bi-house me-2"></i>Ke Dashboard
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Countdown timer
        function updateCountdown() {
            const departureTime = new Date('<?= date('Y-m-d H:i:s', strtotime($tiket['tanggal'] . ' ' . $tiket['waktu_berangkat'])); ?>').getTime();
            const now = new Date().getTime();
            const timeLeft = departureTime - now;

            if (timeLeft > 0) {
                const hours = Math.floor(timeLeft / (1000 * 60 * 60));
                const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);

                document.getElementById('countdown').textContent =
                    String(hours).padStart(2, '0') + ':' +
                    String(minutes).padStart(2, '0') + ':' +
                    String(seconds).padStart(2, '0');
            } else {
                document.getElementById('countdown').textContent = 'Keberangkatan';
                document.getElementById('countdown').className = 'countdown text-danger';
            }
        }

        // Update countdown every second
        setInterval(updateCountdown, 1000);
        updateCountdown(); // Initial call

        // Page load animation
        window.addEventListener('load', function () {
            const cards = document.querySelectorAll('.ticket-detail-card, .refund-form-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';

                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 200);
            });
        });

        // Form validation
        document.getElementById('refundForm')?.addEventListener('submit', function (e) {
            const alasan = document.querySelector('textarea[name="alasan_refund"]').value.trim();
            const agreement = document.getElementById('agreement').checked;

            if (alasan.length < 10) {
                e.preventDefault();
                alert('Alasan refund minimal harus 10 karakter!');
                return false;
            }

            if (!agreement) {
                e.preventDefault();
                alert('Anda harus menyetujui kebijakan refund!');
                return false;
            }
        });
    </script>
</body>

</html>