<?php
require_once '../../../Controleur/produitC.php';
require_once '../../../Modele/produit.php';

$produitC = new ProduitC();
$message = '';
$messageType = '';

// ✅ BASE URL
$baseUrl = '/projet/Esprit-PW-2A22-2526-Devcore';

// DELETE
if (isset($_GET['delete'])) {
    $produitC->supprimerProduit($_GET['delete']);
    header('Location: index.php?deleted=1');
    exit;
}

// ADD
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $nomImage = $produitC->gererUploadImage($_FILES['image'] ?? null);
    $produit = new Produit(
        null,
        trim($_POST['nom']),
        trim($_POST['description']),
        trim($_POST['caracteristiques']),
        floatval($_POST['prix']),
        1,
        $nomImage
    );
    $produitC->ajouterProduit($produit);
    header('Location: index.php?added=1');
    exit;
}

// UPDATE
if (isset($_POST['action']) && $_POST['action'] === 'update') {
    $ancienProduit = $produitC->recupererProduit(intval($_POST['id']));
    $nomImage = $produitC->gererUploadImage($_FILES['image'] ?? null, $ancienProduit['image'] ?? null);
    $produit = new Produit(
        null,
        trim($_POST['nom']),
        trim($_POST['description']),
        trim($_POST['caracteristiques']),
        floatval($_POST['prix']),
        null,
        $nomImage
    );
    $produitC->modifierProduit($produit, intval($_POST['id']));
    header('Location: index.php?updated=1');
    exit;
}

if (isset($_GET['added']))   { $message = "Produit ajouté avec succès.";    $messageType = "success"; }
if (isset($_GET['updated'])) { $message = "Produit mis à jour avec succès."; $messageType = "info"; }
if (isset($_GET['deleted'])) { $message = "Produit supprimé avec succès.";   $messageType = "danger"; }

$liste = $produitC->afficherProduits();
$produitUpdate = null;
if (isset($_GET['edit'])) {
    $produitUpdate = $produitC->recupererProduit(intval($_GET['edit']));
}

