
<?php
session_start();
require_once __DIR__ . "/config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

function kembali($pesan) {
    $_SESSION["checkout_error"] = $pesan;
    header("Location: checkout.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: keranjang.php");
    exit;
}

$token = $_POST["csrf_token"] ?? "";
if (
    !is_string($token) ||
    empty($_SESSION["csrf_token"]) ||
    !hash_equals($_SESSION["csrf_token"], $token)
) {
    kembali("Permintaan tidak valid. Silakan muat ulang halaman.");
}

$metode = $_POST["metode_pembayaran"] ?? "";
if (!in_array($metode, ["Tunai", "QR"], true)) {
    kembali("Pilih metode pembayaran yang tersedia.");
}

$cart = $_SESSION["cart"] ?? [];
if (!is_array($cart) || empty($cart)) {
    kembali("Keranjang belanja kamu kosong.");
}

$jumlahBarang = [];
foreach ($cart as $id => $jumlah) {
    $id = filter_var($id, FILTER_VALIDATE_INT);
    $jumlah = filter_var($jumlah, FILTER_VALIDATE_INT);

    if (!$id || !$jumlah || $jumlah < 1) {
        kembali("Data barang dalam keranjang tidak valid.");
    }

    $jumlahBarang[$id] = $jumlah;
}

try {
    $pdo->beginTransaction();

    $userId = (int) $_SESSION["user_id"];
    $total = 0;
    $rincian = [];

    // Kunci stok selama transaksi agar tidak terjadi penjualan
    // melebihi stok yang tersedia.
    $stmtProduk = $pdo->prepare(
        "SELECT id, kode_produk, nama_produk, harga_jual, stok
         FROM products WHERE id = ? FOR UPDATE"
    );

    foreach ($jumlahBarang as $id => $jumlah) {
        $stmtProduk->execute([$id]);
        $produk = $stmtProduk->fetch(PDO::FETCH_ASSOC);

        if (!$produk) {
            throw new RuntimeException("Ada produk yang sudah tidak tersedia.");
        }

        if ((int)$produk["stok"] < $jumlah) {
            throw new RuntimeException(
                "Stok " . $produk["nama_produk"] .
                " tidak mencukupi. Stok tersedia: " . $produk["stok"] . "."
            );
        }

        $harga = (int)$produk["harga_jual"];
        $subtotal = $harga * $jumlah;
        $total += $subtotal;

        $rincian[] = [
            "id" => (int)$produk["id"],
            "nama" => $produk["nama_produk"],
            "harga" => $harga,
            "jumlah" => $jumlah,
            "subtotal" => $subtotal
        ];
    }

    if ($total <= 0) {
        throw new RuntimeException("Total pesanan tidak valid.");
    }

    $kodeTransaksi = "AVD" . date("YmdHis") .
        strtoupper(bin2hex(random_bytes(3)));

    $stmtTransaksi = $pdo->prepare(
        "INSERT INTO transactions
         (kode_transaksi, user_id, total, metode_pembayaran, status)
         VALUES (?, ?, ?, ?, ?)"
    );

    $status = ($metode === "QR") ? "Menunggu" : "Berhasil";

    $stmtTransaksi->execute([
        $kodeTransaksi,
        $userId,
        $total,
        $metode,
        $status
    ]);

    $transactionId = (int)$pdo->lastInsertId();

    $stmtDetail = $pdo->prepare(
        "INSERT INTO transaction_details
         (transaction_id, product_id, nama_produk, harga, jumlah, subtotal)
         VALUES (?, ?, ?, ?, ?, ?)"
    );

    $stmtStok = $pdo->prepare(
        "UPDATE products SET stok = stok - ?
         WHERE id = ? AND stok >= ?"
    );

    foreach ($rincian as $item) {
        $stmtDetail->execute([
            $transactionId,
            $item["id"],
            $item["nama"],
            $item["harga"],
            $item["jumlah"],
            $item["subtotal"]
        ]);

        
    if ($metode === "Tunai") {
        $stmtStok->execute([
            $item["jumlah"],
            $item["id"],
            $item["jumlah"]
        ]);

        if ($stmtStok->rowCount() !== 1) {
            throw new RuntimeException(
                "Stok berubah saat pesanan diproses. Silakan coba lagi."
            );
        }
    }
}

    $pdo->commit();

    // QR masih berupa simulasi, belum menerima pembayaran sungguhan.
    $_SESSION["checkout_result"] = [
        "kode" => $kodeTransaksi,
        "total" => $total,
        "metode" => $metode,
        "status" => $status
    ];

    unset($_SESSION["cart"]);

    header("Location: hasil_checkout.php");
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if ($e instanceof RuntimeException) {
        kembali($e->getMessage());
    }

    error_log($e->getMessage());
    kembali("Pesanan gagal diproses. Silakan coba lagi.");
}
