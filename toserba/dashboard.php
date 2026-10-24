
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}


$nama = $_SESSION["nama_lengkap"] ?? $_SESSION["username"];
$role = $_SESSION["role"] ?? "pelanggan";

require_once "config/database.php";

$totalProduk = 0;
$totalTransaksi = 0;
$totalPendapatan = 0;
$transaksiMenunggu = 0;

if ($role === "admin") {
    $totalProduk = (int) $pdo
        ->query("SELECT COUNT(*) FROM products")
        ->fetchColumn();

    $totalTransaksi = (int) $pdo
        ->query("SELECT COUNT(*) FROM transactions")
        ->fetchColumn();

    $totalPendapatan = (float) $pdo
        ->query("SELECT COALESCE(SUM(total), 0) FROM transactions WHERE status = 'Berhasil'")
        ->fetchColumn();

    $transaksiMenunggu = (int) $pdo
        ->query("SELECT COUNT(*) FROM transactions WHERE status = 'Menunggu'")
        ->fetchColumn();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard | AVÉDEN MART</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    
<style>
    :root {
        --navy: #101d35;
        --navy-soft: #192b49;
        --blue: #789fc9;
        --ice: #dce8f5;
        --silver: #aebfd3;
        --page: #eaf0f7;
        --text: #233752;
        --muted: #718096;
    }

    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        background: var(--page);
        color: var(--navy);
        font-family: "Segoe UI", Arial, sans-serif;
    }

    /* NAVBAR */
    .navbar {
        background: var(--navy);
        padding: 16px 0;
        border-bottom: 1px solid rgba(220, 232, 245, 0.16);
        box-shadow: 0 4px 18px rgba(16, 29, 53, 0.1);
    }

    .brand {
        color: #f1f5fb;
        font-weight: 800;
        letter-spacing: 1.5px;
    }

    .brand:hover {
        color: #dce8f5;
    }

    .brand span {
        color: var(--silver);
    }

    .navbar .btn-outline-light {
        border-color: #8199b7;
        color: #e4edf7;
        border-radius: 9px;
    }

    .navbar .btn-outline-light:hover {
        background: #dce8f5;
        color: var(--navy);
    }

    /* RINGKASAN ADMIN */
    .container {
        max-width: 1200px;
    }

    .section-title {
        font-weight: 750;
        color: var(--navy);
        letter-spacing: -0.4px;
    }

    main .text-secondary {
        color: var(--muted) !important;
    }

    .menu-card {
        height: 100%;
        padding: 26px;
        border: 1px solid #d5e0ed;
        border-radius: 17px;
        background: linear-gradient(
            145deg,
            #f8fafd,
            #e6edf6
        );
        box-shadow: 0 5px 18px rgba(16, 29, 53, 0.035);
        transition:
            transform 0.22s ease,
            box-shadow 0.22s ease,
            border-color 0.22s ease;
    }

    .menu-card:hover {
        transform: translateY(-4px);
        border-color: #b3c7df;
        box-shadow: 0 12px 26px rgba(16, 29, 53, 0.09);
    }

    .menu-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 52px;
        height: 52px;
        margin-bottom: 18px;
        border: 1px solid #d1deed;
        border-radius: 15px;
        background: #dce8f5;
        color: #527ba9;
        font-size: 24px;
    }

    .menu-card h5 {
        color: #203651;
        font-weight: 700;
    }

    .menu-card p {
        line-height: 1.65;
    }

    /* TOMBOL */
    .btn-main {
        padding: 10px 17px;
        border: 1px solid var(--navy);
        border-radius: 9px;
        color: #f5f8fc;
        background: var(--navy);
        font-weight: 600;
        transition: 0.2s ease;
    }

    .btn-main:hover {
        color: white;
        background: #29466b;
        border-color: #29466b;
        transform: translateY(-1px);
    }

    /* WELCOME */
    .welcome {
        position: relative;
        overflow: hidden;
        padding: 42px;
        border: 1px solid rgba(220, 232, 245, 0.2);
        border-radius: 22px;
        color: #f1f5fb;
        background:
            radial-gradient(
                ellipse at 90% 10%,
                rgba(120, 159, 201, 0.2),
                transparent 36%
            ),
            linear-gradient(
                120deg,
                #101d35,
                #192b49 60%,
                #29466b
            );
        box-shadow: 0 12px 30px rgba(16, 29, 53, 0.1);
    }

    .welcome::after {
        content: "✦";
        position: absolute;
        top: 20px;
        right: 34px;
        color: #c5d5e8;
        font-size: 65px;
        opacity: 0.35;
        pointer-events: none;
    }

    .welcome .star {
        position: relative;
        z-index: 1;
        margin-bottom: 8px;
        color: #c5d5e8;
        font-size: 26px;
    }

    .welcome h1 {
        position: relative;
        z-index: 1;
        font-size: clamp(30px, 4vw, 42px);
        letter-spacing: -0.8px;
    }

    .welcome p {
        position: relative;
        z-index: 1;
        color: #d0dbea;
        line-height: 1.75;
    }

    .welcome .btn-light {
        position: relative;
        z-index: 1;
        padding: 11px 19px;
        border: 1px solid #dce8f5;
        border-radius: 9px;
        color: var(--navy);
        background: #dce8f5;
        font-weight: 650;
    }

    .welcome .btn-light:hover {
        background: #f4f7fb;
    }

    /* FOOTER */
    footer {
        color: #718096;
        font-size: 14px;
    }

    footer p {
        padding-top: 12px;
        border-top: 1px solid #d4deea;
    }

    /* RESPONSIVE */
    @media (max-width: 576px) {
        .welcome {
            padding: 28px 24px;
            border-radius: 18px;
        }

        .welcome::after {
            top: 14px;
            right: 18px;
            font-size: 45px;
        }

        .welcome h1 {
            font-size: 30px;
            overflow-wrap: anywhere;
        }

        .menu-card {
            padding: 22px;
        }

        main.container {
            padding-top: 28px !important;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .menu-card,
        .btn-main {
            transition: none;
        }

        .menu-card:hover,
        .btn-main:hover {
            transform: none;
        }
    }
</style>

</head>
<body>

<nav class="navbar">
    <div class="container">
        <a class="navbar-brand brand" href="dashboard.php">
            AVÉDEN <span>✦</span> MART
        </a>

        <a href="logout.php" class="btn btn-outline-light btn-sm">
            <i class="bi bi-box-arrow-right"></i> Keluar
        </a>
    </div>
</nav>

<main class="container py-5">
    
    <?php if ($role === "admin"): ?>
    <section class="row g-3 mb-5">

        <div class="col-12">
            <h4 class="fw-bold mb-1">Ringkasan Toko</h4>
            <p class="text-secondary mb-0">
                Informasi terkini AVÉDEN MART.
            </p>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="menu-card">
                <div class="menu-icon">
                    <i class="bi bi-box-seam"></i>
                </div>
                <p class="text-secondary mb-1">Total Produk</p>
                <h3 class="fw-bold mb-0">
                    <?= number_format($totalProduk, 0, ',', '.') ?>
                </h3>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="menu-card">
                <div class="menu-icon">
                    <i class="bi bi-receipt"></i>
                </div>
                <p class="text-secondary mb-1">Total Transaksi</p>
                <h3 class="fw-bold mb-0">
                    <?= number_format($totalTransaksi, 0, ',', '.') ?>
                </h3>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="menu-card">
                <div class="menu-icon">
                    <i class="bi bi-wallet2"></i>
                </div>
                <p class="text-secondary mb-1">Total Pendapatan</p>
                <h3 class="fw-bold mb-0" style="font-size: 1.35rem;">
                    Rp <?= number_format($totalPendapatan, 0, ',', '.') ?>
                </h3>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="menu-card">
                <div class="menu-icon">
                    <i class="bi bi-clock-history"></i>
                </div>
                <p class="text-secondary mb-1">Transaksi Menunggu</p>
                <h3 class="fw-bold mb-0">
                    <?= number_format($transaksiMenunggu, 0, ',', '.') ?>
                </h3>
            </div>
        </div>

    </section>
    <?php endif; ?>
    <section class="welcome mb-5">
        <div class="star">✦</div>
        <p class="mb-2">SELAMAT DATANG DI AVÉDEN MART</p>

        <h1 class="fw-bold">
            Halo, <?= htmlspecialchars($nama, ENT_QUOTES, "UTF-8") ?>!
        </h1>

        <p class="mb-4">
            Temukan kebutuhanmu, nikmati pengalaman belanja
            yang nyaman dan praktis.
        </p>

        <a href="produk.php" class="btn btn-light">
            <i class="bi bi-bag"></i> Mulai Belanja
        </a>
    </section>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="section-title mb-1">Jelajahi AVÉDEN</h3>
            <p class="text-secondary mb-0">
                Semua yang kamu butuhkan, dalam satu tempat.
            </p>
        </div>
    </div>

    <div class="row g-4">

        <div class="col-md-6 col-lg-4">
            <div class="menu-card">
                <div class="menu-icon">
                    <i class="bi bi-bag-heart"></i>
                </div>
                <h5>Produk</h5>
                <p class="text-secondary">
                    Lihat dan temukan berbagai kebutuhan sehari-hari.
                </p>
                <a href="produk.php" class="btn btn-main">
                    Lihat Produk <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>

        <?php if ($role === "admin"): ?>
        <div class="col-md-6 col-lg-4">
            <div class="menu-card">
                <div class="menu-icon">
                    <i class="bi bi-box-seam"></i>
                </div>
                <h5>Kelola Produk</h5>
                <p class="text-secondary">
                    Tambah dan atur produk serta stok toko.
                </p>
                <a href="admin/produk.php" class="btn btn-main">
                    Kelola Produk <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($role === "admin"): ?>
        <div class="col-md-6 col-lg-4">
            <div class="menu-card">
                <div class="menu-icon">
                    <i class="bi bi-clipboard-data"></i>
                </div>
                <h5>Kelola Transaksi</h5>
                <p class="text-secondary">
                    Pantau seluruh transaksi dan status pembayaran.
                </p>
                <a href="admin/transaksi.php" class="btn btn-main">
                    Kelola Transaksi <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($role === "admin"): ?>
        <div class="col-md-6 col-lg-4">
            <div class="menu-card">
                <div class="menu-icon">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>
                <h5>Laporan Penjualan</h5>
                <p class="text-secondary">
                    Lihat ringkasan pendapatan dan laporan transaksi berdasarkan tanggal.
                </p>
                <a href="admin/laporan.php" class="btn btn-main">
                    Lihat Laporan <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
        <?php endif; ?>

        <div class="col-md-6 col-lg-4">
            <div class="menu-card">
                <div class="menu-icon">
                    <i class="bi bi-receipt"></i>
                </div>
                <h5>Riwayat Transaksi</h5>
                <p class="text-secondary">
                    Periksa catatan pembelian dan status pembayaran.
                </p>
                <a href="riwayat.php" class="btn btn-main">
                    Lihat Riwayat <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>

    </div>

    <footer class="text-center mt-5 pt-4">
        <p>✦ AVÉDEN MART — Your Everyday Essentials ✦</p>
    </footer>

</main>

</body>
</html>
