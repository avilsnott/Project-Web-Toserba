
<?php
session_start();
require_once __DIR__ . "/config/database.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    $stmt = $pdo->prepare(
        "SELECT id, username, password, nama_lengkap, role
         FROM users WHERE username = ? LIMIT 1"
    );
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user["password"])) {
        session_regenerate_id(true);
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["username"] = $user["username"];
        $_SESSION["nama_lengkap"] = $user["nama_lengkap"];
        $_SESSION["role"] = $user["role"];

        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Username atau password salah.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk | AVÉDEN MART</title>
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
        padding: 24px;
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

    .login-card {
        width: 100%;
        max-width: 420px;
        padding: 38px;
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
        letter-spacing: 1.5px;
    }

    .login-card .text-secondary {
        color: var(--muted) !important;
    }

    .gold {
        color: #789fc9;
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
        border-color: #789fc9;
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

    .login-card a:not(.text-secondary) {
        color: #426b9b;
        font-weight: 600;
        text-decoration: none;
    }

    .login-card a:not(.text-secondary):hover {
        color: #203f67;
        text-decoration: underline;
    }

    @media (max-width: 480px) {
        body {
            padding: 16px;
        }

        .login-card {
            padding: 28px 22px;
            border-radius: 17px;
        }
    }
</style>

</head>
<body>
    <main class="login-card">
        <div class="text-center mb-4">
            <div class="gold fs-3">✦</div>
            <h3 class="brand">AVÉDEN MART</h3>
            <p class="text-secondary mb-0">Selamat datang kembali.</p>
        </div>

        <?php if ($error !== ""): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username"
                       class="form-control" required
                       autocomplete="username">
            </div>

            <div class="mb-4">
                <label class="form-label">Password</label>
                
<div class="input-group">
    <input type="password" name="password" id="password"
           class="form-control" required
           autocomplete="current-password">

    
<button class="btn btn-outline-secondary" type="button"
        onclick="togglePassword('password', this)"
        aria-label="Tampilkan password">
    <i class="bi bi-eye"></i>
</button>

</div>

            </div>

            <button type="submit" class="btn btn-main w-100">
                Masuk
            </button>
        </form>

        <p class="text-center mt-4 mb-0">
            Belum punya akun?
            <a href="register.php">Daftar sekarang</a>
        </p>

        <div class="text-center mt-3">
            <a href="index.php" class="text-secondary small">
                ← Kembali ke Beranda
            </a>
        </div>
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
