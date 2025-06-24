<?php
// Cetak Tiket
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

include '../includes/db.php';

if (!isset($_GET['id'])) {
    header('Location: riwayat.php');
    exit;
}

$id = intval($_GET['id']);
$user_id = $_SESSION['user']['id'];

// Ambil id_penumpang dari id_pengguna
$penumpang_query = $conn->query("SELECT id FROM penumpang WHERE id_pengguna=$user_id");
$penumpang = $penumpang_query ? $penumpang_query->fetch_assoc() : null;
$id_penumpang = $penumpang ? $penumpang['id'] : 0;

if (!$id_penumpang) {
    die('Data penumpang tidak ditemukan! Silakan lengkapi profil Anda terlebih dahulu.');
}

// Query untuk mengambil data tiket lengkap dengan informasi penumpang
$query = "SELECT t.*, 
                 jk.tanggal, jk.waktu_berangkat, jk.waktu_tiba, jk.stasiun_awal, jk.stasiun_tujuan, 
                 k.nama_kereta, k.kelas as kelas_kereta,
                 ks.nomor_kursi, ks.kelas as kelas_kursi,
                 g.nomor_gerbong,
                 pg.nama as nama_penumpang, pg.email as email_penumpang
          FROM tiket t 
          JOIN jadwal_kereta jk ON t.id_jadwal = jk.id 
          JOIN kereta k ON jk.id_kereta = k.id 
          JOIN kursi ks ON t.id_kursi = ks.id 
          JOIN gerbong g ON ks.id_gerbong = g.id
          JOIN penumpang pn ON t.id_penumpang = pn.id
          JOIN pengguna pg ON pn.id_pengguna = pg.id
          WHERE t.id = $id AND t.id_penumpang = $id_penumpang";

$result = $conn->query($query);
$tiket = $result ? $result->fetch_assoc() : null;

if (!$tiket) {
    die('Tiket tidak ditemukan atau Anda tidak memiliki akses ke tiket ini!');
}

// Function untuk mendapatkan nama status dengan styling
function getStatusBadge($status)
{
    switch (strtolower($status)) {
        case 'lunas':
            return '<span class="status-badge status-lunas"><i class="bi bi-check-circle-fill"></i> Lunas</span>';
        case 'pending':
            return '<span class="status-badge status-pending"><i class="bi bi-clock-fill"></i> Pending</span>';
        case 'batal':
            return '<span class="status-badge status-batal"><i class="bi bi-x-circle-fill"></i> Dibatalkan</span>';
        default:
            return '<span class="status-badge status-default">' . ucfirst($status) . '</span>';
    }
}

