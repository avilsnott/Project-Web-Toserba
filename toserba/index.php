<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AVÉDEN MART | Everyday Essentials</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    
<style>
    :root {
        --navy: #101d35;
        --navy-soft: #192b49;
        --blue: #789fc9;
        --ice: #dce8f5;
        --silver: #aebfd3;
        --light: #eaf0f7;
        --gold: #c8d4e3;
    }

    * {
        box-sizing: border-box;
    }

    html {
        scroll-behavior: smooth;
    }

    body {
        margin: 0;
        background: var(--light);
        color: var(--navy);
        font-family: "Segoe UI", Arial, sans-serif;
    }

    /* NAVBAR */
    .navbar {
        background: rgba(16, 29, 53, 0.98);
        padding: 18px 7%;
        border-bottom: 1px solid rgba(220, 232, 245, 0.15);
    }

    .brand {
        color: #f1f5fb;
        font-size: 23px;
        font-weight: 700;
        letter-spacing: 3px;
        text-decoration: none;
    }

    .brand span {
        color: var(--silver);
    }

    .nav-link {
        color: #d4dfed;
        transition: color 0.2s ease;
    }

    .nav-link:hover {
        color: #9fc5ef;
    }

    .navbar-toggler {
        border: 1px solid #8fa8c5;
    }

    .navbar-toggler:focus {
        box-shadow: 0 0 0 3px rgba(143, 168, 197, 0.25);
    }

    /* HERO - CELESTIAL GROCERY */
.hero {
    position: relative;
    isolation: isolate;
    overflow: hidden;
    min-height: 440px;
    display: flex;
    align-items: center;
    padding: 85px 8%;
    color: #f1f5fb;
    background-color: #101d35;
    background-image:
        linear-gradient(
            90deg,
            #101d35 0%,
            rgba(16, 29, 53, 0.98) 28%,
            rgba(25, 43, 73, 0.82) 48%,
            rgba(41, 70, 107, 0.35) 75%,
            rgba(41, 70, 107, 0.18) 100%
        ),
        url("https://images.unsplash.com/photo-1628102491629-778571d893a3?auto=format&fit=crop&fm=jpg&ixlib=rb-4.1.0&q=80&w=2000");

    background-size: cover;
    background-position: center, right 42%;
    background-repeat: no-repeat;
}

.hero::before {
    content: "";
    position: absolute;
    inset: 0;
    z-index: -1;
    background:
        linear-gradient(
            0deg,
            rgba(16, 29, 53, 0.38),
            transparent 45%
        );
    pointer-events: none;
}


.hero > .container-fluid {
    position: relative;
    z-index: 1;
}

.hero p:first-child {
    color: #c1d4eb;
    font-size: 13px;
    font-weight: 600;
    letter-spacing: 3px;
}

.hero h1 {
    max-width: 650px;
    font-size: clamp(36px, 5vw, 60px);
    font-weight: 750;
    line-height: 1.15;
    letter-spacing: -1px;
}

.hero p {
    max-width: 500px;
    color: #d0dbea;
    line-height: 1.8;
}

