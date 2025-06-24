<?php
// Pilih Kursi
session_start();
if (!isset($_SESSION['user']))
    header('Location: login.php');
include '../includes/db.php';
if (!isset($_GET['jadwal_id']))
    header('Location: pesan_tiket.php');

$jadwal_id = intval($_GET['jadwal_id']);

// Ambil detail jadwal dengan informasi kereta
$jadwal_query = "SELECT jk.*, k.nama_kereta, k.jenis_kereta, k.kelas as kelas_kereta, k.kapasitas
                 FROM jadwal_kereta jk 
                 JOIN kereta k ON jk.id_kereta = k.id 
                 WHERE jk.id = $jadwal_id";
$jadwal_result = $conn->query($jadwal_query);
if (!$jadwal_result || $jadwal_result->num_rows == 0) {
    header('Location: pesan_tiket.php');
    exit;
}
$jadwal = $jadwal_result->fetch_assoc();
$id_kereta = $jadwal['id_kereta'];

// Ambil gerbong dan kursi dengan informasi lengkap
$kursi_query = "SELECT k.*, g.nomor_gerbong, g.kelas as kelas_gerbong, g.tipe,
                       CASE WHEN t.id IS NOT NULL THEN 'occupied' ELSE 'available' END as status
                FROM kursi k 
                JOIN gerbong g ON k.id_gerbong = g.id 
                LEFT JOIN tiket t ON k.id = t.id_kursi AND t.id_jadwal = $jadwal_id AND t.status_tiket IN ('Lunas', 'Pending')
                WHERE g.id_kereta = $id_kereta 
                ORDER BY g.nomor_gerbong, k.nomor_kursi";
$kursi_result = $conn->query($kursi_query);

// Group kursi by gerbong and organize by rows
$gerbong_kursi = [];
if ($kursi_result && $kursi_result->num_rows > 0) {
    while ($row = $kursi_result->fetch_assoc()) {
        $gerbong = $row['nomor_gerbong'];
        if (!isset($gerbong_kursi[$gerbong])) {
            $gerbong_kursi[$gerbong] = [
                'info' => [
                    'kelas' => $row['kelas_gerbong'],
                    'tipe' => $row['tipe']
                ],
                'kursi' => []
            ];
        }
        $gerbong_kursi[$gerbong]['kursi'][] = $row;
    }
}

// Function to organize seats in realistic train layout
function organizeSeatLayout($kursi_list, $kelas_kereta)
{
    $organized = [];

    // Tentukan pola kursi berdasarkan kelas
    $seat_config = getSeatConfiguration($kelas_kereta);

    // Sort kursi by number for proper arrangement
    usort($kursi_list, function ($a, $b) {
        return (int) $a['nomor_kursi'] - (int) $b['nomor_kursi'];
    });

    // Group seats by rows
    $seats_per_row = $seat_config['seats_per_row'];
    $current_row = 1;
    $row_seats = [];

    foreach ($kursi_list as $kursi) {
        $seat_number = (int) $kursi['nomor_kursi'];
        $row = ceil($seat_number / $seats_per_row);

        if (!isset($organized[$row])) {
            $organized[$row] = [];
        }

        $organized[$row][] = $kursi;
    }

    return $organized;
}

function getSeatConfiguration($kelas)
{
    switch (strtolower($kelas)) {
        case 'eksekutif':
            return [
                'seats_per_row' => 3,
                'layout' => '2-1', // 2 kursi kiri, 1 kursi kanan
                'seat_width' => 50
            ];
        case 'bisnis':
            return [
                'seats_per_row' => 4,
                'layout' => '2-2', // 2 kursi kiri, 2 kursi kanan
                'seat_width' => 45
            ];
        case 'ekonomi':
        default:
            return [
                'seats_per_row' => 5,
                'layout' => '3-2', // 3 kursi kiri, 2 kursi kanan
                'seat_width' => 38
            ];
    }
}

function renderSeatRow($row_number, $seats, $config, $jadwal_id)
{
    $layout = explode('-', $config['layout']);
    $left_seats = (int) $layout[0];
    $right_seats = (int) $layout[1];

    echo '<div class="seat-row">';
    echo '<div class="row-number">' . $row_number . '</div>';

    // Window indicator untuk kursi pinggir
    if ($row_number % 2 == 1) {
        echo '<div class="window-indicator"><i class="bi bi-window"></i></div>';
    }

    // Left side seats
    echo '<div class="seat-group left-side">';
    for ($i = 0; $i < $left_seats && $i < count($seats); $i++) {
        renderSeat($seats[$i], $jadwal_id);
    }
    echo '</div>';

    // Aisle
    echo '<div class="aisle">⌇</div>';

    // Right side seats  
    echo '<div class="seat-group right-side">';
    for ($i = $left_seats; $i < $left_seats + $right_seats && $i < count($seats); $i++) {
        renderSeat($seats[$i], $jadwal_id);
    }
    echo '</div>';

    echo '</div>';
}

