
<?php
session_start();
require_once __DIR__ . "/config/database.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nama = trim($_POST["nama_lengkap"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";
    $konfirmasi = $_POST["konfirmasi"] ?? "";

    if ($nama === "" || $username === "" || $password === "") {
        $error = "Semua kolom wajib diisi.";
    } elseif (strlen($username) < 3) {
        $error = "Username minimal 3 karakter.";
    } elseif (strlen($password) < 8) {
        $error = "Password minimal 8 karakter.";
    } elseif ($password !== $konfirmasi) {
        $error = "Konfirmasi password tidak cocok.";
    } else {
        $stmt = $pdo->prepare(
            "SELECT id FROM users WHERE username = ?"
        );
        $stmt->execute([$username]);

        if ($stmt->fetch()) {
            $error = "Username sudah digunakan.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare(
                "INSERT INTO users
                 (username, password, nama_lengkap, role)
                 VALUES (?, ?, ?, 'pelanggan')"
            );
            $stmt->execute([$username, $hash, $nama]);

            header("Location: login.php?registered=1");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar | AVÉDEN MART</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    
<style>
    :root {
        --navy: #101d35;
        --navy-soft: #192b49;
        --blue: #789fc9;
        --ice: #dce8f5;
        --silver: #aebfd3;
        --text: #233752;
        --muted: #718096;
    }

    * {
        box-sizing: border-box;
    }

    body {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 28px 16px;
        margin: 0;
        background:
            radial-gradient(
                ellipse at 85% 15%,
                rgba(120, 159, 201, 0.18),
                transparent 35%
            ),
            linear-gradient(
                135deg,
                #101d35,
                #192b49 65%,
                #29466b
            );
        font-family: "Segoe UI", Arial, sans-serif;
    }

    .register-card {
        width: 100%;
        max-width: 450px;
        padding: 36px;
        border: 1px solid rgba(220, 232, 245, 0.65);
        border-radius: 20px;
        background: linear-gradient(
            145deg,
            #f8fafd,
            #eaf0f7
        );
        box-shadow: 0 24px 65px rgba(0, 0, 0, 0.24);
    }

    .brand {
        color: var(--navy);
        font-weight: 800;
        letter-spacing: 1px;
    }

    .register-card .text-secondary {
        color: var(--muted) !important;
    }

    .register-card .text-warning {
        color: var(--blue) !important;
    }

    .form-label {
        color: var(--text);
        font-size: 14px;
        font-weight: 600;
    }

    .form-control {
        padding: 12px;
        color: var(--text);
        background: #fdfefe;
        border: 1px solid #cbd7e5;
        border-radius: 10px;
    }

    .form-control:focus {
        background: #fff;
        border-color: var(--blue);
        box-shadow: 0 0 0 3px rgba(120, 159, 201, 0.18);
    }

    .input-group .form-control {
        border-radius: 10px 0 0 10px;
    }

    .input-group .btn {
        border-color: #cbd7e5;
        border-radius: 0 10px 10px 0;
        background: #e3ebf5;
        color: #344d6c;
    }

    .input-group .btn:hover {
        background: #d3e0ef;
        color: var(--navy);
    }

    .btn-main {
        padding: 12px;
        color: #f5f8fc;
        background: var(--navy);
        border: 1px solid var(--navy);
        border-radius: 10px;
        font-weight: 650;
        transition: background 0.2s ease, transform 0.2s ease;
    }

    .btn-main:hover {
        color: #fff;
        background: #29466b;
        border-color: #29466b;
        transform: translateY(-1px);
    }

    .register-card a {
        color: #426b9b;
        font-weight: 600;
        text-decoration: none;
    }

    .register-card a:hover {
        color: #203f67;
        text-decoration: underline;
    }

    @media (max-width: 480px) {
        .register-card {
            padding: 28px 22px;
            border-radius: 17px;
        }
    }
</style>

</head>
<body>
    <main class="register-card">
        <div class="text-center mb-4">
            <div class="text-warning fs-3">✦</div>
            <h3 class="brand">Buat Akun</h3>
            <p class="text-secondary">Bergabung dengan AVÉDEN MART.</p>
        </div>

        <?php if ($error !== ""): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Nama lengkap</label>
                <input name="nama_lengkap" class="form-control"
                       maxlength="100" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input name="username" class="form-control"
                       minlength="3" maxlength="50" required
                       autocomplete="username">
            </div>
            

<div class="mb-3">
    <label class="form-label">Password</label>
    <div class="input-group">
        <input type="password" name="password" id="password"
               class="form-control" minlength="8" required
               autocomplete="new-password">

        
<button class="btn btn-outline-secondary" type="button"
        onclick="togglePassword('password', this)"
        aria-label="Tampilkan password">
    <i class="bi bi-eye"></i>
</button>

    </div>
</div>

<div class="mb-4">
    <label class="form-label">Konfirmasi password</label>
    <div class="input-group">
        <input type="password" name="konfirmasi" id="konfirmasi"
               class="form-control" minlength="8" required
               autocomplete="new-password">

        
<button class="btn btn-outline-secondary" type="button"
        onclick="togglePassword('konfirmasi', this)"
        aria-label="Tampilkan password">
    <i class="bi bi-eye"></i>
</button>

    </div>
</div>


            <button class="btn btn-main w-100" type="submit">
                Daftar
            </button>
        </form>

        <p class="text-center mt-4 mb-0">
            Sudah punya akun? <a href="login.php">Masuk</a>
        </p>
    </main>
    

<script>
function togglePassword(id, button) {
    const input = document.getElementById(id);
    const icon = button.querySelector("i");

    if (!input || !icon) return;

    const show = input.type === "password";

    input.type = show ? "text" : "password";
    icon.className = show ? "bi bi-eye-slash" : "bi bi-eye";

    button.setAttribute(
        "aria-label",
        show ? "Sembunyikan password" : "Tampilkan password"
    );
}
</script>
</body>
</html>
