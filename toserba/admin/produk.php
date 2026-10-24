
<?php
session_start();
require_once __DIR__ . "/../config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

if (($_SESSION["role"] ?? "") !== "admin") {
    http_response_code(403);
    exit("Akses ditolak. Halaman ini khusus admin.");
}

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$kategoriList = [
    "Makanan",
    "Minuman",
    "Perawatan Tubuh",
    "Kebersihan",
    "Perlengkapan Rumah",
    "ATK",
    "Sembako",
    "Lainnya"
];

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function rupiah($value) {
    return "Rp " . number_format((float)$value, 0, ",", ".");
}

$error = "";
$edit = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";

    if (!hash_equals($_SESSION["csrf_token"], $token)) {
        $error = "Permintaan tidak valid. Muat ulang halaman.";
    } else {
        $aksi = $_POST["aksi"] ?? "";

        if ($aksi === "hapus") {
            $id = filter_var($_POST["id"] ?? "", FILTER_VALIDATE_INT);

            if (!$id || $id < 1) {
                $error = "ID produk tidak valid.";
            } else {
                $stmt = $pdo->prepare(
                    "DELETE FROM products WHERE id = ?"
                );
                $stmt->execute([$id]);

                header("Location: produk.php?status=hapus");
                exit;
            }
        }

        if ($aksi === "simpan") {
            $id = filter_var($_POST["id"] ?? "", FILTER_VALIDATE_INT);
            $kode = trim($_POST["kode_produk"] ?? "");
            $nama = trim($_POST["nama_produk"] ?? "");
            $kategori = $_POST["kategori"] ?? "";
            $modal = $_POST["harga_modal"] ?? "";
            $harga = $_POST["harga_jual"] ?? "";
            $stok = $_POST["stok"] ?? "";
            $gambar = trim($_POST["gambar"] ?? "");

            if ($kode === "" || $nama === "") {
                $error = "Kode dan nama produk wajib diisi.";
            } elseif (
                strlen($kode) > 30 ||
                strlen($nama) > 150
            ) {
                $error = "Kode atau nama produk terlalu panjang.";
            } elseif (!in_array($kategori, $kategoriList, true)) {
                $error = "Pilih kategori yang tersedia.";
            } elseif (
                !is_numeric($modal) || !is_numeric($harga) ||
                !is_numeric($stok) ||
                (float)$modal < 0 || (float)$harga < 0 ||
                (float)$stok < 0 ||
                (float)$modal > 9999999999 ||
                (float)$harga > 9999999999 ||
                (float)$stok > 2147483647 ||
                floor((float)$stok) != (float)$stok
            ) {
                $error = "Harga harus valid dan stok harus bilangan bulat nonnegatif.";
            } elseif (
                $gambar !== "" &&
                (
                    !filter_var($gambar, FILTER_VALIDATE_URL) ||
                    !in_array(
                        strtolower(parse_url($gambar, PHP_URL_SCHEME) ?? ""),
                        ["http", "https"],
                        true
                    )
                )
            ) {
                $error = "Link gambar harus berupa URL HTTP atau HTTPS yang valid.";
            } else {
                try {
                    if ($id) {
                        $stmt = $pdo->prepare(
                            "UPDATE products
                             SET kode_produk = ?, nama_produk = ?,
                                 kategori = ?, harga_modal = ?,
                                 harga_jual = ?, stok = ?, gambar = ?
                             WHERE id = ?"
                        );

                        $stmt->execute([
                            $kode, $nama, $kategori, $modal,
                            $harga, (int)$stok,
                            $gambar !== "" ? $gambar : null, $id
                        ]);
                    } else {
                        $stmt = $pdo->prepare(
                            "INSERT INTO products
                             (kode_produk, nama_produk, kategori,
                              harga_modal, harga_jual, stok, gambar)
                             VALUES (?, ?, ?, ?, ?, ?, ?)"
                        );

                        $stmt->execute([
                            $kode, $nama, $kategori, $modal,
                            $harga, (int)$stok,
                            $gambar !== "" ? $gambar : null
                        ]);
                    }

                    header(
                        "Location: produk.php?status=" .
                        ($id ? "edit" : "tambah")
                    );
                    exit;
                } catch (PDOException $e) {
                    if ($e->getCode() === "23000") {
                        $error = "Kode produk sudah digunakan.";
                    } else {
                        $error = "Data gagal disimpan. Periksa kembali isian.";
                    }
                }
            }
        }
    }
}