$totalProduits = count($liste);
$prixMoyen = $totalProduits > 0 ? array_sum(array_column($liste, 'prix')) / $totalProduits : 0;
$prixMax = $totalProduits > 0 ? max(array_column($liste, 'prix')) : 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — Gestion Produits | Cre8Connect</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg-base:      #0f1117;
            --bg-surface:   #161b27;
            --bg-card:      #1c2235;
            --bg-card-alt:  #212840;
            --bg-input:     #111827;
            --border:       rgba(255,255,255,0.07);
            --border-focus: rgba(139,92,246,0.5);
            --text-primary: #e2e8f0;
            --text-muted:   #7c8ba1;
            --text-dim:     #4a5568;
            --accent:       #8b5cf6;
            --accent-soft:  rgba(139,92,246,0.12);
            --accent-hover: #7c3aed;
            --success:      #10b981;
            --success-soft: rgba(16,185,129,0.12);
            --danger:       #ef4444;
            --danger-soft:  rgba(239,68,68,0.10);
            --warning:      #f59e0b;
            --warning-soft: rgba(245,158,11,0.12);
            --info:         #3b82f6;
            --info-soft:    rgba(59,130,246,0.12);
            --radius-sm:    6px;
            --radius-md:    10px;
            --radius-lg:    14px;
            --sidebar-w:    240px;
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--bg-base);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            font-size: 14px;
            line-height: 1.5;
        }

        /* ── SIDEBAR ── */
        .sidebar {
            width: var(--sidebar-w);
            background: var(--bg-surface);
            border-right: 1px solid var(--border);
            display: flex; flex-direction: column;
            position: fixed; top: 0; left: 0; bottom: 0; z-index: 100;
        }
        .sidebar-logo {
            padding: 18px 20px;
            border-bottom: 1px solid var(--border);
        }
        .sidebar-logo .brand {
            display: flex; align-items: center; gap: 10px; text-decoration: none;
        }

        /* ✅ LOGO IMAGE dans la sidebar */
        .logo-img {
            width: 34px; height: 34px;
            object-fit: contain;
            border-radius: var(--radius-sm);
            flex-shrink: 0;
        }

        .logo-text { font-size: 15px; font-weight: 700; color: var(--text-primary); }
        .logo-badge {
            font-size: 9px; font-weight: 600; letter-spacing: .05em;
            color: var(--accent); background: var(--accent-soft);
            padding: 2px 6px; border-radius: 20px; margin-top: 1px;
        }
        .sidebar-nav { flex: 1; padding: 12px 10px; overflow-y: auto; }
        .nav-section-label {
            font-size: 10px; font-weight: 600; letter-spacing: .08em;
            text-transform: uppercase; color: var(--text-dim);
            padding: 10px 10px 6px;
        }
        .nav-item {
            display: flex; align-items: center; gap: 10px;
            padding: 8px 10px; border-radius: var(--radius-sm);
            color: var(--text-muted); text-decoration: none;
            font-size: 13px; font-weight: 450;
            transition: background .15s, color .15s;
            cursor: pointer; margin-bottom: 2px;
        }
        .nav-item:hover { background: rgba(255,255,255,0.04); color: var(--text-primary); }
        .nav-item.active { background: var(--accent-soft); color: var(--accent); font-weight: 500; }
        .nav-icon { width: 18px; height: 18px; opacity: .8; flex-shrink: 0; }
        .sidebar-footer { padding: 12px; border-top: 1px solid var(--border); }
        .admin-card {
            display: flex; align-items: center; gap: 10px;
            padding: 8px 10px; border-radius: var(--radius-sm);
            background: var(--bg-card);
        }
        .admin-avatar {
            width: 30px; height: 30px; border-radius: 50%;
            background: var(--accent);
            display: flex; align-items: center; justify-content: center;
            font-size: 12px; font-weight: 600; color: #fff; flex-shrink: 0;
        }
        .admin-name { font-size: 12px; font-weight: 500; }
        .admin-role { font-size: 11px; color: var(--text-muted); }

        /* ── TOPBAR ── */
        .topbar {
            position: fixed; top: 0;
            left: var(--sidebar-w); right: 0; height: 58px;
            background: var(--bg-surface); border-bottom: 1px solid var(--border);
            display: flex; align-items: center; padding: 0 28px; gap: 16px; z-index: 90;
        }
        .topbar-breadcrumb {
            display: flex; align-items: center; gap: 8px;
            font-size: 13px; color: var(--text-muted);
        }
        .topbar-breadcrumb .sep { opacity: .4; }
        .topbar-breadcrumb .current { color: var(--text-primary); font-weight: 500; }
        .topbar-actions { margin-left: auto; display: flex; align-items: center; gap: 12px; }
        .btn-add {
            display: flex; align-items: center; gap: 7px;
            background: var(--accent); color: #fff;
            border: none; padding: 7px 14px; border-radius: var(--radius-sm);
            font-size: 13px; font-weight: 500; cursor: pointer;
            text-decoration: none; transition: background .15s;
        }
        .btn-add:hover { background: var(--accent-hover); }
        .search-wrap { position: relative; }
        .search-input {
            background: var(--bg-input); border: 1px solid var(--border);
            border-radius: var(--radius-sm); padding: 7px 12px 7px 34px;
            color: var(--text-primary); font-size: 13px; width: 220px;
            transition: border-color .2s;
        }
        .search-input::placeholder { color: var(--text-dim); }
        .search-input:focus { outline: none; border-color: var(--border-focus); }
        .search-icon {
            position: absolute; left: 10px; top: 50%; transform: translateY(-50%);
            color: var(--text-dim); pointer-events: none; width: 15px; height: 15px;
        }

        /* ── MAIN ── */
        .main { margin-left: var(--sidebar-w); padding-top: 58px; flex: 1; min-height: 100vh; }
        .content { padding: 28px 28px 48px; }

        .page-header {
            display: flex; align-items: flex-start; justify-content: space-between;
            margin-bottom: 24px; flex-wrap: wrap; gap: 16px;
        }
        .page-title { font-size: 20px; font-weight: 600; color: var(--text-primary); }
        .page-subtitle { font-size: 13px; color: var(--text-muted); margin-top: 3px; }

        .alert {
            display: flex; align-items: center; gap: 10px;
            padding: 11px 16px; border-radius: var(--radius-md);
            font-size: 13px; margin-bottom: 20px; font-weight: 450;
        }
        .alert-success { background: var(--success-soft); color: var(--success); border: 1px solid rgba(16,185,129,.2); }
        .alert-info    { background: var(--info-soft);    color: var(--info);    border: 1px solid rgba(59,130,246,.2); }
        .alert-danger  { background: var(--danger-soft);  color: var(--danger);  border: 1px solid rgba(239,68,68,.2); }

        .kpi-strip { display: grid; grid-template-columns: repeat(3,1fr); gap: 14px; margin-bottom: 24px; }
        .kpi-card {
            background: var(--bg-card); border: 1px solid var(--border);
            border-radius: var(--radius-lg); padding: 16px 18px;
            display: flex; flex-direction: column; gap: 4px;
        }
        .kpi-label { font-size: 11px; font-weight: 500; text-transform: uppercase; letter-spacing: .06em; color: var(--text-muted); }
        .kpi-value { font-size: 24px; font-weight: 600; color: var(--text-primary); }
        .kpi-badge {
            display: inline-flex; align-items: center;
            font-size: 11px; font-weight: 500;
            padding: 3px 8px; border-radius: 20px; margin-top: 2px; width: fit-content;
        }

        .form-panel {
            background: var(--bg-card); border: 1px solid var(--border);
            border-radius: var(--radius-lg); margin-bottom: 24px; overflow: hidden;
        }
        .form-panel-header {
            display: flex; align-items: center; gap: 10px;
            padding: 14px 20px; border-bottom: 1px solid var(--border);
        }
        .form-panel-title { font-size: 14px; font-weight: 600; }
        .form-panel-badge { font-size: 11px; font-weight: 500; padding: 2px 8px; border-radius: 20px; }
        .badge-add  { background: var(--success-soft); color: var(--success); }
        .badge-edit { background: var(--warning-soft); color: var(--warning); }
        .form-body { padding: 20px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .form-col-full { grid-column: 1 / -1; }
        .form-group { display: flex; flex-direction: column; gap: 5px; }
        .form-label { font-size: 12px; font-weight: 500; color: var(--text-muted); }
        .form-control {
            background: var(--bg-input); border: 1px solid var(--border);
            border-radius: var(--radius-sm); padding: 9px 12px;
            color: var(--text-primary); font-size: 13px; font-family: inherit;
            transition: border-color .2s; width: 100%;
        }
        .form-control::placeholder { color: var(--text-dim); }
        .form-control:focus { outline: none; border-color: var(--border-focus); }
        textarea.form-control { resize: vertical; min-height: 80px; }

        .upload-admin {
            border: 1.5px dashed rgba(139,92,246,0.3);
            border-radius: var(--radius-sm);
            background: var(--bg-input);
            padding: 16px; cursor: pointer;
            transition: border-color .2s, background .2s;
            position: relative; text-align: center;
        }
        .upload-admin:hover { border-color: var(--accent); background: var(--accent-soft); }
        .upload-admin input[type="file"] {
            position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%;
        }
        .upload-admin-text { font-size: 12px; color: var(--text-muted); }
        .upload-admin-text strong { color: var(--accent); }
        .upload-admin-hint { font-size: 11px; color: var(--text-dim); margin-top: 4px; display: block; }
        .file-chosen { font-size: 12px; color: var(--accent); margin-top: 6px; display: none; }

        .current-image-preview {
            display: flex; align-items: center; gap: 12px;
            padding: 8px 10px;
            background: rgba(139,92,246,0.08);
            border: 1px solid rgba(139,92,246,0.2);
            border-radius: var(--radius-sm); margin-bottom: 8px;
        }
        .current-image-preview img {
            width: 48px; height: 48px; object-fit: cover;
            border-radius: var(--radius-sm); border: 1px solid var(--border);
        }
        .current-image-preview span { font-size: 12px; color: var(--text-muted); }

        .form-actions { display: flex; gap: 10px; margin-top: 6px; }
        .btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 9px 18px; border-radius: var(--radius-sm);
            font-size: 13px; font-weight: 500; cursor: pointer;
            border: none; text-decoration: none; transition: .15s;
        }
        .btn-primary { background: var(--accent); color: #fff; }
        .btn-primary:hover { background: var(--accent-hover); }
        .btn-ghost {
            background: transparent; color: var(--text-muted); border: 1px solid var(--border);
        }
        .btn-ghost:hover { background: rgba(255,255,255,.04); color: var(--text-primary); }

        .table-panel {
            background: var(--bg-card); border: 1px solid var(--border);
            border-radius: var(--radius-lg); overflow: hidden;
        }
        .table-panel-header {
            display: flex; align-items: center;
            padding: 14px 20px; border-bottom: 1px solid var(--border); gap: 12px;
        }
        .table-panel-title { font-size: 14px; font-weight: 600; flex: 1; }
        .count-badge {
            font-size: 11px; font-weight: 600;
            background: var(--accent-soft); color: var(--accent);
            padding: 2px 9px; border-radius: 20px;
        }
        table { width: 100%; border-collapse: collapse; }
        thead th {
            background: var(--bg-card-alt); padding: 10px 18px; text-align: left;
            font-size: 11px; font-weight: 600; text-transform: uppercase;
            letter-spacing: .06em; color: var(--text-muted); border-bottom: 1px solid var(--border);
        }
        tbody tr { border-bottom: 1px solid var(--border); transition: background .12s; }
        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background: rgba(255,255,255,.025); }
        tbody td { padding: 12px 18px; font-size: 13px; color: var(--text-primary); vertical-align: middle; }
        .td-id { color: var(--text-dim); font-size: 12px; font-family: monospace; }
        .td-name { font-weight: 500; }
        .td-desc { color: var(--text-muted); max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .td-carac { color: var(--text-dim); font-size: 12px; max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .prix-badge {
            display: inline-flex; align-items: center;
            background: var(--success-soft); color: var(--success);
            padding: 3px 9px; border-radius: 20px; font-size: 12px; font-weight: 600;
        }
        .td-img img {
            width: 44px; height: 44px; object-fit: cover;
            border-radius: var(--radius-sm); border: 1px solid var(--border);
        }
        .td-img-empty {
            width: 44px; height: 44px; background: var(--bg-card-alt);
            border: 1px dashed var(--border); border-radius: var(--radius-sm);
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; color: var(--text-dim);
        }
        .action-group { display: flex; align-items: center; gap: 6px; }
        .btn-action {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 5px 10px; border-radius: var(--radius-sm);
            font-size: 12px; font-weight: 500; cursor: pointer;
            text-decoration: none; border: 1px solid transparent; transition: .15s;
        }
        .btn-edit { background: var(--info-soft); color: var(--info); border-color: rgba(59,130,246,.2); }
        .btn-edit:hover { background: rgba(59,130,246,.2); }
        .btn-delete { background: var(--danger-soft); color: var(--danger); border-color: rgba(239,68,68,.2); }
        .btn-delete:hover { background: rgba(239,68,68,.18); }

        .empty-state { text-align: center; padding: 52px 20px; color: var(--text-muted); }
        .empty-icon { font-size: 36px; margin-bottom: 12px; opacity: .4; }
        .empty-text { font-size: 14px; font-weight: 500; margin-bottom: 6px; }
        .empty-hint { font-size: 13px; color: var(--text-dim); }

        @media (max-width: 900px) {
            .kpi-strip { grid-template-columns: 1fr 1fr; }
            .form-grid  { grid-template-columns: 1fr; }
            .sidebar { display: none; }
            .topbar, .main { left: 0; margin-left: 0; }
        }

        .modal-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,.6); z-index: 200;
            align-items: center; justify-content: center;
        }
        .modal-overlay.open { display: flex; }
        .modal-box {
            background: var(--bg-card); border: 1px solid var(--border);
            border-radius: var(--radius-lg); padding: 28px 28px 22px;
            width: 380px; max-width: 92%;
        }
        .modal-title { font-size: 16px; font-weight: 600; margin-bottom: 8px; }
        .modal-text  { font-size: 13px; color: var(--text-muted); margin-bottom: 22px; }
        .modal-actions { display: flex; gap: 10px; justify-content: flex-end; }
    </style>
