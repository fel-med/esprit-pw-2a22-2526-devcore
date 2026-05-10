<?php
if (!isset($forum) || !isset($messages)) {
    $BASE = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');
    header('Location: ' . $BASE . '/Controleur/forumC.php');
    exit;
}
$BASE = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($forum['TitreForum']) ?> - Cre8Connect</title>
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

        /* ── CONTAINER ── */
        .container { max-width: 900px; margin: 0 auto; padding: 32px 24px 80px; }

        .back-link { display: inline-flex; align-items: center; gap: 8px; color: var(--primary); text-decoration: none; margin-bottom: 24px; font-weight: 600; font-size: 13.5px; transition: gap 0.18s; }
        .back-link:hover { gap: 12px; }

        /* ── FORUM HEADER ── */
        .forum-header {
            background: var(--white);
            border-radius: var(--radius);
            padding: 28px;
            margin-bottom: 24px;
            border: 1px solid var(--border);
            box-shadow: var(--card-shadow);
            position: relative;
            overflow: hidden;
        }
        .forum-header::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), #7c3aed);
        }
        .forum-header h1 { font-family: 'Fraunces', serif; font-size: 1.5rem; font-weight: 800; margin-bottom: 16px; color: var(--text-main); letter-spacing: -0.3px; }
        .forum-header-meta { display: flex; flex-wrap: wrap; gap: 20px; font-size: 0.78rem; color: var(--text-sub); margin-bottom: 18px; padding-bottom: 16px; border-bottom: 1px solid var(--border); }
        .forum-header-meta span { display: flex; align-items: center; gap: 6px; }
        .forum-sujet { background: var(--bg); padding: 16px 18px; border-radius: var(--radius-sm); border-left: 4px solid var(--primary); line-height: 1.6; color: var(--text-sub); font-size: 0.88rem; }

        /* ── MESSAGE CARDS ── */
        .message-card {
            background: var(--white);
            border-radius: var(--radius);
            padding: 22px 24px;
            margin-bottom: 14px;
            border: 1px solid var(--border);
            transition: border-color .2s, box-shadow .2s;
            box-shadow: var(--card-shadow);
        }
        .message-card:hover { border-color: var(--primary-border); box-shadow: var(--card-shadow-hover); }
        .message-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid var(--border); flex-wrap: wrap; gap: 10px; }
        .message-author { display: flex; align-items: center; gap: 12px; }
        .author-avatar { width: 40px; height: 40px; background: linear-gradient(135deg, var(--primary), #7c3aed); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 0.95rem; flex-shrink: 0; }
        .author-name { font-weight: 700; color: var(--text-main); font-size: 0.9rem; }
        .message-date { font-size: 0.7rem; color: var(--text-dim); margin-top: 2px; }
        .message-actions { display: flex; gap: 6px; }
        .btn-modifier, .btn-signaler { background: none; border: none; font-size: 0.72rem; cursor: pointer; padding: 5px 11px; border-radius: 20px; transition: all 0.18s; font-weight: 600; font-family: 'DM Sans', sans-serif; }
        .btn-modifier { color: var(--primary); }
        .btn-modifier:hover { background: var(--primary-light); }
        .btn-signaler { color: var(--text-sub); }
        .btn-signaler:hover { background: var(--danger-light); color: var(--danger); }
        .message-content { color: var(--text-sub); line-height: 1.7; font-size: 0.92rem; }

        /* ── REPLY FORM ── */
        .message-form {
            background: var(--white);
            border-radius: var(--radius);
            padding: 24px 28px;
            border: 1px solid var(--border);
            box-shadow: var(--card-shadow);
            margin-top: 20px;
        }
        .message-form h3 { margin-bottom: 14px; font-size: 0.95rem; font-weight: 700; display: flex; align-items: center; gap: 8px; color: var(--text-main); }
        .message-form textarea {
            width: 100%; padding: 13px 14px;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            font-family: 'DM Sans', sans-serif;
            font-size: 0.88rem;
            resize: vertical;
            background: var(--bg);
            color: var(--text-main);
            transition: border-color .2s, box-shadow .2s;
            outline: none;
        }
        .message-form textarea:focus { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-glow); background: var(--white); }
        .btn-submit {
            margin-top: 14px;
            background: var(--primary);
            color: white;
            border: none;
            padding: 11px 28px;
            border-radius: 20px;
            font-weight: 700;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            transition: all 0.18s;
            box-shadow: 0 2px 8px var(--primary-glow);
        }
        .btn-submit:hover { background: var(--primary-hover); transform: translateY(-1px); }

        /* ── EMPTY STATE ── */
        .empty-messages { text-align: center; padding: 60px 20px; background: var(--white); border-radius: var(--radius); border: 1px solid var(--border); }
        .empty-messages-icon { font-size: 3rem; margin-bottom: 12px; }
        .empty-messages h3 { font-family: 'Fraunces', serif; font-size: 1.2rem; margin-bottom: 8px; }
        .empty-messages p { color: var(--text-sub); }

        /* ── TOAST ── */
        .toast { position: fixed; bottom: 28px; right: 28px; background: var(--success); color: white; padding: 12px 22px; border-radius: 20px; display: none; z-index: 1003; font-weight: 600; font-size: 13.5px; box-shadow: 0 4px 14px rgba(0,0,0,0.13); }
        .toast.error { background: var(--danger); }

        /* ── MODAL ── */
        .modal-modification { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15,14,26,0.45); backdrop-filter: blur(6px); z-index: 1000; align-items: center; justify-content: center; }
        .modal-modification.show { display: flex; animation: fadeIn 0.25s ease; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .modal-modification-content { background: var(--white); border-radius: var(--radius); width: 500px; max-width: 90%; border: 1px solid var(--border); box-shadow: 0 20px 60px rgba(15,14,26,0.18); animation: slideUp 0.25s ease; }
        .modal-modification-header { display: flex; justify-content: space-between; align-items: center; padding: 20px 24px; border-bottom: 1px solid var(--border); }
        .modal-modification-header h3 { font-family: 'Fraunces', serif; font-size: 1.1rem; font-weight: 800; }
        .modal-modification-close { background: none; border: none; font-size: 22px; cursor: pointer; color: var(--text-sub); width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: all .18s; }
        .modal-modification-close:hover { background: var(--danger-light); color: var(--danger); }
        .modal-modification-body { padding: 20px 24px; }
        .modal-modification-body textarea { width: 100%; padding: 12px 14px; border: 1.5px solid var(--border); border-radius: var(--radius-sm); font-family: 'DM Sans', sans-serif; font-size: 0.88rem; resize: vertical; background: var(--bg); color: var(--text-main); outline: none; }
        .modal-modification-body textarea:focus { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-glow); }
        .modal-modification-footer { display: flex; gap: 10px; padding: 16px 24px 20px; }
        .btn-annuler { flex: 1; padding: 10px; background: transparent; color: var(--text-sub); border: 1.5px solid var(--border); border-radius: var(--radius-sm); font-weight: 600; cursor: pointer; font-family: 'DM Sans', sans-serif; transition: all .18s; }
        .btn-annuler:hover { border-color: var(--text-sub); color: var(--text-main); }
        .btn-enregistrer { flex: 1; padding: 10px; background: var(--primary); color: white; border: none; border-radius: var(--radius-sm); font-weight: 700; cursor: pointer; font-family: 'DM Sans', sans-serif; transition: background .18s; box-shadow: 0 2px 8px var(--primary-glow); }
        .btn-enregistrer:hover { background: var(--primary-hover); }

        /* ── RESPONSIVE ── */
        @media (max-width: 768px) { nav { padding: 0 20px; } .nav-links { display: none; } .container { padding: 20px 16px; } .forum-header { padding: 20px; } .forum-header h1 { font-size: 1.2rem; } .message-card { padding: 16px; } .message-header { flex-direction: column; align-items: flex-start; } .message-actions { align-self: flex-end; } }
    </style>
