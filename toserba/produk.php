
<?php
session_start();
require_once __DIR__ . "/config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$cari = trim($_GET["cari"] ?? "");
$kategori = trim($_GET["kategori"] ?? "");

$sql = "SELECT id, kode_produk, nama_produk, kategori,
               harga_jual, stok, gambar
        FROM products
        WHERE 1=1";
$params = [];

if ($cari !== "") {
    $sql .= " AND nama_produk LIKE ?";
    $params[] = "%" . $cari . "%";
}

if ($kategori !== "") {
    $sql .= " AND kategori = ?";
    $params[] = $kategori;
}

$sql .= " ORDER BY nama_produk ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$produk = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmtKategori = $pdo->query(
    "SELECT DISTINCT kategori FROM products ORDER BY kategori"
);
$daftarKategori = $stmtKategori->fetchAll(PDO::FETCH_COLUMN);

function e($nilai) {
    return htmlspecialchars((string)$nilai, ENT_QUOTES, "UTF-8");
}

function rupiah($angka) {
    return "Rp " . number_format((float)$angka, 0, ",", ".");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Produk | AVÉDEN MART</title>

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
        color: var(--text);
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
        color: var(--ice);
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
        color: var(--navy);
        background: var(--ice);
    }

    /* HERO */
    .hero {
        position: relative;
        overflow: hidden;
        padding: 38px;
        border: 1px solid rgba(220, 232, 245, 0.2);
        border-radius: 22px;
        color: #f1f5fb;
        background:
            radial-gradient(
                ellipse at 90% 10%,
                rgba(120, 159, 201, 0.24),
                transparent 38%
            ),
            linear-gradient(
                120deg,
                var(--navy),
                var(--navy-soft) 60%,
                #29466b
            );
        box-shadow: 0 10px 26px rgba(16, 29, 53, 0.09);
    }

    .hero::after {
        content: "✦";
        position: absolute;
        top: 18px;
        right: 35px;
        color: #c5d5e8;
        font-size: 68px;
        opacity: 0.35;
        pointer-events: none;
    }

    .hero .text-warning {
        position: relative;
        z-index: 1;
        color: #c5d5e8 !important;
    }

    .hero h1,
    .hero p {
        position: relative;
        z-index: 1;
    }

    .hero h1 {
        letter-spacing: -0.7px;
    }

    .hero p {
        color: #d0dbea;
        line-height: 1.75;
    }

    /* PENCARIAN DAN FILTER */
    .search-box,
    .filter-box {
        min-height: 47px;
        padding: 11px 14px;
        border: 1px solid #cedae8;
        border-radius: 10px;
        color: var(--text);
        background: #f9fbfd;
    }

    .search-box::placeholder {
        color: #96a5b8;
    }

    .search-box:focus,
    .filter-box:focus {
        border-color: var(--blue);
        background: #fff;
        box-shadow: 0 0 0 3px rgba(120, 159, 201, 0.18);
    }

    /* JUDUL DAN JUMLAH PRODUK */
    main > .d-flex h4 {
        color: var(--navy);
        letter-spacing: -0.3px;
    }

    main .text-secondary {
        color: var(--muted) !important;
    }

    /* KARTU PRODUK */
    .product-card {
        height: 100%;
        overflow: hidden;
        border: 1px solid #d5e0ed;
        border-radius: 17px;
        background: linear-gradient(
            145deg,
            #f9fbfd,
            #eaf0f7
        );
        box-shadow: 0 5px 18px rgba(16, 29, 53, 0.035);
        transition:
            transform 0.22s ease,
            box-shadow 0.22s ease,
            border-color 0.22s ease;
    }

    .product-card:hover {
        transform: translateY(-5px);
        border-color: #b3c7df;
        box-shadow: 0 14px 28px rgba(16, 29, 53, 0.1);
    }

    .product-image {
        position: relative;
        height: 185px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        color: #789fc9;
        background:
            radial-gradient(
                circle at 50% 40%,
                #f9fbfe,
                transparent 65%
            ),
            linear-gradient(135deg, #dce8f5, #eaf0f7);
        font-size: 48px;
    }

    .product-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.35s ease;
    }

    .product-card:hover .product-image img {
        transform: scale(1.04);
    }

    .product-info {
        display: flex;
        flex-direction: column;
        padding: 18px;
        height: calc(100% - 185px);
    }

    .category {
        align-self: flex-start;
        display: inline-block;
        max-width: 100%;
        padding: 5px 10px;
        border: 1px solid #d1deed;
        border-radius: 20px;
        color: #52749b;
        background: #e4edf7;
        font-size: 11px;
        font-weight: 650;
        line-height: 1.5;
    }

    .product-info h6 {
        color: var(--navy);
        line-height: 1.5;
        overflow-wrap: anywhere;
    }

    .price {
        color: #426c9a;
        font-size: 18px;
        font-weight: 800;
        line-height: 1.5;
        overflow-wrap: anywhere;
    }

    .product-info > p.text-secondary {
        margin-top: auto;
        padding-top: 2px;
        font-size: 12px;
    }

    /* TOMBOL */
    .btn-main {
        padding: 10px 12px;
        color: #f5f8fc;
        background: var(--navy);
        border: 1px solid var(--navy);
        border-radius: 9px;
        font-size: 13px;
        font-weight: 650;
        transition: 0.2s ease;
    }

    .btn-main:hover {
        color: #fff;
        background: #29466b;
        border-color: #29466b;
        transform: translateY(-1px);
    }

    .btn-main:disabled {
        color: #f1f4f8;
        background: #9aa9bc;
        border-color: #9aa9bc;
        opacity: 0.75;
        transform: none;
    }

    /* PRODUK KOSONG */
    .empty-state {
        padding: 55px 20px;
        border: 1px dashed #b9cadd;
        border-radius: 18px;
        background: linear-gradient(
            145deg,
            #f9fbfd,
            #eaf0f7
        );
    }

    .empty-state .text-primary {
        color: #789fc9 !important;
    }

    .empty-state h5 {
        color: var(--navy);
    }

    /* RESPONSIVE */
    @media (max-width: 767px) {
        .hero {
            padding: 29px 24px;
            border-radius: 18px;
        }

        .hero::after {
            top: 12px;
            right: 18px;
            font-size: 48px;
        }

        .hero h1 {
            max-width: 90%;
            font-size: 29px;
        }

        .product-image {
            height: 145px;
        }

        .product-info {
            height: calc(100% - 145px);
            padding: 13px;
        }

        .price {
            font-size: 16px;
        }

        .product-info .btn-main {
            padding: 9px 6px;
            font-size: 12px;
        }

        .category {
            font-size: 10px;
            padding: 4px 8px;
        }
    }

    @media (max-width: 380px) {
        .product-image {
            height: 120px;
        }

        .product-info {
            height: calc(100% - 120px);
            padding: 10px;
        }

        .product-info h6 {
            font-size: 13px;
        }

        .product-info .btn-main {
            font-size: 11px;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .product-card,
        .product-image img,
        .btn-main {
            transition: none;
        }

        .product-card:hover,
        .product-card:hover .product-image img,
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

        <div class="d-flex gap-2">
            <a href="dashboard.php" class="btn btn-outline-light btn-sm">
                <i class="bi bi-house"></i> Dashboard
            </a>
            <a href="logout.php" class="btn btn-outline-light btn-sm">
                Keluar
            </a>
        </div>
    </div>
</nav>

<main class="container py-4 py-md-5">

    <section class="hero mb-4">
        <div class="text-warning fs-3">✦</div>
        <h1 class="fw-bold">Jelajahi Koleksi Kami</h1>
        <p class="mb-0">
            Temukan kebutuhan favoritmu di AVÉDEN MART.
        </p>
    </section>

    <form method="GET" class="row g-3 mb-4">
        <div class="col-md-7">
            <input type="search" name="cari"
                   class="form-control search-box"
                   placeholder="Cari nama produk..."
                   value="<?= e($cari) ?>">
        </div>

        <div class="col-md-3">
            <select name="kategori"
                    class="form-select filter-box">
                <option value="">Semua kategori</option>
                <?php foreach ($daftarKategori as $namaKategori): ?>
                    <option value="<?= e($namaKategori) ?>"
                        <?= $kategori === $namaKategori ? "selected" : "" ?>>
                        <?= e($namaKategori) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2 d-grid">
            <button class="btn btn-main" type="submit">
                <i class="bi bi-search"></i> Cari
            </button>
        </div>
    </form>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0">Produk Pilihan</h4>
        <span class="text-secondary small">
            <?= count($produk) ?> produk ditemukan
        </span>
    </div>

    <?php if (count($produk) > 0): ?>
        <div class="row g-4">
            <?php foreach ($produk as $item): ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <article class="product-card">
                        <div class="product-image">
                            <?php if (!empty($item["gambar"])): ?>
                                <img
                                    src="<?= e($item["gambar"]) ?>"
                                    alt="<?= e($item["nama_produk"]) ?>"
                                    loading="lazy">
                            <?php else: ?>
                                <i class="bi bi-stars"></i>
                            <?php endif; ?>
                        </div>

                        <div class="product-info">
                            <span class="category">
                                <?= e($item["kategori"]) ?>
                            </span>

                            <h6 class="fw-bold mt-3 mb-2">
                                <?= e($item["nama_produk"]) ?>
                            </h6>

                            <p class="price mb-2">
                                <?= rupiah($item["harga_jual"]) ?>
                            </p>

                            <p class="text-secondary small mb-0">
                                <?php if ((int)$item["stok"] > 0): ?>
                                    Stok: <?= (int)$item["stok"] ?>
                                <?php else: ?>
                                    Stok habis
                                <?php endif; ?>
                            </p> 
                            <form action="keranjang.php" method="POST" class="mt-3">
                                <input type="hidden" name="csrf_token"
                                        value="<?= htmlspecialchars($_SESSION["csrf_token"] ?? "", ENT_QUOTES, "UTF-8") ?>">
                                <input type="hidden" name="aksi" value="tambah">

                                <input type="hidden" name="product_id"
                                        value="<?= (int)$item["id"] ?>">

                                <button type="submit" class="btn btn-main w-100"
                                        <?= (int)$item["stok"] <= 0 ? "disabled" : "" ?>>
                                    <i class="bi bi-bag-plus"></i> Tambah ke Keranjang
                                </button>
                            </form>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>

    <?php else: ?>
        <div class="empty-state text-center">
            <div class="fs-1 text-primary mb-3">
                <i class="bi bi-stars"></i>
            </div>

            <h5 class="fw-bold">Belum ada produk yang ditemukan</h5>

            <p class="text-secondary mb-3">
                <?php if ($cari !== "" || $kategori !== ""): ?>
                    Coba ubah kata pencarian atau kategori.
                <?php else: ?>
                    Produk akan tampil di sini setelah ditambahkan ke toko.
                <?php endif; ?>
            </p>

            <?php if ($cari !== "" || $kategori !== ""): ?>
                <a href="produk.php" class="btn btn-main px-4">
                    Tampilkan Semua
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</main>
</body>
</html>
