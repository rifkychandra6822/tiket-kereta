<?php
// Kelola Jadwal Kereta
session_start();
if (!isset($_SESSION['admin'])) header('Location: login.php');
include '../includes/db.php';
$message = '';

// Ambil data kereta aktif untuk dropdown
$kereta_list = $conn->query("SELECT * FROM kereta WHERE status='Aktif' ORDER BY nama_kereta ASC");

if (isset($_POST['tambah'])) {
    $id_kereta = intval($_POST['id_kereta']);
    $stasiun_awal = $conn->real_escape_string($_POST['stasiun_awal']);
    $stasiun_tujuan = $conn->real_escape_string($_POST['stasiun_tujuan']);
    $tanggal = $conn->real_escape_string($_POST['tanggal']);
    $waktu_berangkat = $conn->real_escape_string($_POST['waktu_berangkat']);
    $waktu_tiba = $conn->real_escape_string($_POST['waktu_tiba']);
    $tarif = floatval($_POST['tarif']);
    $status = isset($_POST['status']) ? $_POST['status'] : 'Tersedia';
    
    // Validate time logic
    if ($waktu_tiba <= $waktu_berangkat) {
        $message = '<div class="alert alert-warning">Waktu tiba harus lebih besar dari waktu berangkat!</div>';
    } else {
        $conn->query("INSERT INTO jadwal_kereta (id_kereta, stasiun_awal, stasiun_tujuan, tanggal, waktu_berangkat, waktu_tiba, tarif, status) VALUES ($id_kereta, '$stasiun_awal', '$stasiun_tujuan', '$tanggal', '$waktu_berangkat', '$waktu_tiba', $tarif, '$status')");
        $message = '<div class="alert alert-success">Jadwal berhasil ditambah!</div>';
    }
}

if (isset($_POST['update'])) {
    $id = intval($_POST['id']);
    $id_kereta = intval($_POST['id_kereta']);
    $stasiun_awal = $conn->real_escape_string($_POST['stasiun_awal']);
    $stasiun_tujuan = $conn->real_escape_string($_POST['stasiun_tujuan']);
    $tanggal = $conn->real_escape_string($_POST['tanggal']);
    $waktu_berangkat = $conn->real_escape_string($_POST['waktu_berangkat']);
    $waktu_tiba = $conn->real_escape_string($_POST['waktu_tiba']);
    $tarif = floatval($_POST['tarif']);
    $status = $conn->real_escape_string($_POST['status']);
    
    if ($waktu_tiba <= $waktu_berangkat) {
        $message = '<div class="alert alert-warning">Waktu tiba harus lebih besar dari waktu berangkat!</div>';
    } else {
        $conn->query("UPDATE jadwal_kereta SET id_kereta=$id_kereta, stasiun_awal='$stasiun_awal', stasiun_tujuan='$stasiun_tujuan', tanggal='$tanggal', waktu_berangkat='$waktu_berangkat', waktu_tiba='$waktu_tiba', tarif=$tarif, status='$status' WHERE id=$id");
        $message = '<div class="alert alert-success">Jadwal berhasil diupdate!</div>';
    }
}

if (isset($_GET['toggle_status'])) {
    $id = intval($_GET['toggle_status']);
    $conn->query("UPDATE jadwal_kereta SET status = CASE WHEN status = 'Tersedia' THEN 'Tidak Tersedia' ELSE 'Tersedia' END WHERE id=$id");
    $message = '<div class="alert alert-success">Status jadwal berhasil diubah!</div>';
}

if (isset($_GET['hapus'])) {
    $id = intval($_GET['hapus']);
    // Check if schedule is used in tickets
    $check = $conn->query("SELECT COUNT(*) as count FROM tiket WHERE id_jadwal=$id");
    $check_result = $check->fetch_assoc();
    
    if ($check_result['count'] > 0) {
        $message = '<div class="alert alert-warning">Jadwal tidak dapat dihapus karena sudah ada tiket yang menggunakan jadwal ini!</div>';
    } else {
        $conn->query("DELETE FROM jadwal_kereta WHERE id=$id");
        $message = '<div class="alert alert-success">Jadwal berhasil dihapus!</div>';
    }
}