</head>
<body>

<nav>
    <a href="<?= $BASE ?>/Controleur/forumC.php" class="nav-logo">
        <img src="<?= $BASE ?>/Vue/public/images/logo.png" alt="Cre8Connect">
        <span class="nav-logo-text">Cre8Connect</span>
    </a>
    <ul class="nav-links">
        <li><a href="<?= $BASE ?>/Controleur/evenementC.php" data-i18n="events">Événements</a></li>
        <li><a href="<?= $BASE ?>/Controleur/forumC.php" class="active" data-i18n="forum">Forums</a></li>
    </ul>
    <div class="nav-right">
        <button id="languageToggle" class="lang-toggle-btn" onclick="toggleLanguage()">🇫🇷 FR</button>
        <button id="themeToggle" class="theme-toggle-btn">◑ Dark mode</button>
        <div class="nav-avatar">👤</div>
    </div>
</nav>

<div class="container">
    <a href="<?= $BASE ?>/Controleur/forumC.php?action=list" class="back-link" data-i18n="back_to_forums">← Retour aux forums</a>

    <div class="forum-header">
        <h1><?= htmlspecialchars($forum['TitreForum']) ?></h1>
        <div class="forum-header-meta">
            <span>🎯 <?= htmlspecialchars($forum['nom_evenement']) ?></span>
            <span>👤 <span data-i18n="created_by">Créé par</span> <?= htmlspecialchars($forum['nom_utilisateur']) ?></span>
            <span>📅 <?= date('d/m/Y', strtotime($forum['dateCreation'])) ?></span>
        </div>
        <div class="forum-sujet"><strong data-i18n="subject">📌 Sujet :</strong> <?= htmlspecialchars($forum['sujet']) ?></div>
    </div>

    <div class="messages-container">
        <?php if (empty($messages)): ?>
            <div class="empty-messages"><div class="empty-messages-icon"></div><h3 data-i18n="no_messages">Aucun message pour le moment</h3><p data-i18n="be_first">Soyez le premier à participer à cette discussion !</p></div>
        <?php else: ?>
            <?php foreach ($messages as $msg): ?>
            <div class="message-card" id="message-<?= $msg['idMessage'] ?>">
                <div class="message-header">
                    <div class="message-author">
                        <div class="author-avatar"><?= strtoupper(substr($msg['nom_utilisateur'], 0, 1)) ?></div>
                        <div><div class="author-name"><?= htmlspecialchars($msg['nom_utilisateur']) ?></div><div class="message-date"><?= date('d/m/Y à H:i', strtotime($msg['dateMessage'])) ?></div></div>
                    </div>
                    <div class="message-actions">
                        <button class="btn-modifier" onclick="ouvrirModalModification(<?= $msg['idMessage'] ?>, '<?= htmlspecialchars(addslashes($msg['message'])) ?>')" data-i18n="modify">✏️ Modifier</button>
                        <button class="btn-signaler" onclick="signalerMessage(<?= $msg['idMessage'] ?>)" data-i18n="report">🚩 Signaler</button>
                    </div>
                </div>
                <div class="message-content" id="message-content-<?= $msg['idMessage'] ?>"><?= nl2br(htmlspecialchars($msg['message'])) ?></div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="message-form">
        <h3>✏️ <span data-i18n="reply_to_discussion">Répondre à la discussion</span></h3>
        <form id="messageForm" method="POST" action="<?= $BASE ?>/Controleur/forumC.php?action=ajouter_message&id=<?= $forum['idForum'] ?>" onsubmit="return validerMessage(event)">
            <textarea name="message" id="messageText" rows="4" data-i18n-placeholder="write_message" placeholder="Écrivez votre message ici... (max 2000 caractères)" required></textarea>
            <button type="submit" class="btn-submit" data-i18n="publish_message">📤 Publier le message</button>
        </form>
    </div>
