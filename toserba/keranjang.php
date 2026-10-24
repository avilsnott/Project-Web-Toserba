
<?php
session_start();
require_once __DIR__ . "/config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION["cart"])) {
    $_SESSION["cart"] = [];
}

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function rupiah($value) {
    return "Rp " . number_format((float)$value, 0, ",", ".");
}

$pesan = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";

    if (!hash_equals($_SESSION["csrf_token"], $token)) {
        $error = "Permintaan tidak valid. Muat ulang halaman.";
    } else {
        $aksi = $_POST["aksi"] ?? "";

        if ($aksi === "tambah") {
            $id = filter_var(
                $_POST["product_id"] ?? "",
                FILTER_VALIDATE_INT
            );

            if (!$id || $id < 1) {
                $error = "Produk tidak valid.";
            } else {
                $stmt = $pdo->prepare(
                    "SELECT id, nama_produk, stok
                     FROM products WHERE id = ?"
                );
                $stmt->execute([$id]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$product) {
                    $error = "Produk tidak ditemukan.";
                } elseif ((int)$product["stok"] <= 0) {
                    $error = "Stok produk habis.";
                } else {
                    $jumlahSaatIni = (int)(
                        $_SESSION["cart"][$id] ?? 0
                    );

                    if ($jumlahSaatIni + 1 > (int)$product["stok"]) {
                        $error = "Jumlah melebihi stok yang tersedia.";
                    } else {
                        $_SESSION["cart"][$id] = $jumlahSaatIni + 1;
                        $pesan = "Produk berhasil ditambahkan ke keranjang.";
                    }
                }
            }
        } elseif ($aksi === "ubah") {
            $jumlahList = $_POST["jumlah"] ?? [];

            if (!is_array($jumlahList)) {
                $error = "Data jumlah tidak valid.";
            } else {
                foreach ($_SESSION["cart"] as $id => $jumlahLama) {
                    $jumlahBaru = filter_var(
                        $jumlahList[$id] ?? $jumlahLama,
                        FILTER_VALIDATE_INT
                    );

                    if ($jumlahBaru === false || $jumlahBaru < 1) {
                        $error = "Jumlah minimal setiap produk adalah 1.";
                        break;
                    }

                    $stmt = $pdo->prepare(
                        "SELECT stok FROM products WHERE id = ?"
                    );
                    $stmt->execute([(int)$id]);
                    $stok = $stmt->fetchColumn();

                    if ($stok === false || $jumlahBaru > (int)$stok) {
                        $error = "Jumlah produk melebihi stok atau produknya sudah tidak tersedia.";
                        break;
                    }
                }

                if ($error === "") {
                    foreach ($_SESSION["cart"] as $id => $jumlahLama) {
                        $_SESSION["cart"][$id] = (int)$jumlahList[$id];
                    }
                    $pesan = "Keranjang berhasil diperbarui.";
                }
            }
        } elseif ($aksi === "hapus") {
            $id = filter_var(
                $_POST["product_id"] ?? "",
                FILTER_VALIDATE_INT
            );

            if ($id && isset($_SESSION["cart"][$id])) {
                unset($_SESSION["cart"][$id]);
                $pesan = "Produk dihapus dari keranjang.";
            }
        } elseif ($aksi === "kosongkan") {
            $_SESSION["cart"] = [];
            $pesan = "Keranjang berhasil dikosongkan.";
        }
    }
}

$items = [];
$total = 0;
$stokBermasalah = false;