// Get schedule data with train details and ticket count
$jadwal_query = "SELECT jk.*, k.nama_kereta, k.jenis_kereta, k.kelas, 
                 COUNT(t.id) as total_tiket,
                 COUNT(CASE WHEN t.status_tiket = 'Lunas' THEN 1 END) as tiket_lunas
                 FROM jadwal_kereta jk 
                 JOIN kereta k ON jk.id_kereta = k.id 
                 LEFT JOIN tiket t ON jk.id = t.id_jadwal
                 GROUP BY jk.id 
                 ORDER BY jk.tanggal DESC, jk.waktu_berangkat ASC";
$jadwal_result = $conn->query($jadwal_query);

// Calculate statistics
$total_jadwal = 0;
$jadwal_tersedia = 0;
$jadwal_tidak_tersedia = 0;
$total_pendapatan_potensial = 0;
$avg_tarif = 0;
$jadwal_list = [];

while ($row = $jadwal_result->fetch_assoc()) {
    $jadwal_list[] = $row;
    $total_jadwal++;
    if ($row['status'] == 'Tersedia') {
        $jadwal_tersedia++;
    } else {
        $jadwal_tidak_tersedia++;
    }
    $total_pendapatan_potensial += $row['tarif'];
}

if ($total_jadwal > 0) {
    $avg_tarif = $total_pendapatan_potensial / $total_jadwal;
}