// Function untuk format tanggal Indonesia
function formatTanggalIndonesia($tanggal)
{
    $bulan = [
        1 => 'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    ];

    $hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

    $timestamp = strtotime($tanggal);
    $hari_nama = $hari[date('w', $timestamp)];
    $tanggal_format = date('d', $timestamp);
    $bulan_nama = $bulan[date('n', $timestamp)];
    $tahun = date('Y', $timestamp);

    return "$hari_nama, $tanggal_format $bulan_nama $tahun";
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Cetak Tiket - Kereta Connect</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/cetaktiket.css" rel="stylesheet">
</head>

<body>
    <div class="ticket-container">
        <div class="ticket-card">
            <div class="card card-elegant overflow-hidden">
                <!-- Ticket Header -->
                <div class="ticket-header">
                    <i class="bi bi-ticket-detailed-fill ticket-icon"></i>
                    <h1 class="ticket-title">E-Ticket Kereta</h1>
                    <p class="ticket-subtitle">Kereta Connect - Tiket Digital</p>
                </div>

                <!-- Ticket Body -->
                <div class="ticket-body">
                    <!-- Passenger Information -->
                    <div class="info-section passenger-info">
                        <h6 class="section-title text-primary">
                            <i class="bi bi-person-fill me-2"></i>Informasi Penumpang
                        </h6>
                        <div class="detail-item">
                            <span class="detail-label">
                                <i class="bi bi-person"></i>Nama Penumpang
                            </span>
                            <span
                                class="detail-value"><?= htmlspecialchars($tiket['nama_penumpang'] ?? 'Tidak tersedia'); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">
                                <i class="bi bi-envelope"></i>Email
                            </span>
                            <span
                                class="detail-value"><?= htmlspecialchars($tiket['email_penumpang'] ?? 'Tidak tersedia'); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">
                                <i class="bi bi-ticket"></i>Nomor Tiket
                            </span>
                            <span class="detail-value">#TKT-<?= str_pad($tiket['id'], 6, '0', STR_PAD_LEFT); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">
                                <i class="bi bi-calendar2-check"></i>Tanggal Pemesanan
                            </span>
                            <span class="detail-value"><?= date('d/m/Y', strtotime($tiket['tanggal_pesan'])); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">
                                <i class="bi bi-check-circle"></i>Status Tiket
                            </span>
                            <span class="detail-value"><?= getStatusBadge($tiket['status_tiket']); ?></span>
                        </div>
                    </div>

                    <!-- Journey Details -->
                    <div class="info-section journey-details">
                        <h6 class="section-title text-success">
                            <i class="bi bi-train-front me-2"></i>Detail Perjalanan
                        </h6>
                        <div class="detail-item">
                            <span class="detail-label">
                                <i class="bi bi-train-front"></i>Nama Kereta
                            </span>
                            <span class="detail-value"><?= htmlspecialchars($tiket['nama_kereta']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">
                                <i class="bi bi-geo-alt"></i>Stasiun Keberangkatan
                            </span>
                            <span class="detail-value"><?= htmlspecialchars($tiket['stasiun_awal']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">
                                <i class="bi bi-geo"></i>Stasiun Tujuan
                            </span>
                            <span class="detail-value"><?= htmlspecialchars($tiket['stasiun_tujuan']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">
                                <i class="bi bi-calendar3"></i>Tanggal Keberangkatan
                            </span>
                            <span class="detail-value"><?= formatTanggalIndonesia($tiket['tanggal']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">
                                <i class="bi bi-clock"></i>Waktu Keberangkatan
                            </span>
                            <span class="detail-value"><?= date('H:i', strtotime($tiket['waktu_berangkat'])); ?>
                                WIB</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">
                                <i class="bi bi-clock-history"></i>Waktu Tiba
                            </span>
                            <span class="detail-value"><?= date('H:i', strtotime($tiket['waktu_tiba'])); ?> WIB</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">
                                <i class="bi bi-bookmark"></i>Nomor Kursi
                            </span>
                            <span class="detail-value"><?= htmlspecialchars($tiket['nomor_kursi']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">
                                <i class="bi bi-building"></i>Gerbong
                            </span>
                            <span class="detail-value"><?= htmlspecialchars($tiket['nomor_gerbong']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">
                                <i class="bi bi-star"></i>Kelas
                            </span>
                            <span class="detail-value"><?= htmlspecialchars($tiket['kelas_kursi']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">
                                <i class="bi bi-cash-coin"></i>Total Pembayaran
                            </span>
                            <span
                                class="detail-value text-success">Rp<?= number_format($tiket['total_pembayaran'], 0, ',', '.'); ?></span>
                        </div>
                    </div>

                    <!-- QR Code Section -->
                    <div class="qr-section">
                        <div class="qr-placeholder">
                            <i class="bi bi-qr-code"></i>
                        </div>
                        <small class="text-muted">
                            <i class="bi bi-info-circle me-1"></i>
                            Tunjukkan QR Code ini kepada petugas saat validasi tiket
                        </small>
                    </div>

                    <!-- Action Buttons -->
                    <div class="btn-group-custom no-print">
                        <button class="btn btn-primary-custom" onclick="window.print()">
                            <i class="bi bi-printer me-2"></i>Cetak Tiket
                        </button>
                        <a href="riwayat.php" class="btn btn-outline-primary">
                            <i class="bi bi-arrow-left me-2"></i>Kembali
                        </a>
                    </div>
                </div>

                <!-- Ticket Footer -->
                <div class="ticket-footer">
                    <div class="mb-2">
                        <i class="bi bi-shield-check me-1"></i>
                        Tiket ini telah terverifikasi secara digital oleh sistem Kereta Connect
                    </div>
                    <div>
                        <i class="bi bi-calendar-check me-1"></i>
                        Dicetak pada: <?= date('d/m/Y H:i:s'); ?> WIB
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Print Version (Hidden) -->
    <div class="d-none" id="printVersion">
        <div style="max-width: 400px; margin: 0 auto; font-family: 'Inter', sans-serif;">
            <div
                style="text-align: center; padding: 20px; background: #1e40af; color: white; border-radius: 8px 8px 0 0;">
                <h2 style="margin: 0; font-size: 1.5rem;">E-Ticket Kereta</h2>
                <p style="margin: 5px 0 0 0; font-size: 0.9rem;">Kereta Connect</p>
            </div>

            <div
                style="padding: 20px; background: white; border: 1px solid #ddd; border-top: none; border-radius: 0 0 8px 8px;">
                <h4>Informasi Penumpang</h4>
                <p><strong>Nama:</strong> <?= htmlspecialchars($tiket['nama_penumpang'] ?? 'Tidak tersedia'); ?></p>
                <p><strong>Nomor Tiket:</strong> #TKT-<?= str_pad($tiket['id'], 6, '0', STR_PAD_LEFT); ?></p>
                <p><strong>Status:</strong> <?= ucfirst($tiket['status_tiket']); ?></p>

                <hr>

                <h4>Detail Perjalanan</h4>
                <p><strong>Kereta:</strong> <?= htmlspecialchars($tiket['nama_kereta']); ?></p>
                <p><strong>Dari:</strong> <?= htmlspecialchars($tiket['stasiun_awal']); ?></p>
                <p><strong>Ke:</strong> <?= htmlspecialchars($tiket['stasiun_tujuan']); ?></p>
                <p><strong>Tanggal:</strong> <?= formatTanggalIndonesia($tiket['tanggal']); ?></p>
                <p><strong>Waktu:</strong> <?= date('H:i', strtotime($tiket['waktu_berangkat'])); ?> -
                    <?= date('H:i', strtotime($tiket['waktu_tiba'])); ?> WIB</p>
                <p><strong>Kursi:</strong> <?= $tiket['nomor_kursi']; ?> (<?= $tiket['kelas_kursi']; ?>)</p>
                <p><strong>Gerbong:</strong> <?= $tiket['nomor_gerbong']; ?></p>
                <p><strong>Total:</strong> Rp<?= number_format($tiket['total_pembayaran'], 0, ',', '.'); ?></p>

                <div
                    style="text-align: center; margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                    <div
                        style="width: 80px; height: 80px; border: 2px dashed #6c757d; margin: 0 auto 10px; display: flex; align-items: center; justify-content: center; border-radius: 8px;">
                        QR
                    </div>
                    <small>Scan untuk validasi</small>
                </div>

                <hr>
                <small>Dicetak: <?= date('d/m/Y H:i:s'); ?> WIB | Kereta Connect</small>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Page load animation
        window.addEventListener('load', function () {
            const card = document.querySelector('.ticket-card');
            if (card) {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px) scale(0.95)';

                setTimeout(() => {
                    card.style.transition = 'all 0.8s cubic-bezier(0.4, 0, 0.2, 1)';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0) scale(1)';
                }, 200);
            }
        });

        // Enhanced print function
        function printTicket() {
            // Add print-specific styling
            const printStyle = document.createElement('style');
            printStyle.textContent = `
                @media print {
                    body { margin: 0; padding: 20px; }
                    .ticket-card { max-width: 100%; margin: 0; }
                    .no-print { display: none !important; }
                }
            `;
            document.head.appendChild(printStyle);

            window.print();

            // Remove print styling after print
            setTimeout(() => {
                if (document.head.contains(printStyle)) {
                    document.head.removeChild(printStyle);
                }
            }, 1000);
        }

        // Add keyboard shortcut for print
        document.addEventListener('keydown', function (e) {
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
        });

        // Add animation classes on scroll (if needed)
        function addScrollAnimations() {
            const elements = document.querySelectorAll('.info-section, .qr-section');
            elements.forEach((el, index) => {
                el.classList.add('fade-in');
                setTimeout(() => {
                    el.classList.add('show');
                }, index * 200);
            });
        }

        // Initialize animations
        document.addEventListener('DOMContentLoaded', addScrollAnimations);
    </script>
</body>

</html>