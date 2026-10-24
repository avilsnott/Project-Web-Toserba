
<?php
session_start();
require_once __DIR__ . "/config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$userId = (int) $_SESSION["user_id"];
$isAdmin = ($_SESSION["role"] ?? "") === "admin";
$kode = $_GET["kode"] ?? "";

if (!is_string($kode) || $kode === "") {
    header("Location: riwayat.php");
    exit;
}

if ($isAdmin) {
    $stmt = $pdo->prepare(
        "SELECT t.*, u.nama_lengkap
         FROM transactions t
         LEFT JOIN users u ON u.id = t.user_id
         WHERE t.kode_transaksi = ?
         LIMIT 1"
    );
    $stmt->execute([$kode]);
} else {
    $stmt = $pdo->prepare(
        "SELECT t.*, u.nama_lengkap
         FROM transactions t
         LEFT JOIN users u ON u.id = t.user_id
         WHERE t.kode_transaksi = ? AND t.user_id = ?
         LIMIT 1"
    );
    $stmt->execute([$kode, $userId]);
}

$transaksi = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$transaksi) {
    header("Location: riwayat.php");
    exit;
}

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$stmtDetail = $pdo->prepare(
    "SELECT nama_produk, harga, jumlah, subtotal
     FROM transaction_details
     WHERE transaction_id = ?
"
);
$stmtDetail->execute([(int) $transaksi["id"]]);
$detailProduk = $stmtDetail->fetchAll(PDO::FETCH_ASSOC);

