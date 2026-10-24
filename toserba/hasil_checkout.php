
<?php
session_start();
require_once __DIR__ . "/config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$result = $_SESSION["checkout_result"] ?? null;

if (!is_array($result) || empty($result["kode"])) {
    header("Location: riwayat.php");
    exit;
}

$stmt = $pdo->prepare(
    "SELECT kode_transaksi, total, metode_pembayaran, status
     FROM transactions
     WHERE kode_transaksi = ? AND user_id = ?
     LIMIT 1"
);
$stmt->execute([
    $result["kode"],
    (int)$_SESSION["user_id"]
]);
$transaksi = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$transaksi) {
    unset($_SESSION["checkout_result"]);
    header("Location: riwayat.php");
    exit;
}

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function rupiah($angka) {
    return "Rp " . number_format((float)$angka, 0, ",", ".");
}

$metode = $transaksi["metode_pembayaran"];
$status = $transaksi["status"];
$isQR = $metode === "QR";
$isMenunggu = $isQR && $status === "Menunggu";

$qrText = "AVEDEN MART | " .
    $transaksi["kode_transaksi"] . " | Total " .
    $transaksi["total"] . " | SIMULASI";

$qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=" .
    rawurlencode($qrText);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Detail Pesanan | AVÉDEN MART</title>
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
    min-height: 100vh;
    margin: 0;
    padding: 24px 0;
    background:
        radial-gradient(ellipse at top, rgba(120,159,201,.20), transparent 55%),
        linear-gradient(145deg, #eaf0f7, #dce6f2);
    color: var(--text);
    font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
}

.card-main {
    position: relative;
    max-width: 650px;
    margin: 20px auto;
    padding: 38px;
    overflow: hidden;
    background: linear-gradient(150deg, #ffffff, #f1f5fa);
    border: 1px solid rgba(174,191,211,.7);
    border-radius: 26px;
    box-shadow: 0 22px 60px rgba(16,29,53,.13);
}

.card-main::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 6px;
    background: linear-gradient(90deg, var(--navy), var(--blue), var(--silver));
}

.card-main > .fw-bold:first-child {
    display: inline-block;
    padding: 9px 17px;
    color: var(--ice) !important;
    background: linear-gradient(135deg, var(--navy), var(--navy-soft));
    border: 1px solid rgba(174,191,211,.4);
    border-radius: 30px;
    letter-spacing: 2px;
    font-size: .85rem;
}

h2 {
    color: var(--navy);
    letter-spacing: -.5px;
}

.text-secondary {
    color: var(--muted) !important;
}

.display-5.text-success {
    color: #477e70 !important;
}

.qr-box {
    display: inline-block;
    max-width: 100%;
    padding: 15px;
    background: #fff;
    border: 1px solid var(--silver);
    border-radius: 20px;
    box-shadow: 0 10px 28px rgba(16,29,53,.08);
}

.qr-box img {
    display: block;
    max-width: 100%;
    height: auto;
    border-radius: 8px;
}

