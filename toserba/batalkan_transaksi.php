
<?php
session_start();
require_once __DIR__ . "/config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: riwayat.php");
    exit;
}

$token = $_POST["csrf_token"] ?? "";
$kode = $_POST["kode_transaksi"] ?? "";
$userId = (int) $_SESSION["user_id"];
$isAdmin = ($_SESSION["role"] ?? "") === "admin";

if (
    !is_string($token) ||
    empty($_SESSION["csrf_token"]) ||
    !hash_equals($_SESSION["csrf_token"], $token) ||
    !is_string($kode) ||
    $kode === ""
) {
    $_SESSION["checkout_error"] = "Permintaan tidak valid.";
    header("Location: riwayat.php");
    exit;
}

try {
    $pdo->beginTransaction();

    if ($isAdmin) {
        $stmt = $pdo->prepare(
            "SELECT id, status
             FROM transactions
             WHERE kode_transaksi = ?
             FOR UPDATE"
        );
        $stmt->execute([$kode]);
    } else {
        $stmt = $pdo->prepare(
            "SELECT id, status
             FROM transactions
             WHERE kode_transaksi = ? AND user_id = ?
             FOR UPDATE"
        );
        $stmt->execute([$kode, $userId]);
    }

    $transaksi = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$transaksi) {
        throw new RuntimeException("Transaksi tidak ditemukan.");
    }

    if ($transaksi["status"] !== "Menunggu") {
        throw new RuntimeException(
            "Hanya pesanan yang masih menunggu yang dapat dibatalkan."
        );
    }

    $stmtUpdate = $pdo->prepare(
        "UPDATE transactions
         SET status = 'Dibatalkan'
         WHERE id = ? AND status = 'Menunggu'"
    );
    $stmtUpdate->execute([(int) $transaksi["id"]]);

    if ($stmtUpdate->rowCount() !== 1) {
        throw new RuntimeException("Pesanan gagal dibatalkan.");
    }

    $pdo->commit();

    $_SESSION["checkout_success"] =
        "Pesanan berhasil dibatalkan.";

    header(
        "Location: detail_transaksi.php?kode=" .
        rawurlencode($kode)
    );
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if ($e instanceof RuntimeException) {
        $_SESSION["checkout_error"] = $e->getMessage();
    } else {
        error_log($e->getMessage());
        $_SESSION["checkout_error"] =
            "Pesanan gagal dibatalkan. Silakan coba lagi.";
    }

    header(
        "Location: detail_transaksi.php?kode=" .
        rawurlencode($kode)
    );
    exit;
}
