<?php
// Pesan Makanan
session_start();
if (!isset($_SESSION['user']))
    header('Location: login.php');
include '../includes/db.php';

$user_id = $_SESSION['user']['id'];
$message = '';

// Ambil id_penumpang dari id_pengguna
$penumpang = $conn->query("SELECT id FROM penumpang WHERE id_pengguna=$user_id")->fetch_assoc();
$id_penumpang = $penumpang ? $penumpang['id'] : 0;

// Ambil tiket aktif (yang sudah lunas dan akan datang)
$tiket_aktif = $conn->query("
    SELECT t.*, jk.tanggal, jk.waktu_berangkat, jk.stasiun_awal, jk.stasiun_tujuan, k.nama_kereta, ks.nomor_kursi 
    FROM tiket t 
    JOIN jadwal_kereta jk ON t.id_jadwal=jk.id 
    JOIN kereta k ON jk.id_kereta=k.id 
    JOIN kursi ks ON t.id_kursi=ks.id 
    WHERE t.id_penumpang=$id_penumpang 
    AND t.status_tiket='Lunas' 
    AND jk.tanggal >= CURDATE() 
    ORDER BY jk.tanggal ASC, jk.waktu_berangkat ASC
");

// Ambil menu makanan yang tersedia
$menu_makanan = $conn->query("SELECT * FROM menu_makanan WHERE status=1 ORDER BY nama ASC");

// Proses pemesanan
if (isset($_POST['pesan'])) {
    $tiket_id = intval($_POST['tiket_id']);
    $items = $_POST['items'] ?? [];

    if (!empty($items)) {
        // Hitung total
        $total = 0;
        $jumlah_item = 0;

        foreach ($items as $menu_id => $qty) {
            if ($qty > 0) {
                $menu = $conn->query("SELECT harga FROM menu_makanan WHERE id=$menu_id")->fetch_assoc();
                if ($menu) {
                    $total += $menu['harga'] * $qty;
                    $jumlah_item += $qty;
                }
            }
        }

        if ($total > 0) {
            // Insert pesanan makanan
            $tanggal = date('Y-m-d');
            $conn->query("INSERT INTO pesanan_makanan (id_tiket, tanggal, jumlah, total, konfirmasi) VALUES ($tiket_id, '$tanggal', $jumlah_item, $total, 0)");
            $pesanan_id = $conn->insert_id;

            // Insert detail pesanan
            foreach ($items as $menu_id => $qty) {
                if ($qty > 0) {
                    $menu = $conn->query("SELECT harga FROM menu_makanan WHERE id=$menu_id")->fetch_assoc();
                    if ($menu) {
                        $subtotal = $menu['harga'] * $qty;
                        $conn->query("INSERT INTO detail_pesanan_makanan (id_pesanan, id_menu, jumlah, subtotal) VALUES ($pesanan_id, $menu_id, $qty, $subtotal)");
                    }
                }
            }

            $message = '<div class="alert alert-success alert-custom">
                            <i class="bi bi-check-circle me-2"></i>
                            Pesanan makanan berhasil! Total: Rp' . number_format($total, 0, ',', '.') . '
                        </div>';
        } else {
            $message = '<div class="alert alert-warning alert-custom">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Silakan pilih minimal satu menu makanan!
                        </div>';
        }
    } else {
        $message = '<div class="alert alert-warning alert-custom">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Silakan pilih makanan yang ingin dipesan!
                    </div>';
    }
}

// Ambil riwayat pesanan makanan
$riwayat_pesanan = $conn->query("
    SELECT pm.*, t.id as tiket_id, jk.tanggal, k.nama_kereta, jk.stasiun_awal, jk.stasiun_tujuan
    FROM pesanan_makanan pm
    JOIN tiket t ON pm.id_tiket = t.id
    JOIN jadwal_kereta jk ON t.id_jadwal = jk.id
    JOIN kereta k ON jk.id_kereta = k.id
    WHERE t.id_penumpang = $id_penumpang
    ORDER BY pm.tanggal DESC
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Pesan Makanan - Kereta Connect</title>
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

        .food-container {
            min-height: 100vh;
            padding-top: 20px;
        }

        .menu-card {
            background: var(--white);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: var(--shadow-soft);
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .menu-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-medium);
        }

        .menu-image {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
        }

        .quantity-control {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .quantity-btn {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            border: 2px solid var(--primary-blue);
            background: var(--white);
            color: var(--primary-blue);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .quantity-btn:hover {
            background: var(--primary-blue);
            color: white;
        }

        .quantity-input {
            width: 50px;
            text-align: center;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 5px;
        }

        .order-summary {
            background: var(--white);
            border-radius: 16px;
            padding: 25px;
            box-shadow: var(--shadow-soft);
            position: sticky;
            top: 100px;
        }

        .ticket-selector {
            background: rgba(96, 165, 250, 0.1);
            border-left: 4px solid var(--accent-blue);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .history-card {
            background: var(--white);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: var(--shadow-soft);
            border-left: 4px solid var(--success);
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

        .section-title {
            color: var(--text-dark);
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        @media (max-width: 768px) {
            .order-summary {
                position: relative;
                top: 0;
                margin-top: 20px;
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
                        <a class="nav-link active" href="pesan_makanan.php">
                            <i class="bi bi-cup-hot me-1"></i>Pesan Makanan
                        </a>
                    </li>
                </ul>

                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button"
                            data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-2"></i>
                            <?= htmlspecialchars($_SESSION['user']['nama'] ?? explode('@', $_SESSION['user']['email'])[0]); ?>
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

    <div class="food-container">
        <div class="container">
            <!-- Page Header -->
            <div class="row mb-4">
                <div class="col-12">
                    <h2 class="section-title">
                        <i class="bi bi-cup-hot"></i>Pesan Makanan & Minuman
                    </h2>
                    <p class="text-muted">Nikmati makanan lezat selama perjalanan Anda</p>
                </div>
            </div>

            <?= $message; ?>

            <form method="POST" id="foodOrderForm">
                <div class="row">
                    <!-- Menu Selection -->
                    <div class="col-lg-8">
                        <!-- Ticket Selection -->
                        <div class="ticket-selector">
                            <h6 class="fw-bold text-primary mb-3">
                                <i class="bi bi-ticket-detailed me-2"></i>Pilih Tiket Perjalanan
                            </h6>
                            <?php if ($tiket_aktif && $tiket_aktif->num_rows > 0): ?>
                                <select name="tiket_id" class="form-select form-control-enhanced" required>
                                    <option value="">Pilih tiket perjalanan Anda</option>
                                    <?php while ($tiket = $tiket_aktif->fetch_assoc()): ?>
                                        <option value="<?= $tiket['id']; ?>">
                                            <?= htmlspecialchars($tiket['nama_kereta']); ?> |
                                            <?= htmlspecialchars($tiket['stasiun_awal']); ?> →
                                            <?= htmlspecialchars($tiket['stasiun_tujuan']); ?> |
                                            <?= date('d/m/Y', strtotime($tiket['tanggal'])); ?>
                                            <?= $tiket['waktu_berangkat']; ?> |
                                            Kursi <?= $tiket['nomor_kursi']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    Anda belum memiliki tiket aktif. <a href="pesan_tiket.php">Pesan tiket</a> terlebih
                                    dahulu.
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Menu Items -->
                        <h5 class="section-title">
                            <i class="bi bi-grid-3x3-gap"></i>Menu Tersedia
                        </h5>

                        <?php if ($menu_makanan && $menu_makanan->num_rows > 0): ?>
                            <?php
                            $food_icons = [
                                'Nasi' => 'bi-cup-hot',
                                'Ayam' => 'bi-egg-fried',
                                'Mie' => 'bi-cup-straw',
                                'Sate' => 'bi-egg-fried',
                                'Roti' => 'bi-cookie',
                                'Air' => 'bi-droplet',
                                'Teh' => 'bi-cup-hot'
                            ];
                            ?>
                            <?php while ($menu = $menu_makanan->fetch_assoc()): ?>
                                <?php
                                $icon = 'bi-cup-hot';
                                foreach ($food_icons as $keyword => $iconClass) {
                                    if (stripos($menu['nama'], $keyword) !== false) {
                                        $icon = $iconClass;
                                        break;
                                    }
                                }
                                ?>
                                <div class="menu-card">
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <div class="menu-image">
                                                <i class="bi <?= $icon; ?>"></i>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <h6 class="fw-bold mb-1"><?= htmlspecialchars($menu['nama']); ?></h6>
                                            <p class="text-success fw-semibold mb-2">
                                                Rp<?= number_format($menu['harga'], 0, ',', '.'); ?></p>
                                            <small class="text-muted">Menu lezat untuk menemani perjalanan Anda</small>
                                        </div>
                                        <div class="col-auto">
                                            <div class="quantity-control">
                                                <button type="button" class="quantity-btn"
                                                    onclick="decreaseQuantity(<?= $menu['id']; ?>)">
                                                    <i class="bi bi-dash"></i>
                                                </button>
                                                <input type="number" name="items[<?= $menu['id']; ?>]"
                                                    id="qty_<?= $menu['id']; ?>" class="quantity-input" value="0" min="0"
                                                    max="10" onchange="updateSummary()">
                                                <button type="button" class="quantity-btn"
                                                    onclick="increaseQuantity(<?= $menu['id']; ?>)">
                                                    <i class="bi bi-plus"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="bi bi-cup" style="font-size: 4rem; color: var(--text-light);"></i>
                                <h5 class="mt-3 text-muted">Menu makanan belum tersedia</h5>
                                <p class="text-muted">Silakan coba lagi nanti</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Order Summary -->
                    <div class="col-lg-4">
                        <div class="order-summary">
                            <h5 class="fw-bold text-primary mb-3">
                                <i class="bi bi-cart me-2"></i>Ringkasan Pesanan
                            </h5>

                            <div id="orderItems">
                                <p class="text-muted text-center py-3">Belum ada item dipilih</p>
                            </div>

                            <hr>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="fw-semibold">Total:</span>
                                <span class="fw-bold text-success h5 mb-0" id="totalAmount">Rp0</span>
                            </div>

                            <button type="submit" name="pesan" class="btn btn-primary-custom w-100" id="orderButton"
                                disabled>
                                <i class="bi bi-bag-check me-2"></i>Pesan Sekarang
                            </button>

                            <div class="mt-3 p-3 bg-light rounded">
                                <small class="text-muted">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Makanan akan disajikan selama perjalanan. Pembayaran dilakukan di kereta.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Order History -->
            <?php if ($riwayat_pesanan && $riwayat_pesanan->num_rows > 0): ?>
                <div class="row mt-5">
                    <div class="col-12">
                        <h5 class="section-title">
                            <i class="bi bi-clock-history"></i>Riwayat Pesanan Makanan
                        </h5>

                        <?php while ($pesanan = $riwayat_pesanan->fetch_assoc()): ?>
                            <div class="history-card">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="fw-bold text-primary"><?= htmlspecialchars($pesanan['nama_kereta']); ?></h6>
                                        <p class="mb-1 text-muted">
                                            <i class="bi bi-geo-alt me-1"></i>
                                            <?= htmlspecialchars($pesanan['stasiun_awal']); ?> →
                                            <?= htmlspecialchars($pesanan['stasiun_tujuan']); ?>
                                        </p>
                                        <p class="mb-1">
                                            <i class="bi bi-calendar me-1 text-success"></i>
                                            <?= date('d/m/Y', strtotime($pesanan['tanggal'])); ?>
                                        </p>
                                        <p class="mb-0">
                                            <i class="bi bi-bag me-1 text-warning"></i>
                                            <?= $pesanan['jumlah']; ?> item
                                        </p>
                                    </div>
                                    <div class="text-end">
                                        <p class="fw-bold text-success mb-1">
                                            Rp<?= number_format($pesanan['total'], 0, ',', '.'); ?></p>
                                        <span class="badge bg-<?= $pesanan['konfirmasi'] ? 'success' : 'warning'; ?>">
                                            <?= $pesanan['konfirmasi'] ? 'Dikonfirmasi' : 'Menunggu'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Store menu prices
        const menuPrices = {
            <?php
            $menu_makanan = $conn->query("SELECT * FROM menu_makanan WHERE status=1");
            $prices = [];
            while ($menu = $menu_makanan->fetch_assoc()) {
                $prices[] = $menu['id'] . ': ' . $menu['harga'];
            }
            echo implode(', ', $prices);
            ?>
        };

        const menuNames = {
            <?php
            $menu_makanan = $conn->query("SELECT * FROM menu_makanan WHERE status=1");
            $names = [];
            while ($menu = $menu_makanan->fetch_assoc()) {
                $names[] = $menu['id'] . ': "' . htmlspecialchars($menu['nama']) . '"';
            }
            echo implode(', ', $names);
            ?>
        };

        function increaseQuantity(menuId) {
            const input = document.getElementById('qty_' + menuId);
            const currentValue = parseInt(input.value) || 0;
            if (currentValue < 10) {
                input.value = currentValue + 1;
                updateSummary();
            }
        }

        function decreaseQuantity(menuId) {
            const input = document.getElementById('qty_' + menuId);
            const currentValue = parseInt(input.value) || 0;
            if (currentValue > 0) {
                input.value = currentValue - 1;
                updateSummary();
            }
        }

        function updateSummary() {
            const orderItems = document.getElementById('orderItems');
            const totalAmount = document.getElementById('totalAmount');
            const orderButton = document.getElementById('orderButton');

            let total = 0;
            let hasItems = false;
            let itemsHTML = '';

            // Check all quantity inputs
            document.querySelectorAll('.quantity-input').forEach(input => {
                const menuId = input.name.match(/\[(\d+)\]/)[1];
                const quantity = parseInt(input.value) || 0;

                if (quantity > 0) {
                    hasItems = true;
                    const price = menuPrices[menuId] || 0;
                    const subtotal = price * quantity;
                    total += subtotal;

                    itemsHTML += `
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <small class="fw-semibold">${menuNames[menuId]}</small>
                        <br>
                        <small class="text-muted">${quantity}x Rp${price.toLocaleString('id-ID')}</small>
                    </div>
                    <small class="fw-bold">Rp${subtotal.toLocaleString('id-ID')}</small>
                </div>
            `;
                }
            });

            if (hasItems) {
                orderItems.innerHTML = itemsHTML;
                totalAmount.textContent = 'Rp' + total.toLocaleString('id-ID');
                orderButton.disabled = false;
            } else {
                orderItems.innerHTML = '<p class="text-muted text-center py-3">Belum ada item dipilih</p>';
                totalAmount.textContent = 'Rp0';
                orderButton.disabled = true;
            }
        }

        // Page load animation
        window.addEventListener('load', function () {
            const cards = document.querySelectorAll('.menu-card, .history-card');
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
    </script>
</body>

</html>