</head>
<body>

<!-- ════════════ SIDEBAR ════════════ -->
<aside class="sidebar">
    <div class="sidebar-logo">
        <a href="#" class="brand">
            <!-- ✅ LOGO IMAGE intégré -->
            <img src="<?= $baseUrl ?>/Vue/public/images/logo.png"
                 alt="Cre8Connect Logo"
                 class="logo-img">
            <div>
                <div class="logo-text">Cre8Connect</div>
                <div class="logo-badge">ADMIN</div>
            </div>
        </a>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-label">Tableau de bord</div>
        <a class="nav-item" href="#">
            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            Accueil
        </a>
        <a class="nav-item" href="#">
            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Utilisateurs
        </a>
        <a class="nav-item" href="#">
            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            Réclamations
        </a>
        <div class="nav-section-label" style="margin-top:8px">Modules</div>
        <a class="nav-item" href="#">
            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
            </svg>
            Offres & Candidatures
        </a>
        <a class="nav-item" href="#">
            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            Évènements & Forums
        </a>
        <a class="nav-item" href="#">
            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
            Campagnes & Contrats
        </a>
        <a class="nav-item active" href="index.php">
            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 10V11"/>
            </svg>
            Produits
        </a>
        <a class="nav-item" href="#">
            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
            </svg>
            Posts & Commentaires
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="admin-card">
            <div class="admin-avatar">A</div>
            <div>
                <div class="admin-name">Administrateur</div>
                <div class="admin-role">Super Admin</div>
            </div>
        </div>
    </div>
