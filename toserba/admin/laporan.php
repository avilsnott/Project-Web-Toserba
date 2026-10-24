
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

if (($_SESSION["role"] ?? "pelanggan") !== "admin") {
    header("Location: ../dashboard.php");
    exit;
}

require_once "../config/database.php";

function h($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function rupiah($angka) {
    return "Rp " . number_format((float) $angka, 0, ",", ".");
}

$tanggalAwal = $_GET["tanggal_awal"] ?? date("Y-m-01");
$tanggalAkhir = $_GET["tanggal_akhir"] ?? date("Y-m-d");

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalAwal)
    || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalAkhir)
    || $tanggalAwal > $tanggalAkhir) {
    $tanggalAwal = date("Y-m-01");
    $tanggalAkhir = date("Y-m-d");
}

$sqlTanggal = "
    FROM transactions
    WHERE created_at >= :awal
      AND created_at < DATE_ADD(:akhir, INTERVAL 1 DAY)
";

$stmtRingkasan = $pdo->prepare("
    SELECT
        COUNT(*) AS jumlah_transaksi,
        COALESCE(SUM(CASE WHEN status = 'Berhasil' THEN total ELSE 0 END), 0) AS pendapatan,
        COALESCE(SUM(CASE WHEN status = 'Dibatalkan' THEN 1 ELSE 0 END), 0) AS dibatalkan,
        COALESCE(SUM(CASE WHEN status = 'Menunggu' THEN 1 ELSE 0 END), 0) AS menunggu,
        COALESCE(SUM(CASE WHEN status = 'Berhasil' THEN 1 ELSE 0 END), 0) AS berhasil
    $sqlTanggal
");

$stmtRingkasan->execute([
    ":awal" => $tanggalAwal,
    ":akhir" => $tanggalAkhir
]);

$ringkasan = $stmtRingkasan->fetch(PDO::FETCH_ASSOC);

// Pagination laporan
$perHalaman = 10;
$halaman = max(1, (int) ($_GET["halaman"] ?? 1));

// Hitung total transaksi sesuai periode
$stmtHitung = $pdo->prepare("
    SELECT COUNT(*)
    FROM transactions
    WHERE created_at >= :awal
      AND created_at < DATE_ADD(:akhir, INTERVAL 1 DAY)
");

$stmtHitung->execute([
    ":awal" => $tanggalAwal,
    ":akhir" => $tanggalAkhir
]);

$totalData = (int) $stmtHitung->fetchColumn();
$totalHalaman = max(1, (int) ceil($totalData / $perHalaman));

// Pastikan nomor halaman valid
$halaman = min($halaman, $totalHalaman);
$offset = ($halaman - 1) * $perHalaman;

// Ambil transaksi untuk halaman aktif
$stmtTransaksi = $pdo->prepare("
    SELECT
        t.kode_transaksi,
        t.created_at,
        t.total,
        t.metode_pembayaran,
        t.status,
        u.nama_lengkap,
        u.username
    FROM transactions t
    LEFT JOIN users u ON u.id = t.user_id
    WHERE t.created_at >= :awal
      AND t.created_at < DATE_ADD(:akhir, INTERVAL 1 DAY)
    ORDER BY t.created_at DESC
    LIMIT :batas OFFSET :offset
");

$stmtTransaksi->bindValue(":awal", $tanggalAwal);
$stmtTransaksi->bindValue(":akhir", $tanggalAkhir);
$stmtTransaksi->bindValue(":batas", $perHalaman, PDO::PARAM_INT);
$stmtTransaksi->bindValue(":offset", $offset, PDO::PARAM_INT);
$stmtTransaksi->execute();

$transaksi = $stmtTransaksi->fetchAll(PDO::FETCH_ASSOC);

$dataGrafik = [];
$labelGrafik = [];
$nilaiGrafik = [];

$tanggalMulai = new DateTime($tanggalAwal);
$tanggalSelesai = new DateTime($tanggalAkhir);

$stmtGrafik = $pdo->prepare("
    SELECT
        DATE(created_at) AS tanggal,
        COALESCE(SUM(total), 0) AS pendapatan
    FROM transactions
    WHERE status = 'Berhasil'
      AND created_at >= :awal
      AND created_at < DATE_ADD(:akhir, INTERVAL 1 DAY)
    GROUP BY DATE(created_at)
");

$stmtGrafik->execute([
    ':awal' => $tanggalAwal,
    ':akhir' => $tanggalAkhir
]);

foreach ($stmtGrafik->fetchAll(PDO::FETCH_ASSOC) as $baris) {
    $dataGrafik[$baris['tanggal']] = (float) $baris['pendapatan'];
}

while ($tanggalMulai <= $tanggalSelesai) {
    $tanggal = $tanggalMulai->format('Y-m-d');

    $labelGrafik[] = $tanggalMulai->format('d/m');
    $nilaiGrafik[] = $dataGrafik[$tanggal] ?? 0;

    $tanggalMulai->modify('+1 day');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Penjualan | AVÉDEN MART</title>

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
    background:
        radial-gradient(ellipse at top, rgba(120,159,201,.17), transparent 48%),
        linear-gradient(145deg, #eaf0f7, #e2eaf4);
    color: var(--text);
    font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
}

.navbar {
    background: linear-gradient(110deg, var(--navy), var(--navy-soft), #29476c);
    padding: 16px 0;
    border-bottom: 1px solid rgba(174,191,211,.3);
    box-shadow: 0 5px 20px rgba(16,29,53,.12);
}

.brand {
    color: #fff;
    font-weight: 800;
    letter-spacing: 1px;
    text-decoration: none;
}

.brand span {
    color: var(--silver);
}

main.container {
    max-width: 1440px;
}

main > div:first-child h2 {
    color: var(--navy);
    letter-spacing: -.6px;
}

main > div:first-child .small {
    color: #536b86;
}

.panel,
.stat-card {
    height: 100%;
    padding: 24px;
    background: linear-gradient(145deg, #fff, #f2f6fb);
    border: 1px solid #d5e0ed;
    border-radius: 20px;
    box-shadow: 0 10px 28px rgba(16,29,53,.055);
}

.stat-card {
    position: relative;
    overflow: hidden;
    transition: transform .2s ease, box-shadow .2s ease;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 32px rgba(16,29,53,.1);
}

.stat-card::after {
    content: "✦";
    position: absolute;
    top: 12px;
    right: 17px;
    color: rgba(120,159,201,.17);
    font-size: 30px;
    pointer-events: none;
}

.stat-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 44px;
    height: 44px;
    color: var(--blue);
    background: var(--ice);
    border: 1px solid #d0deed;
    border-radius: 14px;
    font-size: 1.3rem;
}

.stat-number {
    position: relative;
    z-index: 1;
    color: var(--navy);
    font-size: 1.55rem;
    font-weight: 800;
    overflow-wrap: anywhere;
}

.text-secondary {
    color: var(--muted) !important;
}

.panel h5 {
    color: var(--navy);
}

.form-label {
    color: var(--navy-soft);
    font-size: .9rem;
    font-weight: 600;
}

.form-control {
    min-height: 44px;
    color: var(--text);
    background-color: #fbfdff;
    border: 1px solid #cbd8e7;
    border-radius: 11px;
}

.form-control:focus {
    background-color: #fff;
    border-color: var(--blue);
    box-shadow: 0 0 0 .2rem rgba(120,159,201,.2);
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

.btn-main {
    color: #fff;
    background: linear-gradient(135deg, var(--navy), #34577f);
    border: 1px solid var(--navy);
}

.btn-main:hover {
    color: #fff;
    background: #29476c;
    border-color: #29476c;
}

.btn-outline-light {
    border-color: rgba(220,232,245,.6);
}

.btn-light {
    color: var(--navy);
    background: #eaf0f7;
    border-color: #eaf0f7;
}

.btn-light:hover {
    color: #fff;
    background: #34577f;
    border-color: #34577f;
}

.table-responsive {
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
    padding: 15px 13px;
    color: #e5edf7;
    background: var(--navy-soft);
    border-bottom: 0;
    white-space: nowrap;
    font-size: .8rem;
    font-weight: 600;
}

.table tbody td {
    padding: 14px 13px;
    border-color: #e5ebf3;
    vertical-align: middle;
    font-size: .88rem;
}

.table tbody tr:last-child td {
    border-bottom: 0;
}

.badge {
    padding: 7px 10px;
    border-radius: 20px;
    font-weight: 600;
}

.pagination {
    gap: 4px;
}

.pagination .page-link {
    color: var(--navy);
    background: #fff;
    border: 1px solid #d4dfec;
    border-radius: 8px;
}

.pagination .page-item.active .page-link {
    color: #fff;
    background: var(--navy-soft);
    border-color: var(--navy-soft);
}

.pagination .page-item.disabled .page-link {
    color: #9aa8b8;
    background: #edf2f8;
}

#btnKeAtas {
    position: fixed;
    right: 24px;
    bottom: 24px;
    z-index: 1000;
    display: none;
    width: 46px;
    height: 46px;
    padding: 0;
    color: #fff;
    background: var(--navy-soft);
    border: 1px solid var(--silver);
    border-radius: 50%;
    box-shadow: 0 5px 16px rgba(16,29,53,.2);
}

@media (max-width: 768px) {
    main.container {
        padding-top: 28px !important;
    }

    .panel,
    .stat-card {
        padding: 19px 16px;
        border-radius: 17px;
    }

    .stat-number {
        font-size: 1.35rem;
    }

    .navbar .container {
        gap: 12px;
    }

    .navbar .d-flex {
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .table thead th,
    .table tbody td {
        padding: 11px;
    }
}

@media print {
    #btnKeAtas {
        display: none !important;
    }

    @page {
        size: A4 landscape;
        margin: 12mm;
    }

    body {
        background: #fff !important;
        color: #000 !important;
        font-size: 10pt;
    }

    .navbar,
    form,
    .btn,
    .stat-icon,
    .pagination,
    #btnKeAtas {
        display: none !important;
    }

    main.container {
        width: 100% !important;
        max-width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
    }

    h1, h2, h3, h4, h5 {
        color: #000 !important;
    }

    .panel,
    .stat-card {
        height: auto;
        padding: 12px !important;
        background: #fff !important;
        border: 1px solid #ccc !important;
        border-radius: 6px !important;
        box-shadow: none !important;
        break-inside: avoid;
    }

    .row {
        break-inside: avoid;
    }

    .table-responsive {
        overflow: visible !important;
    }

    .table {
        width: 100% !important;
        font-size: 10px;
    }

    .table th,
    .table td {
        padding: 5px !important;
        color: #000 !important;
    }

    .table thead th {
        background: #e5eaf0 !important;
    }

    canvas {
        max-height: 260px !important;
    }

    thead {
        display: table-header-group;
    }

    tr {
        break-inside: avoid;
    }
}
    </style>
</head>
<body>

<nav class="navbar">
    <div class="container d-flex justify-content-between align-items-center">
        <a class="brand" href="../dashboard.php">
            AVÉDEN <span>✦</span> MART
        </a>

        <div class="d-flex gap-2">
    <button
        type="button"
        class="btn btn-outline-light btn-sm"
        onclick="if (history.length > 1) { history.back(); } else { location.href='../dashboard.php'; }"
    >
        <i class="bi bi-arrow-left"></i> Kembali
    </button>

    <a href="../dashboard.php" class="btn btn-light btn-sm">
        <i class="bi bi-grid"></i> Dashboard
    </a>
</div>
    </div>
</nav>

<main class="container py-5">

    <div class="mb-4">
    <h2 class="fw-bold mb-1">Laporan Penjualan</h2>

    <p class="text-secondary mb-1">
        Ringkasan transaksi berdasarkan periode yang dipilih.
    </p>

    <div class="small fw-semibold">
        <i class="bi bi-calendar3 me-1"></i>
        Periode:
        <?= h(date("d/m/Y", strtotime($tanggalAwal))) ?>
        –
        <?= h(date("d/m/Y", strtotime($tanggalAkhir))) ?>
    </div>
</div>

    <section class="panel mb-4">
        <form method="GET" class="row g-3 align-items-end">
            <input type="hidden" name="halaman" value="1">
            <div class="col-md-4">
                <label for="tanggal_awal" class="form-label">Tanggal Awal</label>
                <input
                    type="date"
                    id="tanggal_awal"
                    name="tanggal_awal"
                    class="form-control"
                    value="<?= h($tanggalAwal) ?>"
                    required
                >
            </div>

            <div class="col-md-4">
                <label for="tanggal_akhir" class="form-label">Tanggal Akhir</label>
                <input
                    type="date"
                    id="tanggal_akhir"
                    name="tanggal_akhir"
                    class="form-control"
                    value="<?= h($tanggalAkhir) ?>"
                    required
                >
            </div>

            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-main">
                    <i class="bi bi-funnel"></i> Terapkan
                </button>

                <a href="laporan.php" class="btn btn-outline-secondary">
                    Reset
                </a>
            </div>
        </form>
    </section>

    <div class="row g-3 mb-4">

        <div class="col-md-6 col-xl-3">
            <div class="stat-card">
                <i class="bi bi-receipt stat-icon"></i>
                <p class="text-secondary mt-3 mb-1">Total Transaksi</p>
                <div class="stat-number">
                    <?= number_format((int) $ringkasan["jumlah_transaksi"], 0, ",", ".") ?>
                </div>
                <small class="text-secondary">Semua status</small>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="stat-card">
                <i class="bi bi-wallet2 stat-icon"></i>
                <p class="text-secondary mt-3 mb-1">Total Pendapatan</p>
                <div class="stat-number">
                    <?= rupiah($ringkasan["pendapatan"]) ?>
                </div>
                <small class="text-secondary">Transaksi berhasil</small>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="stat-card">
                <i class="bi bi-check-circle stat-icon"></i>
                <p class="text-secondary mt-3 mb-1">Transaksi Berhasil</p>
                <div class="stat-number">
                    <?= number_format((int) $ringkasan["berhasil"], 0, ",", ".") ?>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="stat-card">
                <i class="bi bi-x-circle stat-icon"></i>
                <p class="text-secondary mt-3 mb-1">Transaksi Dibatalkan</p>
                <div class="stat-number">
                    <?= number_format((int) $ringkasan["dibatalkan"], 0, ",", ".") ?>
                </div>
                <small class="text-secondary">
                    Menunggu: <?= number_format((int) $ringkasan["menunggu"], 0, ",", ".") ?>
                </small>
            </div>
        </div>

    </div>

    <section class="panel mb-4">
        <h5 class="fw-bold mb-1">Grafik Pendapatan Harian</h5>

        <p class="text-secondary">
            Pendapatan dari transaksi berstatus Berhasil
            pada periode yang dipilih.
        </p>

        <div style="position: relative; height: 320px;">
    <?php if (empty($dataGrafik) || array_sum($nilaiGrafik) <= 0): ?>
        <div class="d-flex flex-column align-items-center justify-content-center h-100 text-secondary">
            <i class="bi bi-bar-chart-line fs-1 mb-2"></i>
            <p class="mb-0">Belum ada pendapatan pada periode ini.</p>
        </div>
    <?php else: ?>
        <canvas id="grafikPendapatan"></canvas>
    <?php endif; ?>
</div>
    </section>

    <section class="panel">
        <div class="mb-3">
            <h5 class="fw-bold mb-1">Rincian Transaksi</h5>
            <p class="text-secondary mb-0">
                Periode <?= h(date("d/m/Y", strtotime($tanggalAwal))) ?>
                sampai <?= h(date("d/m/Y", strtotime($tanggalAkhir))) ?>
            </p>
        </div>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Kode Transaksi</th>
                        <th>Pelanggan</th>
                        <th>Tanggal</th>
                        <th>Pembayaran</th>
                        <th>Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($transaksi)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-secondary py-4">
                            Tidak ada transaksi pada periode ini.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($transaksi as $item): ?>
                    <tr>
                        <td class="fw-semibold"><?= h($item["kode_transaksi"]) ?></td>
                        <td>
                            <?= h($item["nama_lengkap"] ?: $item["username"] ?: "Pelanggan") ?>
                        </td>
                        <td>
                            <?= h(date("d/m/Y H:i", strtotime($item["created_at"]))) ?>
                        </td>
                        <td><?= h($item["metode_pembayaran"]) ?></td>
                        <td><?= rupiah($item["total"]) ?></td>
                        <td>
                            <?php
                            $badgeClass = match ($item["status"]) {
                                "Berhasil" => "text-bg-success",
                                "Menunggu" => "text-bg-warning",
                                "Dibatalkan" => "text-bg-secondary",
                                default => "text-bg-light"
                            };
                            ?>
                            <span class="badge <?= $badgeClass ?>">
                                <?= h($item["status"]) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalHalaman > 1): ?>
    <nav class="mt-3" aria-label="Navigasi halaman laporan">
        <ul class="pagination justify-content-center flex-wrap">

            <li class="page-item <?= $halaman <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?<?= http_build_query([
                    'tanggal_awal' => $tanggalAwal,
                    'tanggal_akhir' => $tanggalAkhir,
                    'halaman' => max(1, $halaman - 1)
                ]) ?>">
                    &laquo; Sebelumnya
                </a>
            </li>

            <?php
$mulaiHalaman = max(1, $halaman - 2);
$akhirHalaman = min($totalHalaman, $halaman + 2);
?>

<?php if ($mulaiHalaman > 1): ?>
    <li class="page-item">
        <a class="page-link" href="?<?= http_build_query([
            'tanggal_awal' => $tanggalAwal,
            'tanggal_akhir' => $tanggalAkhir,
            'halaman' => 1
        ]) ?>">1</a>
    </li>

    <?php if ($mulaiHalaman > 2): ?>
        <li class="page-item disabled">
            <span class="page-link">…</span>
        </li>
    <?php endif; ?>
<?php endif; ?>

<?php for ($i = $mulaiHalaman; $i <= $akhirHalaman; $i++): ?>
    <li class="page-item <?= $i === $halaman ? 'active' : '' ?>">
        <a class="page-link" href="?<?= http_build_query([
            'tanggal_awal' => $tanggalAwal,
            'tanggal_akhir' => $tanggalAkhir,
            'halaman' => $i
        ]) ?>">
            <?= $i ?>
        </a>
    </li>