if (!empty($_SESSION["cart"])) {
    $ids = array_keys($_SESSION["cart"]);
    $placeholders = implode(",", array_fill(0, count($ids), "?"));

    $stmt = $pdo->prepare(
        "SELECT id, kode_produk, nama_produk, harga_jual, stok, gambar
         FROM products WHERE id IN ($placeholders)"
    );
    $stmt->execute($ids);
    $hasil = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $produkTersedia = [];

    foreach ($hasil as $product) {
        $produkTersedia[$product["id"]] = $product;
    }

    foreach ($_SESSION["cart"] as $id => $jumlah) {
        if (!isset($produkTersedia[$id])) {
            $stokBermasalah = true;
            continue;
        }

        $product = $produkTersedia[$id];
        $jumlah = (int)$jumlah;
        $subtotal = (float)$product["harga_jual"] * $jumlah;

        if ($jumlah > (int)$product["stok"]) {
            $stokBermasalah = true;
        }

        $product["jumlah"] = $jumlah;
        $product["subtotal"] = $subtotal;
        $items[] = $product;
        $total += $subtotal;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Keranjang | AVÉDEN MART</title>

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

    /* BANNER */
    .banner {
        position: relative;
        overflow: hidden;
        padding: 34px;
        border: 1px solid rgba(220, 232, 245, 0.2);
        border-radius: 21px;
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

    .banner::after {
        content: "✦";
        position: absolute;
        top: 15px;
        right: 32px;
        color: #c5d5e8;
        font-size: 65px;
        opacity: 0.35;
        pointer-events: none;
    }

    .banner > div,
    .banner h1,
    .banner p {
        position: relative;
        z-index: 1;
    }

    .banner > div {
        color: #c5d5e8 !important;
    }

    .banner h1 {
        letter-spacing: -0.6px;
    }

    .banner p {
        color: #d0dbea;
        line-height: 1.7;
    }

    /* PANEL */
    .panel {
        padding: 25px;
        border: 1px solid #d5e0ed;
        border-radius: 18px;
        background: linear-gradient(
            145deg,
            #f8fafd,
            #eaf0f7
        );
        box-shadow: 0 5px 18px rgba(16, 29, 53, 0.04);
    }

    main .text-secondary {
        color: var(--muted) !important;
    }

    /* TABEL KERANJANG */
    .table-responsive {
        border: 1px solid #d9e3ef;
        border-radius: 12px;
        background: #f9fbfd;
    }

    .table {
        margin-bottom: 0;
        color: var(--text);
        --bs-table-bg: transparent;
        --bs-table-hover-bg: #eaf1f9;
    }

    .table th {
        padding: 14px 12px;
        white-space: nowrap;
        color: #e8eff8;
        background: var(--navy-soft);
        font-size: 13px;
        font-weight: 650;
        border-bottom: 0;
    }

    .table td {
        padding: 14px 12px;
        border-color: #e0e7f0;
        vertical-align: middle;
        font-size: 14px;
    }

    .table tbody tr:last-child td {
        border-bottom: 0;
    }

    .product-thumb {
        flex-shrink: 0;
        width: 60px;
        height: 60px;
        object-fit: cover;
        border: 1px solid #d5e0ed;
        border-radius: 11px;
        background: var(--ice);
    }

    .table .fw-bold {
        color: var(--navy);
        line-height: 1.5;
    }

    .price {
        color: #426c9a;
        font-weight: 800;
        white-space: nowrap;
    }

    /* INPUT JUMLAH */
    .table .form-control {
        min-width: 75px;
        min-height: 40px;
        padding: 7px 9px;
        border: 1px solid #cedae8;
        border-radius: 9px;
        color: var(--text);
        background: #fbfcfe;
    }

    .table .form-control:focus {
        border-color: var(--blue);
        box-shadow: 0 0 0 3px rgba(120, 159, 201, 0.18);
    }

    /* TOMBOL */
    .btn-main {
        color: #f5f8fc;
        background: var(--navy);
        border: 1px solid var(--navy);
        border-radius: 9px;
        font-weight: 650;
        transition: 0.2s ease;
    }

    .btn-main:hover {
        color: #fff;
        background: #29466b;
        border-color: #29466b;
        transform: translateY(-1px);
    }

    .btn-main:disabled,
    .btn-secondary:disabled {
        transform: none;
    }

    .btn-outline-primary {
        color: #527ba9;
        border-color: #a9bfd9;
        border-radius: 9px;
    }

    .btn-outline-primary:hover {
        color: #fff;
        background: #527ba9;
        border-color: #527ba9;
    }

    .btn-outline-danger {
        border-radius: 8px;
    }

    .btn-link.text-danger {
        text-decoration: none;
        font-size: 14px;
    }

    .btn-link.text-danger:hover {
        text-decoration: underline;
    }

    /* ALERT */
    .alert {
        border-radius: 12px;
        border-width: 1px;
    }

    .alert-success {
        color: #285c4b;
        background: #e8f4ee;
        border-color: #c9e4d6;
    }

    .alert-danger {
        color: #873f4a;
        background: #f9ecee;
        border-color: #eccfd4;
    }

    .alert-warning {
        color: #765a22;
        background: #f7f1df;
        border-color: #e9d9a9;
    }

    /* KERANJANG KOSONG */
    .panel.text-center > .text-primary {
        color: #789fc9 !important;
    }

    .panel h4 {
        color: var(--navy);
    }

    /* RESPONSIVE */
    @media (max-width: 767px) {
        .banner {
            padding: 28px 23px;
            border-radius: 18px;
        }

        .banner::after {
            top: 12px;
            right: 18px;
            font-size: 47px;
        }

        .banner h1 {
            max-width: 90%;
            font-size: 29px;
        }

        .panel {
            padding: 17px;
            border-radius: 15px;
        }

        .product-thumb {
            width: 45px;
            height: 45px;
        }

        .table th,
        .table td {
            padding: 11px 9px;
        }

        .table td {
            font-size: 13px;
        }

        .table .fw-bold {
            min-width: 100px;
            overflow-wrap: anywhere;
        }

        .panel > .d-flex.justify-content-between {
            align-items: flex-start !important;
            flex-direction: column;
        }

        .panel > .d-flex.justify-content-between .btn-main {
            width: 100%;
            text-align: center;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .btn-main {
            transition: none;
        }

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
        <a href="produk.php" class="btn btn-outline-light btn-sm">
            <i class="bi bi-arrow-left"></i> Kembali Belanja
        </a>
    </div>
</nav>

<main class="container py-4 py-md-5">

    <section class="banner mb-4">
        <div class="fs-3" style="color:var(--gold)">✦</div>
        <h1 class="fw-bold">Keranjang Belanja</h1>
        <p class="mb-0">Periksa pilihanmu sebelum checkout.</p>
    </section>

    <?php if ($pesan !== ""): ?>
        <div class="alert alert-success"><?= h($pesan) ?></div>
    <?php endif; ?>

    <?php if ($error !== ""): ?>
        <div class="alert alert-danger"><?= h($error) ?></div>
    <?php endif; ?>

    <?php if ($stokBermasalah): ?>
        <div class="alert alert-warning">
            Ada produk yang jumlahnya melebihi stok atau sudah tidak tersedia.
            Ubah jumlahnya sebelum checkout.
        </div>
    <?php endif; ?>

    <?php if (empty($items)): ?>
        <section class="panel text-center py-5">
            <i class="bi bi-bag fs-1 text-primary"></i>
            <h4 class="fw-bold mt-3">Keranjangmu masih kosong</h4>
            <p class="text-secondary">Yuk, cari barang yang kamu butuhkan.</p>
            <a href="produk.php" class="btn btn-main px-4">
                Jelajahi Produk
            </a>
        </section>
    <?php else: ?>

        <section class="panel">
            <form method="POST" action="keranjang.php">
                <input type="hidden" name="csrf_token"
                       value="<?= h($_SESSION["csrf_token"]) ?>">
                <input type="hidden" name="aksi" value="ubah">

                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th>Harga</th>
                                <th style="min-width:100px">Jumlah</th>
                                <th>Subtotal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <?php if (!empty($item["gambar"])): ?>
                                            <img class="product-thumb"
                                                 src="<?= h($item["gambar"]) ?>"
                                                 alt="<?= h($item["nama_produk"]) ?>">
                                        <?php else: ?>
                                            <div class="product-thumb d-flex align-items-center justify-content-center">
                                                <i class="bi bi-stars text-primary fs-4"></i>
                                            </div>
                                        <?php endif; ?>

                                        <div>
                                            <div class="fw-bold">
                                                <?= h($item["nama_produk"]) ?>
                                            </div>
                                            <small class="text-secondary">
                                                <?= h($item["kode_produk"]) ?>
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td><?= rupiah($item["harga_jual"]) ?></td>
                                <td>
                                    <input type="number"
                                           name="jumlah[<?= (int)$item["id"] ?>]"
                                           class="form-control"
                                           min="1"
                                           max="<?= max(1, (int)$item["stok"]) ?>"
                                           value="<?= (int)$item["jumlah"] ?>"
                                           required>
                                    <small class="text-secondary">
                                        Stok: <?= (int)$item["stok"] ?>
                                    </small>
                                </td>
                                <td class="price">
                                    <?= rupiah($item["subtotal"]) ?>
                                </td>
                                <td>
                                    <button type="submit"
                                            form="hapus-<?= (int)$item["id"] ?>"
                                            class="btn btn-sm btn-outline-danger"
                                            aria-label="Hapus produk">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex flex-wrap gap-2 justify-content-between mt-3">
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="bi bi-arrow-repeat"></i> Perbarui Jumlah
                    </button>
                </div>
            </form>

            <?php foreach ($items as $item): ?>
                <form id="hapus-<?= (int)$item["id"] ?>"
                      method="POST" action="keranjang.php">
                    <input type="hidden" name="csrf_token"
                           value="<?= h($_SESSION["csrf_token"]) ?>">
                    <input type="hidden" name="aksi" value="hapus">
                    <input type="hidden" name="product_id"
                           value="<?= (int)$item["id"] ?>">
                </form>
            <?php endforeach; ?>

            <hr>

            <div class="d-flex justify-content-between align-items-center gap-3">
                <div>
                    <span class="text-secondary">Total belanja</span>
                    <div class="price fs-3"><?= rupiah($total) ?></div>
                </div>

                <?php if (!$stokBermasalah): ?>
                    <a href="checkout.php" class="btn btn-main px-4 py-3">
                        Lanjut Checkout <i class="bi bi-arrow-right"></i>
                    </a>
                <?php else: ?>
                    <button class="btn btn-secondary px-4 py-3" disabled>
                        Perbaiki Jumlah
                    </button>
                <?php endif; ?>
            </div>

            <form method="POST" action="keranjang.php" class="mt-3"
                  onsubmit="return confirm('Kosongkan seluruh keranjang?')">
                <input type="hidden" name="csrf_token"
                       value="<?= h($_SESSION["csrf_token"]) ?>">
                <input type="hidden" name="aksi" value="kosongkan">
                <button type="submit" class="btn btn-link text-danger px-0">
                    Kosongkan Keranjang
                </button>
            </form>
        </section>
    <?php endif; ?>

</main>
</body>
</html>
