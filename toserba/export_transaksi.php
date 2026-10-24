
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

if (($_SESSION["role"] ?? "pelanggan") !== "admin") {
    http_response_code(403);
    exit("Akses ditolak.");
}

require_once "config/database.php";

$statusFilter = $_GET["status"] ?? "";
$statusValid = ["Menunggu", "Berhasil", "Dibatalkan"];
$keyword = trim($_GET["keyword"] ?? "");

$laporan = ($_GET["laporan"] ?? "") === "1";

$tanggalAwal = $_GET["tanggal_awal"] ?? "";
$tanggalAkhir = $_GET["tanggal_akhir"] ?? "";

if (
    $laporan &&
    (
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalAwal) ||
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalAkhir) ||
        $tanggalAwal > $tanggalAkhir
    )
) {
    http_response_code(400);
    exit("Rentang tanggal tidak valid.");
}


$sql = "
    SELECT
        t.kode_transaksi,
        u.nama_lengkap,
        u.username,
        t.created_at,
        t.metode_pembayaran,
        t.total,
        t.status
    FROM transactions t
    LEFT JOIN users u ON u.id = t.user_id
";

$conditions = [];
$params = [];

if (in_array($statusFilter, $statusValid, true)) {
    $conditions[] = "t.status = :status";
    $params[":status"] = $statusFilter;
}

if ($keyword !== "") {
    $conditions[] = "(
        t.kode_transaksi LIKE :kode
        OR u.nama_lengkap LIKE :nama
        OR u.username LIKE :username
    )";

    $params[":kode"] = "%" . $keyword . "%";
    $params[":nama"] = "%" . $keyword . "%";
    $params[":username"] = "%" . $keyword . "%";
}


if ($laporan) {
    $conditions[] = "t.created_at >= :tanggal_awal";
    $conditions[] = "t.created_at < DATE_ADD(:tanggal_akhir, INTERVAL 1 DAY)";

    $params[":tanggal_awal"] = $tanggalAwal;
    $params[":tanggal_akhir"] = $tanggalAkhir;
}


if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY t.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

header("Content-Type: text/csv; charset=UTF-8");
header('Content-Disposition: attachment; filename="laporan_transaksi_' . date("Y-m-d") . '.csv"');

$output = fopen("php://output", "w");

fputcsv($output, [
    "Kode Transaksi",
    "Nama Pelanggan",
    "Username",
    "Tanggal",
    "Metode Pembayaran",
    "Total",
    "Status"
], ";");

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, [
        $row["kode_transaksi"],
        $row["nama_lengkap"] ?? "",
        $row["username"] ?? "",
        date("d/m/Y H:i", strtotime($row["created_at"])),
        $row["metode_pembayaran"],
        $row["total"],
        $row["status"]
    ], ";");
}

fclose($output);
exit;