</div>

<div id="modalModification" class="modal-modification">
    <div class="modal-modification-content">
        <div class="modal-modification-header"><h3 data-i18n="modify">✏️ Modifier le message</h3><button class="modal-modification-close" onclick="fermerModalModification()">&times;</button></div>
        <div class="modal-modification-body"><textarea id="editMessageText" rows="5" data-i18n-placeholder="write_message" placeholder="Modifiez votre message..."></textarea></div>
        <div class="modal-modification-footer"><button class="btn-annuler" onclick="fermerModalModification()" data-i18n="cancel">Annuler</button><button class="btn-enregistrer" onclick="enregistrerModification(event)" data-i18n="modify">Enregistrer</button></div>
    </div>
</div>

<div id="toast" class="toast"></div>

<script>
    let currentMessageId = null;
    const motsInterdits = ['con', 'connard', 'connasse', 'pute', 'putain', 'merde', 'enculé', 'enculer', 'bâtard', 'salope', 'fuck', 'shit', 'bitch', 'whore', 'nigger', 'fag', 'idiot', 'imbécile', 'crétin', 'abruti'];

    function filtrerMessage(message) {
        let messageModifie = message;
        for (let mot of motsInterdits) {
            let regex = new RegExp('\\b' + mot + '\\b', 'gi');
            if (regex.test(messageModifie)) {
                let remplacement = '*'.repeat(mot.length);
                messageModifie = messageModifie.replace(regex, remplacement);
            }
        }
        return messageModifie;
    }

    function validerMessage(event) {
        const textarea = document.getElementById('messageText');
        let message = textarea.value.trim();
        if (message === '') { showToast(translations[currentLang]['empty_message'], true); textarea.focus(); event.preventDefault(); return false; }
        if (message.length > 2000) { showToast(translations[currentLang]['message_too_long'], true); event.preventDefault(); return false; }
        const filtre = filtrerMessage(message);
        if (filtre !== message) {
            if (confirm(translations[currentLang]['inappropriate_warning'])) { textarea.value = filtre; return true; }
            else { event.preventDefault(); return false; }
        }
        return true;
    }

    function showToast(message, isError = false) {
        const toast = document.getElementById('toast');
        toast.textContent = message;
        toast.className = 'toast' + (isError ? ' error' : '');
        toast.style.display = 'block';
        setTimeout(() => { toast.style.display = 'none'; }, 3000);
    }

    function signalerMessage(idMessage) {
        if (confirm(translations[currentLang]['message_report'])) {
            window.location.href = '<?= $BASE ?>/Controleur/forumC.php?action=signaler&id=' + idMessage;
        }
    }

    function ouvrirModalModification(id, message) {
        currentMessageId = id;
        document.getElementById('editMessageText').value = message;
        document.getElementById('modalModification').classList.add('show');
    }

    function fermerModalModification() {
        document.getElementById('modalModification').classList.remove('show');
        currentMessageId = null;
    }

    function enregistrerModification(event) {
        let nouveauMessage = document.getElementById('editMessageText').value.trim();
        if (nouveauMessage === '') { showToast(translations[currentLang]['empty_message'], true); return; }
        if (nouveauMessage.length > 2000) { showToast(translations[currentLang]['message_too_long'], true); return; }
        const filtre = filtrerMessage(nouveauMessage);
        if (filtre !== nouveauMessage && !confirm(translations[currentLang]['inappropriate_warning'])) { return; }
        const btn = event.target;
        const originalText = btn.textContent;
        btn.disabled = true; btn.textContent = translations[currentLang]['modify'] + '...';
        fetch('<?= $BASE ?>/Controleur/forumC.php?action=modifier_message&id=' + currentMessageId, {
            method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'message=' + encodeURIComponent(filtre)
        }).then(response => response.json()).then(data => {
            showToast(data.message, !data.success);
            if (data.success) { document.getElementById('message-content-' + currentMessageId).innerHTML = filtre.replace(/\n/g, '<br>'); fermerModalModification(); }
            btn.disabled = false; btn.textContent = originalText;
        }).catch(() => { showToast(translations[currentLang]['connection_error'], true); btn.disabled = false; btn.textContent = originalText; });
    }

    document.getElementById('modalModification').addEventListener('click', function(e) { if (e.target === this) fermerModalModification(); });

    function initTheme() {
        const saved = localStorage.getItem('theme');
        if (saved === 'dark') { document.body.classList.add('dark-mode'); document.getElementById('themeToggle').textContent = '◑ Light mode'; }
        else { document.body.classList.remove('dark-mode'); document.getElementById('themeToggle').textContent = '◑ Dark mode'; }
    }
    function toggleTheme() {
        if (document.body.classList.contains('dark-mode')) { document.body.classList.remove('dark-mode'); localStorage.setItem('theme', 'light'); document.getElementById('themeToggle').textContent = '◑ Dark mode'; }
        else { document.body.classList.add('dark-mode'); localStorage.setItem('theme', 'dark'); document.getElementById('themeToggle').textContent = '◑ Light mode'; }
    }
    document.addEventListener('DOMContentLoaded', function() { initTheme(); document.getElementById('themeToggle').addEventListener('click', toggleTheme); initLanguage(); });
</script>
</body>
</html>