<?php endfor; ?>

<?php if ($akhirHalaman < $totalHalaman): ?>
    <?php if ($akhirHalaman < $totalHalaman - 1): ?>
        <li class="page-item disabled">
            <span class="page-link">…</span>
        </li>
    <?php endif; ?>

    <li class="page-item">
        <a class="page-link" href="?<?= http_build_query([
            'tanggal_awal' => $tanggalAwal,
            'tanggal_akhir' => $tanggalAkhir,
            'halaman' => $totalHalaman
        ]) ?>">
            <?= $totalHalaman ?>
        </a>
    </li>
<?php endif; ?>

            <li class="page-item <?= $halaman >= $totalHalaman ? 'disabled' : '' ?>">
                <a class="page-link" href="?<?= http_build_query([
                    'tanggal_awal' => $tanggalAwal,
                    'tanggal_akhir' => $tanggalAkhir,
                    'halaman' => min($totalHalaman, $halaman + 1)
                ]) ?>">
                    Berikutnya &raquo;
                </a>
            </li>

        </ul>

        <p class="text-secondary text-center small">
    Menampilkan
    <?= $totalData > 0 ? $offset + 1 : 0 ?>
    –
    <?= min($offset + $perHalaman, $totalData) ?>
    dari
    <?= number_format($totalData, 0, ',', '.') ?>
    transaksi
    · Halaman <?= $halaman ?> dari <?= $totalHalaman ?>