</aside>

<!-- ════════════ TOPBAR ════════════ -->
<div class="topbar">
    <div class="topbar-breadcrumb">
        <span>Cre8Connect</span>
        <span class="sep">/</span>
        <span>Admin</span>
        <span class="sep">/</span>
        <span class="current">Produits</span>
    </div>
    <div class="topbar-actions">
        <div class="search-wrap">
            <svg class="search-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
            </svg>
            <input type="text" class="search-input" placeholder="Rechercher un produit…" id="searchInput" oninput="filterTable()">
        </div>
        <a href="?add=1" class="btn-add">
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Nouveau produit
        </a>
    </div>
</div>

<!-- ════════════ CONTENT ════════════ -->
<main class="main">
    <div class="content">

        <div class="page-header">
            <div>
                <div class="page-title">Gestion des Produits</div>
                <div class="page-subtitle">Superviser, ajouter, modifier et supprimer les produits de la plateforme.</div>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <?php if ($messageType === 'success'): ?>
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                <?php elseif ($messageType === 'danger'): ?>
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                <?php else: ?>
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                <?php endif; ?>
            </svg>
            <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>

        <!-- KPI -->
        <div class="kpi-strip">
            <div class="kpi-card">
                <div class="kpi-label">Total produits</div>
                <div class="kpi-value"><?= $totalProduits ?></div>
                <div class="kpi-badge" style="background:var(--accent-soft);color:var(--accent);">Catalogue actif</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">Prix moyen</div>
                <div class="kpi-value"><?= number_format($prixMoyen, 2) ?> <span style="font-size:14px;color:var(--text-muted)">DT</span></div>
                <div class="kpi-badge" style="background:var(--info-soft);color:var(--info);">Moyenne catalogue</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">Prix le plus élevé</div>
                <div class="kpi-value"><?= number_format($prixMax, 2) ?> <span style="font-size:14px;color:var(--text-muted)">DT</span></div>
                <div class="kpi-badge" style="background:var(--warning-soft);color:var(--warning);">Maximum</div>
            </div>
        </div>

        <!-- FORM PANEL -->
        <?php if (isset($_GET['add']) || $produitUpdate): ?>
        <div class="form-panel">
            <div class="form-panel-header">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="color:var(--text-muted)">
                    <?php if ($produitUpdate): ?>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    <?php else: ?>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    <?php endif; ?>
                </svg>
                <span class="form-panel-title"><?= $produitUpdate ? 'Modifier le produit' : 'Ajouter un produit' ?></span>
                <span class="form-panel-badge <?= $produitUpdate ? 'badge-edit' : 'badge-add' ?>">
                    <?= $produitUpdate ? 'Édition' : 'Nouveau' ?>
                </span>
            </div>
            <div class="form-body">
                <form method="POST" action="index.php" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="<?= $produitUpdate ? 'update' : 'add' ?>">
                    <input type="hidden" name="id" value="<?= $produitUpdate['idProduit'] ?? '' ?>">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Nom du produit *</label>
                            <input type="text" name="nom" class="form-control"
                                placeholder="Ex : Sac à main en cuir"
                                value="<?= htmlspecialchars($produitUpdate['nomProduit'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Prix (DT) *</label>
                            <input type="number" step="0.01" min="0" name="prix" class="form-control"
                                placeholder="0.00"
                                value="<?= htmlspecialchars($produitUpdate['prix'] ?? '') ?>" required>
                        </div>
                        <div class="form-group form-col-full">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control"
                                placeholder="Description détaillée du produit…"><?= htmlspecialchars($produitUpdate['description'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group form-col-full">
                            <label class="form-label">Caractéristiques</label>
                            <textarea name="caracteristiques" class="form-control" style="min-height:64px"
                                placeholder="Couleur, taille, matière, etc."><?= htmlspecialchars($produitUpdate['caracteristiques'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group form-col-full">
                            <label class="form-label">Image du produit</label>
                            <?php if ($produitUpdate && !empty($produitUpdate['image'])): ?>
                                <div class="current-image-preview">
                                    <img src="<?= $baseUrl ?>/Vue/public/produits/<?= htmlspecialchars($produitUpdate['image']) ?>"
                                         alt="Image actuelle">
                                    <span>Image actuelle — laissez vide pour la conserver, ou importez une nouvelle pour la remplacer.</span>
                                </div>
                            <?php endif; ?>
                            <div class="upload-admin" id="uploadAdmin">
                                <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp" id="fileInputAdmin">
                                <div class="upload-admin-text">
                                    <strong>Cliquez pour importer</strong> une image
                                </div>
                                <span class="upload-admin-hint">JPG, PNG, WEBP — 2 Mo max</span>
                                <div class="file-chosen" id="fileChosenAdmin"></div>
                            </div>
                        </div>
                    </div>
                    <div class="form-actions" style="margin-top:16px">
                        <button type="submit" class="btn btn-primary">
                            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                            <?= $produitUpdate ? 'Enregistrer les modifications' : 'Ajouter le produit' ?>
                        </button>
                        <a href="index.php" class="btn btn-ghost">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- TABLE PANEL -->
        <div class="table-panel">
            <div class="table-panel-header">
                <span class="table-panel-title">Liste des produits</span>
                <span class="count-badge"><?= $totalProduits ?> entrée<?= $totalProduits > 1 ? 's' : '' ?></span>
            </div>

            <?php if (empty($liste)): ?>
            <div class="empty-state">
                <div class="empty-icon">📦</div>
                <div class="empty-text">Aucun produit dans le catalogue</div>
                <div class="empty-hint">Ajoutez votre premier produit via le bouton ci-dessus.</div>
            </div>
            <?php else: ?>
            <table id="produitsTable">
                <thead>
                    <tr>
                        <th>#ID</th>
                        <th>Image</th>
                        <th>Nom du produit</th>
                        <th>Description</th>
                        <th>Caractéristiques</th>
                        <th>Prix</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($liste as $p): ?>
                    <tr>
                        <td class="td-id">#<?= htmlspecialchars($p['idProduit']) ?></td>
                        <td class="td-img">
                            <?php if (!empty($p['image'])): ?>
                                <img src="<?= $baseUrl ?>/Vue/public/produits/<?= htmlspecialchars($p['image']) ?>"
                                     alt="<?= htmlspecialchars($p['nomProduit']) ?>">
                            <?php else: ?>
                                <div class="td-img-empty">📦</div>
                            <?php endif; ?>
                        </td>
                        <td class="td-name"><?= htmlspecialchars($p['nomProduit']) ?></td>
                        <td class="td-desc" title="<?= htmlspecialchars($p['description']) ?>">
                            <?= htmlspecialchars($p['description']) ?>
                        </td>
                        <td class="td-carac" title="<?= htmlspecialchars($p['caracteristiques']) ?>">
                            <?= htmlspecialchars($p['caracteristiques']) ?>
                        </td>
                        <td><span class="prix-badge"><?= number_format((float)$p['prix'], 2) ?> DT</span></td>
                        <td>
                            <div class="action-group">
                                <a href="?edit=<?= $p['idProduit'] ?>" class="btn-action btn-edit">
                                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                    Modifier
                                </a>
                                <button type="button" class="btn-action btn-delete"
                                    onclick="openDeleteModal(<?= $p['idProduit'] ?>, '<?= htmlspecialchars($p['nomProduit'], ENT_QUOTES) ?>')">
                                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                    Supprimer
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

    </div>
</main>

<!-- MODAL -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal-box">
        <div class="modal-title">Confirmer la suppression</div>
        <div class="modal-text" id="modalText">Voulez-vous vraiment supprimer ce produit ? Cette action est irréversible.</div>
        <div class="modal-actions">
            <button class="btn btn-ghost" onclick="closeDeleteModal()">Annuler</button>
            <a class="btn" id="confirmDeleteBtn" href="#" style="background:var(--danger);color:#fff;">Supprimer</a>
        </div>
    </div>
</div>

<script>
function openDeleteModal(id, name) {
    document.getElementById('modalText').textContent =
        'Voulez-vous vraiment supprimer le produit "' + name + '" ? Cette action est irréversible.';
    document.getElementById('confirmDeleteBtn').href = 'index.php?delete=' + id;
    document.getElementById('deleteModal').classList.add('open');
}
function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('open');
}
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) closeDeleteModal();
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeDeleteModal();
});

document.getElementById('fileInputAdmin').addEventListener('change', function () {
    const display = document.getElementById('fileChosenAdmin');
    if (this.files && this.files[0]) {
        display.textContent = '📎 ' + this.files[0].name;
        display.style.display = 'block';
        document.getElementById('uploadAdmin').style.borderColor = 'var(--accent)';
    } else {
        display.style.display = 'none';
    }
});

function filterTable() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('#produitsTable tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}

setTimeout(() => {
    const a = document.querySelector('.alert');
    if (a) { a.style.opacity = '0'; a.style.transition = 'opacity .5s'; setTimeout(() => a.remove(), 500); }
}, 4000);
</script>
</body>
</html>