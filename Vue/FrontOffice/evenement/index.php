<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Événements – Cre8Connect</title>
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,400&display=swap" rel="stylesheet"/>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --primary:    #4f46e5;
      --primary-lt: #ede9fe;
      --accent:     #ec4899;
      --accent-lt:  #fdf2f8;
      --text-main:  #111827;
      --text-soft:  #6b7280;
      --text-muted: #9ca3af;
      --bg:         #f9fafb;
      --white:      #ffffff;
      --border:     #e5e7eb;
      --radius:     14px;
      --shadow-sm:  0 1px 4px rgba(0,0,0,.06);
      --shadow-md:  0 4px 20px rgba(0,0,0,.08);
      --shadow-lg:  0 12px 40px rgba(79,70,229,.12);
    }

    body {
      font-family: 'DM Sans', sans-serif;
      background: var(--bg);
      color: var(--text-main);
      min-height: 100vh;
    }

    /* ── NAV ── */
    nav {
      background: var(--white);
      border-bottom: 1px solid var(--border);
      padding: 0 48px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      height: 64px;
      position: sticky; top: 0; z-index: 100;
    }
    .nav-logo {
      font-family: 'Sora', sans-serif;
      font-weight: 700;
      font-size: 1.25rem;
      color: var(--primary);
      text-decoration: none;
    }
    .nav-logo span { color: var(--accent); }
    .nav-links { display: flex; gap: 32px; list-style: none; }
    .nav-links a {
      font-size: .92rem; font-weight: 500;
      color: var(--text-soft); text-decoration: none;
      transition: color .2s;
    }
    .nav-links a:hover, .nav-links a.active { color: var(--primary); }
    .nav-cta {
      background: var(--primary); color: #fff;
      padding: 9px 22px; border-radius: 50px;
      font-size: .875rem; font-weight: 600;
      text-decoration: none; transition: background .2s, box-shadow .2s;
    }
    .nav-cta:hover { background: #4338ca; box-shadow: 0 4px 14px rgba(79,70,229,.3); }

    /* ── HERO ── */
    .hero {
      background: var(--white);
      padding: 64px 48px 48px;
      border-bottom: 1px solid var(--border);
    }
    .hero-inner { max-width: 1100px; margin: 0 auto; }
    .hero-badge {
      display: inline-flex; align-items: center; gap: 6px;
      background: var(--primary-lt); color: var(--primary);
      font-size: .78rem; font-weight: 600; letter-spacing: .04em;
      padding: 5px 14px; border-radius: 50px; margin-bottom: 20px;
    }
    .hero h1 {
      font-family: 'Sora', sans-serif;
      font-size: clamp(2rem, 4vw, 3rem);
      font-weight: 700; line-height: 1.15;
      color: var(--text-main); margin-bottom: 14px;
    }
    .hero h1 em { color: var(--primary); font-style: normal; }
    .hero p {
      font-size: 1.05rem; color: var(--text-soft);
      max-width: 520px; line-height: 1.7;
    }

    /* ── FILTERS ── */
    .filters-bar {
      max-width: 1100px; margin: 36px auto 0;
      display: flex; gap: 12px; flex-wrap: wrap; align-items: center;
    }
    .search-wrap {
      flex: 1; min-width: 240px;
      position: relative;
    }
    .search-wrap svg {
      position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
      color: var(--text-muted);
    }
    .search-wrap input {
      width: 100%; padding: 11px 16px 11px 42px;
      border: 1.5px solid var(--border); border-radius: 10px;
      font-size: .92rem; font-family: inherit;
      background: var(--white); color: var(--text-main);
      transition: border-color .2s, box-shadow .2s;
    }
    .search-wrap input:focus {
      outline: none; border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(79,70,229,.1);
    }
    .filter-select {
      padding: 11px 16px; border: 1.5px solid var(--border);
      border-radius: 10px; font-size: .92rem; font-family: inherit;
      background: var(--white); color: var(--text-main); cursor: pointer;
      transition: border-color .2s;
    }
    .filter-select:focus { outline: none; border-color: var(--primary); }
    .filter-chips { display: flex; gap: 8px; flex-wrap: wrap; }
    .chip {
      padding: 7px 16px; border-radius: 50px; font-size: .82rem; font-weight: 500;
      border: 1.5px solid var(--border); background: var(--white);
      color: var(--text-soft); cursor: pointer; transition: all .2s;
    }
    .chip:hover, .chip.active {
      background: var(--primary); border-color: var(--primary); color: #fff;
    }

    /* ── MAIN CONTENT ── */
    .main { max-width: 1100px; margin: 0 auto; padding: 48px 48px 80px; }

    .section-label {
      font-family: 'Sora', sans-serif;
      font-size: .78rem; font-weight: 600; letter-spacing: .1em;
      color: var(--text-muted); text-transform: uppercase;
      margin-bottom: 20px;
    }

    /* Featured event */
    .featured-card {
      background: var(--white); border-radius: 20px;
      overflow: hidden; box-shadow: var(--shadow-md);
      display: grid; grid-template-columns: 1fr 420px;
      margin-bottom: 56px; border: 1px solid var(--border);
      transition: box-shadow .3s, transform .3s;
    }
    .featured-card:hover {
      box-shadow: var(--shadow-lg);
      transform: translateY(-2px);
    }
    .featured-img {
      background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #ec4899 100%);
      display: flex; align-items: center; justify-content: center;
      min-height: 320px; position: relative; overflow: hidden;
    }
    .featured-img::before {
      content: ''; position: absolute; inset: 0;
      background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    }
    .featured-emoji { font-size: 5rem; }
    .featured-body { padding: 40px; display: flex; flex-direction: column; }
    .event-type-badge {
      display: inline-flex; align-items: center; gap: 5px;
      font-size: .75rem; font-weight: 600; letter-spacing: .05em;
      padding: 4px 12px; border-radius: 50px; width: fit-content;
      margin-bottom: 16px;
    }
    .badge-formation { background: #dbeafe; color: #1d4ed8; }
    .badge-evenement { background: #d1fae5; color: #065f46; }
    .badge-webinaire  { background: #fef3c7; color: #92400e; }
    .badge-meetup    { background: var(--accent-lt); color: var(--accent); }
    .featured-body h2 {
      font-family: 'Sora', sans-serif; font-size: 1.5rem;
      font-weight: 700; margin-bottom: 12px; line-height: 1.3;
    }
    .featured-body p { color: var(--text-soft); font-size: .95rem; line-height: 1.7; margin-bottom: 24px; }
    .event-meta { display: flex; flex-wrap: wrap; gap: 16px; margin-bottom: 28px; }
    .meta-item {
      display: flex; align-items: center; gap: 6px;
      font-size: .83rem; color: var(--text-soft);
    }
    .meta-item svg { color: var(--primary); flex-shrink: 0; }
    .btn-primary {
      display: inline-flex; align-items: center; gap: 8px;
      background: var(--primary); color: #fff;
      padding: 12px 26px; border-radius: 50px;
      font-size: .9rem; font-weight: 600; font-family: inherit;
      text-decoration: none; border: none; cursor: pointer;
      transition: background .2s, box-shadow .2s, transform .15s;
      width: fit-content;
    }
    .btn-primary:hover {
      background: #4338ca;
      box-shadow: 0 6px 20px rgba(79,70,229,.3);
      transform: translateY(-1px);
    }
    .btn-outline {
      display: inline-flex; align-items: center; gap: 8px;
      background: transparent; color: var(--primary);
      padding: 11px 24px; border-radius: 50px;
      font-size: .9rem; font-weight: 600; font-family: inherit;
      text-decoration: none; border: 2px solid var(--primary); cursor: pointer;
      transition: all .2s;
    }
    .btn-outline:hover { background: var(--primary); color: #fff; }

    /* Event grid */
    .events-grid {
      display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
      gap: 24px; margin-bottom: 48px;
    }
    .event-card {
      background: var(--white); border-radius: var(--radius);
      border: 1px solid var(--border); overflow: hidden;
      box-shadow: var(--shadow-sm);
      transition: box-shadow .25s, transform .25s;
      display: flex; flex-direction: column;
    }
    .event-card:hover { box-shadow: var(--shadow-md); transform: translateY(-3px); }
    .card-header {
      height: 140px; display: flex; align-items: center; justify-content: center;
      font-size: 3rem; position: relative;
    }
    .card-header.purple { background: linear-gradient(135deg, #ede9fe, #ddd6fe); }
    .card-header.blue   { background: linear-gradient(135deg, #dbeafe, #bfdbfe); }
    .card-header.green  { background: linear-gradient(135deg, #d1fae5, #a7f3d0); }
    .card-header.pink   { background: linear-gradient(135deg, #fce7f3, #fbcfe8); }
    .card-header.amber  { background: linear-gradient(135deg, #fef3c7, #fde68a); }
    .card-spots {
      position: absolute; top: 12px; right: 12px;
      background: rgba(255,255,255,.9); backdrop-filter: blur(4px);
      border-radius: 50px; padding: 3px 10px;
      font-size: .72rem; font-weight: 600; color: var(--text-main);
    }
    .card-spots.full { background: #fee2e2; color: #dc2626; }
    .card-body { padding: 20px; flex: 1; display: flex; flex-direction: column; }
    .card-body h3 {
      font-family: 'Sora', sans-serif; font-size: 1rem;
      font-weight: 600; margin: 10px 0 8px; line-height: 1.4;
    }
    .card-body p { font-size: .85rem; color: var(--text-soft); line-height: 1.6; flex: 1; }
    .card-footer {
      padding: 16px 20px;
      border-top: 1px solid var(--border);
      display: flex; align-items: center; justify-content: space-between;
    }
    .card-date { font-size: .8rem; color: var(--text-muted); }
    .btn-sm {
      padding: 7px 18px; border-radius: 50px;
      font-size: .8rem; font-weight: 600; font-family: inherit;
      background: var(--primary); color: #fff;
      border: none; cursor: pointer; text-decoration: none;
      transition: background .2s;
    }
    .btn-sm:hover { background: #4338ca; }
    .btn-sm.outline { background: transparent; color: var(--primary); border: 1.5px solid var(--primary); }
    .btn-sm.outline:hover { background: var(--primary); color: #fff; }

    /* Stats bar */
    .stats-bar {
      background: var(--white); border: 1px solid var(--border);
      border-radius: var(--radius); padding: 28px 32px;
      display: flex; gap: 0; margin-bottom: 48px;
    }
    .stat-item {
      flex: 1; text-align: center;
      border-right: 1px solid var(--border);
      padding: 0 20px;
    }
    .stat-item:last-child { border-right: none; }
    .stat-value {
      font-family: 'Sora', sans-serif;
      font-size: 1.75rem; font-weight: 700; color: var(--primary);
    }
    .stat-label { font-size: .8rem; color: var(--text-muted); margin-top: 4px; }

    /* Pagination */
    .pagination { display: flex; gap: 8px; justify-content: center; }
    .page-btn {
      width: 38px; height: 38px; border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      font-size: .9rem; font-weight: 500; cursor: pointer;
      border: 1.5px solid var(--border); background: var(--white);
      color: var(--text-soft); transition: all .2s;
    }
    .page-btn:hover, .page-btn.active {
      background: var(--primary); border-color: var(--primary); color: #fff;
    }

    /* Empty state */
    .empty { text-align: center; padding: 80px 20px; color: var(--text-muted); }
    .empty svg { margin-bottom: 16px; opacity: .4; }
    .empty p { font-size: .95rem; }

    @media (max-width: 768px) {
      nav { padding: 0 20px; }
      .hero { padding: 40px 20px 32px; }
      .filters-bar { margin: 24px auto 0; }
      .main { padding: 32px 20px 60px; }
      .featured-card { grid-template-columns: 1fr; }
      .featured-img { min-height: 180px; }
      .stats-bar { flex-wrap: wrap; gap: 16px; }
      .stat-item { border-right: none; border-bottom: 1px solid var(--border); padding: 0 0 16px; }
      .stat-item:last-child { border-bottom: none; }
    }
  </style>
</head>
<body>

<!-- ── NAVIGATION ── -->
<nav>
  <a href="#" class="nav-logo">Cre8<span>Connect</span></a>
  <ul class="nav-links">
    <li><a href="#">Accueil</a></li>
    <li><a href="#">Offres</a></li>
    <li><a href="#" class="active">Événements</a></li>
    <li><a href="#">Forum</a></li>
    <li><a href="#">Campagnes</a></li>
  </ul>
  <a href="#" class="nav-cta">Mon espace</a>
</nav>

<!-- ── HERO ── -->
<section class="hero">
  <div class="hero-inner">
    <div class="hero-badge">
      <svg width="12" height="12" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
      Événements & Formations
    </div>
    <h1>Découvrez les <em>événements</em><br/>de la communauté</h1>
    <p>Formations, webinaires, meetups et ateliers pour les créateurs et les marques. Rejoignez des événements qui font avancer votre carrière.</p>

    <!-- Filters inside hero -->
    <div class="filters-bar" style="margin-top: 32px; padding: 0;">
      <div class="search-wrap">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input type="text" placeholder="Rechercher un événement..."/>
      </div>
      <select class="filter-select">
        <option>Tous les types</option>
        <option>Formation</option>
        <option>Webinaire</option>
        <option>Meetup</option>
        <option>Atelier</option>
      </select>
      <select class="filter-select">
        <option>Toutes les dates</option>
        <option>Cette semaine</option>
        <option>Ce mois-ci</option>
        <option>Mois prochain</option>
      </select>
    </div>
    <div class="filters-bar" style="margin-top: 12px; padding: 0;">
      <div class="filter-chips">
        <button class="chip active">Tous</button>
        <button class="chip">Gratuit</button>
        <button class="chip">En ligne</button>
        <button class="chip">Présentiel</button>
        <button class="chip">Créateurs</button>
        <button class="chip">Marques</button>
      </div>
    </div>
  </div>
</section>

<!-- ── MAIN ── -->
<main class="main">

  <!-- Stats -->
  <div class="stats-bar">
    <div class="stat-item">
      <div class="stat-value">42</div>
      <div class="stat-label">Événements à venir</div>
    </div>
    <div class="stat-item">
      <div class="stat-value">1 200+</div>
      <div class="stat-label">Participants inscrits</div>
    </div>
    <div class="stat-item">
      <div class="stat-value">18</div>
      <div class="stat-label">Formations disponibles</div>
    </div>
    <div class="stat-item">
      <div class="stat-value">5</div>
      <div class="stat-label">Événements cette semaine</div>
    </div>
  </div>

  <!-- Featured -->
  <p class="section-label">⭐ Événement à la une</p>
  <div class="featured-card">
    <div class="featured-img">
      <span class="featured-emoji">🎯</span>
    </div>
    <div class="featured-body">
      <span class="event-type-badge badge-formation">🎓 Formation</span>
      <h2>Maîtriser le Personal Branding pour Créateurs</h2>
      <p>Une formation intensive de 2 jours pour apprendre à construire une identité forte, attirer des marques partenaires, et monétiser votre audience efficacement.</p>
      <div class="event-meta">
        <span class="meta-item">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          15–16 Mai 2025
        </span>
        <span class="meta-item">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
          Paris, France
        </span>
        <span class="meta-item">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          48 places · 12 restantes
        </span>
        <span class="meta-item">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
          249 €
        </span>
      </div>
      <div style="display:flex; gap:12px;">
        <a href="evenement_detail.php?id=1" class="btn-primary">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
          S'inscrire maintenant
        </a>
        <a href="#" class="btn-outline">Voir les détails</a>
      </div>
    </div>
  </div>

  <!-- Grid -->
  <p class="section-label">📅 Tous les événements</p>
  <div class="events-grid">

    <!-- Card 1 -->
    <div class="event-card">
      <div class="card-header blue">
        🎤
        <span class="card-spots">22 places</span>
      </div>
      <div class="card-body">
        <span class="event-type-badge badge-webinaire">🖥 Webinaire</span>
        <h3>Négocier ses contrats de collaboration avec les marques</h3>
        <p>Apprenez les bonnes pratiques pour structurer vos contrats, éviter les pièges et défendre votre valeur.</p>
      </div>
      <div class="card-footer">
        <span class="card-date">📅 22 Avril 2025 · En ligne</span>
        <a href="evenement_detail.php?id=2" class="btn-sm">Rejoindre</a>
      </div>
    </div>

    <!-- Card 2 -->
    <div class="event-card">
      <div class="card-header green">
        🌿
        <span class="card-spots">Gratuit</span>
      </div>
      <div class="card-body">
        <span class="event-type-badge badge-meetup">🤝 Meetup</span>
        <h3>Meetup Créateurs & Marques – Printemps 2025</h3>
        <p>Un espace de rencontre informel pour créateurs et marques. Networking, échanges et opportunités à saisir.</p>
      </div>
      <div class="card-footer">
        <span class="card-date">📅 3 Mai 2025 · Lyon</span>
        <a href="evenement_detail.php?id=3" class="btn-sm">Participer</a>
      </div>
    </div>

    <!-- Card 3 -->
    <div class="event-card">
      <div class="card-header pink">
        ✂️
        <span class="card-spots full">Complet</span>
      </div>
      <div class="card-body">
        <span class="event-type-badge badge-formation">🎓 Formation</span>
        <h3>Montage Vidéo Avancé pour Réseaux Sociaux</h3>
        <p>Techniques professionnelles de montage vidéo adaptées à TikTok, Instagram Reels et YouTube Shorts.</p>
      </div>
      <div class="card-footer">
        <span class="card-date">📅 28 Avril 2025 · En ligne</span>
        <a href="#" class="btn-sm outline">Liste d'attente</a>
      </div>
    </div>

    <!-- Card 4 -->
    <div class="event-card">
      <div class="card-header amber">
        📊
        <span class="card-spots">8 places</span>
      </div>
      <div class="card-body">
        <span class="event-type-badge badge-webinaire">🖥 Webinaire</span>
        <h3>Analytics & Performance : Comprendre vos données créateurs</h3>
        <p>Maîtrisez les métriques qui comptent vraiment et apprenez à présenter vos stats aux marques partenaires.</p>
      </div>
      <div class="card-footer">
        <span class="card-date">📅 10 Mai 2025 · En ligne</span>
        <a href="evenement_detail.php?id=4" class="btn-sm">S'inscrire</a>
      </div>
    </div>

    <!-- Card 5 -->
    <div class="event-card">
      <div class="card-header purple">
        🚀
        <span class="card-spots">35 places</span>
      </div>
      <div class="card-body">
        <span class="event-type-badge badge-evenement">📌 Événement</span>
        <h3>Lancement Cre8Connect : Soirée Fondateurs</h3>
        <p>Rejoignez la communauté des premiers membres de Cre8Connect pour une soirée exclusive de lancement.</p>
      </div>
      <div class="card-footer">
        <span class="card-date">📅 1 Juin 2025 · Paris</span>
        <a href="evenement_detail.php?id=5" class="btn-sm">Rejoindre</a>
      </div>
    </div>

    <!-- Card 6 -->
    <div class="event-card">
      <div class="card-header blue">
        🎨
        <span class="card-spots">Gratuit</span>
      </div>
      <div class="card-body">
        <span class="event-type-badge badge-meetup">🤝 Meetup</span>
        <h3>Atelier Identité Visuelle pour Créateurs Indépendants</h3>
        <p>Un atelier pratique pour créer une charte graphique cohérente et professionnelle pour votre marque personnelle.</p>
      </div>
      <div class="card-footer">
        <span class="card-date">📅 17 Mai 2025 · Bordeaux</span>
        <a href="evenement_detail.php?id=6" class="btn-sm">Participer</a>
      </div>
    </div>

  </div>

  <!-- Pagination -->
  <div class="pagination">
    <button class="page-btn">←</button>
    <button class="page-btn active">1</button>
    <button class="page-btn">2</button>
    <button class="page-btn">3</button>
    <button class="page-btn">→</button>
  </div>

</main>

<script>
  // Chip filter toggle
  document.querySelectorAll('.chip').forEach(chip => {
    chip.addEventListener('click', () => {
      document.querySelectorAll('.chip').forEach(c => c.classList.remove('active'));
      chip.classList.add('active');
    });
  });
</script>
</body>
</html>