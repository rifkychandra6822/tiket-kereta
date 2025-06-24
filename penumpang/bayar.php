<?php
// Bayar Tiket
session_start();
if (!isset($_SESSION['user']))
    header('Location: login.php');
include '../includes/db.php';

// Ambil data dari POST (multi kursi)
$jadwal_id = isset($_POST['jadwal_id']) ? intval($_POST['jadwal_id']) : 0;
$seat_ids = isset($_POST['seat_ids']) ? $_POST['seat_ids'] : '';

if ($jadwal_id == 0 || empty($seat_ids)) {
    echo '<div style="padding:2rem;text-align:center;color:red;">Data tidak valid. Silakan ulangi pemesanan.</div>';
    exit;
}

// Ambil id_penumpang dari id_pengguna
$user_id = $_SESSION['user']['id'];
$penumpang = $conn->query("SELECT id FROM penumpang WHERE id_pengguna=$user_id")->fetch_assoc();
$id_penumpang = $penumpang ? $penumpang['id'] : 0;

// Ambil data jadwal
$jadwal = $conn->query("SELECT jk.*, k.nama_kereta, k.kelas FROM jadwal_kereta jk JOIN kereta k ON jk.id_kereta = k.id WHERE jk.id = $jadwal_id")->fetch_assoc();
$tarif = $jadwal['tarif'];
$tanggal_pesan = date('Y-m-d');