/* HERO RESPONSIVE */
@media (max-width: 576px) {
    .hero {
        min-height: 420px;
        padding: 70px 6%;
        background-position: center, 62% center;
        background-image:
            linear-gradient(
                90deg,
                rgba(16, 29, 53, 0.96),
                rgba(16, 29, 53, 0.78)
            ),
            url("https://images.unsplash.com/photo-1628102491629-778571d893a3?auto=format&fit=crop&fm=jpg&ixlib=rb-4.1.0&q=80&w=1200");
    }

    .hero::after {
        right: 7%;
        top: 8%;
        font-size: 45px;
    }

    .hero h1 {
        font-size: 37px;
    }
}

    .btn-eden {
        display: inline-block;
        margin-top: 18px;
        padding: 13px 26px;
        border: 1px solid #d1deed;
        border-radius: 9px;
        background: #dce8f5;
        color: #152642;
        font-weight: 700;
        text-decoration: none;
        transition: 0.2s ease;
    }

    .btn-eden:hover {
        background: #f0f5fb;
        color: #101d35;
        transform: translateY(-2px);
    }

    /* SECTION */
    .section {
        padding: 65px 8%;
    }

    .section-title {
        font-weight: 750;
        color: #182b47;
    }

    #kategori {
        background: #eaf0f7;
    }

    #kategori .text-secondary,
    #tentang .text-secondary {
        color: #64758c !important;
    }

    /* KARTU KATEGORI */
    .category {
        padding: 28px 12px;
        background: linear-gradient(145deg, #f8fafd, #e0e9f4);
        border: 1px solid #cfdae8;
        border-radius: 14px;
        text-align: center;
        height: 100%;
        box-shadow: 0 5px 16px rgba(25, 43, 73, 0.05);
        transition:
            transform 0.25s ease,
            box-shadow 0.25s ease,
            border-color 0.25s ease;
    }

    .category:hover {
        transform: translateY(-4px);
        border-color: #9eb6d2;
        box-shadow: 0 10px 22px rgba(25, 43, 73, 0.1);
    }

    .category .symbol {
        color: #6789b2;
        font-size: 32px;
        margin-bottom: 12px;
    }

    .category h5 {
        color: #233b5c;
        font-weight: 650;
        font-size: 16px;
    }

    /* TENTANG */
    #tentang {
        background: #f3f6fa;
    }

    /* FOOTER */
    footer {
        background: #101d35;
        color: #cbd8e8;
        padding: 25px;
        text-align: center;
        border-top: 1px solid #304563;
        font-size: 14px;
    }

    /* LAYAR HP */
    @media (max-width: 576px) {
        .navbar {
            padding: 15px 5%;
        }

        .brand {
            font-size: 19px;
            letter-spacing: 2px;
        }

        .hero {
            padding: 75px 6%;
        }

        .hero::after {
            right: 7%;
            top: 8%;
            font-size: 52px;
        }

        .hero h1 {
            font-size: 37px;
        }

        .section {
            padding: 48px 6%;
        }
    }
</style>

</head>

<body>

<nav class="navbar navbar-expand-lg">
    <div class="container-fluid">
        <a class="brand" href="index.php">
            AVÉDEN <span>✦</span> MART
        </a>

        <button
            class="navbar-toggler bg-light"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#menu"
            aria-controls="menu"
            aria-expanded="false"
            aria-label="Buka navigasi"
        >
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="menu">
            <div class="navbar-nav ms-auto gap-lg-3">
                <a class="nav-link" href="index.php">Beranda</a>
                <a class="nav-link" href="#kategori">Kategori</a>
                <a class="nav-link" href="#tentang">Tentang</a>
                <a class="nav-link" href="login.php">Masuk</a>
            </div>
        </div>
    </div>
</nav>

<section class="hero">
    <div class="container-fluid">
        <p>✧ YOUR EVERYDAY ESSENTIALS ✧</p>

        <h1>
            Temukan Keajaiban
            di Setiap Kebutuhan.
        </h1>

        <p>
            Semua kebutuhan harianmu, mulai dari makanan
            hingga perlengkapan rumah, dalam satu tempat.
        </p>

        <a href="#kategori" class="btn-eden">
            Jelajahi Toko &rarr;
        </a>
    </div>
</section>

<section class="section" id="kategori">
    <div class="text-center mb-5">
        <h2 class="section-title">Jelajahi Kategori</h2>
        <p class="text-secondary">
            Temukan kebutuhan favoritmu dengan mudah.
        </p>
    </div>

    <div class="row g-3">
        <div class="col-6 col-md-3">
            <div class="category">
                <div class="symbol">✧</div>
                <h5>Makanan</h5>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="category">
                <div class="symbol">❖</div>
                <h5>Minuman</h5>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="category">
                <div class="symbol">✦</div>
                <h5>Perawatan Diri</h5>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="category">
                <div class="symbol">◇</div>
                <h5>Perlengkapan Rumah</h5>
            </div>
        </div>
    </div>
</section>

<section class="section pt-0" id="tentang">
    <div class="text-center">
        <h2 class="section-title">Welcome to AVÉDEN MART</h2>
        <p class="text-secondary">
            Belanja kebutuhan harian dengan pengalaman
            yang praktis dan menyenangkan.
        </p>
    </div>
</section>

<footer>
    &copy; <?= date('Y') ?> AVÉDEN MART.
    Made for your everyday needs.
</footer>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>

</body>
</html>