.info {
    padding: 22px;
    background: linear-gradient(145deg, #edf3fa, #e5edf6);
    border: 1px solid #d4dfec;
    border-radius: 18px;
}

.info > div {
    padding-bottom: 12px;
    border-bottom: 1px solid rgba(174,191,211,.55);
}

.info > div:last-child {
    padding-bottom: 0;
    border-bottom: 0;
}

.info span {
    font-size: .92rem;
}

.info strong {
    color: var(--navy);
    overflow-wrap: anywhere;
}

.btn {
    padding: 12px 18px;
    border-radius: 12px;
    font-weight: 600;
    transition: .2s ease;
}

.btn-main {
    color: #fff;
    background: linear-gradient(135deg, var(--navy), #294b75);
    border: 1px solid var(--navy);
    box-shadow: 0 6px 16px rgba(16,29,53,.16);
}

.btn-main:hover {
    color: #fff;
    background: linear-gradient(135deg, #203958, #38628e);
    transform: translateY(-1px);
}

.btn-outline-primary {
    color: var(--navy);
    border-color: var(--blue);
}

.btn-outline-primary:hover {
    color: #fff;
    background: var(--navy-soft);
    border-color: var(--navy-soft);
}

.btn-outline-secondary {
    color: #52657d;
    border-color: #bdcad9;
}

.btn-outline-secondary:hover {
    color: var(--navy);
    background: var(--ice);
    border-color: var(--silver);
}

.small {
    line-height: 1.7;
}

@media (max-width: 576px) {
    body {
        padding: 10px 0;
    }

    .card-main {
        margin: 12px auto;
        padding: 25px 18px;
        border-radius: 20px;
    }

    .info {
        padding: 17px 14px;
    }

    .info span,
    .info strong {
        font-size: .85rem;
    }

    .qr-box {
        padding: 10px;
    }
}

@media print {
    body {
        padding: 0;
        background: #fff;
    }

    .card-main {
        margin: 0 auto;
        box-shadow: none;
        border: 1px solid #ccc;
    }

    .btn,
    form,
    .small {
        display: none !important;
    }
}
    </style>
</head>
<body>
<div class="container px-3">
    <div class="card-main text-center">
        <div class="fw-bold mb-4" style="color:#347fce;letter-spacing:2px">
            AVÉDEN MART
        </div>

        <?php if ($status === "Berhasil"): ?>
            <div class="display-5 text-success mb-3">
                <i class="bi bi-check-circle"></i>
            </div>
            <h2 class="fw-bold">Pesanan Berhasil</h2>
            <p class="text-secondary">Pesananmu sudah tercatat.</p>

        <?php elseif ($status === "Menunggu" && $isQR): ?>
            <h2 class="fw-bold">Pembayaran QRIS</h2>
            <p class="text-secondary">
                Pindai QR di bawah untuk melihat kode simulasi pembayaran.
            </p>

            <div class="qr-box my-3">
                <img src="<?= h($qrUrl) ?>"
                     alt="QR pembayaran simulasi"
                     width="220" height="220">
            </div>

            <p class="small text-secondary">
                Kode QR ini digunakan untuk menampilkan informasi pesanan. 
                Pembayaran akan tercatat setelah Anda menekan tombol konfirmasi.
            </p>

        <?php else: ?>
            <h2 class="fw-bold">Status Pesanan</h2>
            <p class="text-secondary">
                Status transaksi: <?= h($status) ?>
            </p>
        <?php endif; ?>

        <div class="info text-start my-4">
            <div class="d-flex justify-content-between gap-3 mb-3">
                <span class="text-secondary">Kode Transaksi</span>
                <strong class="text-end"><?= h($transaksi["kode_transaksi"]) ?></strong>
            </div>
            <div class="d-flex justify-content-between gap-3 mb-3">
                <span class="text-secondary">Metode Pembayaran</span>
                <strong><?= $isQR ? "QRIS" : "Tunai" ?></strong>
            </div>
            <div class="d-flex justify-content-between gap-3 mb-3">
                <span class="text-secondary">Total</span>
                <strong><?= rupiah($transaksi["total"]) ?></strong>
            </div>
            <div class="d-flex justify-content-between gap-3">
                <span class="text-secondary">Status</span>
                <strong><?= h($status) ?></strong>
            </div>
        </div>

        <?php if ($isMenunggu): ?>
            <form action="konfirmasi_qris.php" method="POST">
                <input type="hidden" name="csrf_token"
                       value="<?= h($_SESSION["csrf_token"]) ?>">
                <input type="hidden" name="kode_transaksi"
                       value="<?= h($transaksi["kode_transaksi"]) ?>">

                <button type="submit" class="btn btn-main btn-lg w-100">
                    Konfirmasi Pembayaran
                </button>
            </form>
            <p class="small text-secondary mt-3">
                Silakan konfirmasi pesanan Anda untuk menyelesaikan proses transaksi.
            </p>
        <?php endif; ?>

        <a href="riwayat.php" class="btn btn-outline-primary w-100 mt-2">
            Lihat Riwayat Transaksi
        </a>
        <a href="produk.php" class="btn btn-outline-secondary w-100 mt-2">
            Belanja Lagi
        </a>
    </div>
</div>
</body>
</html>
