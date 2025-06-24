<?php
// Kelola Data Kereta
session_start();
if (!isset($_SESSION['admin'])) header('Location: login.php');
include '../includes/db.php';
$message = '';

if (isset($_POST['tambah'])) {
    $nama = $conn->real_escape_string($_POST['nama']);
    $jenis = $conn->real_escape_string($_POST['jenis']);
    $kapasitas = intval($_POST['kapasitas']);
    $kelas = $conn->real_escape_string($_POST['kelas']);
    $status = isset($_POST['status']) ? $_POST['status'] : 'Aktif';
    
    $conn->query("INSERT INTO kereta (nama_kereta, jenis_kereta, kapasitas, kelas, status) VALUES ('$nama', '$jenis', $kapasitas, '$kelas', '$status')");
    $message = '<div class="alert alert-success">Data kereta berhasil ditambah!</div>';
}

if (isset($_POST['update'])) {
    $id = intval($_POST['id']);
    $nama = $conn->real_escape_string($_POST['nama']);
    $jenis = $conn->real_escape_string($_POST['jenis']);
    $kapasitas = intval($_POST['kapasitas']);
    $kelas = $conn->real_escape_string($_POST['kelas']);
    $status = $conn->real_escape_string($_POST['status']);
    
    $conn->query("UPDATE kereta SET nama_kereta='$nama', jenis_kereta='$jenis', kapasitas=$kapasitas, kelas='$kelas', status='$status' WHERE id=$id");
    $message = '<div class="alert alert-success">Data kereta berhasil diupdate!</div>';
}

if (isset($_GET['toggle_status'])) {
    $id = intval($_GET['toggle_status']);
    $conn->query("UPDATE kereta SET status = CASE WHEN status = 'Aktif' THEN 'Non-Aktif' ELSE 'Aktif' END WHERE id=$id");
    $message = '<div class="alert alert-success">Status kereta berhasil diubah!</div>';
}

if (isset($_GET['hapus'])) {
    $id = intval($_GET['hapus']);
    // Check if train is used in schedules
    $check = $conn->query("SELECT COUNT(*) as count FROM jadwal_kereta WHERE id_kereta=$id");
    $check_result = $check->fetch_assoc();
    
    if ($check_result['count'] > 0) {
        $message = '<div class="alert alert-warning">Kereta tidak dapat dihapus karena masih digunakan dalam jadwal!</div>';
    } else {
        $conn->query("DELETE FROM kereta WHERE id=$id");
        $message = '<div class="alert alert-success">Data kereta berhasil dihapus!</div>';
    }
}

// Get train data with usage statistics
$kereta_query = "SELECT k.*, 
                 COUNT(jk.id) as total_jadwal,
                 COUNT(CASE WHEN jk.status = 'Tersedia' THEN 1 END) as jadwal_aktif
                 FROM kereta k 
                 LEFT JOIN jadwal_kereta jk ON k.id = jk.id_kereta 
                 GROUP BY k.id 
                 ORDER BY k.id DESC";
$kereta_result = $conn->query($kereta_query);

// Calculate statistics
$total_kereta = 0;
$kereta_aktif = 0;
$kereta_nonaktif = 0;
$total_kapasitas = 0;
$avg_kapasitas = 0;
$kereta_list = [];

while ($row = $kereta_result->fetch_assoc()) {
    $kereta_list[] = $row;
    $total_kereta++;
    if ($row['status'] == 'Aktif') {
        $kereta_aktif++;
    } else {
        $kereta_nonaktif++;
    }
    $total_kapasitas += $row['kapasitas'];
}

if ($total_kereta > 0) {
    $avg_kapasitas = $total_kapasitas / $total_kereta;
}