if (isset($_GET["edit"])) {
    $idEdit = filter_var($_GET["edit"], FILTER_VALIDATE_INT);

    if ($idEdit && $idEdit > 0) {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$idEdit]);
        $edit = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}

$stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
$produk = $stmt->fetchAll(PDO::FETCH_ASSOC);

$status = $_GET["status"] ?? "";
$pesanSukses = [
    "tambah" => "Produk berhasil ditambahkan!",
    "edit" => "Produk berhasil diperbarui!",
    "hapus" => "Produk berhasil dihapus."
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kelola Produk | AVÉDEN MART</title>

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
        margin: 0;
        background: var(--page);
        color: var(--text);
        font-family: "Segoe UI", Arial, sans-serif;
    }

    /* NAVBAR */
    .navbar {
        background: var(--navy);
        padding: 16px 0;
        border-bottom: 1px solid rgba(220, 232, 245, 0.16);
        box-shadow: 0 4px 18px rgba(16, 29, 53, 0.1);
    }

    .brand {
        color: #f1f5fb;
        font-weight: 800;
        letter-spacing: 1.5px;
    }

    .brand:hover {
        color: var(--ice);
    }

    .brand span {
        color: var(--silver);
    }

    .navbar .btn-outline-light {
        border-color: #8199b7;
        color: #e4edf7;
        border-radius: 9px;
    }

    .navbar .btn-outline-light:hover {
        background: var(--ice);
        color: var(--navy);
    }

    /* BANNER */
    .page-banner {
        position: relative;
        overflow: hidden;
        padding: 34px;
        border: 1px solid rgba(220, 232, 245, 0.2);
        border-radius: 20px;
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

    .page-banner::after {
        content: "✦";
        position: absolute;
        top: 16px;
        right: 30px;
        color: #c5d5e8;
        font-size: 64px;
        opacity: 0.35;
        pointer-events: none;
    }

    .page-banner .gold {
        position: relative;
        z-index: 1;
        color: #c5d5e8;
    }

    .page-banner h1,
    .page-banner p {
        position: relative;
        z-index: 1;
    }

    .page-banner p {
        color: #d0dbea;
        line-height: 1.7;
    }

    /* PANEL FORM DAN TABEL */
    .panel {
        padding: 26px;
        border: 1px solid #d5e0ed;
        border-radius: 18px;
        background: linear-gradient(
            145deg,
            #f8fafd,
            #eaf0f7
        );
        box-shadow: 0 5px 18px rgba(16, 29, 53, 0.035);
    }

    .panel h4 {
        color: var(--navy);
        letter-spacing: -0.3px;
    }

    .panel h4 .bi {
        color: #638bb7;
    }

    /* FORM */
    .form-label {
        color: #334b68;
        font-size: 14px;
        font-weight: 650;
    }

    .form-control,
    .form-select {
        min-height: 45px;
        padding: 10px 12px;
        border: 1px solid #cedae8;
        border-radius: 9px;
        color: var(--text);
        background-color: #fbfcfe;
    }

    .form-control::placeholder {
        color: #9aa8ba;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #789fc9;
        background-color: #fff;
        box-shadow: 0 0 0 3px rgba(120, 159, 201, 0.18);
    }

    .panel small.text-secondary {
        display: inline-block;
        margin-top: 6px;
        color: var(--muted) !important;
    }

    /* TOMBOL */
    .btn-main {
        color: #f5f8fc;
        background: var(--navy);
        border: 1px solid var(--navy);
        border-radius: 9px;
        font-weight: 600;
        transition: 0.2s ease;
    }

    .btn-main:hover {
        color: #fff;
        background: #29466b;
        border-color: #29466b;
        transform: translateY(-1px);
    }

    .btn-outline-secondary {
        border-radius: 9px;
    }

    .btn-outline-primary {
        color: #527ba9;
        border-color: #a9bfd9;
        border-radius: 8px;
    }

    .btn-outline-primary:hover {
        color: #fff;
        background: #527ba9;
        border-color: #527ba9;
    }

    .btn-outline-danger {
        border-radius: 8px;
    }

    /* TABEL PRODUK */
    .table-responsive {
        border: 1px solid #d9e3ef;
        border-radius: 12px;
        background: #f9fbfd;
    }

    .table {
        margin-bottom: 0;
        color: var(--text);
        --bs-table-bg: transparent;
        --bs-table-hover-bg: #eaf1f9;
        --bs-table-hover-color: var(--navy);
    }

    .table th {
        padding: 14px 12px;
        white-space: nowrap;
        color: #e8eff8;
        background: var(--navy-soft);
        font-size: 13px;
        font-weight: 650;
        border-bottom: 0;
    }

    .table td {
        padding: 13px 12px;
        border-color: #e0e7f0;
        vertical-align: middle;
        font-size: 14px;
    }

    .table tbody tr:last-child td {
        border-bottom: 0;
    }

    .product-thumb {
        width: 48px;
        height: 48px;
        object-fit: cover;
        border: 1px solid #d5e0ed;
        border-radius: 10px;
        background: var(--ice);
    }

    .table .text-primary {
        color: #638bb7 !important;
    }

    /* ALERT */
    .alert {
        border-radius: 12px;
        border-width: 1px;
    }

    .alert-success {
        color: #285c4b;
        background: #e8f4ee;
        border-color: #c9e4d6;
    }

    .alert-danger {
        color: #873f4a;
        background: #f9ecee;
        border-color: #eccfd4;
    }

    /* RESPONSIVE */
    @media (max-width: 576px) {
        .page-banner {
            padding: 26px 23px;
            border-radius: 17px;
        }

        .page-banner::after {
            top: 12px;
            right: 17px;
            font-size: 45px;
        }

        .page-banner h1 {
            font-size: 28px;
        }

        .panel {
            padding: 19px;
            border-radius: 15px;
        }

        .navbar .container {
            gap: 12px;
        }

        .brand {
            font-size: 16px;
        }

        .navbar .btn {
            font-size: 12px;
        }

        .table td,
        .table th {
            padding: 11px 10px;
        }
    }

    @media (prefers-reduced-motion: reduce) {
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

<nav class="navbar">
    <div class="container">
        <a class="navbar-brand brand" href="../dashboard.php">
            AVÉDEN <span>✦</span> MART
        </a>

        <div class="d-flex gap-2">
            <a href="../produk.php" class="btn btn-outline-light btn-sm">
                <i class="bi bi-bag"></i> Katalog
            </a>
            <a href="../dashboard.php" class="btn btn-outline-light btn-sm">
                Dashboard
            </a>
        </div>
    </div>
</nav>

<main class="container py-4 py-md-5">

    <section class="page-banner mb-4">
        <div class="gold fs-3">✦</div>
        <h1 class="fw-bold">Kelola Produk</h1>
        <p class="mb-0">
            Atur koleksi produk dan stok AVÉDEN MART di sini.
        </p>
    </section>

    <?php if (isset($pesanSukses[$status])): ?>
        <div class="alert alert-success">
            <?= h($pesanSukses[$status]) ?>
        </div>
    <?php endif; ?>

    <?php if ($error !== ""): ?>
        <div class="alert alert-danger"><?= h($error) ?></div>
    <?php endif; ?>

    <section class="panel mb-4">
        <h4 class="fw-bold mb-4">
            <i class="bi bi-<?= $edit ? "pencil-square" : "plus-circle" ?>"></i>
            <?= $edit ? "Edit Produk" : "Tambah Produk Baru" ?>
        </h4>

        <form method="POST" action="produk.php">
            <input type="hidden" name="csrf_token"
                   value="<?= h($_SESSION["csrf_token"]) ?>">
            <input type="hidden" name="aksi" value="simpan">
            <input type="hidden" name="id"
                   value="<?= h($edit["id"] ?? "") ?>">

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Kode produk *</label>
                    <input type="text" name="kode_produk"
                           class="form-control" maxlength="30" required
                           value="<?= h($edit["kode_produk"] ?? "") ?>">
                </div>

                <div class="col-md-8">
                    <label class="form-label">Nama produk *</label>
                    <input type="text" name="nama_produk"
                           class="form-control" maxlength="150" required
                           value="<?= h($edit["nama_produk"] ?? "") ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Kategori *</label>
                    <select name="kategori" class="form-select" required>
                        <option value="">Pilih kategori</option>
                        <?php foreach ($kategoriList as $kat): ?>
                            <option value="<?= h($kat) ?>"
                                <?= ($edit["kategori"] ?? "") === $kat ? "selected" : "" ?>>
                                <?= h($kat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Harga modal (Rp) *</label>
                    <input type="number" name="harga_modal"
                           class="form-control" min="0" step="0.01"
                           required value="<?= h($edit["harga_modal"] ?? "") ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Harga jual (Rp) *</label>
                    <input type="number" name="harga_jual"
                           class="form-control" min="0" step="0.01"
                           required value="<?= h($edit["harga_jual"] ?? "") ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Stok *</label>
                    <input type="number" name="stok"
                           class="form-control" min="0" step="1"
                           required value="<?= h($edit["stok"] ?? "0") ?>"
                           placeholder="Jumlah stok">
                </div>

                <div class="col-md-8">
                    <label class="form-label">URL gambar (opsional)</label>
                    <input type="url" name="gambar"
                           class="form-control"
                           value="<?= h($edit["gambar"] ?? "") ?>">
                    <small class="text-secondary">
                        Kosongkan jika belum memiliki gambar.
                    </small>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-main px-4 py-2">
                    <i class="bi bi-check2-circle"></i>
                    <?= $edit ? "Simpan Perubahan" : "Tambah Produk" ?>
                </button>

                <?php if ($edit): ?>
                    <a href="produk.php" class="btn btn-outline-secondary">
                        Batal Edit
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="panel">
        <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
            <h4 class="fw-bold mb-0">Daftar Produk</h4>
            <span class="text-secondary">
                Total: <?= count($produk) ?> produk
            </span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Gambar</th>
                        <th>Kode</th>
                        <th>Nama Produk</th>
                        <th>Kategori</th>
                        <th>Modal</th>
                        <th>Harga Jual</th>
                        <th>Stok</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$produk): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <i class="bi bi-stars fs-2 text-primary"></i>
                                <p class="mt-2 mb-0">
                                    Belum ada produk. Tambahkan produk pertamamu!
                                </p>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($produk as $p): ?>
                        <tr>
                            <td>
                                <?php if (!empty($p["gambar"])): ?>
                                    <img class="product-thumb"
                                         src="<?= h($p["gambar"]) ?>"
                                         alt="<?= h($p["nama_produk"]) ?>">
                                <?php else: ?>
                                    <div class="product-thumb d-flex align-items-center justify-content-center">
                                        <i class="bi bi-stars text-primary"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?= h($p["kode_produk"]) ?></td>
                            <td><?= h($p["nama_produk"]) ?></td>
                            <td><?= h($p["kategori"]) ?></td>
                            <td><?= rupiah($p["harga_modal"]) ?></td>
                            <td><?= rupiah($p["harga_jual"]) ?></td>
                            <td><?= (int)$p["stok"] ?></td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="produk.php?edit=<?= (int)$p["id"] ?>"
                                       class="btn btn-sm btn-outline-primary"
                                       aria-label="Edit produk">
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    <form method="POST" action="produk.php"
                                          onsubmit="return confirm('Yakin ingin menghapus produk ini?')">
                                        <input type="hidden" name="csrf_token"
                                               value="<?= h($_SESSION["csrf_token"]) ?>">
                                        <input type="hidden" name="aksi" value="hapus">
                                        <input type="hidden" name="id"
                                               value="<?= (int)$p["id"] ?>">
                                        <button class="btn btn-sm btn-outline-danger"
                                                type="submit"
                                                aria-label="Hapus produk">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

</main>
</body>
</html>