// Proses setiap kursi
$kursi_ids = array_map('intval', explode(',', $seat_ids));
$berhasil = 0;
$gagal = 0;
$kursi_berhasil = [];
$kursi_gagal = [];
foreach ($kursi_ids as $kursi_id) {
    // Cek apakah kursi masih tersedia
    $cek = $conn->query("SELECT * FROM kursi WHERE id=$kursi_id AND status='Tersedia'");
    if ($cek->num_rows == 0) {
        $gagal++;
        $kursi_gagal[] = $kursi_id;
        continue;
    }
    // Simpan tiket dan update kursi
    $conn->query("INSERT INTO tiket (id_penumpang, id_jadwal, id_kursi, tanggal_pesan, status_tiket, total_pembayaran, status_refund, validasi_tiket) VALUES ($id_penumpang, $jadwal_id, $kursi_id, '$tanggal_pesan', 'Lunas', $tarif, 0, 0)");
    $conn->query("UPDATE kursi SET status='Terisi' WHERE id=$kursi_id");
    $berhasil++;
    $kursi_berhasil[] = $kursi_id;
}

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Pembayaran - Kereta Connect</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .payment-container {
            min-height: 100vh;
            background: var(--gradient-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            padding: 40px 0;
        }

        .payment-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background:
                radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(120, 119, 198, 0.2) 0%, transparent 50%);
            animation: float 15s ease-in-out infinite;
        }

        .payment-card {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 480px;
            margin: 20px;
        }

        .success-icon {
            font-size: 4rem;
            color: var(--success);
            margin-bottom: 20px;
            animation: bounce 2s ease-in-out infinite;
        }

        .error-icon {
            font-size: 4rem;
            color: var(--danger);
            margin-bottom: 20px;
            animation: shake 0.5s ease-in-out;
        }

        @keyframes bounce {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            25% {
                transform: translateX(-5px);
            }

            75% {
                transform: translateX(5px);
            }
        }

        .ticket-details {
            background: rgba(96, 165, 250, 0.1);
            border-left: 4px solid var(--accent-blue);
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid rgba(96, 165, 250, 0.2);
        }

        .detail-row:last-child {
            border-bottom: none;
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--primary-blue);
        }

        .btn-group-custom {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .btn-group-custom .btn {
            flex: 1;
            min-width: 140px;
        }

        @media (max-width: 480px) {
            .btn-group-custom {
                flex-direction: column;
            }

            .btn-group-custom .btn {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="payment-container">
        <div class="payment-card">
            <div class="card card-elegant">
                <div class="card-body p-4 text-center">

                    <?php if ($berhasil > 0): ?>
                        <!-- Success State -->
                        <i class="bi bi-check-circle-fill success-icon"></i>
                        <h2 class="fw-bold text-success mb-3">Pembayaran Berhasil!</h2>
                        <p class="text-muted mb-4">
                            <?= $berhasil ?> tiket berhasil dipesan dan kursi sudah dikunci.
                        </p>

                        <!-- Ticket Details -->
                        <div class="ticket-details text-start">
                            <h6 class="fw-bold text-primary mb-3">
                                <i class="bi bi-ticket-detailed me-2"></i>Detail Tiket
                            </h6>

                            <div class="detail-row">
                                <span class="text-muted">Kereta:</span>
                                <span class="fw-semibold"><?= htmlspecialchars($jadwal['nama_kereta']); ?></span>
                            </div>

                            <div class="detail-row">
                                <span class="text-muted">Rute:</span>
                                <span class="fw-semibold"><?= htmlspecialchars($jadwal['stasiun_awal']); ?> →
                                    <?= htmlspecialchars($jadwal['stasiun_tujuan']); ?></span>
                            </div>

                            <div class="detail-row">
                                <span class="text-muted">Tanggal:</span>
                                <span class="fw-semibold"><?= date('d/m/Y', strtotime($jadwal['tanggal'])); ?></span>
                            </div>

                            <div class="detail-row">
                                <span class="text-muted">Waktu:</span>
                                <span class="fw-semibold"><?= $jadwal['waktu_berangkat']; ?> -
                                    <?= $jadwal['waktu_tiba']; ?></span>
                            </div>

                            <div class="detail-row">
                                <span class="text-muted">Kursi:</span>
                                <span class="fw-semibold">
                                    <?php
                                    // Tampilkan nomor kursi yang berhasil
                                    if (count($kursi_berhasil) > 0) {
                                        $kursi_str = implode(',', $kursi_berhasil);
                                        $q = $conn->query("SELECT k.nomor_kursi, g.nomor_gerbong FROM kursi k JOIN gerbong g ON k.id_gerbong = g.id WHERE k.id IN ($kursi_str)");
                                        while ($row = $q->fetch_assoc()) {
                                            echo 'Gerbong ' . htmlspecialchars($row['nomor_gerbong']) . ' - Kursi ' . htmlspecialchars($row['nomor_kursi']) . '; ';
                                        }
                                    }
                                    ?>
                                </span>
                            </div>

                            <div class="detail-row">
                                <span class="text-muted">Total Pembayaran:</span>
                                <span class="fw-bold text-success">Rp<?= number_format($tarif * $berhasil, 0, ',', '.'); ?></span>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="btn-group-custom">
                            <a href="riwayat.php" class="btn btn-success">
                                <i class="bi bi-ticket-detailed me-2"></i>Lihat Tiket Saya
                            </a>
                            <a href="dashboard.php" class="btn btn-outline-primary">
                                <i class="bi bi-house me-2"></i>Dashboard
                            </a>
                        </div>

                        <!-- Additional Info -->
                        <div class="mt-4 p-3 bg-light rounded-3">
                            <small class="text-muted">
                                <i class="bi bi-info-circle me-1"></i>
                                Simpan tiket ini sebagai bukti pembayaran. Tunjukkan kepada petugas saat keberangkatan.
                            </small>
                        </div>
                    <?php else: ?>
                        <!-- Error State -->
                        <i class="bi bi-x-circle-fill error-icon"></i>
                        <h2 class="fw-bold text-danger mb-3">Pembayaran Gagal!</h2>
                        <div class="alert alert-danger alert-custom">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Semua kursi sudah dipesan orang lain atau terjadi kesalahan.
                        </div>
                        <p class="text-muted mb-4">
                            Silakan pilih kursi lain atau coba lagi nanti.
                        </p>

                        <div class="btn-group-custom">
                            <a href="pesan_tiket.php" class="btn btn-primary-custom">
                                <i class="bi bi-arrow-left me-2"></i>Pilih Kursi Lain
                            </a>
                            <a href="dashboard.php" class="btn btn-outline-primary">
                                <i class="bi bi-house me-2"></i>Dashboard
                            </a>
                        </div>

                    <?php endif; ?>

                </div>
            </div>

            <!-- Footer Note -->
            <div class="text-center mt-3">
                <p class="text-white-50 small mb-0">
                    <i class="bi bi-shield-check me-1"></i>
                    Transaksi Anda aman dan terenkripsi
                </p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add page load animation
        window.addEventListener('load', function () {
            const card = document.querySelector('.payment-card');
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';

            setTimeout(() => {
                card.style.transition = 'all 0.6s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 100);
        });
    </script>
</body>

</html>