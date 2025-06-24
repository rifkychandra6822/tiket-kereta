<?php
// Login Penumpang
session_start();
if (isset($_SESSION['user']))
    header('Location: dashboard.php');
include '../includes/db.php';
$message = '';
if (isset($_POST['login'])) {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];
    $res = $conn->query("SELECT * FROM pengguna WHERE email='$username'");
    $data = $res->fetch_assoc();
    if ($data && password_verify($password, $data['password'])) {
        $_SESSION['user'] = $data;
        header('Location: dashboard.php');
        exit;
    } else {
        $message = '<div class="alert alert-danger alert-custom">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Email atau password yang Anda masukkan salah!
                    </div>';
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Login - Kereta Connect</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/login.css" rel="stylesheet">
</head>

<body>
    <div class="login-container">
        <div class="login-card">
            <div class="card card-elegant">
                <div class="card-body p-4"> <!-- Diperkecil dari p-5 -->
                    <!-- Brand Logo -->
                    <div class="brand-logo">
                        <i class="bi bi-train-front brand-icon"></i>
                        <h1 class="brand-title">Kereta Connect</h1>
                        <p class="brand-subtitle">Sistem Pemesanan Tiket Kereta Api</p>
                    </div>

                    <!-- Alert Message -->
                    <?= $message; ?>

                    <!-- Login Form -->
                    <form method="POST" id="loginForm">
                        <!-- Email Input -->
                        <div class="form-group-enhanced">
                            <div class="input-group-enhanced">
                                <i class="bi bi-envelope input-icon"></i>
                                <input type="email" name="username" class="form-control form-control-enhanced"
                                    placeholder="Email Anda" required autofocus autocomplete="email">
                            </div>
                        </div>

                        <!-- Password Input -->
                        <div class="form-group-enhanced">
                            <div class="input-group-enhanced">
                                <i class="bi bi-lock input-icon"></i>
                                <input type="password" name="password" class="form-control form-control-enhanced"
                                    placeholder="Password" required autocomplete="current-password">
                            </div>
                        </div>

                        <!-- Remember Me & Forgot Password -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <!-- Diperkecil dari mb-4 -->
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="rememberMe">
                                <label class="form-check-label text-muted small" for="rememberMe">
                                    Ingat saya
                                </label>
                            </div>
                            <a href="#" class="link-enhanced small">Lupa password?</a>
                        </div>

                        <!-- Login Button -->
                        <button type="submit" name="login" class="btn btn-login" id="loginBtn">
                            <i class="bi bi-box-arrow-in-right me-2"></i>
                            Masuk ke Akun
                        </button>

                        <!-- Register Link -->
                        <div class="text-center mt-3"> <!-- Diperkecil dari mt-4 -->
                            <p class="text-muted mb-0 small">
                                Belum memiliki akun?
                                <a href="register.php" class="link-enhanced">
                                    Daftar sekarang
                                </a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Footer Links -->
            <div class="footer-links text-center">
                <a href="../admin/login.php" class="footer-link me-2"> <!-- Diperkecil margin -->
                    <i class="bi bi-person-gear"></i>
                    Admin
                </a>
                <a href="../petugas/login.php" class="footer-link">
                    <i class="bi bi-person-badge"></i>
                    Petugas
                </a>
            </div>

            <!-- Copyright -->
            <div class="text-center mt-2"> <!-- Diperkecil dari mt-3 -->
                <p class="text-white-50 small mb-0" style="font-size: 0.8rem;">
                    © 2024 Kereta Connect. All rights reserved.
                </p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Enhanced form interaction
        document.getElementById('loginForm').addEventListener('submit', function (e) {
            const btn = document.getElementById('loginBtn');
            btn.classList.add('loading');
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Sedang masuk...';
        });

        // Input focus effects
        document.querySelectorAll('.form-control-enhanced').forEach(input => {
            input.addEventListener('focus', function () {
                this.parentElement.style.transform = 'translateY(-2px)';
                this.parentElement.style.boxShadow = '0 10px 40px rgba(0, 0, 0, 0.1)';
            });

            input.addEventListener('blur', function () {
                this.parentElement.style.transform = 'translateY(0)';
                this.parentElement.style.boxShadow = 'none';
            });
        });

        // Add subtle animation on page load
        window.addEventListener('load', function () {
            document.querySelector('.login-card').style.opacity = '0';
            document.querySelector('.login-card').style.transform = 'translateY(30px)';

            setTimeout(() => {
                document.querySelector('.login-card').style.transition = 'all 0.6s ease';
                document.querySelector('.login-card').style.opacity = '1';
                document.querySelector('.login-card').style.transform = 'translateY(0)';
            }, 100);
        });
    </script>
</body>

</html>