<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Login Admin - Hanya dengan level_akses
session_start();
if (isset($_SESSION['admin']))
    header('Location: dashboard.php');
include '../includes/db.php';

$message = '';

if (isset($_POST['login'])) {
    $level_akses = trim($_POST['level_akses']);
    // Cari admin berdasarkan level_akses
    $admin_query = "SELECT pg.*, a.level_akses, a.id as admin_id 
                   FROM pengguna pg 
                   JOIN admin a ON pg.id = a.id_pengguna 
                   WHERE a.level_akses = '" . $conn->real_escape_string($level_akses) . "' 
                   AND pg.validasi_akun = 1";
    $admin_result = $conn->query($admin_query);
    if ($admin_result && $admin_result->num_rows > 0) {
        $admin_data = $admin_result->fetch_assoc();
        // Set session admin
        $_SESSION['admin'] = [
            'id' => $admin_data['id'],
            'nama' => $admin_data['nama'],
            'email' => $admin_data['email'],
            'level_akses' => $admin_data['level_akses'],
            'login_time' => date('Y-m-d H:i:s')
        ];
        // Log aktivitas login
        $log_query = "INSERT INTO notifikasi (id_pengguna, judul, pesan, tanggal, status, dari_sistem) 
                     VALUES ({$admin_data['id']}, 'Login Admin', 'Admin berhasil login ke sistem', NOW(), 1, 1)";
        $conn->query($log_query);
        header('Location: dashboard.php');
        exit;
    } else {
        $message = '<div class="alert alert-danger"><i class="icon">⚠️</i><div><strong>Login Gagal!</strong><br>Level akses tidak ditemukan atau akun belum divalidasi.</div></div>';
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Kereta Connect</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #1e40af;
            --secondary-blue: #3b82f6;
            --success-green: #10b981;
            --warning-yellow: #f59e0b;
            --danger-red: #ef4444;
            --text-dark: #1f2937;
            --text-light: #6b7280;
            --border-light: #e5e7eb;
            --background-light: #f9fafb;
            --shadow-light: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            --shadow-medium: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-secondary: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            position: relative;
            overflow: hidden;
        }

        /* Floating shapes background */
        body::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background-image:
                radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(120, 119, 198, 0.2) 0%, transparent 50%);
            animation: float 20s ease-in-out infinite;
            z-index: 0;
        }

        @keyframes float {

            0%,
            100% {
                transform: translate(0, 0) rotate(0deg);
            }

            33% {
                transform: translate(30px, -30px) rotate(120deg);
            }

            66% {
                transform: translate(-20px, 20px) rotate(240deg);
            }
        }

        .auth-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 420px;
        }

        .auth-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 24px;
            padding: 2.5rem;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            position: relative;
            overflow: hidden;
        }

        .auth-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
            border-radius: 24px 24px 0 0;
        }

        .brand-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .brand-logo {
            width: 80px;
            height: 80px;
            background: var(--gradient-primary);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin: 0 auto 1.5rem;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
            position: relative;
        }

        .brand-logo::after {
            content: '';
            position: absolute;
            inset: -2px;
            background: var(--gradient-primary);
            border-radius: 22px;
            z-index: -1;
            opacity: 0.3;
            filter: blur(8px);
        }

        .brand-title {
            font-size: 1.875rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .brand-subtitle {
            color: var(--text-light);
            font-size: 1rem;
            line-height: 1.5;
        }

        .alert {
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            font-size: 0.875rem;
            line-height: 1.5;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #dc2626;
        }

        .alert .icon {
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }

        .form-control {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid var(--border-light);
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--secondary-blue);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            background: rgba(255, 255, 255, 0.95);
        }

        .form-control::placeholder {
            color: var(--text-light);
        }

        .btn-primary {
            width: 100%;
            background: var(--gradient-primary);
            border: none;
            color: white;
            padding: 1rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:hover::before {
            left: 100%;
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .auth-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-light);
        }

        .auth-footer p {
            color: var(--text-light);
            font-size: 0.875rem;
        }

        .auth-footer a {
            color: var(--secondary-blue);
            text-decoration: none;
            font-weight: 600;
        }

        .auth-footer a:hover {
            text-decoration: underline;
        }

        .login-info {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .login-info h6 {
            color: var(--secondary-blue);
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }

        .login-info p {
            color: var(--text-light);
            font-size: 0.75rem;
            margin: 0.25rem 0;
        }

        .credentials {
            background: rgba(255, 255, 255, 0.8);
            border-radius: 8px;
            padding: 0.5rem;
            font-family: 'Courier New', monospace;
            font-size: 0.75rem;
            margin: 0.25rem 0;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .auth-card {
                padding: 2rem 1.5rem;
                margin: 1rem;
            }

            .brand-logo {
                width: 70px;
                height: 70px;
                font-size: 2rem;
            }

            .brand-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="brand-header">
                <div class="brand-logo">
                    <i class="bi bi-shield-lock"></i>
                </div>
                <h1 class="brand-title">Admin Login</h1>
                <p class="brand-subtitle">Masuk ke dashboard admin dengan level akses</p>
            </div>
            <?= $message; ?>
            <form method="post" class="form-login" autocomplete="off">
                <div class="form-group">
                    <label class="form-label" for="level_akses">
                        <i class="bi bi-person-badge me-1"></i>Level Akses
                    </label>
                    <input type="text" name="level_akses" id="level_akses" class="form-control"
                        placeholder="Masukkan level akses admin" required autofocus>
                </div>
                <button type="submit" name="login" class="btn-primary">
                    <i class="bi bi-box-arrow-in-right me-2"></i>
                    Login Admin
                </button>
            </form>
            <div class="auth-footer">
                <p>
                    <i class="bi bi-question-circle me-1"></i>
                    Butuh bantuan? <a href="mailto:admin@keretaconnect.com">Hubungi IT Support</a>
                </p>
                <p style="margin-top: 0.5rem;">
                    <a href="../" style="color: var(--text-light); font-size: 0.75rem;">
                        <i class="bi bi-arrow-left me-1"></i>Kembali ke Beranda
                    </a>
                </p>
            </div>
        </div>
    </div>
</body>

</html>
<?php ob_end_flush(); ?>