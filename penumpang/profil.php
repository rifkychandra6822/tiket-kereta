<?php
// Profil Penumpang
session_start();
if (!isset($_SESSION['user']))
    header('Location: login.php');
include '../includes/db.php';

$user = $_SESSION['user'];
$message = '';
$user_id = $_SESSION['user']['id'];

// Ambil data lengkap pengguna dari database
$pengguna_query = "SELECT * FROM pengguna WHERE id = $user_id";
$pengguna_result = $conn->query($pengguna_query);
$pengguna_data = $pengguna_result->fetch_assoc();

// Ambil data penumpang jika ada
$penumpang_query = "SELECT * FROM penumpang WHERE id_pengguna = $user_id";
$penumpang_result = $conn->query($penumpang_query);
$penumpang_data = $penumpang_result->fetch_assoc();

// Ambil statistik tiket
$tiket_stats = $conn->query("
    SELECT 
        COUNT(*) as total_tiket,
        SUM(CASE WHEN status_tiket = 'Lunas' THEN 1 ELSE 0 END) as tiket_lunas,
        SUM(CASE WHEN status_tiket = 'Pending' THEN 1 ELSE 0 END) as tiket_pending,
        SUM(CASE WHEN status_tiket = 'Lunas' THEN total_pembayaran ELSE 0 END) as total_pengeluaran
    FROM tiket t
    JOIN penumpang p ON t.id_penumpang = p.id
    WHERE p.id_pengguna = $user_id
")->fetch_assoc();

if (isset($_POST['update'])) {
    $nama = $conn->real_escape_string($_POST['nama']);
    $email = $conn->real_escape_string($_POST['email']);
    $alamat = $conn->real_escape_string($_POST['alamat']);
    $nomor_hp = $conn->real_escape_string($_POST['nomor_hp']);
    $tanggal_lahir = $_POST['tanggal_lahir'] ? $conn->real_escape_string($_POST['tanggal_lahir']) : null;

    // Update password jika diisi
    $password_update = "";
    if (!empty($_POST['password'])) {
        $password_hash = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $password_update = ", password = '$password_hash'";
    }

    // Update tabel pengguna
    $update_pengguna = "UPDATE pengguna SET 
                        nama = '$nama', 
                        email = '$email', 
                        alamat = '$alamat', 
                        nomor_hp = '$nomor_hp'
                        $password_update
                        WHERE id = $user_id";

    if ($conn->query($update_pengguna)) {
        // Update atau insert data penumpang
        if ($penumpang_data) {
            if ($tanggal_lahir) {
                $conn->query("UPDATE penumpang SET tanggal_lahir = '$tanggal_lahir' WHERE id_pengguna = $user_id");
            }
        } else {
            if ($tanggal_lahir) {
                $conn->query("INSERT INTO penumpang (id_pengguna, tanggal_lahir) VALUES ($user_id, '$tanggal_lahir')");
            }
        }

        // Update session
        $_SESSION['user']['nama'] = $nama;
        $_SESSION['user']['email'] = $email;

        $message = '<div class="alert alert-success alert-custom">
                        <i class="bi bi-check-circle me-2"></i>
                        Profil berhasil diperbarui!
                    </div>';

        // Refresh data
        $pengguna_result = $conn->query($pengguna_query);
        $pengguna_data = $pengguna_result->fetch_assoc();
        $penumpang_result = $conn->query($penumpang_query);
        $penumpang_data = $penumpang_result->fetch_assoc();
    } else {
        $message = '<div class="alert alert-danger alert-custom">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Gagal memperbarui profil!
                    </div>';
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Profil Saya - Kereta Connect</title>
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

        .profile-container {
            min-height: 100vh;
            padding-top: 20px;
        }

        .profile-header {
            background: var(--gradient-primary);
            color: white;
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .profile-header::before {
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

        .avatar-section {
            text-align: center;
            margin-bottom: 20px;
        }

        .avatar {
            width: 120px;
            height: 120px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            margin: 0 auto 15px;
            border: 4px solid rgba(255, 255, 255, 0.3);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--white);
            border-radius: 16px;
            padding: 25px;
            text-align: center;
            box-shadow: var(--shadow-soft);
            border: none;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-medium);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin: 0 auto 15px;
        }

        .stat-primary {
            background: rgba(30, 64, 175, 0.1);
            color: var(--primary-blue);
        }

        .stat-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        .stat-warning {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }

        .stat-info {
            background: rgba(59, 130, 246, 0.1);
            color: var(--secondary-blue);
        }

        .profile-form-card {
            background: var(--white);
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--shadow-soft);
            border: none;
        }

        .form-section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e2e8f0;
        }

        .form-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .section-title {
            color: var(--text-dark);
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
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

        .member-since {
            background: rgba(96, 165, 250, 0.1);
            border-left: 4px solid var(--accent-blue);
            border-radius: 8px;
            padding: 15px 20px;
            margin-top: 20px;
        }

        @media (max-width: 768px) {
            .profile-header {
                padding: 25px;
                text-align: center;
            }

            .avatar {
                width: 100px;
                height: 100px;
                font-size: 2.5rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
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
                        <a class="nav-link" href="pesan_tiket.php">
                            <i class="bi bi-plus-circle me-1"></i>Pesan Tiket
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
                            <?= htmlspecialchars($pengguna_data['nama'] ?? explode('@', $pengguna_data['email'])[0]); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item active" href="profil.php">
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

    <div class="profile-container">
        <div class="container">
            <!-- Profile Header -->
            <div class="profile-header">
                <div class="row align-items-center">
                    <div class="col-md-3">
                        <div class="avatar-section">
                            <div class="avatar">
                                <i class="bi bi-person-fill"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <h2 class="fw-bold mb-2">
                            <?= htmlspecialchars($pengguna_data['nama'] ?? 'Pengguna'); ?>
                        </h2>
                        <p class="mb-1 opacity-90">
                            <i class="bi bi-envelope me-2"></i>
                            <?= htmlspecialchars($pengguna_data['email']); ?>
                        </p>
                        <?php if ($pengguna_data['nomor_hp']): ?>
                            <p class="mb-1 opacity-90">
                                <i class="bi bi-phone me-2"></i>
                                <?= htmlspecialchars($pengguna_data['nomor_hp']); ?>
                            </p>
                        <?php endif; ?>
                        <p class="mb-0 opacity-90">
                            <i class="bi bi-shield-check me-2"></i>
                            Status: <?= $pengguna_data['validasi_akun'] ? 'Terverifikasi' : 'Belum Terverifikasi'; ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon stat-primary">
                        <i class="bi bi-ticket-perforated"></i>
                    </div>
                    <h4 class="fw-bold text-primary"><?= $tiket_stats['total_tiket'] ?? 0; ?></h4>
                    <p class="text-muted mb-0">Total Tiket</p>
                </div>

                <div class="stat-card">
                    <div class="stat-icon stat-success">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <h4 class="fw-bold text-success"><?= $tiket_stats['tiket_lunas'] ?? 0; ?></h4>
                    <p class="text-muted mb-0">Tiket Lunas</p>
                </div>

                <div class="stat-card">
                    <div class="stat-icon stat-warning">
                        <i class="bi bi-clock"></i>
                    </div>
                    <h4 class="fw-bold text-warning"><?= $tiket_stats['tiket_pending'] ?? 0; ?></h4>
                    <p class="text-muted mb-0">Tiket Pending</p>
                </div>

                <div class="stat-card">
                    <div class="stat-icon stat-info">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <h4 class="fw-bold text-info">
                        Rp<?= number_format($tiket_stats['total_pengeluaran'] ?? 0, 0, ',', '.'); ?></h4>
                    <p class="text-muted mb-0">Total Pengeluaran</p>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <!-- Profile Form -->
                    <div class="profile-form-card">
                        <h4 class="section-title">
                            <i class="bi bi-person-gear"></i>Edit Profil
                        </h4>

                        <?= $message; ?>

                        <form method="POST" id="profileForm">
                            <!-- Informasi Pribadi -->
                            <div class="form-section">
                                <h6 class="section-title">
                                    <i class="bi bi-person"></i>Informasi Pribadi
                                </h6>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">
                                                <i class="bi bi-person text-primary"></i>Nama Lengkap
                                            </label>
                                            <input type="text" name="nama" class="form-control form-control-enhanced"
                                                value="<?= htmlspecialchars($pengguna_data['nama'] ?? ''); ?>" required>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">
                                                <i class="bi bi-envelope text-primary"></i>Email
                                            </label>
                                            <input type="email" name="email" class="form-control form-control-enhanced"
                                                value="<?= htmlspecialchars($pengguna_data['email']); ?>" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">
                                                <i class="bi bi-phone text-primary"></i>Nomor HP
                                            </label>
                                            <input type="tel" name="nomor_hp" class="form-control form-control-enhanced"
                                                value="<?= htmlspecialchars($pengguna_data['nomor_hp'] ?? ''); ?>"
                                                placeholder="08xxxxxxxxxx">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">
                                                <i class="bi bi-calendar text-primary"></i>Tanggal Lahir
                                            </label>
                                            <input type="date" name="tanggal_lahir"
                                                class="form-control form-control-enhanced"
                                                value="<?= $penumpang_data['tanggal_lahir'] ?? ''; ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="bi bi-geo-alt text-primary"></i>Alamat
                                    </label>
                                    <textarea name="alamat" class="form-control form-control-enhanced" rows="3"
                                        placeholder="Masukkan alamat lengkap Anda"><?= htmlspecialchars($pengguna_data['alamat'] ?? ''); ?></textarea>
                                </div>
                            </div>

                            <!-- Keamanan -->
                            <div class="form-section">
                                <h6 class="section-title">
                                    <i class="bi bi-shield-lock"></i>Keamanan Akun
                                </h6>

                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="bi bi-key text-warning"></i>Password Baru
                                    </label>
                                    <input type="password" name="password" class="form-control form-control-enhanced"
                                        placeholder="Kosongkan jika tidak ingin mengubah password">
                                    <small class="text-muted">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Minimal 6 karakter untuk keamanan yang lebih baik
                                    </small>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="dashboard.php" class="btn btn-outline-secondary me-md-2">
                                    <i class="bi bi-arrow-left me-2"></i>Kembali
                                </a>
                                <button type="submit" name="update" class="btn btn-primary-custom">
                                    <i class="bi bi-check-circle me-2"></i>Simpan Perubahan
                                </button>
                            </div>
                        </form>

                        <!-- Member Since -->
                        <div class="member-since">
                            <h6 class="fw-bold text-primary mb-2">
                                <i class="bi bi-calendar-check me-2"></i>Informasi Keanggotaan
                            </h6>
                            <p class="mb-1">
                                <strong>Bergabung sejak:</strong>
                                <?= date('d F Y', strtotime($pengguna_data['tanggal_daftar'])); ?>
                            </p>
                            <p class="mb-0">
                                <strong>Status Akun:</strong>
                                <span class="badge bg-<?= $pengguna_data['validasi_akun'] ? 'success' : 'warning'; ?>">
                                    <?= $pengguna_data['validasi_akun'] ? 'Terverifikasi' : 'Belum Terverifikasi'; ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Page load animation
        window.addEventListener('load', function () {
            const cards = document.querySelectorAll('.stat-card, .profile-form-card');
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

        // Form validation
        document.getElementById('profileForm').addEventListener('submit', function (e) {
            const password = document.querySelector('input[name="password"]').value;

            if (password && password.length < 6) {
                e.preventDefault();
                alert('Password minimal harus 6 karakter!');
                return false;
            }

            // Phone number validation
            const phone = document.querySelector('input[name="nomor_hp"]').value;
            if (phone && !phone.match(/^08\d{8,12}$/)) {
                e.preventDefault();
                alert('Format nomor HP tidak valid! Gunakan format 08xxxxxxxxxx');
                return false;
            }
        });

        // Auto-format phone number
        document.querySelector('input[name="nomor_hp"]').addEventListener('input', function (e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 0 && !value.startsWith('08')) {
                value = '08' + value;
            }
            e.target.value = value;
        });

        // Avatar click for future file upload feature
        document.querySelector('.avatar').addEventListener('click', function () {
            // Future: implement avatar upload
            console.log('Avatar upload feature - coming soon!');
        });
    </script>
</body>

</html>