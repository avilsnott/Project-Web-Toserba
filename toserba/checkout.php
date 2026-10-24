
<?php
session_start();
require_once __DIR__ . "/config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

if (empty($_SESSION["cart"]) || !is_array($_SESSION["cart"])) {
    header("Location: keranjang.php");
    exit;
}

$cart = $_SESSION["cart"];
$ids = [];
$jumlahBarang = [];

foreach ($cart as $id => $jumlah) {
    $id = filter_var($id, FILTER_VALIDATE_INT);
    $jumlah = filter_var($jumlah, FILTER_VALIDATE_INT);

    if ($id && $jumlah && $jumlah > 0) {
        $ids[] = $id;
        $jumlahBarang[$id] = $jumlah;
    }
}

if (!$ids) {
    header("Location: keranjang.php");
    exit;
}

$placeholders = implode(",", array_fill(0, count($ids), "?"));
$stmt = $pdo->prepare(
    "SELECT id, kode_produk, nama_produk, harga_jual, stok
     FROM products
     WHERE id IN ($placeholders)"
);
$stmt->execute($ids);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = 0;
foreach ($products as $product) {
    $id = (int)$product["id"];
    $jumlah = $jumlahBarang[$id] ?? 0;
    $total += (int)$product["harga_jual"] * $jumlah;
}

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
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
    <title>Checkout | AVÉDEN MART</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
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

    /* BANNER */
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
        top: 16px;
        right: 34px;
        color: #c5d5e8;
        font-size: 68px;
        opacity: 0.35;
        pointer-events: none;
    }

    .hero > div,
    .hero h1,
    .hero p {
        position: relative;
        z-index: 1;
    }

    .hero > div {
        color: #c5d5e8 !important;
        letter-spacing: 1px;
        font-weight: 650;
    }

    .hero h1 {
        letter-spacing: -0.7px;
    }

    .hero p {
        color: #d0dbea;
        line-height: 1.75;
    }

    /* PANEL */
    .panel {
        height: 100%;
        padding: 27px;
        border: 1px solid #d5e0ed;
        border-radius: 19px;
        background: linear-gradient(
            145deg,
            #f8fafd,
            #eaf0f7
        );
        box-shadow: 0 5px 18px rgba(16, 29, 53, 0.04);
    }

    .panel h4 {
        color: var(--navy);
        letter-spacing: -0.3px;
    }

    .text-secondary {
        color: var(--muted) !important;
    }

    /* DAFTAR PESANAN */
    .panel .border-bottom {
        border-color: #d9e3ef !important;
    }

    .panel .fw-semibold {
        color: var(--text);
    }

    .panel .fs-4 {
        color: #426c9a;
        overflow-wrap: anywhere;
    }

    /* PILIHAN PEMBAYARAN */
    .form-check {
        position: relative;
        display: flex;
        align-items: flex-start;
        gap: 10px;
        margin-left: 0;
        padding: 16px;
        border: 1px solid #d2deeb !important;
        border-radius: 12px !important;
        background: rgba(249, 251, 253, 0.8);
        transition:
            border-color 0.2s ease,
            background 0.2s ease,
            box-shadow 0.2s ease;
    }

    .form-check:has(.form-check-input:checked) {
        border-color: #789fc9 !important;
        background: #e5eef8;
        box-shadow: 0 0 0 2px rgba(120, 159, 201, 0.12);
    }

    .form-check-input {
        flex-shrink: 0;
        margin: 3px 0 0 !important;
        border-color: #9eafc4;
        cursor: pointer;
    }

    .form-check-input:checked {
        background-color: var(--navy-soft);
        border-color: var(--navy-soft);
    }

    .form-check-input:focus {
        border-color: var(--blue);
        box-shadow: 0 0 0 3px rgba(120, 159, 201, 0.2);
    }

    .form-check-label {
        flex: 1;
        color: var(--navy);
        cursor: pointer;
        line-height: 1.6;
    }

    .form-check-label strong {
        font-weight: 700;
    }

    /* TOMBOL */
    .btn-main {
        padding: 13px 18px;
        color: #f5f8fc;
        background: var(--navy);
        border: 1px solid var(--navy);
        border-radius: 10px;
        font-weight: 650;
        transition: 0.2s ease;
    }

    .btn-main:hover {
        color: #fff;
        background: #29466b;
        border-color: #29466b;
        transform: translateY(-1px);
    }

    .btn-outline-secondary {
        padding: 11px 16px;
        border-color: #c5d2e2;
        border-radius: 10px;
        color: #536780;
    }

    .btn-outline-secondary:hover {
        color: var(--navy);
        background: var(--ice);
        border-color: #b3c7df;
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
            font-size: 30px;
        }

        .panel {
            padding: 21px;
            border-radius: 16px;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .form-check,
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
<div class="container py-4 py-md-5">

    <div class="hero mb-4">
        <div class="mb-2" style="color:#f3c969">✦ AVÉDEN MART</div>
        <h1 class="fw-bold">Checkout</h1>
        <p class="mb-0">Periksa pesanan dan pilih metode pembayaranmu.</p>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="panel">
                <h4 class="fw-bold mb-4">Ringkasan Pesanan</h4>

                <?php foreach ($products as $product): ?>
                    <?php
                    $id = (int)$product["id"];
                    $jumlah = $jumlahBarang[$id] ?? 0;
                    $subtotal = (int)$product["harga_jual"] * $jumlah;
                    ?>
                    <div class="d-flex justify-content-between gap-3 border-bottom py-3">
                        <div>
                            <div class="fw-semibold">
                                <?= htmlspecialchars($product["nama_produk"]) ?>
                            </div>
                            <small class="text-secondary">
                                <?= $jumlah ?> × <?= rupiah($product["harga_jual"]) ?>
                            </small>
                        </div>
                        <div class="fw-semibold text-nowrap">
                            <?= rupiah($subtotal) ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="d-flex justify-content-between pt-4">
                    <span class="fw-semibold">Total Belanja</span>
                    <span class="fs-4 fw-bold"><?= rupiah($total) ?></span>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="panel">
                <h4 class="fw-bold mb-3">Metode Pembayaran</h4>

                <form action="proses_checkout.php" method="POST">
                    <input type="hidden" name="csrf_token"
                           value="<?= htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8") ?>">

                    <div class="form-check border rounded-3 p-3 mb-3">
                        <input class="form-check-input ms-0 me-2"
                               type="radio" name="metode_pembayaran"
                               id="tunai" value="Tunai" required>
                        <label class="form-check-label" for="tunai">
                            <strong>Bayar Tunai</strong>
                            <div class="text-secondary small">Pembayaran langsung secara tunai.</div>
                        </label>
                    </div>

                    <div class="form-check border rounded-3 p-3 mb-4">
                        <input class="form-check-input ms-0 me-2"
                               type="radio" name="metode_pembayaran"
                               id="qr" value="QR" required>
                        <label class="form-check-label" for="qr">
                            <strong>QRIS</strong>
                            <div class="text-secondary small">Bayar dengan memindai kode QR.</div>
                        </label>
                    </div>

                    <button type="submit" class="btn btn-main btn-lg w-100">
                        Buat Pesanan
                    </button>

                    <a href="keranjang.php"
                       class="btn btn-outline-secondary w-100 mt-2">
                        Kembali ke Keranjang
                    </a>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>
