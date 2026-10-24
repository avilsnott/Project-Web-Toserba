
<?php
session_start();
require_once __DIR__ . "/config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$pesanSukses = $_SESSION["checkout_success"] ?? "";
$pesanError = $_SESSION["checkout_error"] ?? "";

unset($_SESSION["checkout_success"]);
unset($_SESSION["checkout_error"]);

$userId = (int) $_SESSION["user_id"];
$isAdmin = ($_SESSION["role"] ?? "") === "admin";

if ($isAdmin) {
    $stmt = $pdo->query(
        "SELECT t.*, u.nama_lengkap
         FROM transactions t
         LEFT JOIN users u ON u.id = t.user_id
         ORDER BY t.created_at DESC"
    );
    $transaksi = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->prepare(
        "SELECT t.*, u.nama_lengkap
         FROM transactions t
         LEFT JOIN users u ON u.id = t.user_id
         WHERE t.user_id = ?
         ORDER BY t.created_at DESC"
    );
    $stmt->execute([$userId]);
    $transaksi = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
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
    <title>Riwayat Transaksi | AVÉDEN MART</title>
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
    min-height: 100vh;
    background:
        radial-gradient(ellipse at top, rgba(120,159,201,.18), transparent 50%),
        linear-gradient(145deg, #eaf0f7, #e2eaf4);
    color: var(--text);
    font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
}

.hero {
    position: relative;
    overflow: hidden;
    padding: 34px;
    color: #fff;
    background: linear-gradient(125deg, #101d35, #192b49 65%, #34577f);
    border: 1px solid rgba(174,191,211,.35);
    border-radius: 24px;
    box-shadow: 0 14px 35px rgba(16,29,53,.16);
}

.hero::after {
    content: "✦";
    position: absolute;
    top: 12px;
    right: 30px;
    color: rgba(220,232,245,.5);
    font-size: 68px;
    pointer-events: none;
}

.hero h1 {
    position: relative;
    z-index: 1;
    letter-spacing: -.7px;
}

.hero p {
    position: relative;
    z-index: 1;
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
    box-shadow: 0 12px 35px rgba(16,29,53,.07);
}

.panel h4,
.panel h5 {
    color: var(--navy);
}

.text-secondary {
    color: var(--muted) !important;
}

.table-responsive {
    border: 1px solid #dce5ef;
    border-radius: 15px;
    background: #fff;
}

.table {
    margin-bottom: 0;
    color: var(--text);
    --bs-table-bg: transparent;
    --bs-table-hover-bg: #edf3fa;
}

.table thead th {
    padding: 16px 14px;
    white-space: nowrap;
    color: #e5edf7;
    background: var(--navy-soft);
    border-bottom: 0;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: .35px;
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

.table tbody td:first-child {
    color: var(--navy);
}

.status {
    display: inline-block;
    padding: 7px 12px;
    border: 1px solid transparent;
    border-radius: 30px;
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
}

.status-berhasil {
    color: #286653;
    background: #e0f0e9;
    border-color: #c4e1d4;
}

.status-menunggu {
    color: #80652a;
    background: #f8efda;
    border-color: #ebddb8;
}

.status-dibatalkan {
    color: #914b55;
    background: #f7e5e8;
    border-color: #edcfd4;
}

.btn {
    padding: 9px 15px;
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

.alert {
    border-radius: 13px;
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
        padding: 18px 14px;
        border-radius: 18px;
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
        <div class="mb-2" style="color:#f3c969">✦ AVÉDEN MART</div>
        <h1 class="fw-bold">Riwayat Transaksi</h1>
        <p class="mb-0">
            <?= $isAdmin
                ? "Pantau seluruh transaksi yang tercatat."
                : "Lihat pesanan dan status pembayaranmu." ?>
        </p>
    </div>

    <div class="panel">
        <?php if ($pesanSukses !== ""): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= h($pesanSukses) ?>
                <button type="button" class="btn-close"
                    data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($pesanError !== ""): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= h($pesanError) ?>
                    <button type="button" class="btn-close"
                        data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <h4 class="fw-bold mb-0">Daftar Transaksi</h4>
            <a href="dashboard.php" class="btn btn-outline-primary">
                Kembali ke Dashboard
            </a>
        </div>

        <?php if (empty($transaksi)): ?>
            <div class="text-center py-5">
                <h5 class="fw-bold">Belum Ada Transaksi</h5>
                <p class="text-secondary">
                    Transaksi yang kamu buat akan muncul di sini.
                </p>
                <a href="produk.php" class="btn btn-primary">
                    Mulai Belanja
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Kode Transaksi</th>
                            <?php if ($isAdmin): ?>
                                <th>Pelanggan</th>
                            <?php endif; ?>
                            <th>Tanggal</th>
                            <th>Total</th>
                            <th>Pembayaran</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($transaksi as $item): ?>
                        <?php
                        $status = $item["status"] ?? "";
                        $statusClass = match ($status) {
                            "Berhasil" => "status-berhasil",
                            "Menunggu" => "status-menunggu",
                            "Dibatalkan" => "status-dibatalkan",
                            default => ""
                        };
                        ?>
                        <tr>
                            <td class="fw-semibold">
                                <?= h($item["kode_transaksi"]) ?>
                            </td>

                            <?php if ($isAdmin): ?>
                                <td><?= h($item["nama_lengkap"] ?? "-") ?></td>
                            <?php endif; ?>

                            <td class="text-nowrap">
                                <?= h(date(
                                    "d/m/Y H:i",
                                    strtotime($item["created_at"])
                                )) ?>
                            </td>

                            <td class="text-nowrap fw-semibold">
                                <?= rupiah($item["total"]) ?>
                            </td>

                            <td>
                                <?= ($item["metode_pembayaran"] ?? "") === "QR"
                                    ? "QRIS"
                                    : "Tunai" ?>
                            </td>

                            <td>
                                <span class="status <?= h($statusClass) ?>">
                                    <?= h($status) ?>
                                </span>
                            </td>

                            <td>
                                <a 
                                    href="detail_transaksi.php?kode=<?= rawurlencode($item['kode_transaksi']) ?>"
                                    class="btn btn-sm btn-outline-primary text-nowrap"
                                >
                                    Lihat Detail
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
