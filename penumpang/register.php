<?php
// Registrasi Penumpang
include '../includes/db.php';
$message = '';

if (isset($_POST['register'])) {
    $nama = $conn->real_escape_string($_POST['nama']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $alamat = $conn->real_escape_string($_POST['alamat']);
    $nomor_hp = $conn->real_escape_string($_POST['nomor_hp']);
    $tanggal_lahir = $_POST['tanggal_lahir'] ? $conn->real_escape_string($_POST['tanggal_lahir']) : null;

    // Validasi password
    if (strlen($_POST['password']) < 6) {
        $message = '<div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Password minimal 6 karakter!
                    </div>';
    } else {
        // Cek email di tabel pengguna
        $cek = $conn->query("SELECT * FROM pengguna WHERE email='$email'");
        if ($cek->num_rows > 0) {
            $message = '<div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Email sudah terdaftar! Silakan gunakan email lain.
                        </div>';
        } else {
            // Begin transaction
            $conn->begin_transaction();

            try {
                $tanggal_daftar = date('Y-m-d');

                // Insert ke tabel pengguna
                $query_pengguna = "INSERT INTO pengguna (nama, email, password, alamat, nomor_hp, tanggal_daftar, validasi_akun) 
                                   VALUES ('$nama', '$email', '$password', '$alamat', '$nomor_hp', '$tanggal_daftar', 1)";
                $conn->query($query_pengguna);
                $id_pengguna = $conn->insert_id;

                // Insert ke tabel penumpang
                if ($tanggal_lahir) {
                    $conn->query("INSERT INTO penumpang (id_pengguna, tanggal_lahir) VALUES ($id_pengguna, '$tanggal_lahir')");
                } else {
                    $conn->query("INSERT INTO penumpang (id_pengguna) VALUES ($id_pengguna)");
                }

                // Insert notifikasi selamat datang
                $tanggal_now = date('Y-m-d H:i:s');
                $judul_notif = "Selamat Datang di Kereta Connect!";
                $pesan_notif = "Halo $nama! Akun Anda telah berhasil dibuat. Selamat menikmati layanan pemesanan tiket kereta kami.";

                $conn->query("INSERT INTO notifikasi (id_pengguna, judul, pesan, tanggal, status, dari_sistem) 
                              VALUES ($id_pengguna, '$judul_notif', '$pesan_notif', '$tanggal_now', 0, 1)");

                $conn->commit();

                $message = '<div class="alert alert-success">
                                <i class="bi bi-check-circle me-2"></i>
                                Registrasi berhasil! Akun Anda telah dibuat. 
                                <a href="login.php" class="alert-link">Login sekarang</a>
                            </div>';
            } catch (Exception $e) {
                $conn->rollback();
                $message = '<div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                Terjadi kesalahan saat registrasi. Silakan coba lagi.
                            </div>';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Registrasi - Kereta Connect</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
        <link href="../assets/css/register.css" rel="stylesheet">
</head>

<body>
    <div class="auth-container">
        <div class="auth-card">
            <!-- Brand Header -->
            <div class="brand-header">
                <div class="brand-logo">
                    <i class="bi bi-train-front"></i>
                </div>
                <h1 class="brand-title">Buat Akun Baru</h1>
                <p class="brand-subtitle">Bergabung dengan Kereta Connect untuk menikmati kemudahan pemesanan tiket
                </p>
            </div>

            <!-- Alert Messages -->
            <?= $message; ?>

            <!-- Registration Form -->
            <form method="POST" id="registerForm">
                <!-- Nama & Email -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="nama" class="form-control" placeholder="Nama lengkap Anda" required
                            autofocus>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="email@domain.com" required>
                    </div>
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter"
                        required minlength="6">
                    <div class="text-small">Gunakan kombinasi huruf, angka, dan simbol untuk keamanan yang lebih baik
                    </div>
                </div>

                <!-- Nomor HP & Tanggal Lahir -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Nomor HP</label>
                        <input type="tel" name="nomor_hp" class="form-control" placeholder="08xxxxxxxxxx"
                            pattern="08[0-9]{8,12}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tanggal Lahir</label>
                        <input type="date" name="tanggal_lahir" class="form-control"
                            max="<?= date('Y-m-d', strtotime('-13 years')); ?>">
                    </div>
                </div>

                <!-- Alamat -->
                <div class="form-group">
                    <label class="form-label">Alamat (Opsional)</label>
                    <textarea name="alamat" class="form-control" placeholder="Alamat lengkap Anda"></textarea>
                </div>

                <!-- Terms Agreement -->
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="terms" required>
                    <label class="form-check-label" for="terms">
                        Saya menyetujui <a href="#" target="_blank">Syarat & Ketentuan</a> dan
                        <a href="#" target="_blank">Kebijakan Privasi</a> yang berlaku
                    </label>
                </div>

                <!-- Submit Button -->
                <button type="submit" name="register" class="btn btn-primary">
                    <i class="bi bi-person-plus me-2"></i>Daftar Sekarang
                </button>
            </form>

            <!-- Login Link -->
            <div class="auth-footer">
                <p class="text-muted mb-0">
                    Sudah memiliki akun?
                    <a href="login.php">Masuk di sini</a>
                </p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function (e) {
            const password = document.querySelector('input[name="password"]').value;
            const phone = document.querySelector('input[name="nomor_hp"]').value;
            const terms = document.getElementById('terms').checked;

            if (password.length < 6) {
                e.preventDefault();
                alert('Password minimal harus 6 karakter!');
                return false;
            }

            if (phone && !phone.match(/^08\d{8,12}$/)) {
                e.preventDefault();
                alert('Format nomor HP tidak valid! Gunakan format 08xxxxxxxxxx');
                return false;
            }

            if (!terms) {
                e.preventDefault();
                alert('Anda harus menyetujui syarat dan ketentuan!');
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

        // Password strength indicator
        document.querySelector('input[name="password"]').addEventListener('input', function (e) {
            const password = e.target.value;

            // Remove existing feedback
            const existingFeedback = e.target.parentNode.querySelector('.password-strength');
            if (existingFeedback) {
                existingFeedback.remove();
            }

            if (password.length > 0) {
                const strength = getPasswordStrength(password);
                const feedback = document.createElement('div');
                feedback.className = 'password-strength';
                feedback.innerHTML = `<i class="bi bi-shield-${strength.icon} me-1"></i>${strength.text}`;
                feedback.style.color = strength.color;
                e.target.parentNode.appendChild(feedback);
            }
        });

        function getPasswordStrength(password) {
            if (password.length < 6) {
                return { text: 'Password terlalu pendek', color: '#ef4444', icon: 'exclamation' };
            } else if (password.length < 8) {
                return { text: 'Password lemah', color: '#f59e0b', icon: 'dash' };
            } else if (password.match(/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/)) {
                return { text: 'Password kuat', color: '#10b981', icon: 'check' };
            } else {
                return { text: 'Password sedang', color: '#f59e0b', icon: 'dash' };
            }
        }

        // Page animation
        window.addEventListener('load', function () {
            const card = document.querySelector('.auth-card');
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';

            setTimeout(() => {
                card.style.transition = 'all 0.6s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 100);
        });
    </script>
</body>

</html>