function renderSeat($kursi, $jadwal_id)
{
    $status = $kursi['status'];
    $seat_class = "seat $status";
    $id = (int)$kursi['id'];
    $nomor_kursi = addslashes($kursi['nomor_kursi']);
    $onclick = ($status == 'available') ? "toggleSeat(this, $id, '$nomor_kursi')" : '';
    $tabindex = ($status == 'available') ? 'tabindex="0"' : '';
    $role = ($status == 'available') ? 'role="button" aria-pressed="false"' : '';
    $style = ($status == 'available') ? 'style="cursor:pointer;"' : '';
    echo "<div class='$seat_class' onclick=\"$onclick\" $tabindex $role $style data-seat-id='$id' data-seat-number='$nomor_kursi'>";
    echo htmlspecialchars($kursi['nomor_kursi']);
    echo "</div>";
}

// Hitung statistik kursi
$total_kursi = 0;
$kursi_tersedia = 0;
$kursi_terisi = 0;

foreach ($gerbong_kursi as $gerbong => $data) {
    foreach ($data['kursi'] as $kursi) {
        $total_kursi++;
        if ($kursi['status'] == 'available') {
            $kursi_tersedia++;
        } else {
            $kursi_terisi++;
        }
    }
}

$user = $_SESSION['user'];
$nama_display = '';
if (!empty($user['nama']) && $user['nama'] !== $user['email']) {
    $nama_display = $user['nama'];
} else if (!empty($user['username'])) {
    $nama_display = $user['username'];
} else {
    $nama_display = explode('@', $user['email'])[0];
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Pilih Kursi - Kereta Connect</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <link href="../assets/css/pilihkursi.css" rel="stylesheet">
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

    <div class="seat-container">
        <div class="container">
            <!-- Journey Info -->
            <div class="journey-info-card">
                <div class="row">
                    <div class="col-md-8">
                        <h3 class="fw-bold mb-3">
                            <i class="bi bi-bookmark-plus me-2"></i>Pilih Kursi Anda
                        </h3>
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="fw-semibold text-primary"><?= htmlspecialchars($jadwal['nama_kereta']) ?>
                                </h5>
                                <p class="mb-2">
                                    <i class="bi bi-geo-alt me-1"></i>
                                    <?= htmlspecialchars($jadwal['stasiun_awal']) ?> →
                                    <?= htmlspecialchars($jadwal['stasiun_tujuan']) ?>
                                </p>
                                <p class="mb-2">
                                    <i class="bi bi-calendar me-1"></i>
                                    <?= date('d F Y', strtotime($jadwal['tanggal'])) ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-2">
                                    <i class="bi bi-clock me-1"></i>
                                    <?= $jadwal['waktu_berangkat'] ?> - <?= $jadwal['waktu_tiba'] ?>
                                </p>
                                <p class="mb-2">
                                    <i class="bi bi-tag me-1"></i>
                                    Kelas <?= ucfirst($jadwal['kelas_kereta']) ?>
                                </p>
                                <p class="mb-0 text-success fw-semibold">
                                    <i class="bi bi-cash me-1"></i>
                                    Rp<?= number_format($jadwal['tarif'], 0, ',', '.') ?> / kursi
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="text-center">
                            <h6 class="text-muted">Ketersediaan Kursi</h6>
                            <div class="d-flex justify-content-center gap-3">
                                <div>
                                    <div class="fw-bold text-success fs-4"><?= $kursi_tersedia ?></div>
                                    <small class="text-muted">Tersedia</small>
                                </div>
                                <div>
                                    <div class="fw-bold text-danger fs-4"><?= $kursi_terisi ?></div>
                                    <small class="text-muted">Terisi</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <!-- Legend -->
                    <div class="legend-card">
                        <h6 class="fw-semibold mb-3">Keterangan:</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="legend-item">
                                    <div class="legend-seat seat-available">A</div>
                                    <span>Kursi Tersedia</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="legend-item">
                                    <div class="legend-seat seat-occupied">X</div>
                                    <span>Kursi Terisi</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="legend-item">
                                    <div class="legend-seat seat-selected">✓</div>
                                    <span>Kursi Dipilih</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Train Layout -->
                    <?php foreach ($gerbong_kursi as $gerbong_num => $gerbong_data): ?>
                        <div class="coach-container <?= strtolower($gerbong_data['info']['kelas']) ?>">
                            <div class="coach-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="fw-bold mb-0">
                                        <i class="bi bi-train-freight-front me-2"></i>
                                        Gerbong <?= $gerbong_num ?>
                                    </h5>
                                    <span class="coach-badge <?= strtolower($gerbong_data['info']['kelas']) ?>">
                                        <?= ucfirst($gerbong_data['info']['kelas']) ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Train Direction Indicator -->
                            <div class="train-front-indicator">
                                <div class="train-nose"></div>
                                <small class="text-muted">Arah Perjalanan</small>
                            </div>

                            <div class="coach-shape">
                                <div class="seat-layout layout-<?= strtolower($gerbong_data['info']['kelas']) ?>">
                                    <?php
                                    $config = getSeatConfiguration($gerbong_data['info']['kelas']);
                                    $organized_seats = organizeSeatLayout($gerbong_data['kursi'], $gerbong_data['info']['kelas']);

                                    foreach ($organized_seats as $row_num => $row_seats) {
                                        renderSeatRow($row_num, $row_seats, $config, $jadwal_id);
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="col-lg-4">
                    <!-- Summary -->
                    <div class="summary-card">
                        <div class="summary-header">
                            <h5 class="fw-bold">Ringkasan Pemesanan</h5>
                        </div>

                        <div class="selected-seats">
                            <h6 class="fw-semibold">Kursi Dipilih:</h6>
                            <div id="selected-seats-list">
                                <p class="text-muted">Belum ada kursi dipilih</p>
                            </div>
                        </div>

                        <div class="price-breakdown">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Harga per kursi:</span>
                                <span>Rp<?= number_format($jadwal['tarif'], 0, ',', '.') ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Jumlah kursi:</span>
                                <span id="seat-count">0</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between">
                                <strong>Total:</strong>
                                <strong class="total-price" id="total-price">Rp0</strong>
                            </div>
                        </div>

                        <div class="mt-3">
                            <button type="button" class="btn btn-secondary-custom mb-2" onclick="clearSelection()">
                                <i class="bi bi-arrow-clockwise me-1"></i>Reset Pilihan
                            </button>
                            <button type="button" class="btn btn-primary-custom" id="proceed-btn"
                                onclick="proceedToBooking()" disabled>
                                <i class="bi bi-arrow-right me-1"></i>Lanjut Pemesanan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let selectedSeats = [];
        const seatPrice = <?= $jadwal['tarif'] ?>;
        const jadwalId = <?= $jadwal_id ?>;

        function toggleSeat(element, seatId, seatNumber) {
            seatId = parseInt(seatId);
            if (element.classList.contains('occupied')) {
                return;
            }

            const seatIndex = selectedSeats.findIndex(s => s.id === seatId);

            if (seatIndex > -1) {
                // Remove seat
                selectedSeats.splice(seatIndex, 1);
                element.classList.remove('selected');
                element.classList.add('available');
            } else {
                // Add seat (max 4 seats)
                if (selectedSeats.length >= 4) {
                    alert('Maksimal 4 kursi per pemesanan');
                    return;
                }

                selectedSeats.push({
                    id: seatId,
                    number: seatNumber
                });
                element.classList.remove('available');
                element.classList.add('selected');
            }

            updateSummary();
        }

        // Tambahkan event listener untuk keyboard accessibility
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.seat.available').forEach(function(seat) {
                seat.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        seat.click();
                    }
                });
            });
            // Auto-scroll to first available seat
            const firstAvailable = document.querySelector('.seat.available');
            if (firstAvailable) {
                firstAvailable.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });

        function updateSummary() {
            const seatsList = document.getElementById('selected-seats-list');
            const seatCount = document.getElementById('seat-count');
            const totalPrice = document.getElementById('total-price');
            const proceedBtn = document.getElementById('proceed-btn');

            if (selectedSeats.length === 0) {
                seatsList.innerHTML = '<p class="text-muted">Belum ada kursi dipilih</p>';
                proceedBtn.disabled = true;
            } else {
                const seatsHtml = selectedSeats.map(seat =>
                    `<span class="seat-tag">Kursi ${seat.number}</span>`
                ).join('');
                seatsList.innerHTML = seatsHtml;
                proceedBtn.disabled = false;
            }

            seatCount.textContent = selectedSeats.length;
            totalPrice.textContent = 'Rp' + (selectedSeats.length * seatPrice).toLocaleString('id-ID');
        }

        function clearSelection() {
            selectedSeats = [];
            document.querySelectorAll('.seat.selected').forEach(seat => {
                seat.classList.remove('selected');
                seat.classList.add('available');
            });
            updateSummary();
        }

        function proceedToBooking() {
            if (selectedSeats.length === 0) {
                alert('Pilih minimal 1 kursi');
                return;
            }

            const seatIds = selectedSeats.map(s => s.id).join(',');
            window.location.href = `konfirmasi_pesanan.php?jadwal_id=${jadwalId}&seat_ids=${seatIds}`;
        }
    </script>
</body>
</html>