function h($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function rupiah($angka) {
    return "Rp " . number_format((float) $angka, 0, ",", ".");
}

$metode = $transaksi["metode_pembayaran"] === "QR"
    ? "QRIS"
    : "Tunai";

$status = $transaksi["status"];

$statusClass = match ($status) {
    "Berhasil" => "success",
    "Menunggu" => "warning",
    "Dibatalkan" => "danger",
    default => "secondary"
};
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Detail Transaksi | AVÉDEN MART</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">
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
    background:
        radial-gradient(ellipse at top, rgba(120,159,201,.18), transparent 52%),
        linear-gradient(145deg, #eaf0f7, #e1eaf4);
    color: var(--text);
    font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
}

.hero {
    position: relative;
    overflow: hidden;
    padding: 34px;
    color: #fff;
    background: linear-gradient(125deg, var(--navy), var(--navy-soft) 65%, #34577f);
    border: 1px solid rgba(174,191,211,.35);
    border-radius: 24px;
    box-shadow: 0 14px 35px rgba(16,29,53,.15);
}

.hero::after {
    content: "✦";
    position: absolute;
    top: 10px;
    right: 30px;
    color: rgba(220,232,245,.48);
    font-size: 68px;
    pointer-events: none;
}

.hero h1,
.hero p,
.hero > div {
    position: relative;
    z-index: 1;
}

.hero h1 {
    letter-spacing: -.7px;
}

.hero p {
    color: #d0dceb;
    line-height: 1.7;
}

.hero > div:first-child {
    color: var(--silver) !important;
    letter-spacing: 2px;
    font-size: .85rem;
    font-weight: 700;
}

.panel {
    padding: 26px;
    background: linear-gradient(145deg, #fff, #f2f6fb);
    border: 1px solid #d5e0ed;
    border-radius: 22px;
    box-shadow: 0 10px 30px rgba(16,29,53,.06);
}

.panel h5 {
    color: var(--navy);
}

.label {
    margin-bottom: 7px;
    color: var(--muted);
    font-size: .82rem;
    letter-spacing: .2px;
}

.panel .badge {
    padding: 9px 14px;
    border-radius: 30px;
    font-size: .82rem !important;
    font-weight: 700;
}

.info-box {
    height: 100%;
    padding: 19px;
    background: linear-gradient(145deg, #edf3fa, #e5edf6);
    border: 1px solid #d6e1ee;
    border-radius: 16px;
    overflow-wrap: anywhere;
}

.info-box strong {
    color: var(--navy);
    line-height: 1.6;
}

.table-responsive {
    overflow-x: auto;
    border: 1px solid #dce5ef;
    border-radius: 14px;
}

.table {
    margin-bottom: 0;
    color: var(--text);
    --bs-table-bg: transparent;
    --bs-table-hover-bg: #edf3fa;
}

.table thead th {
    padding: 15px 14px;
    color: #e5edf7;
    background: var(--navy-soft);
    border-bottom: 0;
    white-space: nowrap;
    font-size: .8rem;
    font-weight: 600;
}

.table tbody td {
    padding: 15px 14px;
    border-color: #e5ebf3;
    vertical-align: middle;
    font-size: .9rem;
}

.table tbody tr:last-child td {
    border-bottom: 0;
}

.border-top {
    border-color: #d9e3ee !important;
}

.panel h4 {
    color: var(--navy) !important;
}

.text-secondary {
    color: var(--muted) !important;
}

.btn {
    padding: 10px 16px;
    border-radius: 11px;
    font-weight: 600;
    transition: .2s ease;
}

.btn:hover {
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

.btn-primary {
    color: #fff;
    background: linear-gradient(135deg, var(--navy), #34577f);
    border-color: var(--navy);
}

.btn-primary:hover {
    color: #fff;
    background: #29476c;
    border-color: #29476c;
}

.btn-outline-danger {
    color: #984b56;
    border-color: #d7a3aa;
}

.btn-outline-danger:hover {
    color: #fff;
    background: #984b56;
    border-color: #984b56;
}

@media (max-width: 768px) {
    .hero {
        padding: 27px 22px;
        border-radius: 20px;
    }

    .hero::after {
        top: 15px;
        right: 18px;
        font-size: 48px;
    }

    .hero h1 {
        font-size: 1.8rem;
    }

    .panel {
        padding: 19px 15px;
        border-radius: 18px;
    }

    .info-box {
        padding: 16px;
    }

    .table thead th,
    .table tbody td {
        padding: 12px;
    }
}
    </style>
</head>
<body>
<div class="container py-4 py-md-5">

    <div class="hero mb-4">
        <div class="mb-2" style="color:#f3c969">
            ✦ AVÉDEN MART
        </div>
        <h1 class="fw-bold">Detail Transaksi</h1>
        <p class="mb-0">
            Informasi lengkap pesanan Anda.
        </p>
    </div>

    <div class="panel mb-4">
        <div class="d-flex flex-wrap justify-content-between
                    align-items-start gap-3 mb-4">
            <div>
                <div class="label">Kode Transaksi</div>
                <h5 class="fw-bold mb-0">
                    <?= h($transaksi["kode_transaksi"]) ?>
                </h5>
            </div>

            <span class="badge text-bg-<?= h($statusClass) ?> fs-6">
                <?= h($status) ?>
            </span>
        </div>

        <div class="row g-3">
            <?php if ($isAdmin): ?>
                <div class="col-md-6">
                    <div class="info-box">
                        <div class="label">Nama Pelanggan</div>
                        <strong>
                            <?= h($transaksi["nama_lengkap"] ?? "-") ?>
                        </strong>
                    </div>
                </div>
            <?php endif; ?>

            <div class="col-md-6">
                <div class="info-box">
                    <div class="label">Tanggal Transaksi</div>
                    <strong>
                        <?= h(date(
                            "d/m/Y H:i",
                            strtotime($transaksi["created_at"])
                        )) ?>
                    </strong>
                </div>
            </div>

            <div class="col-md-6">
                <div class="info-box">
                    <div class="label">Metode Pembayaran</div>
                    <strong><?= h($metode) ?></strong>
                </div>
            </div>
        </div>
    </div>

    <div class="panel mb-4">
        <h5 class="fw-bold mb-3">Rincian Produk</h5>

        <?php if (empty($detailProduk)): ?>
            <p class="text-secondary mb-0">
                Rincian produk tidak tersedia.
            </p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Harga</th>
                            <th>Jumlah</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($detailProduk as $item): ?>
                        <tr>
                            <td><?= h($item["nama_produk"]) ?></td>
                            <td class="text-nowrap">
                                <?= rupiah($item["harga"]) ?>
                            </td>
                            <td><?= (int) $item["jumlah"] ?></td>
                            <td class="text-nowrap fw-semibold">
                                <?= rupiah($item["subtotal"]) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center
                    border-top pt-3 mt-3 gap-3">
            <span class="fw-semibold">Total Transaksi</span>
            <h4 class="fw-bold mb-0" style="color:#347fce">
                <?= rupiah($transaksi["total"]) ?>
            </h4>
        </div>
    </div>

    
<div class="d-flex flex-wrap gap-2">

    <a href="riwayat.php" class="btn btn-outline-primary">
        Kembali ke Riwayat
    </a>

    <a href="produk.php" class="btn btn-primary">
        Belanja Lagi
    </a>

    <?php if ($status === "Menunggu"): ?>
        <form action="batalkan_transaksi.php" method="POST"
              onsubmit="return confirm('Yakin ingin membatalkan pesanan ini?');">

            <input type="hidden" name="csrf_token"
                   value="<?= h($_SESSION["csrf_token"]) ?>">

            <input type="hidden" name="kode_transaksi"
                   value="<?= h($transaksi["kode_transaksi"]) ?>">

            <button type="submit" class="btn btn-outline-danger">
                Batalkan Pesanan
            </button>
        </form>
    <?php endif; ?>

</div>

</div>
</body>
</html>
