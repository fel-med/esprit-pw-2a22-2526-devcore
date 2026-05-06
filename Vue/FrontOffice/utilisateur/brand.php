<?php
session_start();

if (!isset($_SESSION['id'])) {

    echo "Utilisateur non connecté";
    exit();
}

/*
|--------------------------------------------------------------------------
| Nom utilisateur connecté
|--------------------------------------------------------------------------
*/

$userName = $_SESSION['nom'] ?? 'Brand User';

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Brand Page</title>

    <!-- GOOGLE FONTS -->
    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700;800&family=Fraunces:wght@700;800&display=swap" rel="stylesheet">

    <!-- ICONS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">

    <style>

        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
        }

        :root{

            /* ===== BLEU COMME CREATOR ===== */

            --primary:#5b4fff;
            --primary-hover:#4438e0;
            --primary-light:#ece9ff;

            --bg:#f6f6fc;
            --white:#ffffff;

            --text:#111827;
            --text-sub:#6b7280;

            --border:#ececec;

            --danger:#ef4444;
        }

        body{
            font-family:'DM Sans',sans-serif;
            background:var(--bg);
            color:var(--text);
            min-height:100vh;
        }

        /* =========================
           NAVBAR
        ========================= */

        nav{

            height:75px;

            background:white;

            border-bottom:1px solid var(--border);

            display:flex;
            align-items:center;
            justify-content:space-between;

            padding:0 50px;

            position:sticky;
            top:0;

            z-index:999;
        }

        .nav-logo{
            text-decoration:none;
        }

        .nav-logo-text{

            font-size:28px;

            font-weight:800;

            font-family:'Fraunces',serif;

            color:var(--primary);
        }

        .nav-links{

            list-style:none;

            display:flex;
            align-items:center;
            gap:10px;
        }

        .nav-links a{

            text-decoration:none;

            color:var(--text-sub);

            padding:10px 16px;

            border-radius:12px;

            font-weight:700;

            transition:0.3s;
        }

        .nav-links a:hover{

            background:var(--primary-light);
            color:var(--primary);
        }

        .nav-links .active{

            background:var(--primary-light);
            color:var(--primary);
        }

        .nav-right{

            display:flex;
            align-items:center;
            gap:15px;
        }

        .nav-badge{

            background:var(--primary);

            color:white;

            padding:8px 14px;

            border-radius:30px;

            font-size:13px;
            font-weight:700;
        }

        .nav-avatar{

            width:42px;
            height:42px;

            border-radius:50%;

            background:var(--primary-light);

            display:flex;
            align-items:center;
            justify-content:center;

            color:var(--primary);

            font-size:16px;
            font-weight:800;
        }

        /* =========================
           PAGE
        ========================= */

        .page-wrapper{

            max-width:1200px;

            margin:auto;

            padding:70px 25px;
        }

        .page-header{

            display:flex;
            justify-content:space-between;
            align-items:center;

            gap:20px;

            flex-wrap:wrap;
        }

        .page-header-left h1{

            font-size:65px;

            font-family:'Fraunces',serif;

            margin-bottom:15px;
        }

        .page-header-left p{

            font-size:22px;

            color:var(--text-sub);
        }

        .page-header-actions{

            display:flex;
            gap:15px;
        }

        .btn-export{

            text-decoration:none;

            background:white;

            border:1px solid var(--border);

            padding:18px 35px;

            border-radius:18px;

            color:var(--text);

            font-weight:700;

            transition:0.3s;
        }

        .btn-export:hover{

            background:var(--primary-light);

            color:var(--primary);
        }

        .btn-brand{

            text-decoration:none;

            background:var(--primary);

            color:white;

            padding:18px 35px;

            border-radius:18px;

            font-weight:700;

            transition:0.3s;
        }

        .btn-brand:hover{

            background:var(--primary-hover);
        }

        /* =========================
           HERO SECTION
        ========================= */

        .hero-section{

            margin-top:70px;

            background:linear-gradient(135deg,#5b4fff,#7c3aed);

            border-radius:30px;

            padding:110px 40px;

            text-align:center;

            color:white;

            position:relative;

            overflow:hidden;
        }

        .hero-section::before{

            content:'';

            position:absolute;

            width:350px;
            height:350px;

            border-radius:50%;

            background:rgba(255,255,255,0.1);

            top:-120px;
            right:-120px;
        }

        .hero-section::after{

            content:'';

            position:absolute;

            width:250px;
            height:250px;

            border-radius:50%;

            background:rgba(255,255,255,0.08);

            bottom:-100px;
            left:-100px;
        }

        .hero-content{

            position:relative;
            z-index:2;
        }

        .hero-section h2{

            font-size:52px;

            font-family:'Fraunces',serif;

            margin-bottom:20px;
        }

        .hero-section p{

            font-size:20px;

            opacity:0.95;
        }

        /* =========================
           CARDS
        ========================= */

        .cards-grid{

            margin-top:50px;

            display:grid;

            grid-template-columns:repeat(auto-fit,minmax(250px,1fr));

            gap:25px;
        }

        .info-card{

            background:white;

            border-radius:24px;

            padding:30px;

            box-shadow:0 10px 30px rgba(0,0,0,0.05);

            transition:0.3s;
        }

        .info-card:hover{

            transform:translateY(-5px);
        }

        .info-card i{

            font-size:40px;

            color:var(--primary);

            margin-bottom:20px;
        }

        .info-card h3{

            margin-bottom:10px;

            font-size:22px;
        }

        .info-card p{

            color:var(--text-sub);

            line-height:1.6;
        }

        /* =========================
           FOOTER
        ========================= */

        footer{

            margin-top:80px;

            background:white;

            border-top:1px solid var(--border);

            padding:35px;

            text-align:center;
        }

        footer p{

            color:var(--text-sub);

            font-size:14px;
        }

        /* =========================
           DARK MODE
        ========================= */

        .light-mode{

            background:#111827 !important;
            color:white !important;
        }

        .light-mode nav{

            background:#1f2937;
            border-color:#374151;
        }

        .light-mode .info-card,
        .light-mode footer{

            background:#1f2937;
            color:white;
        }

        .light-mode .page-header-left p,
        .light-mode .info-card p{

            color:#d1d5db;
        }

        .light-mode .btn-export{

            background:#1f2937;
            border-color:#374151;
            color:white;
        }

        /* =========================
           RESPONSIVE
        ========================= */

        @media(max-width:768px){

            nav{

                padding:20px;

                height:auto;

                flex-direction:column;

                gap:15px;
            }

            .page-header{

                flex-direction:column;

                align-items:flex-start;
            }

            .page-header-left h1{

                font-size:45px;
            }

            .hero-section h2{

                font-size:36px;
            }

            .page-header-actions{

                width:100%;

                flex-direction:column;
            }

            .btn-export,
            .btn-brand{

                width:100%;

                text-align:center;
            }
        }

    </style>

</head>

<body>

<!-- =========================
     NAVBAR
========================= -->

<nav>

    <a class="nav-logo" href="brand.php">

        <div class="nav-logo-text">
            Cre8connect
        </div>

    </a>

    <ul class="nav-links">

        <li>
            <a href="brand.php" class="active">
                Home
            </a>
        </li>

        <li>
            <a href="reclamation.php">
                Reclamation
            </a>
        </li>

        <li>
            <a href="#" onclick="toggleDarkMode(); return false;">
                <i class="bi bi-moon-stars" id="themeIcon"></i>
            </a>
        </li>

        <li>
            <a href="logout.php" style="color:#ef4444;">
                <i class="bi bi-box-arrow-right"></i>
                Logout
            </a>
        </li>

    </ul>

    <div class="nav-right">

        <div class="nav-badge">
            👤 <?php echo htmlspecialchars($userName); ?>
        </div>

        <div class="nav-avatar">
            <?php echo strtoupper(substr($userName,0,1)); ?>
        </div>

    </div>

</nav>

<!-- =========================
     MAIN
========================= -->

<div class="page-wrapper">

    <div class="page-header">

        <div class="page-header-left">

            <h1>Brand Page</h1>

            <p>
                Hello :
                <strong>
                    <?php echo htmlspecialchars($userName); ?>
                </strong>
            </p>

        </div>

        <div class="page-header-actions">

            <a href="reclamation.php" class="btn-export">
                Reclamation
            </a>

            <a href="#" class="btn-brand">
                Brand Space
            </a>

        </div>

    </div>

    <!-- HERO -->

    <section class="hero-section">

        <div class="hero-content">

            <h2>
                  Let's build something together
            </h2>

    
        </div>

    </section>


</div>

<!-- =========================
     FOOTER
========================= -->

<footer>

    <p>
        Copyright © Cre8connect 2026
    </p>

</footer>

<!-- =========================
     JS DARK MODE
========================= -->

<script>

    function toggleDarkMode() {

        document.body.classList.toggle('light-mode');

        let icon = document.getElementById('themeIcon');

        if (document.body.classList.contains('light-mode')) {

            localStorage.setItem('theme', 'light');

            if (icon) {
                icon.className = 'bi bi-brightness-high';
            }

        } else {

            localStorage.setItem('theme', 'dark');

            if (icon) {
                icon.className = 'bi bi-moon-stars';
            }
        }
    }

    window.addEventListener('DOMContentLoaded', function() {

        let icon = document.getElementById('themeIcon');

        if (localStorage.getItem('theme') === 'light') {

            document.body.classList.add('light-mode');

            if (icon) {
                icon.className = 'bi bi-brightness-high';
            }
        }
    });

</script>

</body>
</html>