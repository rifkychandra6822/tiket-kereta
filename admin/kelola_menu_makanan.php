<?php
// Kelola Menu Makanan
session_start();
if (!isset($_SESSION['admin'])) header('Location: login.php');
include '../includes/db.php';
$message = '';

if (isset($_POST['tambah'])) {
    $nama = $conn->real_escape_string($_POST['nama']);
    $harga = intval($_POST['harga']);
    $status = isset($_POST['status']) ? 1 : 0;
    
    $conn->query("INSERT INTO menu_makanan (nama, harga, status) VALUES ('$nama', $harga, $status)");
    $message = '<div class="alert alert-success">Menu makanan berhasil ditambah!</div>';
}

if (isset($_POST['update'])) {
    $id = intval($_POST['id']);
    $nama = $conn->real_escape_string($_POST['nama']);
    $harga = intval($_POST['harga']);
    $status = isset($_POST['status']) ? 1 : 0;
    
    $conn->query("UPDATE menu_makanan SET nama='$nama', harga=$harga, status=$status WHERE id=$id");
    $message = '<div class="alert alert-success">Menu makanan berhasil diupdate!</div>';
}

if (isset($_GET['toggle_status'])) {
    $id = intval($_GET['toggle_status']);
    $conn->query("UPDATE menu_makanan SET status = NOT status WHERE id=$id");
    $message = '<div class="alert alert-success">Status menu berhasil diubah!</div>';
}

if (isset($_GET['hapus'])) {
    $id = intval($_GET['hapus']);
    $conn->query("DELETE FROM menu_makanan WHERE id=$id");
    $message = '<div class="alert alert-success">Menu makanan berhasil dihapus!</div>';
}

// Get menu data
$makanan_query = "SELECT * FROM menu_makanan ORDER BY id DESC";
$makanan_result = $conn->query($makanan_query);

// Calculate statistics
$total_menu = 0;
$menu_aktif = 0;
$menu_nonaktif = 0;
$avg_harga = 0;
$total_harga = 0;
$menu_list = [];

while ($row = $makanan_result->fetch_assoc()) {
    $menu_list[] = $row;
    $total_menu++;
    if ($row['status']) {
        $menu_aktif++;
    } else {
        $menu_nonaktif++;
    }
    $total_harga += $row['harga'];
}

if ($total_menu > 0) {
    $avg_harga = $total_harga / $total_menu;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Menu Makanan - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-card">
            <div class="header">
                <div class="header-left">
                    <div class="brand-logo">
                        🍽️
                    </div>
                    <div class="header-title">
                        <h1>Kelola Menu Makanan</h1>
                        <p>Atur menu makanan dan minuman untuk penumpang</p>
                    </div>
                </div>
            </div>

            <!-- Alert Messages -->
            <?= $message; ?>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card total">
                    <div class="stat-value"><?= $total_menu ?></div>
                    <div class="stat-label">Total Menu</div>
                </div>
                <div class="stat-card active">
                    <div class="stat-value"><?= $menu_aktif ?></div>
                    <div class="stat-label">Menu Aktif</div>
                </div>
                <div class="stat-card inactive">
                    <div class="stat-value"><?= $menu_nonaktif ?></div>
                    <div class="stat-label">Menu Non-Aktif</div>
                </div>
                <div class="stat-card average">
                    <div class="stat-value">Rp<?= number_format($avg_harga, 0, ',', '.') ?></div>
                    <div class="stat-label">Rata-rata Harga</div>
                </div>
            </div>

            <!-- Add New Menu Form -->
            <div class="form-container">
                <h3 class="form-title">Tambah Menu Baru</h3>
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Nama Menu</label>
                            <input type="text" name="nama" class="form-control" placeholder="Contoh: Nasi Goreng Spesial" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Harga (Rp)</label>
                            <input type="number" name="harga" class="form-control" placeholder="35000" min="0" required>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" name="status" id="status" checked>
                            <label for="status">Menu Aktif</label>
                        </div>
                        <button type="submit" name="tambah" class="btn-success">
                            ➕ Tambah Menu
                        </button>
                    </div>
                </form>
            </div>

            <!-- Data Table -->
            <div class="table-container">
                <div class="table-header">
                    <h3>Daftar Menu Makanan</h3>
                </div>
                
                <?php if (count($menu_list) > 0): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nama Menu</th>
                                <th>Harga</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($menu_list as $row): ?>
                                <tr>
                                    <td class="menu-name-cell"><?= htmlspecialchars($row['nama']); ?></td>
                                    <td class="price-cell">Rp<?= number_format($row['harga'], 0, ',', '.'); ?></td>
                                    <td>
                                        <?php if ($row['status']): ?>
                                            <span class="status-badge status-active">Aktif</span>
                                        <?php else: ?>
                                            <span class="status-badge status-inactive">Non-Aktif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-warning" onclick="editMenu(<?= $row['id'] ?>, '<?= htmlspecialchars($row['nama']) ?>', <?= $row['harga'] ?>, <?= $row['status'] ?>)">
                                                ✏️ Edit
                                            </button>
                                            <a href="?toggle_status=<?= $row['id']; ?>" class="btn-info" onclick="return confirm('Ubah status menu ini?')">
                                                <?= $row['status'] ? '🔴 Nonaktifkan' : '🟢 Aktifkan' ?>
                                            </a>
                                            <a href="?hapus=<?= $row['id']; ?>" class="btn-danger" onclick="return confirm('Hapus menu ini? Tindakan ini tidak dapat dibatalkan!')">
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
                        <div class="empty-state-icon">🍽️</div>
                        <h4>Belum Ada Menu Makanan</h4>
                        <p>Tambahkan menu makanan dan minuman untuk penumpang</p>
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

    <!-- Edit Menu Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <div class="modal-header">
                <h3 class="modal-title">Edit Menu Makanan</h3>
            </div>
            <form method="POST" id="editForm">
                <input type="hidden" name="id" id="editId">
                <div class="form-group" style="margin-bottom: 15px;">
                    <label class="form-label">Nama Menu</label>
                    <input type="text" name="nama" id="editNama" class="form-control" required>
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label class="form-label">Harga (Rp)</label>
                    <input type="number" name="harga" id="editHarga" class="form-control" min="0" required>
                </div>
                <div class="form-check" style="margin-bottom: 20px;">
                    <input type="checkbox" name="status" id="editStatus">
                    <label for="editStatus">Menu Aktif</label>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" name="update" class="btn-success">Update Menu</button>
                    <button type="button" class="btn-secondary" onclick="closeModal()">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal functions
        function editMenu(id, nama, harga, status) {
            document.getElementById('editId').value = id;
            document.getElementById('editNama').value = nama;
            document.getElementById('editHarga').value = harga;
            document.getElementById('editStatus').checked = status == 1;
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

        // Format number input for harga
        document.querySelectorAll('input[name="harga"]').forEach(input => {
            input.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                e.target.value = value;
            });
        });

        // Auto-refresh every 60 seconds
        setInterval(function() {
            console.log('Checking for menu updates...');
        }, 60000);

        // Confirmation for status toggle
        document.querySelectorAll('a[href*="toggle_status"]').forEach(link => {
            link.addEventListener('click', function(e) {
                const isActive = this.textContent.includes('Nonaktifkan');
                const action = isActive ? 'menonaktifkan' : 'mengaktifkan';
                if (!confirm(`Apakah Anda yakin ingin ${action} menu ini?`)) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>