
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

    $stmt = $pdo->prepare(
        "SELECT id, status, metode_pembayaran
         FROM transactions
         WHERE kode_transaksi = ? AND user_id = ?
         FOR UPDATE"
    );
    $stmt->execute([$kode, (int)$_SESSION["user_id"]]);
    $transaksi = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$transaksi) {
        throw new RuntimeException("Transaksi tidak ditemukan.");
    }

    if ($transaksi["metode_pembayaran"] !== "QR") {
        throw new RuntimeException("Transaksi ini bukan pembayaran QRIS.");
    }

    if ($transaksi["status"] !== "Menunggu") {
        throw new RuntimeException("Transaksi ini sudah diproses.");
    }

    $stmtDetail = $pdo->prepare(
        "SELECT product_id, jumlah
         FROM transaction_details
         WHERE transaction_id = ?"
    );
    $stmtDetail->execute([(int)$transaksi["id"]]);
    $details = $stmtDetail->fetchAll(PDO::FETCH_ASSOC);

    if (!$details) {
        throw new RuntimeException("Detail pesanan tidak ditemukan.");
    }

    $stmtProduk = $pdo->prepare(
        "SELECT id, nama_produk, stok
         FROM products
         WHERE id = ?
         FOR UPDATE"
    );

    $stmtStok = $pdo->prepare(
        "UPDATE products
         SET stok = stok - ?
         WHERE id = ? AND stok >= ?"
    );

    foreach ($details as $detail) {
        $jumlah = (int)$detail["jumlah"];
        $productId = (int)$detail["product_id"];

        $stmtProduk->execute([$productId]);
        $produk = $stmtProduk->fetch(PDO::FETCH_ASSOC);

        if (!$produk || (int)$produk["stok"] < $jumlah) {
            $nama = $produk["nama_produk"] ?? "Produk";
            throw new RuntimeException(
                "Stok " . $nama . " tidak mencukupi. Pesanan belum dikonfirmasi."
            );
        }

        $stmtStok->execute([$jumlah, $productId, $jumlah]);

        if ($stmtStok->rowCount() !== 1) {
            throw new RuntimeException(
                "Stok berubah. Silakan periksa pesanan kembali."
            );
        }
    }

    $stmtUpdate = $pdo->prepare(
        "UPDATE transactions
         SET status = 'Berhasil'
         WHERE id = ? AND status = 'Menunggu'"
    );
    $stmtUpdate->execute([(int)$transaksi["id"]]);

    if ($stmtUpdate->rowCount() !== 1) {
        throw new RuntimeException("Status transaksi gagal diperbarui.");
    }

    $pdo->commit();

    $_SESSION["checkout_success"] =
    "Pembayaran QRIS berhasil dikonfirmasi!";

    header("Location: hasil_checkout.php");
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if ($e instanceof RuntimeException) {
        $_SESSION["checkout_error"] = $e->getMessage();
    } else {
        error_log($e->getMessage());
        $_SESSION["checkout_error"] = "Konfirmasi gagal. Silakan coba lagi.";
    }

    header("Location: riwayat.php");
    exit;
}
