
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


$statusFilter = $_GET["status"] ?? "";
$statusValid = ["Menunggu", "Berhasil", "Dibatalkan"];

$keyword = trim($_GET["keyword"] ?? "");


$sql = "
    SELECT
        t.id,
        t.kode_transaksi,
        t.total,
        t.metode_pembayaran,
        t.status,
        t.created_at,
        u.nama_lengkap,
        u.username
    FROM transactions t
    LEFT JOIN users u ON u.id = t.user_id
";

$params = [];


$conditions = [];

if (in_array($statusFilter, $statusValid, true)) {
    $conditions[] = "t.status = :status";
    $params[":status"] = $statusFilter;
}

if ($keyword !== "") {
    $conditions[] = "(
        t.kode_transaksi LIKE :keyword
        OR u.nama_lengkap LIKE :keyword
        OR u.username LIKE :keyword
    )";
    $params[":keyword"] = "%" . $keyword . "%";
}

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY t.created_at DESC";

$stmtCount = $pdo->prepare(
    "SELECT COUNT(*)
     FROM transactions t
     LEFT JOIN users u ON u.id = t.user_id"
     . (!empty($conditions) ? " WHERE " . implode(" AND ", $conditions) : "")
);
$stmtCount->execute($params);
$totalData = (int) $stmtCount->fetchColumn();

$perHalaman = 10;
$totalHalaman = max(1, (int) ceil($totalData / $perHalaman));

$halaman = filter_input(INPUT_GET, "halaman", FILTER_VALIDATE_INT);
$halaman = ($halaman && $halaman > 0) ? $halaman : 1;
$halaman = min($halaman, $totalHalaman);

$offset = ($halaman - 1) * $perHalaman;

