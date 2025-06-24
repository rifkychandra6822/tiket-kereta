<?php
// Kelola Menu Makanan
session_start();
if (!isset($_SESSION['admin'])) header('Location: login.php');
include '../includes/db.php';
$message = '';

// PRG pattern: handle POST, then redirect
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah'])) {
    $nama = trim($conn->real_escape_string($_POST['nama']));
    $harga = intval($_POST['harga']);
    $status = isset($_POST['status']) ? 1 : 0;
    
    // Cek duplikat nama menu
    $cek = $conn->query("SELECT COUNT(*) as jml FROM menu_makanan WHERE nama='$nama'");
    $rowCek = $cek ? $cek->fetch_assoc() : ['jml'=>0];
    if ($nama === '' || $harga < 0) {
        $message = '<div class="alert alert-danger">Nama dan harga harus diisi dengan benar.</div>';
    } else if ($rowCek['jml'] > 0) {
        $message = '<div class="alert alert-danger">Menu dengan nama yang sama sudah ada.</div>';
    } else {
        $insert = $conn->query("INSERT INTO menu_makanan (nama, harga, status) VALUES ('$nama', $harga, $status)");
        if ($insert) {
            header('Location: kelola_menu_makanan.php?success=1&nama=' . urlencode($nama));
            exit();
        } else {
            $message = '<div class="alert alert-danger">Gagal menambah menu: ' . $conn->error . '</div>';
        }
    }
}

if (isset($_GET['success']) && isset($_GET['nama'])) {
    $message = '<div class="alert alert-success">Menu makanan <b>' . htmlspecialchars($_GET['nama']) . '</b> berhasil ditambah!</div>';
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
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Menu Makanan - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/login_admin.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { min-height: 0; height: auto; overflow-y: auto !important; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        html { height: auto; }
        .dashboard-container { max-width: 900px; margin: 2rem auto; }
        .dashboard-card { background: #fff; border-radius: 24px; box-shadow: 0 8px 32px rgba(0,0,0,0.08); padding: 2rem; z-index: 1; position: relative; }
        .header { display: flex; align-items: center; margin-bottom: 2rem; }
        .header-left { display: flex; align-items: center; gap: 1.5rem; }
        .brand-logo { width: 60px; height: 60px; background: var(--gradient-primary); border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: #fff; box-shadow: 0 4px 16px rgba(102,126,234,0.15); }
        .header-title h1 { font-size: 2rem; font-weight: 700; margin: 0; }
        .header-title p { color: var(--text-light); margin: 0.25rem 0 0 0; }
        .stats-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 1rem; margin-bottom: 2rem; }
        .stat-card { background: var(--background-light); border-radius: 16px; padding: 1.25rem; text-align: center; box-shadow: 0 2px 8px rgba(59,130,246,0.04); }
        .stat-value { font-size: 1.5rem; font-weight: 700; color: var(--primary-blue); }
        .stat-label { color: var(--text-light); font-size: 0.95rem; }
        .form-container { background: #f9fafb; border-radius: 16px; padding: 1.5rem; margin-bottom: 2rem; box-shadow: 0 2px 8px rgba(59,130,246,0.04); }
        .form-title { font-size: 1.2rem; font-weight: 600; margin-bottom: 1rem; }
        .form-row { display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end; }
        .form-group { flex: 1 1 200px; }
        .form-check { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; }
        .btn-success, .btn-warning, .btn-danger, .btn-info, .btn-secondary { border: none; border-radius: 8px; padding: 0.6rem 1.2rem; font-weight: 600; cursor: pointer; transition: background 0.2s; pointer-events: auto; }
        .btn-success { background: linear-gradient(90deg,#10b981,#3b82f6); color: #fff; }
        .btn-warning { background: #f59e0b; color: #fff; }
        .btn-danger { background: #ef4444; color: #fff; }
        .btn-info { background: #3b82f6; color: #fff; }
        .btn-secondary { background: #e5e7eb; color: #1e40af; }
        .btn-success:hover { background: #059669; }
        .btn-warning:hover { background: #d97706; }
        .btn-danger:hover { background: #b91c1c; }
        .btn-info:hover { background: #2563eb; }
        .btn-secondary:hover { background: #cbd5e1; }
        .table-container { margin-top: 2rem; }
        .table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 2px 8px rgba(59,130,246,0.04); }
        .table th, .table td { padding: 0.9rem 1rem; text-align: left; border-bottom: 1px solid #e5e7eb; }
        .table th { background: #f3f4f6; font-weight: 600; color: var(--primary-blue); }
        .table tr:last-child td { border-bottom: none; }
        .status-badge { padding: 0.3rem 0.9rem; border-radius: 12px; font-size: 0.95rem; font-weight: 600; }
        .status-active { background: #d1fae5; color: #059669; }
        .status-inactive { background: #fee2e2; color: #b91c1c; }
        .action-buttons { display: flex; gap: 0.5rem; pointer-events: auto !important; }
        .empty-state { text-align: center; color: var(--text-light); margin: 2rem 0; }
        .empty-state-icon { font-size: 2.5rem; margin-bottom: 0.5rem; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100vw; height: 100vh; overflow: auto; background: rgba(31,41,55,0.25); }
        .modal-content { background: #fff; margin: 5% auto; border-radius: 16px; padding: 2rem; width: 100%; max-width: 400px; box-shadow: 0 8px 32px rgba(0,0,0,0.12); position: relative; }
        .modal-header { margin-bottom: 1rem; }
        .modal-title { font-size: 1.2rem; font-weight: 600; }
        .close { position: absolute; right: 1.2rem; top: 1.2rem; font-size: 1.5rem; color: #6b7280; cursor: pointer; }
        @media (max-width: 900px) { .dashboard-container { padding: 0 0.5rem; } .stats-grid { grid-template-columns: 1fr 1fr; } }
        @media (max-width: 600px) { .dashboard-card { padding: 1rem; } .stats-grid { grid-template-columns: 1fr; } .form-row { flex-direction: column; } }
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
                                            <button class="btn-warning" type="button" onclick="editMenu(<?= $row['id'] ?>, '<?= htmlspecialchars($row['nama'], ENT_QUOTES) ?>', <?= $row['harga'] ?>, <?= $row['status'] ?>)">✏️ Edit</button>
                                            <a href="?toggle_status=<?= $row['id']; ?>" class="btn-info" onclick="return confirm('Ubah status menu ini?')">
                                                <?= $row['status'] ? '🔴 Nonaktifkan' : '🟢 Aktifkan' ?>
                                            </a>
                                            <a href="?hapus=<?= $row['id']; ?>" class="btn-danger" onclick="return confirm('Hapus menu ini? Tindakan ini tidak dapat dibatalkan!')">
                                                🗑️ Hapus
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
                    <label class="form-label" for="editNama">Nama Menu</label>
                    <input type="text" name="nama" id="editNama" class="form-control" required>
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label class="form-label" for="editHarga">Harga (Rp)</label>
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