</p>
    </nav>
<?php endif; ?>

        <a href="../export_transaksi.php?<?= http_build_query([
            "tanggal_awal" => $tanggalAwal,
            "tanggal_akhir" => $tanggalAkhir,
            "laporan" => "1"
        ]) ?>" class="btn btn-outline-success">
            <i class="bi bi-file-earmark-spreadsheet"></i> Export CSV
        </a>

        <button
    type="button"
    onclick="window.print()"
    class="btn btn-main ms-2"
>
    <i class="bi bi-printer"></i> Cetak Laporan
</button>
    </section>

</main>
<button
    type="button"
    id="btnKeAtas"
    class="btn btn-main"
    title="Kembali ke atas"
    aria-label="Kembali ke atas"
>
    <i class="bi bi-arrow-up"></i>
</button>

<script>
const btnKeAtas = document.getElementById("btnKeAtas");

window.addEventListener("scroll", function() {
    btnKeAtas.style.display =
        window.scrollY > 300 ? "block" : "none";
});

btnKeAtas.addEventListener("click", function() {
    window.scrollTo({
        top: 0,
        behavior: "smooth"
    });
});
</script>

<script>
const inputTanggalAwal = document.getElementById("tanggal_awal");
const inputTanggalAkhir = document.getElementById("tanggal_akhir");

