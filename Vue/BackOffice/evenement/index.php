<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Gestion Événements – Admin Cre8Connect</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet"/>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --bg-base:      #0d1117;
      --bg-surface:   #161b22;
      --bg-elevated:  #21262d;
      --bg-hover:     #30363d;
      --border:       #30363d;
      --border-soft:  #21262d;
      --text-main:    #e6edf3;
      --text-soft:    #8b949e;
      --text-muted:   #484f58;
      --primary:      #58a6ff;
      --primary-dim:  rgba(88,166,255,.12);
      --accent:       #f78166;
      --success:      #3fb950;
      --success-dim:  rgba(63,185,80,.12);
      --warning:      #d29922;
      --warning-dim:  rgba(210,153,34,.12);
      --danger:       #f85149;
      --danger-dim:   rgba(248,81,73,.12);
      --purple:       #bc8cff;
      --purple-dim:   rgba(188,140,255,.12);
      --radius:       8px;
      --radius-lg:    12px;
      --shadow:       0 8px 32px rgba(0,0,0,.4);
    }

    body {
      font-family: 'Inter', sans-serif;
      background: var(--bg-base);
      color: var(--text-main);
      display: flex; min-height: 100vh;
    }

    /* ── SIDEBAR ── */
    .sidebar {
      width: 240px; flex-shrink: 0;
      background: var(--bg-surface);
      border-right: 1px solid var(--border);
      display: flex; flex-direction: column;
      position: fixed; top: 0; left: 0; height: 100vh;
      overflow-y: auto; z-index: 50;
    }
    .sidebar-logo {
      padding: 20px 20px 16px;
      border-bottom: 1px solid var(--border);
      display: flex; align-items: center; gap: 10px;
    }
    .logo-icon {
      width: 32px; height: 32px; border-radius: 8px;
      background: linear-gradient(135deg, #58a6ff, #bc8cff);
      display: flex; align-items: center; justify-content: center;
      font-size: .9rem; font-weight: 700; color: #fff;
      font-family: 'JetBrains Mono', monospace;
    }
    .logo-text { font-size: .95rem; font-weight: 600; color: var(--text-main); }
    .logo-badge {
      font-size: .62rem; font-weight: 600; letter-spacing: .06em;
      background: var(--primary-dim); color: var(--primary);
      padding: 2px 6px; border-radius: 4px; text-transform: uppercase;
    }

    .sidebar-section {
      padding: 20px 12px 8px;
    }
    .sidebar-section-label {
      font-size: .65rem; font-weight: 600; letter-spacing: .1em;
      color: var(--text-muted); text-transform: uppercase;
      padding: 0 8px; margin-bottom: 6px;
    }
    .nav-item {
      display: flex; align-items: center; gap: 10px;
      padding: 8px 10px; border-radius: var(--radius);
      font-size: .85rem; font-weight: 500; color: var(--text-soft);
      cursor: pointer; text-decoration: none;
      transition: background .15s, color .15s;
      margin-bottom: 2px;
    }
    .nav-item:hover { background: var(--bg-hover); color: var(--text-main); }
    .nav-item.active {
      background: var(--primary-dim); color: var(--primary);
    }
    .nav-item svg { flex-shrink: 0; opacity: .7; }
    .nav-item.active svg { opacity: 1; }
    .nav-badge {
      margin-left: auto; font-size: .7rem; font-weight: 600;
      background: var(--danger); color: #fff;
      padding: 1px 7px; border-radius: 50px;
    }
    .nav-badge.info { background: var(--primary-dim); color: var(--primary); }

    .sidebar-footer {
      margin-top: auto; padding: 16px 12px;
      border-top: 1px solid var(--border);
    }
    .admin-avatar {
      display: flex; align-items: center; gap: 10px;
      padding: 8px 10px; border-radius: var(--radius);
      cursor: pointer; transition: background .15s;
    }
    .admin-avatar:hover { background: var(--bg-hover); }
    .avatar-circle {
      width: 32px; height: 32px; border-radius: 50%;
      background: linear-gradient(135deg, #58a6ff, #bc8cff);
      display: flex; align-items: center; justify-content: center;
      font-size: .8rem; font-weight: 700; color: #fff; flex-shrink: 0;
    }
    .avatar-info { flex: 1; min-width: 0; }
    .avatar-name { font-size: .83rem; font-weight: 600; color: var(--text-main); }
    .avatar-role { font-size: .72rem; color: var(--text-muted); }

    /* ── MAIN ── */
    .main-wrap {
      margin-left: 240px; flex: 1;
      display: flex; flex-direction: column;
    }

    /* ── TOP NAV ── */
    .topbar {
      background: var(--bg-surface);
      border-bottom: 1px solid var(--border);
      padding: 0 28px;
      height: 56px;
      display: flex; align-items: center; gap: 16px;
      position: sticky; top: 0; z-index: 40;
    }
    .breadcrumb {
      display: flex; align-items: center; gap: 8px;
      font-size: .83rem; color: var(--text-muted);
    }
    .breadcrumb span { color: var(--text-main); }
    .topbar-actions { margin-left: auto; display: flex; align-items: center; gap: 12px; }
    .topbar-search {
      display: flex; align-items: center; gap: 8px;
      background: var(--bg-base); border: 1px solid var(--border);
      border-radius: var(--radius); padding: 6px 12px;
    }
    .topbar-search input {
      background: none; border: none; outline: none;
      font-size: .83rem; color: var(--text-main); width: 180px;
      font-family: inherit;
    }
    .topbar-search input::placeholder { color: var(--text-muted); }
    .icon-btn {
      width: 34px; height: 34px; border-radius: var(--radius);
      background: var(--bg-elevated); border: 1px solid var(--border);
      display: flex; align-items: center; justify-content: center;
      cursor: pointer; color: var(--text-soft); transition: background .15s, color .15s;
      position: relative;
    }
    .icon-btn:hover { background: var(--bg-hover); color: var(--text-main); }
    .icon-btn .dot {
      position: absolute; top: 6px; right: 6px;
      width: 7px; height: 7px; border-radius: 50%;
      background: var(--danger); border: 1.5px solid var(--bg-surface);
    }

    /* ── CONTENT ── */
    .content { padding: 28px; flex: 1; }

    /* Page header */
    .page-header {
      display: flex; align-items: flex-start; justify-content: space-between;
      margin-bottom: 24px;
    }
    .page-title-wrap h1 {
      font-size: 1.3rem; font-weight: 700; color: var(--text-main);
      margin-bottom: 4px;
    }
    .page-title-wrap p { font-size: .83rem; color: var(--text-soft); }
    .page-actions { display: flex; gap: 10px; }
    .btn-admin {
      display: inline-flex; align-items: center; gap: 7px;
      padding: 8px 18px; border-radius: var(--radius);
      font-size: .83rem; font-weight: 600; font-family: inherit;
      cursor: pointer; transition: all .15s; text-decoration: none; border: none;
    }
    .btn-primary-admin {
      background: var(--primary); color: #0d1117;
    }
    .btn-primary-admin:hover { background: #79c0ff; }
    .btn-ghost {
      background: var(--bg-elevated); color: var(--text-soft);
      border: 1px solid var(--border);
    }
    .btn-ghost:hover { background: var(--bg-hover); color: var(--text-main); }

    /* KPI cards */
    .kpi-grid {
      display: grid; grid-template-columns: repeat(4, 1fr);
      gap: 16px; margin-bottom: 24px;
    }
    .kpi-card {
      background: var(--bg-surface); border: 1px solid var(--border);
      border-radius: var(--radius-lg); padding: 20px;
      transition: border-color .2s;
    }
    .kpi-card:hover { border-color: var(--bg-hover); }
    .kpi-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
    .kpi-label { font-size: .75rem; font-weight: 500; color: var(--text-soft); }
    .kpi-icon {
      width: 30px; height: 30px; border-radius: 8px;
      display: flex; align-items: center; justify-content: center; font-size: .9rem;
    }
    .kpi-icon.blue   { background: var(--primary-dim); }
    .kpi-icon.green  { background: var(--success-dim); }
    .kpi-icon.yellow { background: var(--warning-dim); }
    .kpi-icon.purple { background: var(--purple-dim); }
    .kpi-value {
      font-size: 1.65rem; font-weight: 700; color: var(--text-main);
      font-family: 'JetBrains Mono', monospace; margin-bottom: 6px;
    }
    .kpi-delta {
      font-size: .73rem; font-weight: 500;
    }
    .kpi-delta.up   { color: var(--success); }
    .kpi-delta.warn { color: var(--warning); }
    .kpi-delta.down { color: var(--danger); }

    /* Toolbar */
    .toolbar {
      background: var(--bg-surface); border: 1px solid var(--border);
      border-radius: var(--radius-lg) var(--radius-lg) 0 0;
      padding: 14px 20px;
      display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
    }
    .toolbar-search {
      display: flex; align-items: center; gap: 8px;
      background: var(--bg-base); border: 1px solid var(--border);
      border-radius: var(--radius); padding: 7px 12px; flex: 1; min-width: 200px;
    }
    .toolbar-search input {
      background: none; border: none; outline: none;
      font-size: .83rem; color: var(--text-main); width: 100%; font-family: inherit;
    }
    .toolbar-search input::placeholder { color: var(--text-muted); }
    .toolbar-select {
      background: var(--bg-base); border: 1px solid var(--border);
      border-radius: var(--radius); padding: 7px 12px;
      font-size: .83rem; color: var(--text-main); font-family: inherit; cursor: pointer;
    }
    .toolbar-select:focus { outline: none; border-color: var(--primary); }

    /* Table */
    .table-wrap {
      background: var(--bg-surface); border: 1px solid var(--border);
      border-top: none; border-radius: 0 0 var(--radius-lg) var(--radius-lg);
      overflow: hidden; margin-bottom: 24px;
    }
    table { width: 100%; border-collapse: collapse; }
    thead {
      background: var(--bg-base);
      border-bottom: 1px solid var(--border);
    }
    thead th {
      padding: 11px 16px;
      font-size: .72rem; font-weight: 600; letter-spacing: .06em;
      color: var(--text-muted); text-transform: uppercase;
      text-align: left; white-space: nowrap;
    }
    thead th:first-child { padding-left: 20px; }
    thead th input[type="checkbox"] { accent-color: var(--primary); }
    tbody tr {
      border-bottom: 1px solid var(--border-soft);
      transition: background .12s;
    }
    tbody tr:last-child { border-bottom: none; }
    tbody tr:hover { background: var(--bg-elevated); }
    tbody td { padding: 13px 16px; font-size: .83rem; color: var(--text-soft); vertical-align: middle; }
    tbody td:first-child { padding-left: 20px; }

    .event-cell { display: flex; align-items: center; gap: 12px; }
    .event-thumb {
      width: 40px; height: 40px; border-radius: 8px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.2rem; flex-shrink: 0;
    }
    .thumb-purple { background: var(--purple-dim); }
    .thumb-blue   { background: var(--primary-dim); }
    .thumb-green  { background: var(--success-dim); }
    .thumb-yellow { background: var(--warning-dim); }
    .thumb-red    { background: var(--danger-dim); }
    .event-name { font-size: .85rem; font-weight: 600; color: var(--text-main); margin-bottom: 2px; line-height: 1.3; }
    .event-org  { font-size: .75rem; color: var(--text-muted); }

    /* Status badges */
    .badge {
      display: inline-flex; align-items: center; gap: 5px;
      font-size: .72rem; font-weight: 600; padding: 3px 10px;
      border-radius: 50px; white-space: nowrap;
    }
    .badge::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; opacity: .7; }
    .badge-active   { background: var(--success-dim); color: var(--success); }
    .badge-pending  { background: var(--warning-dim); color: var(--warning); }
    .badge-closed   { background: var(--bg-hover);    color: var(--text-muted); }
    .badge-canceled { background: var(--danger-dim);  color: var(--danger); }
    .badge-draft    { background: var(--purple-dim);  color: var(--purple); }

    .type-chip {
      font-size: .72rem; font-weight: 500;
      padding: 3px 9px; border-radius: 4px;
    }
    .type-formation { background: rgba(88,166,255,.1); color: var(--primary); }
    .type-webinaire  { background: rgba(210,153,34,.1); color: var(--warning); }
    .type-meetup    { background: rgba(63,185,80,.1);  color: var(--success); }
    .type-atelier   { background: rgba(188,140,255,.1); color: var(--purple); }

    .progress-wrap { display: flex; align-items: center; gap: 8px; }
    .progress-bar {
      flex: 1; height: 6px; background: var(--bg-elevated);
      border-radius: 50px; overflow: hidden;
    }
    .progress-fill {
      height: 100%; border-radius: 50px;
      background: linear-gradient(90deg, var(--primary), var(--purple));
      transition: width .4s ease;
    }
    .progress-fill.full { background: var(--danger); }
    .progress-pct { font-size: .73rem; color: var(--text-muted); font-family: 'JetBrains Mono', monospace; }

    .action-btn {
      padding: 5px 10px; border-radius: 6px;
      font-size: .75rem; font-weight: 500; font-family: inherit;
      cursor: pointer; border: none; transition: all .15s;
      text-decoration: none; display: inline-flex; align-items: center; gap: 4px;
    }
    .action-edit   { background: var(--primary-dim); color: var(--primary); }
    .action-edit:hover { background: rgba(88,166,255,.2); }
    .action-view   { background: var(--bg-elevated); color: var(--text-soft); border: 1px solid var(--border); }
    .action-view:hover { background: var(--bg-hover); color: var(--text-main); }
    .action-delete { background: var(--danger-dim); color: var(--danger); }
    .action-delete:hover { background: rgba(248,81,73,.2); }

    .actions-cell { display: flex; gap: 6px; }

    /* Pagination */
    .table-footer {
      display: flex; align-items: center; justify-content: space-between;
      background: var(--bg-surface); border: 1px solid var(--border);
      border-top: none; border-radius: 0 0 var(--radius-lg) var(--radius-lg);
      padding: 14px 20px; margin-top: -24px; margin-bottom: 24px;
    }
    .table-info { font-size: .8rem; color: var(--text-muted); }
    .pagination { display: flex; gap: 4px; }
    .page-btn {
      width: 32px; height: 32px; border-radius: 6px;
      display: flex; align-items: center; justify-content: center;
      font-size: .8rem; font-weight: 500; cursor: pointer;
      background: var(--bg-elevated); border: 1px solid var(--border);
      color: var(--text-soft); transition: all .15s;
    }
    .page-btn:hover, .page-btn.active {
      background: var(--primary); border-color: var(--primary); color: #0d1117;
    }

    /* Modal overlay */
    .modal-overlay {
      display: none; position: fixed; inset: 0; z-index: 200;
      background: rgba(0,0,0,.6); backdrop-filter: blur(4px);
      align-items: center; justify-content: center;
    }
    .modal-overlay.open { display: flex; }
    .modal {
      background: var(--bg-surface); border: 1px solid var(--border);
      border-radius: var(--radius-lg); width: 560px; max-width: 95vw;
      box-shadow: var(--shadow); animation: slideIn .2s ease;
    }
    @keyframes slideIn { from { transform: translateY(-16px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    .modal-header {
      padding: 20px 24px; border-bottom: 1px solid var(--border);
      display: flex; align-items: center; justify-content: space-between;
    }
    .modal-header h2 { font-size: 1rem; font-weight: 600; }
    .modal-close {
      background: var(--bg-elevated); border: 1px solid var(--border);
      border-radius: 6px; width: 28px; height: 28px;
      display: flex; align-items: center; justify-content: center;
      cursor: pointer; color: var(--text-soft); font-size: 1rem;
      transition: all .15s;
    }
    .modal-close:hover { background: var(--danger-dim); color: var(--danger); border-color: var(--danger); }
    .modal-body { padding: 24px; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
    .form-group { margin-bottom: 16px; }
    .form-group:last-child { margin-bottom: 0; }
    .form-label { font-size: .78rem; font-weight: 600; color: var(--text-soft); margin-bottom: 6px; display: block; }
    .form-control {
      width: 100%; padding: 9px 12px;
      background: var(--bg-base); border: 1px solid var(--border);
      border-radius: var(--radius); font-size: .85rem; font-family: inherit;
      color: var(--text-main); transition: border-color .15s;
    }
    .form-control:focus { outline: none; border-color: var(--primary); }
    .form-control::placeholder { color: var(--text-muted); }
    textarea.form-control { resize: vertical; min-height: 90px; }
    .modal-footer {
      padding: 16px 24px; border-top: 1px solid var(--border);
      display: flex; justify-content: flex-end; gap: 10px;
    }

    /* Alert banner */
    .alert-banner {
      background: var(--warning-dim); border: 1px solid rgba(210,153,34,.3);
      border-radius: var(--radius); padding: 12px 16px;
      display: flex; align-items: center; gap: 10px;
      font-size: .83rem; color: var(--warning);
      margin-bottom: 20px;
    }

    @media (max-width: 1024px) {
      .sidebar { width: 200px; }
      .main-wrap { margin-left: 200px; }
      .kpi-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 768px) {
      .sidebar { transform: translateX(-100%); }
      .main-wrap { margin-left: 0; }
      .kpi-grid { grid-template-columns: 1fr 1fr; }
    }
  </style>
</head>
<body>

<!-- ── SIDEBAR ── -->
<aside class="sidebar">
  <div class="sidebar-logo">
    <div class="logo-icon">C8</div>
    <div>
      <div class="logo-text">Cre8Connect</div>
      <div class="logo-badge">Admin</div>
    </div>
  </div>

  <div class="sidebar-section">
    <div class="sidebar-section-label">Navigation</div>
    <a href="#" class="nav-item">
      <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
      Dashboard
    </a>
    <a href="#" class="nav-item">
      <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      Utilisateurs
      <span class="nav-badge">3</span>
    </a>
    <a href="#" class="nav-item">
      <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      Offres
    </a>
  </div>

  <div class="sidebar-section">
    <div class="sidebar-section-label">Communauté</div>
    <a href="#" class="nav-item active">
      <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
      Événements
      <span class="nav-badge info">42</span>
    </a>
    <a href="#" class="nav-item">
      <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
      Forum
    </a>
    <a href="#" class="nav-item">
      <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
      Posts
    </a>
  </div>

  <div class="sidebar-section">
    <div class="sidebar-section-label">Opérations</div>
    <a href="#" class="nav-item">
      <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
      Campagnes
    </a>
    <a href="#" class="nav-item">
      <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      Réclamations
      <span class="nav-badge">7</span>
    </a>
  </div>

  <div class="sidebar-footer">
    <div class="admin-avatar">
      <div class="avatar-circle">AD</div>
      <div class="avatar-info">
        <div class="avatar-name">Admin Cre8</div>
        <div class="avatar-role">Administrateur</div>
      </div>
    </div>
  </div>
</aside>

<!-- ── MAIN WRAP ── -->
<div class="main-wrap">

  <!-- Topbar -->
  <header class="topbar">
    <div class="breadcrumb">
      Dashboard
      <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
      Communauté
      <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
      <span>Événements</span>
    </div>
    <div class="topbar-actions">
      <div class="topbar-search">
        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input type="text" placeholder="Recherche rapide…"/>
      </div>
      <div class="icon-btn">
        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
        <span class="dot"></span>
      </div>
      <div class="icon-btn">
        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93l-1.41 1.41M4.93 4.93l1.41 1.41M12 2v2M12 20v2M19.07 19.07l-1.41-1.41M4.93 19.07l1.41-1.41M2 12h2M20 12h2"/></svg>
      </div>
    </div>
  </header>

  <!-- Content -->
  <div class="content">

    <!-- Page header -->
    <div class="page-header">
      <div class="page-title-wrap">
        <h1>Gestion des Événements</h1>
        <p>Supervision, modération et administration de tous les événements de la plateforme</p>
      </div>
      <div class="page-actions">
        <button class="btn-admin btn-ghost" onclick="exportData()">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
          Exporter
        </button>
        <button class="btn-admin btn-primary-admin" onclick="openModal()">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Nouvel événement
        </button>
      </div>
    </div>

    <!-- Alert -->
    <div class="alert-banner">
      <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
      <strong>3 événements</strong>&nbsp;en attente de validation nécessitent votre attention.
    </div>

    <!-- KPI row -->
    <div class="kpi-grid">
      <div class="kpi-card">
        <div class="kpi-header">
          <span class="kpi-label">Total Événements</span>
          <div class="kpi-icon blue">📅</div>
        </div>
        <div class="kpi-value">42</div>
        <div class="kpi-delta up">↑ +8 ce mois</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-header">
          <span class="kpi-label">Participants inscrits</span>
          <div class="kpi-icon green">👥</div>
        </div>
        <div class="kpi-value">1 247</div>
        <div class="kpi-delta up">↑ +143 cette semaine</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-header">
          <span class="kpi-label">En attente de validation</span>
          <div class="kpi-icon yellow">⏳</div>
        </div>
        <div class="kpi-value">3</div>
        <div class="kpi-delta warn">⚠ Action requise</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-header">
          <span class="kpi-label">Événements cette semaine</span>
          <div class="kpi-icon purple">🚀</div>
        </div>
        <div class="kpi-value">5</div>
        <div class="kpi-delta up">↑ +2 vs semaine passée</div>
      </div>
    </div>

    <!-- Table toolbar -->
    <div class="toolbar">
      <div class="toolbar-search">
        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input type="text" placeholder="Rechercher un événement…"/>
      </div>
      <select class="toolbar-select">
        <option>Tous les statuts</option>
        <option>Actif</option>
        <option>En attente</option>
        <option>Clôturé</option>
        <option>Annulé</option>
        <option>Brouillon</option>
      </select>
      <select class="toolbar-select">
        <option>Tous les types</option>
        <option>Formation</option>
        <option>Webinaire</option>
        <option>Meetup</option>
        <option>Atelier</option>
      </select>
      <select class="toolbar-select">
        <option>Toutes les dates</option>
        <option>Cette semaine</option>
        <option>Ce mois</option>
        <option>Mois prochain</option>
      </select>
      <button class="btn-admin btn-ghost" style="margin-left:auto;">
        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
        Colonnes
      </button>
    </div>

    <!-- Table -->
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th><input type="checkbox"/></th>
            <th>Événement</th>
            <th>Type</th>
            <th>Statut</th>
            <th>Date</th>
            <th>Lieu</th>
            <th>Inscriptions</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>

          <tr>
            <td><input type="checkbox"/></td>
            <td>
              <div class="event-cell">
                <div class="event-thumb thumb-purple">🎯</div>
                <div>
                  <div class="event-name">Maîtriser le Personal Branding</div>
                  <div class="event-org">Organisé par: Marque Alpha</div>
                </div>
              </div>
            </td>
            <td><span class="type-chip type-formation">Formation</span></td>
            <td><span class="badge badge-active">Actif</span></td>
            <td style="font-size:.8rem; font-family:'JetBrains Mono',monospace; color:var(--text-soft);">15 Mai 2025</td>
            <td style="font-size:.82rem;">Paris 🇫🇷</td>
            <td>
              <div class="progress-wrap">
                <div class="progress-bar"><div class="progress-fill" style="width:75%"></div></div>
                <span class="progress-pct">36/48</span>
              </div>
            </td>
            <td>
              <div class="actions-cell">
                <button class="action-btn action-view">👁 Voir</button>
                <button class="action-btn action-edit">✏ Éditer</button>
                <button class="action-btn action-delete">🗑</button>
              </div>
            </td>
          </tr>

          <tr>
            <td><input type="checkbox"/></td>
            <td>
              <div class="event-cell">
                <div class="event-thumb thumb-yellow">⏳</div>
                <div>
                  <div class="event-name">Négocier ses contrats de collaboration</div>
                  <div class="event-org">Organisé par: Studio Créa</div>
                </div>
              </div>
            </td>
            <td><span class="type-chip type-webinaire">Webinaire</span></td>
            <td><span class="badge badge-pending">En attente</span></td>
            <td style="font-size:.8rem; font-family:'JetBrains Mono',monospace; color:var(--text-soft);">22 Avr 2025</td>
            <td style="font-size:.82rem;">En ligne 🌐</td>
            <td>
              <div class="progress-wrap">
                <div class="progress-bar"><div class="progress-fill" style="width:45%"></div></div>
                <span class="progress-pct">22/50</span>
              </div>
            </td>
            <td>
              <div class="actions-cell">
                <button class="action-btn action-view">👁 Voir</button>
                <button class="action-btn action-edit" style="background:var(--success-dim);color:var(--success);">✓ Valider</button>
                <button class="action-btn action-delete">🗑</button>
              </div>
            </td>
          </tr>

          <tr>
            <td><input type="checkbox"/></td>
            <td>
              <div class="event-cell">
                <div class="event-thumb thumb-green">🌿</div>
                <div>
                  <div class="event-name">Meetup Créateurs & Marques – Printemps</div>
                  <div class="event-org">Organisé par: Cre8Connect</div>
                </div>
              </div>
            </td>
            <td><span class="type-chip type-meetup">Meetup</span></td>
            <td><span class="badge badge-active">Actif</span></td>
            <td style="font-size:.8rem; font-family:'JetBrains Mono',monospace; color:var(--text-soft);">3 Mai 2025</td>
            <td style="font-size:.82rem;">Lyon 🇫🇷</td>
            <td>
              <div class="progress-wrap">
                <div class="progress-bar"><div class="progress-fill" style="width:90%"></div></div>
                <span class="progress-pct">90/100</span>
              </div>
            </td>
            <td>
              <div class="actions-cell">
                <button class="action-btn action-view">👁 Voir</button>
                <button class="action-btn action-edit">✏ Éditer</button>
                <button class="action-btn action-delete">🗑</button>
              </div>
            </td>
          </tr>

          <tr>
            <td><input type="checkbox"/></td>
            <td>
              <div class="event-cell">
                <div class="event-thumb thumb-red">✂️</div>
                <div>
                  <div class="event-name">Montage Vidéo Avancé – Réseaux Sociaux</div>
                  <div class="event-org">Organisé par: DigitalCraft</div>
                </div>
              </div>
            </td>
            <td><span class="type-chip type-formation">Formation</span></td>
            <td><span class="badge badge-closed">Clôturé</span></td>
            <td style="font-size:.8rem; font-family:'JetBrains Mono',monospace; color:var(--text-soft);">28 Avr 2025</td>
            <td style="font-size:.82rem;">En ligne 🌐</td>
            <td>
              <div class="progress-wrap">
                <div class="progress-bar"><div class="progress-fill full" style="width:100%"></div></div>
                <span class="progress-pct">30/30</span>
              </div>
            </td>
            <td>
              <div class="actions-cell">
                <button class="action-btn action-view">👁 Voir</button>
                <button class="action-btn action-edit">✏ Éditer</button>
                <button class="action-btn action-delete">🗑</button>
              </div>
            </td>
          </tr>

          <tr>
            <td><input type="checkbox"/></td>
            <td>
              <div class="event-cell">
                <div class="event-thumb thumb-blue">📊</div>
                <div>
                  <div class="event-name">Analytics & Performance Créateurs</div>
                  <div class="event-org">Organisé par: DataCreators</div>
                </div>
              </div>
            </td>
            <td><span class="type-chip type-webinaire">Webinaire</span></td>
            <td><span class="badge badge-pending">En attente</span></td>
            <td style="font-size:.8rem; font-family:'JetBrains Mono',monospace; color:var(--text-soft);">10 Mai 2025</td>
            <td style="font-size:.82rem;">En ligne 🌐</td>
            <td>
              <div class="progress-wrap">
                <div class="progress-bar"><div class="progress-fill" style="width:20%"></div></div>
                <span class="progress-pct">8/40</span>
              </div>
            </td>
            <td>
              <div class="actions-cell">
                <button class="action-btn action-view">👁 Voir</button>
                <button class="action-btn action-edit" style="background:var(--success-dim);color:var(--success);">✓ Valider</button>
                <button class="action-btn action-delete">🗑</button>
              </div>
            </td>
          </tr>

          <tr>
            <td><input type="checkbox"/></td>
            <td>
              <div class="event-cell">
                <div class="event-thumb thumb-purple">🚀</div>
                <div>
                  <div class="event-name">Lancement Cre8Connect : Soirée Fondateurs</div>
                  <div class="event-org">Organisé par: Cre8Connect</div>
                </div>
              </div>
            </td>
            <td><span class="type-chip type-atelier">Atelier</span></td>
            <td><span class="badge badge-draft">Brouillon</span></td>
            <td style="font-size:.8rem; font-family:'JetBrains Mono',monospace; color:var(--text-soft);">1 Juin 2025</td>
            <td style="font-size:.82rem;">Paris 🇫🇷</td>
            <td>
              <div class="progress-wrap">
                <div class="progress-bar"><div class="progress-fill" style="width:0%"></div></div>
                <span class="progress-pct">0/60</span>
              </div>
            </td>
            <td>
              <div class="actions-cell">
                <button class="action-btn action-view">👁 Voir</button>
                <button class="action-btn action-edit">✏ Éditer</button>
                <button class="action-btn action-delete">🗑</button>
              </div>
            </td>
          </tr>

        </tbody>
      </table>
    </div>

    <!-- Table footer / pagination -->
    <div class="table-footer">
      <span class="table-info">Affichage 1–6 sur 42 événements</span>
      <div class="pagination">
        <button class="page-btn">←</button>
        <button class="page-btn active">1</button>
        <button class="page-btn">2</button>
        <button class="page-btn">3</button>
        <button class="page-btn">…</button>
        <button class="page-btn">7</button>
        <button class="page-btn">→</button>
      </div>
    </div>

  </div><!-- /content -->
</div><!-- /main-wrap -->

<!-- ── MODAL : Créer un événement ── -->
<div class="modal-overlay" id="createModal">
  <div class="modal">
    <div class="modal-header">
      <h2>➕ Nouvel Événement</h2>
      <button class="modal-close" onclick="closeModal()">✕</button>
    </div>
    <div class="modal-body">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Titre de l'événement *</label>
          <input type="text" class="form-control" placeholder="Ex: Webinaire Personal Branding"/>
        </div>
        <div class="form-group">
          <label class="form-label">Type *</label>
          <select class="form-control">
            <option value="">Choisir un type…</option>
            <option>Formation</option>
            <option>Webinaire</option>
            <option>Meetup</option>
            <option>Atelier</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Description</label>
        <textarea class="form-control" placeholder="Décrivez l'événement…"></textarea>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Date de l'événement *</label>
          <input type="date" class="form-control"/>
        </div>
        <div class="form-group">
          <label class="form-label">Lieu / Mode</label>
          <input type="text" class="form-control" placeholder="Paris / En ligne"/>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Capacité (places)</label>
          <input type="number" class="form-control" placeholder="50"/>
        </div>
        <div class="form-group">
          <label class="form-label">Statut initial</label>
          <select class="form-control">
            <option>Brouillon</option>
            <option>En attente</option>
            <option>Actif</option>
          </select>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-admin btn-ghost" onclick="closeModal()">Annuler</button>
      <button class="btn-admin btn-primary-admin">Créer l'événement</button>
    </div>
  </div>
</div>

<script>
  function openModal()  { document.getElementById('createModal').classList.add('open'); }
  function closeModal() { document.getElementById('createModal').classList.remove('open'); }
  document.getElementById('createModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
  });
  function exportData() { alert('Export CSV en cours…'); }
</script>

</body>
</html>