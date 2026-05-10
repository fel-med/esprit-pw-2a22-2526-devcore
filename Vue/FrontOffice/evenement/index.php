<?php
// This file can work both directly AND through the controller
$BASE = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');

if (!isset($evenements)) {
    require_once __DIR__ . '/../../../config.php';
    require_once __DIR__ . '/../../../Modele/evenement.php';
    
    try {
        $pdo = config::getConnexion();
        $stmt = $pdo->prepare(
            "SELECT * FROM evenement WHERE statut = 'actif' ORDER BY DateFormation ASC"
        );
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $evenements = [];
        foreach ($rows as $row) {
            $evenements[] = new Evenement(
                (int)($row['idFormation'] ?? 0),
                $row['TitreFormation'] ?? '',
                $row['description'] ?? '',
                $row['type'] ?? '',
                $row['statut'] ?? '',
                $row['lieu'] ?? '',
                $row['DateFormation'] ?? '',
                (int)($row['capacite'] ?? 0),
                (int)($row['nb_inscrits'] ?? 0),
                (int)($row['Duree'] ?? 0),
                $row['created_at'] ?? '',
                $row['image'] ?? null,
                $row['adresse_complete'] ?? null
            );
        }
        
        $forumsData = [];
        $stmtForum = $pdo->prepare("SELECT idFormation, idForum, est_actif FROM forum WHERE est_actif = 1");
        $stmtForum->execute();
        while ($row = $stmtForum->fetch(PDO::FETCH_ASSOC)) {
            $forumsData[$row['idFormation']] = $row;
        }
        
    } catch (Exception $e) {
        $evenements = [];
        $forumsData = [];
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Événements - Cre8Connect</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700;9..144,800&display=swap" rel="stylesheet">
    <script src="<?= $BASE ?>/Vue/public/js/translations.js"></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary:        #5b4fff;
            --primary-light:  #ece9ff;
            --primary-hover:  #4438e0;
            --primary-glow:   rgba(91,79,255,0.15);
            --primary-border: rgba(91,79,255,0.2);
            --text-main:      #0f0e1a;
            --text-sub:       #6b6f80;
            --text-dim:       #a0a4b2;
            --border:         #e8e8f0;
            --bg:             #f4f4fb;
            --white:          #ffffff;
            --danger:         #f43f5e;
            --danger-light:   #fff1f3;
            --success:        #0ea370;
            --success-light:  #edfaf5;
            --warning:        #f59e0b;
            --warning-light:  #fffbeb;
            --radius:         16px;
            --radius-sm:      10px;
            --nav-h:          60px;
            --card-shadow:    0 1px 4px rgba(15,14,26,0.07), 0 4px 16px rgba(91,79,255,0.05);
            --card-shadow-hover: 0 8px 32px rgba(91,79,255,0.13);
        }

        body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text-main); min-height: 100vh; }

        body.dark-mode {
            --primary:        #7c6eff;
            --primary-light:  #2a2648;
            --primary-hover:  #8f82ff;
            --primary-glow:   rgba(124,110,255,0.2);
            --primary-border: rgba(124,110,255,0.3);
            --text-main:      #e6edf3;
            --text-sub:       #8b949e;
            --text-dim:       #6e7681;
            --border:         #30363d;
            --bg:             #0d1117;
            --white:          #161b22;
            --danger-light:   #3b1a24;
            --success-light:  #1a3e2a;
            --warning-light:  #3b2a1a;
            --card-shadow:    0 1px 3px rgba(0,0,0,0.3), 0 4px 20px rgba(0,0,0,0.2);
            --card-shadow-hover: 0 12px 32px rgba(0,0,0,0.4);
        }

        /* ── NAV ── */
        nav {
            background: var(--white);
            border-bottom: 1px solid var(--border);
            padding: 0 48px;
            height: var(--nav-h);
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 200;
            box-shadow: 0 1px 0 var(--border);
        }
        .nav-logo { display: flex; align-items: center; gap: 8px; text-decoration: none; }
        .nav-logo img { width: 32px; height: 32px; object-fit: contain; border-radius: 8px; }
        .nav-logo-text { font-family: 'Fraunces', serif; font-size: 18px; font-weight: 800; color: var(--primary); letter-spacing: -0.5px; }
        .nav-links { display: flex; gap: 4px; list-style: none; }
        .nav-links a { text-decoration: none; color: var(--text-sub); font-size: 13.5px; font-weight: 600; padding: 6px 14px; border-radius: 8px; transition: all 0.18s; }
        .nav-links a:hover { background: var(--bg); color: var(--text-main); }
        .nav-links a.active { background: var(--primary); color: #fff; border-radius: 20px; }
        .nav-right { display: flex; align-items: center; gap: 10px; }
        .nav-avatar { width: 34px; height: 34px; border-radius: 50%; background: var(--primary); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; cursor: pointer; }
        .theme-toggle-btn, .lang-toggle-btn { background: var(--bg); border: 1px solid var(--border); border-radius: 20px; padding: 5px 12px; cursor: pointer; font-size: 0.8rem; font-weight: 600; color: var(--text-sub); transition: all 0.2s; display: flex; align-items: center; gap: 5px; height: 32px; }
        .theme-toggle-btn:hover, .lang-toggle-btn:hover { background: var(--primary-light); color: var(--primary); border-color: var(--primary-border); }

.hero { background: var(--bg); padding: 28px 48px; }

.hero-card {
    background: linear-gradient(140deg, #edeaff 0%, #eef0ff 50%, #e8ecff 100%);
    border: 1px solid #dddaf5;
    border-radius: 18px;
    padding: 32px 40px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
    max-width: 1400px;
    margin: 0 auto;
    overflow: hidden;
}

body.dark-mode .hero-card {
    background: linear-gradient(140deg, #1e1a3a 0%, #1a1d35 50%, #181c30 100%);
    border-color: #2e2a50;
}

body.dark-mode .hero-content h1 {
    color: var(--text-main);
}

.hero-content { flex: 1; }
.hero-content h1 {
    font-family: 'Fraunces', serif;
    font-size: 1.75rem;
    font-weight: 800;
    color: #0f0e1a;
    margin-bottom: 8px;
    letter-spacing: -0.5px;
    line-height: 1.2;
}
.hero-content p {
    font-size: 12.5px;
    color: var(--text-sub);
    max-width: 400px;
    line-height: 1.6;
    margin-bottom: 0;
}

.stats-bar {
    display: flex;
    gap: 20px;
    margin-top: 18px;
    flex-wrap: wrap;
    align-items: center;
}
.stat-item {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 12.5px;
    font-weight: 600;
    color: var(--text-sub);
    background: none;
    border: none;
    padding: 0;
}
.stat-icon {
    color: var(--primary);
    font-weight: 800;
    font-size: 14px;
}
.stat-num {
    font-family: 'Fraunces', serif;
    font-weight: 800;
    font-size: 14px;
    color: var(--primary);
}
.stat-label { color: var(--text-sub); font-weight: 600; }

.hero-img {
    flex-shrink: 0;
    width: 200px;
    height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0.5;
}
.hero-img img { width: 100%; height: 100%; object-fit: contain; }

@media (max-width: 768px) {
    .hero { padding: 16px; }
    .hero-card { padding: 22px 18px; }
    .hero-img { width: 70px; height: 70px; }
    .hero-content h1 { font-size: 1.3rem; }
}
        /* old hero styles removed — using .hero-card layout */

        /* ── PAGE WRAPPER ── */
        .page-wrapper { max-width: 1400px; margin: 0 auto; padding: 36px 32px 80px; display: flex; gap: 28px; }

        /* ── SIDEBAR ── */
        .sidebar { width: 260px; flex-shrink: 0; }
        .filter-section { background: var(--white); border-radius: var(--radius); padding: 20px; margin-bottom: 14px; border: 1px solid var(--border); box-shadow: var(--card-shadow); }
        .filter-title { font-size: 10.5px; font-weight: 700; color: var(--text-dim); text-transform: uppercase; letter-spacing: .08em; margin-bottom: 14px; }
        .filter-options { display: flex; flex-direction: column; gap: 10px; }
        .filter-option { display: flex; align-items: center; gap: 9px; font-size: 13px; color: var(--text-sub); cursor: pointer; font-weight: 500; transition: color .15s; }
        .filter-option:hover { color: var(--primary); }
        .filter-option input { width: 15px; height: 15px; accent-color: var(--primary); cursor: pointer; }
        .search-box { display: flex; align-items: center; gap: 8px; background: var(--bg); border: 1.5px solid var(--border); border-radius: var(--radius-sm); padding: 9px 13px; transition: border-color .2s, box-shadow .2s; }
        .search-box:focus-within { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-glow); background: var(--white); }
        .search-box svg { color: var(--text-dim); flex-shrink: 0; }
        .search-box input { border: none; outline: none; font-size: 13px; width: 100%; background: transparent; color: var(--text-main); font-family: 'DM Sans', sans-serif; }
        .chip-group { display: flex; flex-wrap: wrap; gap: 6px; }
        .chip { display: inline-flex; align-items: center; padding: 5px 12px; border-radius: 20px; border: 1.5px solid var(--border); background: var(--white); font-size: 12px; font-weight: 600; color: var(--text-sub); cursor: pointer; transition: all .15s; font-family: 'DM Sans', sans-serif; }
        .chip:hover { background: var(--primary-light); color: var(--primary); border-color: var(--primary-border); }
        .chip.active { background: var(--primary); border-color: var(--primary); color: #fff; font-weight: 700; }

        /* ── EVENTS SECTION ── */
        .events-section { flex: 1; min-width: 0; }
        .events-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
        .events-count { font-size: 12px; color: var(--text-sub); font-weight: 600; }
        .sort-controls { display: flex; gap: 8px; align-items: center; font-size: 12px; color: var(--text-sub); font-weight: 600; }
        .sort-controls select { padding: 7px 12px; border: 1.5px solid var(--border); border-radius: var(--radius-sm); font-size: 13px; background: var(--white); cursor: pointer; font-family: 'DM Sans', sans-serif; color: var(--text-main); outline: none; transition: border-color .2s; }
        .sort-controls select:focus { border-color: var(--primary); }

        /* ── EVENT GRID ── */
        .events-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; }

        /* ── EVENT CARD — matches screenshot style ── */
        .event-card {
            background: var(--white);
            border-radius: var(--radius);
            overflow: hidden;
            border: 1px solid var(--border);
            box-shadow: var(--card-shadow);
            transition: transform .22s, box-shadow .22s, border-color .22s;
            display: flex;
            flex-direction: column;
            position: relative;
        }
        .event-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: var(--primary);
            border-radius: var(--radius) var(--radius) 0 0;
        }
        /* Color top border by type */
        .event-card[data-type="formation"]::before { background: var(--primary); }
        .event-card[data-type="webinaire"]::before  { background: var(--warning); }
        .event-card[data-type="meetup"]::before     { background: #ec4899; }
        .event-card[data-type="atelier"]::before    { background: var(--success); }

        .event-card:hover { transform: translateY(-3px); box-shadow: var(--card-shadow-hover); border-color: var(--primary-border); }

        .event-image {
            height: 180px;
            background-size: cover;
            background-position: center;
            background-color: var(--bg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            overflow: hidden;
            flex-shrink: 0;
        }
        .event-image img { width: 100%; height: 100%; object-fit: cover; transition: transform .35s; }
        .event-card:hover .event-image img { transform: scale(1.04); }

        .event-info { padding: 18px 18px 16px; display: flex; flex-direction: column; flex: 1; }

        /* Type badge */
        .event-type { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; margin-bottom: 10px; letter-spacing: .02em; }
        .type-formation { background: var(--primary-light); color: var(--primary); }
        .type-webinaire { background: var(--warning-light); color: var(--warning); }
        .type-meetup    { background: #fce7f3; color: #ec4899; }
        .type-atelier   { background: var(--success-light); color: var(--success); }

        .event-title { font-family: 'Fraunces', serif; font-size: 15px; font-weight: 800; color: var(--text-main); line-height: 1.3; margin-bottom: 7px; letter-spacing: -0.2px; }
        .event-description { font-size: 12.5px; color: var(--text-sub); line-height: 1.6; margin-bottom: 12px; flex: 1; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

        /* Meta row */
        .event-meta { display: flex; gap: 12px; font-size: 11.5px; color: var(--text-sub); margin-bottom: 10px; flex-wrap: wrap; padding: 10px 0; border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); }

        /* Spots */
        .event-spots { font-size: 12px; font-weight: 700; margin-bottom: 14px; padding: 6px 10px; border-radius: var(--radius-sm); display: inline-flex; align-items: center; gap: 5px; }
        .event-spots.available { background: var(--success-light); color: var(--success); }
        .event-spots.full      { background: var(--danger-light);  color: var(--danger); }

        /* Buttons */
        .btn-event {
            display: inline-flex; align-items: center; justify-content: center; gap: 6px;
            width: 100%; padding: 10px 18px;
            background: var(--primary); color: #fff;
            border: none; border-radius: var(--radius-sm);
            font-size: 13px; font-weight: 700; font-family: 'DM Sans', sans-serif;
            cursor: pointer; transition: background .18s, transform .15s;
            margin-bottom: 8px; text-decoration: none;
            box-shadow: 0 2px 8px var(--primary-glow);
        }
        .btn-event:hover { background: var(--primary-hover); transform: translateY(-1px); }
        .btn-event.outline { background: transparent; color: var(--primary); border: 1.5px solid var(--primary); box-shadow: none; }
        .btn-event.outline:hover { background: var(--primary); color: #fff; }

        .btn-detail {
            display: inline-flex; align-items: center; justify-content: center; gap: 6px;
            width: 100%; padding: 9px 16px;
            background: transparent; color: var(--text-sub);
            border: 1.5px solid var(--border); border-radius: var(--radius-sm);
            font-size: 12.5px; font-weight: 600; font-family: 'DM Sans', sans-serif;
            cursor: pointer; transition: all .18s; margin-bottom: 8px;
        }
        .btn-detail:hover { border-color: var(--primary); color: var(--primary); background: var(--primary-light); }

        .btn-forum {
            display: inline-flex; align-items: center; justify-content: center; gap: 6px;
            width: 100%; padding: 9px 16px;
            background: var(--success-light); color: var(--success);
            border: 1px solid rgba(14,163,112,0.2); border-radius: var(--radius-sm);
            font-size: 12.5px; font-weight: 700; font-family: 'DM Sans', sans-serif;
            cursor: pointer; text-decoration: none; transition: all .18s;
        }
        .btn-forum:hover { background: var(--success); color: #fff; }

        /* ── MODALS ── */
        .inscription-modal, .detail-modal {
            display: none; position: fixed; top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(15,14,26,0.45);
            backdrop-filter: blur(6px);
            z-index: 1002; align-items: center; justify-content: center;
        }
        .inscription-modal.show, .detail-modal.show { display: flex; animation: fadeIn 0.25s ease; }
        @keyframes fadeIn  { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

        .inscription-card {
            background: var(--white); border-radius: 20px;
            width: 460px; max-width: 90%; overflow: hidden;
            animation: slideUp 0.25s ease;
            box-shadow: 0 20px 60px rgba(15,14,26,0.18);
            border: 1px solid var(--border);
        }
        .inscription-header { background: linear-gradient(135deg, var(--primary), #7c3aed); padding: 24px 26px 18px; text-align: center; color: white; }
        .inscription-header h3 { font-family: 'Fraunces', serif; font-size: 1.4rem; font-weight: 800; margin-bottom: 4px; }
        .inscription-header p { font-size: 13px; opacity: 0.88; }
        .inscription-body { padding: 24px 26px; }
        .inscription-form-group { margin-bottom: 15px; }
        .inscription-label { display: block; font-size: 12px; font-weight: 700; color: var(--text-main); margin-bottom: 7px; letter-spacing: .01em; }
        .inscription-input {
            width: 100%; padding: 10px 13px;
            border: 1.5px solid var(--border); border-radius: var(--radius-sm);
            font-size: 13.5px; font-family: 'DM Sans', sans-serif; color: var(--text-main);
            background: var(--bg); transition: border-color .2s, box-shadow .2s, background .2s; outline: none;
        }
        .inscription-input::placeholder { color: var(--text-dim); }
        .inscription-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-glow); background: var(--white); }
        .inscription-buttons { display: flex; gap: 10px; margin-top: 18px; }
        .btn-inscrire-modal { flex: 1; padding: 11px 20px; background: var(--primary); color: #fff; border: none; border-radius: var(--radius-sm); font-size: 13.5px; font-weight: 700; font-family: 'DM Sans', sans-serif; cursor: pointer; transition: background .18s, transform .15s; box-shadow: 0 2px 8px var(--primary-glow); }
        .btn-inscrire-modal:hover { background: var(--primary-hover); transform: translateY(-1px); }
        .btn-annuler-modal { flex: 1; padding: 10px 18px; background: transparent; color: var(--text-sub); border: 1.5px solid var(--border); border-radius: var(--radius-sm); font-size: 13.5px; font-weight: 600; font-family: 'DM Sans', sans-serif; cursor: pointer; transition: all .18s; }
        .btn-annuler-modal:hover { border-color: var(--text-sub); color: var(--text-main); background: var(--bg); }

        .detail-modal-content {
            background: var(--white); border-radius: 20px;
            width: 860px; max-width: 90%; max-height: 85vh;
            overflow-y: auto; position: relative;
            animation: slideUp 0.25s ease;
            border: 1px solid var(--border);
            box-shadow: 0 20px 60px rgba(15,14,26,0.18);
        }
        .detail-modal-close { position: absolute; top: 13px; right: 15px; background: var(--bg); border: 1px solid var(--border); font-size: 18px; cursor: pointer; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: all .18s; z-index: 10; color: var(--text-sub); }
        .detail-modal-close:hover { background: var(--danger-light); color: var(--danger); border-color: rgba(244,63,94,0.2); }
        .detail-modal-body { display: flex; }
        .detail-image { flex: 1; min-height: 340px; background: var(--bg); display: flex; align-items: center; justify-content: center; border-radius: 20px 0 0 20px; overflow: hidden; font-size: 3rem; color: #c0bde0; }
        .detail-image img { width: 100%; height: 100%; object-fit: cover; }
        .detail-info { flex: 1; padding: 28px; }
        .detail-info h2 { font-family: 'Fraunces', serif; font-size: 1.6rem; font-weight: 800; margin-bottom: 10px; color: var(--text-main); letter-spacing: -0.4px; }
        .detail-info p { color: var(--text-sub); line-height: 1.7; margin-bottom: 18px; font-size: 13.5px; }
        .detail-meta { margin: 16px 0; padding: 14px 0; border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); display: flex; flex-direction: column; gap: 10px; }
        .detail-meta span { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--text-sub); }
        .detail-map { margin-top: 16px; border-radius: var(--radius-sm); overflow: hidden; }
        .detail-map iframe { width: 100%; height: 200px; border: 0; border-radius: var(--radius-sm); }
        .detail-actions { margin-top: 20px; }
        .btn-inscription-detail { display: inline-flex; align-items: center; justify-content: center; gap: 6px; width: 100%; padding: 11px 20px; background: var(--primary); color: #fff; border: none; border-radius: var(--radius-sm); font-size: 14px; font-weight: 700; font-family: 'DM Sans', sans-serif; cursor: pointer; transition: background .18s, transform .15s; box-shadow: 0 2px 10px var(--primary-glow); }
        .btn-inscription-detail:hover { background: var(--primary-hover); transform: translateY(-1px); }

        /* ── TOAST ── */
        .toast { position: fixed; bottom: 28px; right: 28px; background: var(--success); color: white; padding: 12px 22px; border-radius: 20px; display: none; z-index: 1003; font-weight: 600; font-size: 13.5px; box-shadow: 0 4px 14px rgba(0,0,0,0.13); }
        .toast.error { background: var(--danger); }

        /* ── RESPONSIVE ── */
        @media (max-width: 1024px) { .page-wrapper { flex-direction: column; } .sidebar { width: 100%; display: flex; gap: 14px; overflow-x: auto; } .filter-section { min-width: 230px; margin-bottom: 0; } }
        @media (max-width: 768px) { nav { padding: 0 20px; } .nav-links { display: none; } .hero { padding: 16px; } .hero-card { padding: 22px 18px; } .hero-content h1 { font-size: 1.3rem; } .hero-img { width: 70px; height: 70px; } .page-wrapper { padding: 20px 16px; } .events-grid { grid-template-columns: 1fr; } .detail-modal-body { flex-direction: column; } .detail-image { border-radius: 20px 20px 0 0; min-height: 200px; } .stats-bar { gap: 10px; } }
    </style>
</head>
<body>

<nav>
    <a href="#" class="nav-logo">
        <img src="<?= $BASE ?>/Vue/public/images/logo.png" alt="Cre8Connect">
        <span class="nav-logo-text">Cre8Connect</span>
    </a>
    <ul class="nav-links">
        <li><a href="#" class="active" data-i18n="events">Événements</a></li>
        <li><a href="<?= $BASE ?>/Controleur/forumC.php" data-i18n="forum">Forums</a></li>
    </ul>
    <div class="nav-right">
        <button id="languageToggle" class="lang-toggle-btn" onclick="toggleLanguage()">🇫🇷 FR</button>
        <button id="themeToggle" class="theme-toggle-btn">◑ Dark mode</button>
        <div class="nav-avatar">👤</div>
    </div>
</nav>

<section class="hero">
    <div class="hero-card">
        <div class="hero-content">
            <h1 data-i18n="discover_events">Découvrez les événements de la communauté</h1>
            <p data-i18n="events_desc">Formations, webinaires, meetups et ateliers pour les créateurs et les marques.</p>
    <div class="stats-bar">
      <div class="stat-item">
        <span class="stat-num"><?= count($evenements) ?></span>
        <span class="stat-label" data-i18n="events_available">Événements disponibles</span>
    </div>
    <div class="stat-item">
        <span class="stat-num"><?= array_sum(array_map(function($e) { return $e->getNbInscrits(); }, $evenements)) ?></span>
        <span class="stat-label" data-i18n="participants">Participants</span>
    </div>
</div>
        </div>
        <div class="hero-img">
            <img src="<?= $BASE ?>/Vue/public/images/Robertas_Pizza-removebg-preview.png" alt="hero" onerror="this.style.display='none';this.nextElementSibling.style.display='block'">
            <span style="display:none;font-size:5rem;opacity:0.4;">🛍️</span>
        </div>
    </div>
</section>

<div class="page-wrapper">
    <aside class="sidebar">
        <div class="filter-section">
            <div class="filter-title" data-i18n="search">🔍 RECHERCHE</div>
            <div class="search-box"><svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg><input type="text" id="searchInput" data-i18n-placeholder="search" placeholder="Rechercher un événement..." onkeyup="filterEvents()"></div>
        </div>
        <div class="filter-section">
            <div class="filter-title" data-i18n="type">📂 TYPE</div>
            <div class="filter-options">
                <label class="filter-option"><input type="checkbox" value="formation" class="type-filter"> <span data-i18n="formation">Formation</span></label>
                <label class="filter-option"><input type="checkbox" value="webinaire" class="type-filter"> <span data-i18n="webinaire">Webinaire</span></label>
                <label class="filter-option"><input type="checkbox" value="meetup" class="type-filter"> <span data-i18n="meetup">Meetup</span></label>
                <label class="filter-option"><input type="checkbox" value="atelier" class="type-filter"> <span data-i18n="atelier">Atelier</span></label>
            </div>
        </div>
        <div class="filter-section">
            <div class="filter-title" data-i18n="location">📍 LIEU</div>
            <div class="chip-group"><button class="chip active" onclick="filterChip(this, '')" data-i18n="all">Tous</button><button class="chip" onclick="filterChip(this, 'en_ligne')" data-i18n="online">En ligne</button><button class="chip" onclick="filterChip(this, 'presentiel')" data-i18n="in_person">Présentiel</button></div>
        </div>
    </aside>

    <div class="events-section">
        <div class="events-header">
            <div class="events-count" id="eventsCount"><?= count($evenements) ?> événements</div>
            <div class="sort-controls"><span data-i18n="sort_by">TRIER PAR</span><select id="sortSelect" data-i18n="__select__" onchange="sortEvents()"><option value="date" data-i18n-value="date_recent">Date (plus récent)</option><option value="date_asc" data-i18n-value="date_oldest">Date (plus ancien)</option><option value="places" data-i18n-value="spots_available">Places disponibles</option></select></div>
        </div>

        <div class="events-grid" id="eventsGrid">
            <?php foreach ($evenements as $event): 
                $spotsLeft = $event->getCapacite() - $event->getNbInscrits();
                $isFull = ($spotsLeft <= 0);
                $isOnline = strpos(strtolower($event->getLieu()), 'en ligne') !== false || empty($event->getLieu());
                $forumLink = '';
                if (isset($forumsData[$event->getId()])) {
                    $forumLink = '<a href="<?= $BASE ?>/Controleur/forumC.php?action=voir&id=' . $forumsData[$event->getId()]['idForum'] . '" class="btn-forum" data-i18n="access_forum">💬 Accéder au forum</a>';
                }
            ?>
            <div class="event-card" data-type="<?= $event->getType() ?>" data-lieu="<?= $isOnline ? 'en_ligne' : 'presentiel' ?>" data-title="<?= strtolower(htmlspecialchars($event->getTitre())) ?>" data-date="<?= strtotime($event->getDateEvenement()) ?>" data-places="<?= $spotsLeft ?>">
                <div class="event-image" style="<?= $event->getImage() ? 'background-image: url(<?= $BASE ?>/' . $event->getImage() . '); background-size: cover;' : '' ?>"><?php if (!$event->getImage()): ?><span>🎯</span><?php endif; ?></div>
                <div class="event-info">
                    <span class="event-type type-<?= $event->getType() ?>"><?= ucfirst($event->getType()) ?></span>
                    <div class="event-title"><?= htmlspecialchars($event->getTitre()) ?></div>
                    <div class="event-description"><?= htmlspecialchars(substr($event->getDescription(), 0, 100)) ?>...</div>
                    <div class="event-meta"><span>📅 <?= date('d M Y', strtotime($event->getDateEvenement())) ?></span><span>📍 <?= htmlspecialchars($event->getLieu() ?: 'En ligne') ?></span></div>
                    <div class="event-spots <?= $isFull ? 'full' : 'available' ?>">
                        <?php if ($isFull): ?>
                            ❌ <span data-i18n="full">Complet</span>
                        <?php else: ?>
                            ✅ <?= $spotsLeft ?> <span data-i18n="spots_left">places restantes</span>
                        <?php endif; ?>
                    </div>
                    <button onclick="ouvrirModalInscription(<?= $event->getId() ?>)" class="btn-event <?= $isFull ? 'outline' : '' ?>" data-i18n="<?= $isFull ? 'waiting_list' : 'register' ?>"><?= $isFull ? '📋 Liste d\'attente' : '✨ S\'inscrire' ?></button>
                    <button onclick="voirDetail(<?= $event->getId() ?>)" class="btn-detail" data-i18n="view_details">👁 Voir les détails</button>
                    <?= $forumLink ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div id="inscriptionModal" class="inscription-modal">
    <div class="inscription-card">
        <div class="inscription-header">
            <h3 data-i18n="registration">✨ Inscription</h3>
            <p data-i18n="join_event">Rejoignez cet événement exceptionnel</p>
        </div>
        <div class="inscription-body">
            <div class="inscription-form-group">
                <label class="inscription-label" data-i18n="full_name">Nom complet</label>
                <input type="text" id="inscrireNom" class="inscription-input" data-i18n-placeholder="full_name" placeholder="Votre nom et prénom">
            </div>
            <div class="inscription-form-group">
                <label class="inscription-label" data-i18n="email_address">Adresse email</label>
                <input type="email" id="inscrireEmail" class="inscription-input" data-i18n-placeholder="email_address" placeholder="votre@email.com">
            </div>
            <div class="inscription-buttons">
                <button class="btn-annuler-modal" onclick="fermerModalInscription()" data-i18n="cancel">Annuler</button>
                <button class="btn-inscrire-modal" onclick="confirmerInscription()" data-i18n="confirm_registration">Confirmer l'inscription</button>
            </div>
        </div>
    </div>
</div>

<div id="detailModal" class="detail-modal">
    <div class="detail-modal-content">
        <button class="detail-modal-close" onclick="fermerDetailModal()">&times;</button>
        <div class="detail-modal-body">
            <div class="detail-image" id="detailImage"></div>
            <div class="detail-info">
                <span class="event-type" id="detailType"></span>
                <h2 id="detailTitre"></h2>
                <p id="detailDescription"></p>
                <div class="detail-meta">
                    <span id="detailDate"></span>
                    <span id="detailLieu"></span>
                    <span id="detailPlaces"></span>
                </div>
                <div id="detailMap" class="detail-map"></div>
                <div class="detail-actions">
                    <button id="detailInscrireBtn" class="btn-inscription-detail" data-i18n="register_now">✨ S'inscrire maintenant</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="toast" class="toast"></div>

<script>
    let currentEventId = null;

    function showToast(message, isError = false) {
        const toast = document.getElementById('toast');
        toast.textContent = message;
        toast.className = 'toast' + (isError ? ' error' : '');
        toast.style.display = 'block';
        setTimeout(() => { toast.style.display = 'none'; }, 3000);
    }

    function ouvrirModalInscription(eventId) {
        currentEventId = eventId;
        document.getElementById('inscrireNom').value = '';
        document.getElementById('inscrireEmail').value = '';
        document.getElementById('inscriptionModal').classList.add('show');
    }

    function fermerModalInscription() {
        document.getElementById('inscriptionModal').classList.remove('show');
    }

    function confirmerInscription() {
        const nom = document.getElementById('inscrireNom').value.trim();
        const email = document.getElementById('inscrireEmail').value.trim();
        
        if (!nom || !email) {
            showToast(translations[currentLang]['fill_fields'], true);
            return;
        }
        if (!email.includes('@')) {
            showToast(translations[currentLang]['invalid_email'], true);
            return;
        }
        
        fermerModalInscription();
        showToast(translations[currentLang]['registration_in_progress'], false);
        
        const formData = new FormData();
        formData.append('nom', nom);
        formData.append('email', email);
        
        fetch('<?= $BASE ?>/Controleur/evenementC.php?action=inscrire&id=' + currentEventId, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            showToast(data.message, !data.success);
            if (data.success) setTimeout(() => location.reload(), 2000);
        })
        .catch(() => showToast(translations[currentLang]['connection_error'], true));
    }

    function voirDetail(eventId) {
        fetch('<?= $BASE ?>/Controleur/evenementC.php?action=get&id=' + eventId)
            .then(response => response.json())
            .then(data => {
                if (data.error) return;
                document.getElementById('detailTitre').innerHTML = data.titre;
                document.getElementById('detailType').innerHTML = data.type;
                document.getElementById('detailType').className = 'event-type type-' + data.type;
                document.getElementById('detailDescription').innerHTML = data.description;
                document.getElementById('detailDate').innerHTML = '📅 ' + new Date(data.date_evenement).toLocaleDateString('fr-FR', {day:'numeric', month:'long', year:'numeric'});
                document.getElementById('detailLieu').innerHTML = '📍 ' + (data.lieu || 'En ligne');
                const placesRestantes = data.capacite - data.nb_inscrits;
                const placesText = placesRestantes > 0 ? placesRestantes + ' ' + translations[currentLang]['spots_remaining'] : translations[currentLang]['complete'];
                document.getElementById('detailPlaces').innerHTML = placesRestantes > 0 ? '✅ ' + placesRestantes + ' ' + translations[currentLang]['spots_remaining'] : '❌ ' + translations[currentLang]['complete'];
                document.getElementById('detailImage').innerHTML = data.image ? '<img src="<?= $BASE ?>/' + data.image + '">' : '<span style="font-size:3rem;">🎯</span>';
                
                if (data.adresse_complete) {
                    const encodedAddress = encodeURIComponent(data.adresse_complete);
                    document.getElementById('detailMap').innerHTML = `<iframe src="https://maps.google.com/maps?q=${encodedAddress}&output=embed"></iframe><p style="margin-top:8px; font-size:0.8rem;">📍 ${data.adresse_complete}</p>`;
                } else {
                    document.getElementById('detailMap').innerHTML = '';
                }
                
                const btn = document.getElementById('detailInscrireBtn');
                btn.onclick = function() { ouvrirModalInscription(data.id); };
                btn.innerHTML = placesRestantes <= 0 ? '📋 ' + translations[currentLang]['waiting_list'] : '✨ ' + translations[currentLang]['register_now'];
                document.getElementById('detailModal').classList.add('show');
            })
            .catch(() => showToast(translations[currentLang]['loading_error'], true));
    }

    function fermerDetailModal() {
        document.getElementById('detailModal').classList.remove('show');
    }

    function filterEvents() {
        const searchTerm = document.getElementById('searchInput').value.toLowerCase();
        const checkboxes = document.querySelectorAll('.type-filter:checked');
        const selectedTypes = Array.from(checkboxes).map(cb => cb.value);
        const activeChip = document.querySelector('.chip.active');
        const lieuFilter = activeChip ? activeChip.textContent.toLowerCase() : '';
        
        const cards = document.querySelectorAll('.event-card');
        let visibleCount = 0;
        
        cards.forEach(card => {
            let show = true;
            const title = card.getAttribute('data-title');
            const type = card.getAttribute('data-type');
            const lieu = card.getAttribute('data-lieu');
            if (searchTerm && !title.includes(searchTerm)) show = false;
            if (selectedTypes.length > 0 && !selectedTypes.includes(type)) show = false;
            if (lieuFilter === 'en ligne' && lieu !== 'en_ligne') show = false;
            if (lieuFilter === 'présentiel' && lieu !== 'presentiel') show = false;
            card.style.display = show ? '' : 'none';
            if (show) visibleCount++;
        });
        document.getElementById('eventsCount').textContent = visibleCount + ' ' + (visibleCount > 1 ? 'événements' : 'événement');
    }
    
    function filterChip(btn, filter) {
        document.querySelectorAll('.chip').forEach(chip => chip.classList.remove('active'));
        btn.classList.add('active');
        filterEvents();
    }
    
    function sortEvents() {
        const sortBy = document.getElementById('sortSelect').value;
        const grid = document.getElementById('eventsGrid');
        const cards = Array.from(grid.querySelectorAll('.event-card'));
        cards.sort((a, b) => {
            if (sortBy === 'date') return parseInt(b.getAttribute('data-date')) - parseInt(a.getAttribute('data-date'));
            if (sortBy === 'date_asc') return parseInt(a.getAttribute('data-date')) - parseInt(b.getAttribute('data-date'));
            if (sortBy === 'places') return parseInt(b.getAttribute('data-places')) - parseInt(a.getAttribute('data-places'));
            return 0;
        });
        cards.forEach(card => grid.appendChild(card));
    }
    
    document.querySelectorAll('.type-filter').forEach(cb => cb.addEventListener('change', filterEvents));
    document.getElementById('inscriptionModal').addEventListener('click', function(e) { if (e.target === this) fermerModalInscription(); });
    document.getElementById('detailModal').addEventListener('click', function(e) { if (e.target === this) fermerDetailModal(); });

    function initTheme() {
        const saved = localStorage.getItem('theme');
        if (saved === 'dark') {
            document.body.classList.add('dark-mode');
            document.getElementById('themeToggle').textContent = '◑ Light mode';
        } else {
            document.body.classList.remove('dark-mode');
            document.getElementById('themeToggle').textContent = '◑ Dark mode';
        }
    }
    function toggleTheme() {
        if (document.body.classList.contains('dark-mode')) {
            document.body.classList.remove('dark-mode');
            localStorage.setItem('theme', 'light');
            document.getElementById('themeToggle').textContent = '◑ Dark mode';
        } else {
            document.body.classList.add('dark-mode');
            localStorage.setItem('theme', 'dark');
            document.getElementById('themeToggle').textContent = '◑ Light mode';
        }
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        initTheme();
        document.getElementById('themeToggle').addEventListener('click', toggleTheme);
        initLanguage();
    });
</script>
</body>
</html>