function aturBatasTanggal() {
    inputTanggalAkhir.min = inputTanggalAwal.value;
    inputTanggalAwal.max = inputTanggalAkhir.value;
}

inputTanggalAwal.addEventListener("change", aturBatasTanggal);
inputTanggalAkhir.addEventListener("change", aturBatasTanggal);

aturBatasTanggal();
</script>
</body>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const labelGrafik = <?= json_encode(
    $labelGrafik,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
) ?>;

const nilaiGrafik = <?= json_encode(
    $nilaiGrafik,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
) ?>;

const canvas = document.getElementById("grafikPendapatan");

if (canvas) {
    new Chart(canvas, {
    type: "line",

    data: {
        labels: labelGrafik,
        datasets: [{
            label: "Pendapatan (Rp)",
            data: nilaiGrafik,
            borderColor: "#438bdf",
            backgroundColor: "rgba(67, 139, 223, 0.15)",
            fill: true,
            tension: 0.3,
            pointRadius: 3
        }]
    },

    options: {
    responsive: true,
    maintainAspectRatio: false,

    interaction: {
        intersect: false,
        mode: "index"
    },

    plugins: {
        legend: {
            display: true
        },
        tooltip: {
            callbacks: {
                label: function(context) {
                    return "Pendapatan: Rp " +
                        new Intl.NumberFormat("id-ID").format(context.parsed.y);
                }
            }
        }
    },

    scales: {
        x: {
            title: {
                display: true,
                text: "Tanggal"
            }
        },
        y: {
            beginAtZero: true,
            title: {
                display: true,
                text: "Pendapatan"
            },
            ticks: {
                callback: function(value) {
                    return "Rp " +
                        new Intl.NumberFormat("id-ID").format(value);
                }
            }
        }
    }
}
})};
</script>
</html>