$sql .= " LIMIT " . (int) $perHalaman . " OFFSET " . (int) $offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transaksi = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalTransaksi = $totalData;

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kelola Transaksi | AVÉDEN MART</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        
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
        background:
            radial-gradient(ellipse at top right, rgba(120,159,201,.17), transparent 38%),
            linear-gradient(145deg, #eaf0f7, #f3f6fb);
        color: var(--text);
        font-family: Arial, sans-serif;
        min-height: 100vh;
    }

    .navbar {
        background: linear-gradient(110deg, #101d35, #1d3557);
        padding: 17px 0;
        border-bottom: 1px solid rgba(220,232,245,.2);
        box-shadow: 0 8px 28px rgba(16,29,53,.14);
    }

    .brand {
        color: #f4f7fc;
        font-weight: 800;
        letter-spacing: 1.3px;
        text-decoration: none;
        font-size: 1.08rem;
    }

    .brand span {
        color: var(--silver);
        text-shadow: 0 0 12px rgba(220,232,245,.4);
    }

    .navbar .btn-outline-light {
        border-color: rgba(220,232,245,.45);
        border-radius: 10px;
        padding: 8px 14px;
    }

    .navbar .btn-outline-light:hover {
        background: var(--ice);
        border-color: var(--ice);
        color: var(--navy);
    }

    main.container {
        max-width: 1440px;
    }

    main h2 {
        color: var(--navy);
        letter-spacing: -.6px;
    }

    main > .mb-4 p {
        color: var(--muted) !important;
    }

    .panel {
        background: rgba(255,255,255,.76);
        border: 1px solid rgba(174,191,211,.58);
        border-radius: 20px;
        padding: 26px;
        box-shadow: 0 14px 38px rgba(25,43,73,.07);
        backdrop-filter: blur(8px);
    }

    .panel h5 {
        color: var(--navy);
    }

    .panel p.text-secondary {
        color: var(--muted) !important;
    }

    .form-control,
    .form-select {
        min-height: 43px;
        border: 1px solid #d4dfec;
        border-radius: 10px;
        background-color: #fbfcfe;
        color: var(--text);
        font-size: .92rem;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: var(--blue);
        box-shadow: 0 0 0 .2rem rgba(120,159,201,.2);
    }

    form[method="GET"] {
        align-items: center;
    }

    form[method="GET"] input {
        min-width: 220px;
    }

    .btn {
        border-radius: 10px;
        transition: background .2s ease, color .2s ease,
                    border-color .2s ease, transform .2s ease;
    }

    .btn-main {
        background: linear-gradient(135deg, #263f62, #456b98);
        color: #fff;
        border: 1px solid transparent;
        padding: 9px 15px;
    }

    .btn-main:hover {
        background: linear-gradient(135deg, #192b49, #34557e);
        color: #fff;
        transform: translateY(-1px);
    }

    .btn-outline-secondary {
        border-color: #c8d4e2;
        color: #52647c;
    }

    .btn-outline-secondary:hover {
        background: var(--ice);
        border-color: var(--silver);
        color: var(--navy);
    }

    .btn-outline-success {
        border-color: #a9c8c1;
        color: #356e64;
    }

    .btn-outline-success:hover {
        background: #e0eee9;
        border-color: #a9c8c1;
        color: #28574f;
    }

    .table-responsive {
        border: 1px solid #dfe7f0;
        border-radius: 14px;
        background: rgba(255,255,255,.65);
    }

    .table {
        margin-bottom: 0;
        color: var(--text);
        --bs-table-bg: transparent;
        --bs-table-hover-bg: #edf3fa;
        --bs-table-hover-color: var(--navy);
    }

    .table thead th {
        background: linear-gradient(110deg, #192b49, #263f62);
        color: #edf4fc;
        border-bottom: 0;
        font-size: .8rem;
        font-weight: 600;
        letter-spacing: .35px;
        padding: 15px 14px;
        white-space: nowrap;
    }

    .table thead th:first-child {
        border-top-left-radius: 12px;
    }

    .table thead th:last-child {
        border-top-right-radius: 12px;
    }

    .table td {
        vertical-align: middle;
        padding: 15px 14px;
        border-color: #e6edf5;
        font-size: .9rem;
    }

    .table tbody tr:last-child td {
        border-bottom: 0;
    }

    .table td .fw-semibold {
        color: var(--navy);
    }

    .table td small {
        color: var(--muted) !important;
    }

    .badge {
        border-radius: 8px;
        padding: 7px 10px;
        font-size: .76rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .badge.text-bg-success {
        background: #dcefe6 !important;
        color: #27664e !important;
    }

    .badge.text-bg-warning {
        background: #f7ebc9 !important;
        color: #80601a !important;
    }

    .badge.text-bg-secondary {
        background: #e2e7ed !important;
        color: #536174 !important;
    }

    .badge.text-bg-light {
        background: #edf1f6 !important;
        color: #52647c !important;
    }

    .btn-outline-primary {
        border-color: #a9bfd9;
        color: #345a85;
    }

    .btn-outline-primary:hover {
        background: #e1ebf7;
        border-color: #789fc9;
        color: var(--navy);
    }

    .pagination {
        gap: 5px;
    }

    .pagination .page-link {
        border: 1px solid #d4dfec;
        border-radius: 9px;
        color: #34516f;
        background: rgba(255,255,255,.8);
        padding: 8px 13px;
    }

    .pagination .page-link:hover {
        background: var(--ice);
        border-color: var(--blue);
        color: var(--navy);
    }

    .pagination .page-item.active .page-link {
        background: linear-gradient(135deg, #192b49, #456b98);
        border-color: #263f62;
        color: white;
    }

    .pagination .page-item.disabled .page-link {
        background: #edf1f6;
        color: #a0acbb;
        border-color: #e0e6ee;
    }

    @media (max-width: 768px) {
        main.container {
            padding-top: 28px !important;
        }

        .panel {
            padding: 17px;
            border-radius: 16px;
        }

        .panel > .d-flex {
            align-items: stretch !important;
        }

        form[method="GET"] {
            width: 100%;
        }

        form[method="GET"] input,
        form[method="GET"] select {
            width: 100%;
            min-width: 0;
        }

        form[method="GET"] button,
        form[method="GET"] a {
            flex: 1 1 auto;
            text-align: center;
        }

        .table td,
        .table th {
            padding: 12px 10px;
        }

        .navbar .container {
            gap: 12px;
        }
    }

    @media (max-width: 420px) {
        .brand {
            font-size: .92rem;
            letter-spacing: .6px;
        }

        .navbar .btn-outline-light {
            padding: 7px 10px;
            font-size: .78rem;
        }

        main h2 {
            font-size: 1.55rem;
        }
    }
</style>

    </style>
</head>
<body>

<nav class="navbar">
    <div class="container d-flex justify-content-between align-items-center">
        <a class="brand" href="../dashboard.php">
            AVÉDEN <span>✦</span> MART
        </a>

        <a href="../dashboard.php" class="btn btn-outline-light btn-sm">
            <i class="bi bi-arrow-left"></i> Dashboard
        </a>
    </div>
</nav>

<main class="container py-5">

    <div class="mb-4">
        <h2 class="fw-bold mb-1">Kelola Transaksi</h2>
        <p class="text-secondary mb-0">
            Periksa transaksi dan status pembayaran pelanggan.
        </p>
    </div>

    <section class="panel">

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h5 class="fw-bold mb-1">Daftar Transaksi</h5>
                <p class="text-secondary mb-0">
                    <?= number_format($totalTransaksi, 0, ",", ".") ?> transaksi ditemukan
                </p>
            </div>

            
<form method="GET" class="d-flex flex-wrap gap-2">
    <input
        type="search"
        name="keyword"
        class="form-control"
        placeholder="Cari kode atau pelanggan..."
        value="<?= h($keyword) ?>"
    >

    <select name="status" class="form-select">
        <option value="">Semua Status</option>
        <option value="Menunggu" <?= $statusFilter === "Menunggu" ? "selected" : "" ?>>
            Menunggu
        </option>
        <option value="Berhasil" <?= $statusFilter === "Berhasil" ? "selected" : "" ?>>
            Berhasil
        </option>
        <option value="Dibatalkan" <?= $statusFilter === "Dibatalkan" ? "selected" : "" ?>>
            Dibatalkan
        </option>
    </select>

    <button type="submit" class="btn btn-main">
        <i class="bi bi-search"></i> Cari
    </button>

    <a href="transaksi.php" class="btn btn-outline-secondary">
        Reset
    </a>

    <a
       href="../export_transaksi.php?<?= http_build_query([
            "keyword" => $keyword,
            "status" => $statusFilter
        ]) ?>"
        class="btn btn-outline-success"
    >
        <i class="bi bi-file-earmark-spreadsheet"></i>
        Export CSV
    </a>

</form>

        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Kode Transaksi</th>
                        <th>Pelanggan</th>
                        <th>Tanggal</th>
                        <th>Pembayaran</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Detail</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (empty($transaksi)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-secondary py-4">
                            Belum ada transaksi untuk status ini.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($transaksi as $item): ?>
                    <tr>
                        <td class="fw-semibold">
                            <?= h($item["kode_transaksi"]) ?>
                        </td>

                        <td>
                            <div class="fw-semibold">
                                <?= h($item["nama_lengkap"] ?: $item["username"] ?: "Pelanggan") ?>
                            </div>
                            <?php if (!empty($item["username"])): ?>
                                <small class="text-secondary">
                                    @<?= h($item["username"]) ?>
                                </small>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?= h(date("d/m/Y H:i", strtotime($item["created_at"]))) ?>
                        </td>

                        <td><?= h($item["metode_pembayaran"]) ?></td>

                        <td class="fw-semibold">
                            <?= rupiah($item["total"]) ?>
                        </td>

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

                        <td>
                            <a
                                href="../detail_transaksi.php?kode=<?= urlencode($item["kode_transaksi"]) ?>"
                                class="btn btn-sm btn-outline-primary"
                            >
                                <i class="bi bi-eye"></i> Lihat
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($totalHalaman > 1): ?>
        <nav class="mt-3" aria-label="Navigasi halaman transaksi">
            <ul class="pagination justify-content-center flex-wrap">

                <li class="page-item <?= $halaman <= 1 ? "disabled" : "" ?>">
                    <a class="page-link"
                       href="?<?= http_build_query([
                           "keyword" => $keyword,
                           "status" => $statusFilter,
                           "halaman" => max(1, $halaman - 1)
                       ]) ?>">
                        Sebelumnya
                    </a>
                </li>

                <?php for ($i = 1; $i <= $totalHalaman; $i++): ?>
                    <li class="page-item <?= $i === $halaman ? "active" : "" ?>">
                        <a class="page-link"
                           href="?<?= http_build_query([
                               "keyword" => $keyword,
                               "status" => $statusFilter,
                               "halaman" => $i
                           ]) ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>

                <li class="page-item <?= $halaman >= $totalHalaman ? "disabled" : "" ?>">
                    <a class="page-link"
                       href="?<?= http_build_query([
                           "keyword" => $keyword,
                           "status" => $statusFilter,
                           "halaman" => min($totalHalaman, $halaman + 1)
                       ]) ?>">
                        Berikutnya
                    </a>
                </li>

            </ul>
        </nav>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