// Get class statistics
$kelas_stats = $conn->query("SELECT kelas, COUNT(*) as count FROM kereta GROUP BY kelas");
$kelas_data = [];
while ($row = $kelas_stats->fetch_assoc()) {
    $kelas_data[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Data Kereta - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/kelola_kereta.css" rel="stylesheet">

</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-card">
            <div class="header">
                <div class="header-left">
                    <div class="brand-logo">
                        🚂
                    </div>
                    <div class="header-title">
                        <h1>Kelola Data Kereta</h1>
                        <p>Atur informasi kereta api dan spesifikasinya</p>
                    </div>
                </div>
            </div>

            <!-- Alert Messages -->
            <?= $message; ?>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card total">
                    <div class="stat-value"><?= $total_kereta ?></div>
                    <div class="stat-label">Total Kereta</div>
                </div>
                <div class="stat-card active">
                    <div class="stat-value"><?= $kereta_aktif ?></div>
                    <div class="stat-label">Kereta Aktif</div>
                </div>
                <div class="stat-card inactive">
                    <div class="stat-value"><?= $kereta_nonaktif ?></div>
                    <div class="stat-label">Kereta Non-Aktif</div>
                </div>
                <div class="stat-card capacity">
                    <div class="stat-value"><?= number_format($avg_kapasitas, 0) ?></div>
                    <div class="stat-label">Rata-rata Kapasitas</div>
                </div>
            </div>

            <!-- Add New Train Form -->
            <div class="form-container">
                <h3 class="form-title">Tambah Kereta Baru</h3>
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Nama Kereta</label>
                            <input type="text" name="nama" class="form-control" placeholder="Contoh: Argo Bromo Anggrek" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Jenis Kereta</label>
                            <select name="jenis" class="form-control" required>
                                <option value="">Pilih Jenis</option>
                                <option value="Eksekutif">Eksekutif</option>
                                <option value="Bisnis">Bisnis</option>
                                <option value="Ekonomi">Ekonomi</option>
                                <option value="Campuran">Campuran</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Kapasitas</label>
                            <input type="number" name="kapasitas" class="form-control" placeholder="350" min="1" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Kelas</label>
                            <select name="kelas" class="form-control" required>
                                <option value="">Pilih Kelas</option>
                                <option value="Eksekutif">Eksekutif</option>
                                <option value="Bisnis">Bisnis</option>
                                <option value="Ekonomi">Ekonomi</option>
                            </select>
                        </div>
                        <button type="submit" name="tambah" class="btn-success">
                            ➕ Tambah Kereta
                        </button>
                    </div>
                </form>
            </div>

            <!-- Data Table -->
            <div class="table-container">
                <div class="table-header">
                    <h3>Daftar Kereta</h3>
                </div>
                
                <?php if (count($kereta_list) > 0): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nama Kereta</th>
                                <th>Jenis</th>
                                <th>Kapasitas</th>
                                <th>Kelas</th>
                                <th>Status</th>
                                <th>Jadwal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($kereta_list as $row): ?>
                                <tr>
                                    <td class="train-name-cell"><?= htmlspecialchars($row['nama_kereta']); ?></td>
                                    <td><?= htmlspecialchars($row['jenis_kereta']); ?></td>
                                    <td class="capacity-cell"><?= number_format($row['kapasitas']); ?> penumpang</td>
                                    <td>
                                        <span class="class-badge class-<?= strtolower($row['kelas']) ?>">
                                            <?= htmlspecialchars($row['kelas']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($row['status'] == 'Aktif'): ?>
                                            <span class="status-badge status-active">Aktif</span>
                                        <?php else: ?>
                                            <span class="status-badge status-inactive">Non-Aktif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div><?= $row['total_jadwal'] ?> total</div>
                                        <div class="schedule-info"><?= $row['jadwal_aktif'] ?> aktif</div>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-warning" onclick="editKereta(<?= $row['id'] ?>, '<?= htmlspecialchars($row['nama_kereta']) ?>', '<?= htmlspecialchars($row['jenis_kereta']) ?>', <?= $row['kapasitas'] ?>, '<?= htmlspecialchars($row['kelas']) ?>', '<?= htmlspecialchars($row['status']) ?>')">
                                                ✏️ Edit
                                            </button>
                                            <a href="?toggle_status=<?= $row['id']; ?>" class="btn-info" onclick="return confirm('Ubah status kereta ini?')">
                                                <?= $row['status'] == 'Aktif' ? '🔴 Nonaktifkan' : '🟢 Aktifkan' ?>
                                            </a>
                                            <a href="?hapus=<?= $row['id']; ?>" class="btn-danger" onclick="return confirm('Hapus kereta ini? Pastikan kereta tidak digunakan dalam jadwal!')">
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
                        <div class="empty-state-icon">🚂</div>
                        <h4>Belum Ada Data Kereta</h4>
                        <p>Tambahkan kereta untuk mulai mengelola armada</p>
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

    <!-- Edit Train Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <div class="modal-header">
                <h3 class="modal-title">Edit Data Kereta</h3>
            </div>
            <form method="POST" id="editForm">
                <input type="hidden" name="id" id="editId">
                <div class="form-group" style="margin-bottom: 15px;">
                    <label class="form-label">Nama Kereta</label>
                    <input type="text" name="nama" id="editNama" class="form-control" required>
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label class="form-label">Jenis Kereta</label>
                    <select name="jenis" id="editJenis" class="form-control" required>
                        <option value="Eksekutif">Eksekutif</option>
                        <option value="Bisnis">Bisnis</option>
                        <option value="Ekonomi">Ekonomi</option>
                        <option value="Campuran">Campuran</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label class="form-label">Kapasitas</label>
                    <input type="number" name="kapasitas" id="editKapasitas" class="form-control" min="1" required>
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label class="form-label">Kelas</label>
                    <select name="kelas" id="editKelas" class="form-control" required>
                        <option value="Eksekutif">Eksekutif</option>
                        <option value="Bisnis">Bisnis</option>
                        <option value="Ekonomi">Ekonomi</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 20px;">
                    <label class="form-label">Status</label>
                    <select name="status" id="editStatus" class="form-control" required>
                        <option value="Aktif">Aktif</option>
                        <option value="Non-Aktif">Non-Aktif</option>
                    </select>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" name="update" class="btn-success">Update Kereta</button>
                    <button type="button" class="btn-secondary" onclick="closeModal()">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal functions
        function editKereta(id, nama, jenis, kapasitas, kelas, status) {
            document.getElementById('editId').value = id;
            document.getElementById('editNama').value = nama;
            document.getElementById('editJenis').value = jenis;
            document.getElementById('editKapasitas').value = kapasitas;
            document.getElementById('editKelas').value = kelas;
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

        // Format number input for capacity
        document.querySelectorAll('input[name="kapasitas"]').forEach(input => {
            input.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                e.target.value = value;
            });
        });

        // Auto-refresh every 60 seconds
        setInterval(function() {
            console.log('Checking for train updates...');
        }, 60000);

        // Confirmation for status toggle
        document.querySelectorAll('a[href*="toggle_status"]').forEach(link => {
            link.addEventListener('click', function(e) {
                const isActive = this.textContent.includes('Nonaktifkan');
                const action = isActive ? 'menonaktifkan' : 'mengaktifkan';
                if (!confirm(`Apakah Anda yakin ingin ${action} kereta ini?`)) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>