// Get unique routes count
$rute_result = $conn->query("SELECT COUNT(DISTINCT CONCAT(stasiun_awal, '-', stasiun_tujuan)) as total_rute FROM jadwal_kereta");
$rute_data = $rute_result->fetch_assoc();
$total_rute = $rute_data['total_rute'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Jadwal Kereta - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/kelola_jadwal.css" rel="stylesheet">

</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-card">
            <div class="header">
                <div class="header-left">
                    <div class="brand-logo">
                        🕒
                    </div>
                    <div class="header-title">
                        <h1>Kelola Jadwal Kereta</h1>
                        <p>Atur jadwal perjalanan dan tarif kereta api</p>
                    </div>
                </div>
            </div>

            <!-- Alert Messages -->
            <?= $message; ?>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card total">
                    <div class="stat-value"><?= $total_jadwal ?></div>
                    <div class="stat-label">Total Jadwal</div>
                </div>
                <div class="stat-card available">
                    <div class="stat-value"><?= $jadwal_tersedia ?></div>
                    <div class="stat-label">Jadwal Tersedia</div>
                </div>
                <div class="stat-card unavailable">
                    <div class="stat-value"><?= $jadwal_tidak_tersedia ?></div>
                    <div class="stat-label">Tidak Tersedia</div>
                </div>
                <div class="stat-card routes">
                    <div class="stat-value"><?= $total_rute ?></div>
                    <div class="stat-label">Rute Unik</div>
                </div>
                <div class="stat-card average">
                    <div class="stat-value">Rp<?= number_format($avg_tarif, 0, ',', '.') ?></div>
                    <div class="stat-label">Rata-rata Tarif</div>
                </div>
            </div>

            <!-- Add New Schedule Form -->
            <div class="form-container">
                <h3 class="form-title">Tambah Jadwal Baru</h3>
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Kereta</label>
                            <select name="id_kereta" class="form-control" required>
                                <option value="">Pilih Kereta</option>
                                <?php 
                                // Reset pointer untuk dropdown
                                $kereta_list = $conn->query("SELECT * FROM kereta WHERE status='Aktif' ORDER BY nama_kereta ASC");
                                while ($k = $kereta_list->fetch_assoc()): 
                                ?>
                                    <option value="<?= $k['id']; ?>"><?= htmlspecialchars($k['nama_kereta']); ?> - <?= htmlspecialchars($k['kelas']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Stasiun Awal</label>
                            <input type="text" name="stasiun_awal" class="form-control" placeholder="Jakarta Gambir" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Stasiun Tujuan</label>
                            <input type="text" name="stasiun_tujuan" class="form-control" placeholder="Surabaya Pasar Turi" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tanggal</label>
                            <input type="date" name="tanggal" class="form-control" min="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    <div class="form-row-wide">
                        <div class="form-group">
                            <label class="form-label">Waktu Berangkat</label>
                            <input type="time" name="waktu_berangkat" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Waktu Tiba</label>
                            <input type="time" name="waktu_tiba" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tarif (Rp)</label>
                            <input type="number" name="tarif" class="form-control" placeholder="450000" min="0" required>
                        </div>
                        <button type="submit" name="tambah" class="btn-success">
                            ➕ Tambah Jadwal
                        </button>
                    </div>
                </form>
            </div>

            <!-- Data Table -->
            <div class="table-container">
                <div class="table-header">
                    <h3>Daftar Jadwal Kereta</h3>
                </div>
                
                <?php if (count($jadwal_list) > 0): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Kereta</th>
                                <th>Rute</th>
                                <th>Tanggal</th>
                                <th>Waktu</th>
                                <th>Tarif</th>
                                <th>Status</th>
                                <th>Tiket</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($jadwal_list as $row): ?>
                                <tr>
                                    <td>
                                        <div class="train-cell"><?= htmlspecialchars($row['nama_kereta']); ?></div>
                                        <span class="class-badge class-<?= strtolower($row['kelas']) ?>">
                                            <?= htmlspecialchars($row['kelas']); ?>
                                        </span>
                                    </td>
                                    <td class="route-cell">
                                        <?= htmlspecialchars($row['stasiun_awal']); ?> → 
                                        <?= htmlspecialchars($row['stasiun_tujuan']); ?>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                                    <td>
                                        <div><?= date('H:i', strtotime($row['waktu_berangkat'])); ?> - <?= date('H:i', strtotime($row['waktu_tiba'])); ?></div>
                                        <div class="ticket-info">
                                            <?php
                                            $duration = (strtotime($row['waktu_tiba']) - strtotime($row['waktu_berangkat'])) / 3600;
                                            echo sprintf("%.1f jam", $duration);
                                            ?>
                                        </div>
                                    </td>
                                    <td class="price-cell">Rp<?= number_format($row['tarif'], 0, ',', '.'); ?></td>
                                    <td>
                                        <?php if ($row['status'] == 'Tersedia'): ?>
                                            <span class="status-badge status-available">Tersedia</span>
                                        <?php else: ?>
                                            <span class="status-badge status-unavailable">Tidak Tersedia</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div><?= $row['total_tiket'] ?> total</div>
                                        <div class="ticket-info"><?= $row['tiket_lunas'] ?> lunas</div>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-warning" onclick="editJadwal(<?= $row['id'] ?>, <?= $row['id_kereta'] ?>, '<?= htmlspecialchars($row['stasiun_awal']) ?>', '<?= htmlspecialchars($row['stasiun_tujuan']) ?>', '<?= $row['tanggal'] ?>', '<?= $row['waktu_berangkat'] ?>', '<?= $row['waktu_tiba'] ?>', <?= $row['tarif'] ?>, '<?= htmlspecialchars($row['status']) ?>')">
                                                ✏️ Edit
                                            </button>
                                            <a href="?toggle_status=<?= $row['id']; ?>" class="btn-info" onclick="return confirm('Ubah status jadwal ini?')">
                                                <?= $row['status'] == 'Tersedia' ? '🔴 Nonaktif' : '🟢 Aktifkan' ?>
                                            </a>
                                            <a href="?hapus=<?= $row['id']; ?>" class="btn-danger" onclick="return confirm('Hapus jadwal ini? Pastikan tidak ada tiket yang menggunakan jadwal ini!')">
                                                🗑️ Hapus
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">🕒</div>
                        <h4>Belum Ada Jadwal</h4>
                        <p>Tambahkan jadwal kereta untuk mulai mengelola perjalanan</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Action Buttons -->
            <div class="actions">
                <a href="dashboard.php" class="btn-secondary">
                    ← Kembali ke Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- Edit Schedule Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <div class="modal-header">
                <h3 class="modal-title">Edit Jadwal Kereta</h3>
            </div>
            <form method="POST" id="editForm">
                <input type="hidden" name="id" id="editId">
                <div class="form-row" style="margin-bottom: 15px;">
                    <div class="form-group">
                        <label class="form-label">Kereta</label>
                        <select name="id_kereta" id="editIdKereta" class="form-control" required>
                            <?php 
                            $kereta_list = $conn->query("SELECT * FROM kereta WHERE status='Aktif' ORDER BY nama_kereta ASC");
                            while ($k = $kereta_list->fetch_assoc()): 
                            ?>
                                <option value="<?= $k['id']; ?>"><?= htmlspecialchars($k['nama_kereta']); ?> - <?= htmlspecialchars($k['kelas']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Stasiun Awal</label>
                        <input type="text" name="stasiun_awal" id="editStasiunAwal" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Stasiun Tujuan</label>
                        <input type="text" name="stasiun_tujuan" id="editStasiunTujuan" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tanggal</label>
                        <input type="date" name="tanggal" id="editTanggal" class="form-control" required>
                    </div>
                </div>
                <div class="form-row" style="margin-bottom: 20px;">
                    <div class="form-group">
                        <label class="form-label">Waktu Berangkat</label>
                        <input type="time" name="waktu_berangkat" id="editWaktuBerangkat" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Waktu Tiba</label>
                        <input type="time" name="waktu_tiba" id="editWaktuTiba" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tarif (Rp)</label>
                        <input type="number" name="tarif" id="editTarif" class="form-control" min="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" id="editStatus" class="form-control" required>
                            <option value="Tersedia">Tersedia</option>
                            <option value="Tidak Tersedia">Tidak Tersedia</option>
                        </select>
                    </div>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" name="update" class="btn-success">Update Jadwal</button>
                    <button type="button" class="btn-secondary" onclick="closeModal()">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal functions
        function editJadwal(id, id_kereta, stasiun_awal, stasiun_tujuan, tanggal, waktu_berangkat, waktu_tiba, tarif, status) {
            document.getElementById('editId').value = id;
            document.getElementById('editIdKereta').value = id_kereta;
            document.getElementById('editStasiunAwal').value = stasiun_awal;
            document.getElementById('editStasiunTujuan').value = stasiun_tujuan;
            document.getElementById('editTanggal').value = tanggal;
            document.getElementById('editWaktuBerangkat').value = waktu_berangkat;
            document.getElementById('editWaktuTiba').value = waktu_tiba;
            document.getElementById('editTarif').value = tarif;
            document.getElementById('editStatus').value = status;
            document.getElementById('editModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }

        // Close modal with X button
        document.querySelector('.close').onclick = function() {
            closeModal();
        }

        // Set minimum date to today
        document.querySelectorAll('input[name="tanggal"]').forEach(input => {
            input.min = new Date().toISOString().split('T')[0];
        });

        // Format number input for tarif
        document.querySelectorAll('input[name="tarif"]').forEach(input => {
            input.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                e.target.value = value;
            });
        });

        // Validate time inputs
        function validateTimes() {
            const berangkat = document.querySelector('input[name="waktu_berangkat"]').value;
            const tiba = document.querySelector('input[name="waktu_tiba"]').value;
            
            if (berangkat && tiba && tiba <= berangkat) {
                alert('Waktu tiba harus lebih besar dari waktu berangkat!');
                return false;
            }
            return true;
        }

        // Add validation to forms
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                if (!validateTimes()) {
                    e.preventDefault();
                }
            });
        });

        // Auto-refresh every 60 seconds
        setInterval(function() {
            console.log('Checking for schedule updates...');
        }, 60000);
    </script>
</body>
</html>