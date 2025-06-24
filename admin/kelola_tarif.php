<?php
// Kelola Tarif
session_start();
if (!isset($_SESSION['admin'])) header('Location: login.php');
include '../includes/db.php';
$message = '';

if (isset($_POST['tambah'])) {
    $id_kereta = intval($_POST['id_kereta']);
    $stasiun_awal = $conn->real_escape_string($_POST['stasiun_awal']);
    $stasiun_tujuan = $conn->real_escape_string($_POST['stasiun_tujuan']);
    $tarif = intval($_POST['tarif']);
    $tanggal = $conn->real_escape_string($_POST['tanggal']);
    $waktu_berangkat = $conn->real_escape_string($_POST['waktu_berangkat']);
    $waktu_tiba = $conn->real_escape_string($_POST['waktu_tiba']);
    
    $conn->query("INSERT INTO jadwal_kereta (id_kereta, stasiun_awal, stasiun_tujuan, tanggal, waktu_berangkat, waktu_tiba, tarif, status) VALUES ($id_kereta, '$stasiun_awal', '$stasiun_tujuan', '$tanggal', '$waktu_berangkat', '$waktu_tiba', $tarif, 'Tersedia')");
    $message = '<div class="alert alert-success">Tarif dan jadwal berhasil ditambah!</div>';
}

if (isset($_POST['update'])) {
    $id = intval($_POST['id']);
    $tarif = intval($_POST['tarif']);
    $conn->query("UPDATE jadwal_kereta SET tarif=$tarif WHERE id=$id");
    $message = '<div class="alert alert-success">Tarif berhasil diupdate!</div>';
}

if (isset($_GET['hapus'])) {
    $id = intval($_GET['hapus']);
    $conn->query("DELETE FROM jadwal_kereta WHERE id=$id");
    $message = '<div class="alert alert-success">Jadwal dan tarif berhasil dihapus!</div>';
}

// Get trains for dropdown
$kereta_list = $conn->query("SELECT id, nama_kereta, jenis_kereta FROM kereta WHERE status='Aktif' ORDER BY nama_kereta");

// Get current tariffs with train details
$tarif_query = "SELECT jk.id, k.nama_kereta, jk.stasiun_awal, jk.stasiun_tujuan, jk.tanggal, jk.waktu_berangkat, jk.waktu_tiba, jk.tarif, jk.status 
                FROM jadwal_kereta jk 
                JOIN kereta k ON jk.id_kereta = k.id 
                ORDER BY jk.tanggal DESC, jk.waktu_berangkat ASC";
$tarif_data = $conn->query($tarif_query);

// Calculate statistics
$total_jadwal = 0;
$total_rute = 0;
$avg_tarif = 0;
$total_tarif = 0;
$jadwal_list = [];

while ($row = $tarif_data->fetch_assoc()) {
    $jadwal_list[] = $row;
    $total_jadwal++;
    $total_tarif += $row['tarif'];
}

if ($total_jadwal > 0) {
    $avg_tarif = $total_tarif / $total_jadwal;
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
    <title>Kelola Tarif - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/kelola_tarif.css" rel="stylesheet">

</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-card">
            <div class="header">
                <div class="header-left">
                    <div class="brand-logo">
                        💰
                    </div>
                    <div class="header-title">
                        <h1>Kelola Tarif Kereta</h1>
                        <p>Atur tarif dan jadwal perjalanan kereta api</p>
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
                <div class="stat-card routes">
                    <div class="stat-value"><?= $total_rute ?></div>
                    <div class="stat-label">Rute Tersedia</div>
                </div>
                <div class="stat-card average">
                    <div class="stat-value">Rp<?= number_format($avg_tarif, 0, ',', '.') ?></div>
                    <div class="stat-label">Rata-rata Tarif</div>
                </div>
                <div class="stat-card revenue">
                    <div class="stat-value">Rp<?= number_format($total_tarif, 0, ',', '.') ?></div>
                    <div class="stat-label">Total Potensi</div>
                </div>
            </div>

            <!-- Add New Schedule Form -->
            <div class="form-container">
                <h3 class="form-title">Tambah Jadwal & Tarif Baru</h3>
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Kereta</label>
                            <select name="id_kereta" class="form-control" required>
                                <option value="">Pilih Kereta</option>
                                <?php while ($kereta = $kereta_list->fetch_assoc()): ?>
                                    <option value="<?= $kereta['id'] ?>"><?= htmlspecialchars($kereta['nama_kereta']) ?> (<?= htmlspecialchars($kereta['jenis_kereta']) ?>)</option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Stasiun Awal</label>
                            <input type="text" name="stasiun_awal" class="form-control" placeholder="Contoh: Gambir" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Stasiun Tujuan</label>
                            <input type="text" name="stasiun_tujuan" class="form-control" placeholder="Contoh: Surabaya" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Tanggal</label>
                            <input type="date" name="tanggal" class="form-control" required>
                        </div>
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
                    </div>
                    <button type="submit" name="tambah" class="btn-success">
                        ➕ Tambah Jadwal & Tarif
                    </button>
                </form>
            </div>

            <!-- Data Table -->
            <div class="table-container">
                <div class="table-header">
                    <h3>Daftar Jadwal & Tarif</h3>
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
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($jadwal_list as $row): ?>
                                <tr>
                                    <td class="train-cell"><?= htmlspecialchars($row['nama_kereta']); ?></td>
                                    <td class="route-cell"><?= htmlspecialchars($row['stasiun_awal']); ?> → <?= htmlspecialchars($row['stasiun_tujuan']); ?></td>
                                    <td><?= date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                                    <td><?= date('H:i', strtotime($row['waktu_berangkat'])); ?> - <?= date('H:i', strtotime($row['waktu_tiba'])); ?></td>
                                    <td class="price-cell">Rp<?= number_format($row['tarif'], 0, ',', '.'); ?></td>
                                    <td>
                                        <span class="status-badge status-available"><?= htmlspecialchars($row['status']); ?></span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-warning" onclick="editTarif(<?= $row['id'] ?>, <?= $row['tarif'] ?>)">
                                                ✏️ Edit
                                            </button>
                                            <a href="?hapus=<?= $row['id']; ?>" class="btn-danger" onclick="return confirm('Hapus jadwal dan tarif ini?')">
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
                        <div class="empty-state-icon">💰</div>
                        <h4>Belum Ada Jadwal & Tarif</h4>
                        <p>Tambahkan jadwal dan tarif kereta untuk mulai mengelola perjalanan</p>
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

    <!-- Edit Tarif Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <div class="modal-header">
                <h3 class="modal-title">Edit Tarif</h3>
            </div>
            <form method="POST" id="editForm">
                <input type="hidden" name="id" id="editId">
                <div class="form-group">
                    <label class="form-label">Tarif Baru (Rp)</label>
                    <input type="number" name="tarif" id="editTarif" class="form-control" min="0" required>
                </div>
                <div style="margin-top: 20px; display: flex; gap: 10px;">
                    <button type="submit" name="update" class="btn-success">Update Tarif</button>
                    <button type="button" class="btn-secondary" onclick="closeModal()">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal functions
        function editTarif(id, currentTarif) {
            document.getElementById('editId').value = id;
            document.getElementById('editTarif').value = currentTarif;
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
        document.querySelector('input[name="tanggal"]').min = new Date().toISOString().split('T')[0];

        // Format number input for tarif
        document.querySelector('input[name="tarif"]').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            e.target.value = value;
        });

        // Auto-refresh every 60 seconds
        setInterval(function() {
            console.log('Checking for updates...');
        }, 60000);
    </script>
</body>
</html>