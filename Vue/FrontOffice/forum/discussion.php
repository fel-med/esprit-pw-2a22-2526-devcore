<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($forum['TitreForum']) ?> - Cre8Connect</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700;9..144,800&display=swap" rel="stylesheet">
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
            --border:         #ebebf2;
            --bg:             #f6f6fc;
            --white:          #ffffff;
            --danger:         #f43f5e;
            --danger-light:   #fff1f3;
            --success:        #0ea370;
            --success-light:  #edfaf5;
            --warning:        #f59e0b;
            --warning-light:  #fffbeb;
            --radius:         14px;
            --radius-sm:      8px;
            --nav-h:          66px;
            --card-shadow:    0 1px 3px rgba(15,14,26,0.06), 0 4px 16px rgba(91,79,255,0.06);
            --card-shadow-hover: 0 8px 32px rgba(91,79,255,0.14);
        }

        body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text-main); min-height: 100vh; }

        /* Mode sombre */
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
            --card-shadow:    0 1px 3px rgba(0,0,0,0.3), 0 4px 16px rgba(0,0,0,0.2);
            --card-shadow-hover: 0 8px 32px rgba(0,0,0,0.4);
        }

        nav { background: var(--white); border-bottom: 1px solid var(--border); padding: 0 48px; height: var(--nav-h); display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 200; box-shadow: 0 1px 0 var(--border), 0 2px 12px rgba(15,14,26,0.04); }
        .nav-logo { display: flex; align-items: center; gap: 10px; text-decoration: none; }
        .nav-logo img { width: 36px; height: 36px; object-fit: contain; border-radius: 9px; }
        .nav-logo-text { font-family: 'Fraunces', serif; font-size: 19px; font-weight: 800; color: var(--primary); letter-spacing: -0.5px; }
        .nav-links { display: flex; gap: 6px; list-style: none; }
        .nav-links a { text-decoration: none; color: var(--text-sub); font-size: 13.5px; font-weight: 600; padding: 6px 14px; border-radius: 8px; transition: all 0.18s; }
        .nav-links a:hover { background: var(--bg); color: var(--text-main); }
        .nav-links a.active { background: var(--primary-light); color: var(--primary); }
        .nav-right { display: flex; align-items: center; gap: 12px; }
        .nav-avatar { width: 36px; height: 36px; border-radius: 50%; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 14px; cursor: pointer; border: 2px solid var(--primary-border); }
        .theme-toggle-btn { background: var(--bg); border: 1px solid var(--border); border-radius: 50%; width: 36px; height: 36px; cursor: pointer; font-size: 1.2rem; display: flex; align-items: center; justify-content: center; transition: all 0.3s; }
        .theme-toggle-btn:hover { transform: scale(1.05); background: var(--primary-light); }

        .container { max-width: 900px; margin: 0 auto; padding: 32px 24px; }
        .back-link { display: inline-flex; align-items: center; gap: 8px; color: var(--primary); text-decoration: none; margin-bottom: 24px; font-weight: 600; transition: gap 0.2s; }
        .back-link:hover { gap: 12px; }

        .forum-header { background: var(--white); border-radius: var(--radius); padding: 28px; margin-bottom: 28px; border: 1px solid var(--border); box-shadow: var(--card-shadow); }
        .forum-header h1 { font-family: 'Fraunces', serif; font-size: 1.5rem; font-weight: 800; margin-bottom: 16px; }
        .forum-header-meta { display: flex; flex-wrap: wrap; gap: 24px; font-size: 0.8rem; color: var(--text-sub); margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid var(--border); }
        .forum-header-meta span { display: flex; align-items: center; gap: 8px; }
        .forum-sujet { background: var(--bg); padding: 20px; border-radius: var(--radius-sm); border-left: 4px solid var(--primary); line-height: 1.6; color: var(--text-sub); }

        .alert-warning { background: var(--warning-light); border: 1px solid #fcd34d; color: #92400e; padding: 12px 20px; border-radius: var(--radius-sm); margin-bottom: 20px; font-size: 0.85rem; font-weight: 500; display: flex; align-items: center; gap: 10px; }

        .message-card { background: var(--white); border-radius: var(--radius); padding: 24px; margin-bottom: 16px; border: 1px solid var(--border); transition: all 0.2s; }
        .message-card:hover { border-color: var(--primary-border); box-shadow: var(--card-shadow-hover); }
        .message-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid var(--border); flex-wrap: wrap; gap: 12px; }
        .message-author { display: flex; align-items: center; gap: 12px; }
        .author-avatar { width: 44px; height: 44px; background: linear-gradient(135deg, var(--primary), #7c3aed); border-radius: 14px; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 1rem; box-shadow: 0 4px 8px rgba(91,79,255,0.2); }
        .author-name { font-weight: 700; color: var(--text-main); }
        .message-date { font-size: 0.7rem; color: var(--text-sub); margin-top: 2px; }
        .message-actions { display: flex; gap: 8px; }
        .btn-modifier, .btn-signaler { background: none; border: none; font-size: 0.7rem; cursor: pointer; padding: 6px 12px; border-radius: 30px; transition: all 0.2s; font-weight: 600; }
        .btn-modifier { color: var(--primary); }
        .btn-modifier:hover { background: var(--primary-light); }
        .btn-signaler { color: var(--text-sub); }
        .btn-signaler:hover { background: var(--danger-light); color: var(--danger); }
        .message-content { color: var(--text-sub); line-height: 1.7; margin-bottom: 8px; font-size: 0.95rem; }

        .message-form { background: var(--white); border-radius: var(--radius); padding: 28px; border: 1px solid var(--border); position: sticky; bottom: 20px; box-shadow: var(--card-shadow); margin-top: 24px; }
        .message-form h3 { margin-bottom: 16px; font-size: 1rem; font-weight: 700; display: flex; align-items: center; gap: 8px; }
        .message-form textarea { width: 100%; padding: 14px; border: 1.5px solid var(--border); border-radius: var(--radius-sm); font-family: inherit; font-size: 0.9rem; resize: vertical; transition: all 0.2s; background: var(--white); }
        .message-form textarea:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-glow); }
        .btn-submit { margin-top: 16px; background: var(--primary); color: white; border: none; padding: 12px 28px; border-radius: 40px; font-weight: 700; cursor: pointer; transition: all 0.2s; }
        .btn-submit:hover { background: var(--primary-hover); transform: translateY(-1px); box-shadow: 0 4px 12px var(--primary-glow); }

        .empty-messages { text-align: center; padding: 60px 20px; background: var(--white); border-radius: var(--radius); border: 1px solid var(--border); }
        .empty-messages-icon { font-size: 3rem; margin-bottom: 12px; }
        .empty-messages h3 { font-family: 'Fraunces', serif; font-size: 1.2rem; margin-bottom: 8px; }
        .empty-messages p { color: var(--text-sub); }

        .toast { position: fixed; bottom: 20px; right: 20px; background: var(--success); color: white; padding: 12px 24px; border-radius: 50px; display: none; z-index: 1002; font-weight: 600; }
        .toast.error { background: var(--danger); }

        .modal-modification { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15,14,26,0.6); backdrop-filter: blur(4px); z-index: 1000; align-items: center; justify-content: center; }
        .modal-modification.show { display: flex; }
        .modal-modification-content { background: var(--white); border-radius: var(--radius); width: 500px; max-width: 90%; animation: slideUp 0.2s ease; }
        .modal-modification-header { display: flex; justify-content: space-between; align-items: center; padding: 20px 24px; border-bottom: 1px solid var(--border); }
        .modal-modification-header h3 { font-family: 'Fraunces', serif; font-size: 1.2rem; font-weight: 800; }
        .modal-modification-close { background: none; border: none; font-size: 24px; cursor: pointer; color: var(--text-sub); transition: color 0.2s; }
        .modal-modification-close:hover { color: var(--danger); }
        .modal-modification-body { padding: 24px; }
        .modal-modification-body textarea { width: 100%; padding: 12px; border: 1.5px solid var(--border); border-radius: var(--radius-sm); font-family: inherit; font-size: 0.9rem; resize: vertical; }
        .modal-modification-body textarea:focus { outline: none; border-color: var(--primary); }
        .modal-modification-footer { display: flex; gap: 12px; padding: 16px 24px 24px; }
        .btn-annuler { flex: 1; padding: 10px; background: var(--bg); color: var(--text-sub); border: 1px solid var(--border); border-radius: var(--radius-sm); font-weight: 700; cursor: pointer; transition: all 0.2s; }
        .btn-annuler:hover { background: var(--border); }
        .btn-enregistrer { flex: 1; padding: 10px; background: var(--primary); color: white; border: none; border-radius: var(--radius-sm); font-weight: 700; cursor: pointer; transition: all 0.2s; }
        .btn-enregistrer:hover { background: var(--primary-hover); transform: translateY(-1px); }

        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        
        @media (max-width: 768px) {
            nav { padding: 0 20px; }
            .nav-links { display: none; }
            .container { padding: 20px; }
            .forum-header { padding: 20px; }
            .forum-header h1 { font-size: 1.2rem; }
            .message-card { padding: 16px; }
            .message-header { flex-direction: column; align-items: flex-start; }
            .message-actions { align-self: flex-end; }
        }
    </style>
</head>
<body>

<nav>
    <a href="/ProjetWeb/Esprit-PW-2A22-2526-Devcore/Controleur/forumC.php" class="nav-logo">
        <img src="/ProjetWeb/Esprit-PW-2A22-2526-Devcore/Vue/public/images/logo.png" alt="Cre8Connect">
        <span class="nav-logo-text">Cre8Connect</span>
    </a>
    <ul class="nav-links">
        <li><a href="/ProjetWeb/Esprit-PW-2A22-2526-Devcore/Controleur/evenementC.php">Événements</a></li>
        <li><a href="#" class="active">Forums</a></li>
    </ul>
    <div class="nav-right">
        <button id="themeToggle" class="theme-toggle-btn">◑</button>
        <div class="nav-avatar">👤</div>
    </div>
</nav>

<div class="container">
    <a href="/ProjetWeb/Esprit-PW-2A22-2526-Devcore/Controleur/forumC.php?action=list" class="back-link">← Retour aux forums</a>

    <?php if (isset($_SESSION['warning'])): ?>
        <div class="alert-warning">⚠️ <?= $_SESSION['warning']; unset($_SESSION['warning']); ?></div>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert-warning" style="background: var(--success-light); color: var(--success); border-color: var(--success-border);">✅ <?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <div class="forum-header">
        <h1> <?= htmlspecialchars($forum['TitreForum']) ?></h1>
        <div class="forum-header-meta">
            <span>🎯 <?= htmlspecialchars($forum['nom_evenement']) ?></span>
            <span>👤 Créé par <?= htmlspecialchars($forum['nom_utilisateur']) ?></span>
            <span>📅 <?= date('d/m/Y', strtotime($forum['dateCreation'])) ?></span>
        </div>
        <div class="forum-sujet">
            <strong>📌 Sujet :</strong> <?= htmlspecialchars($forum['sujet']) ?>
        </div>
    </div>

    <div class="messages-container">
        <?php if (empty($messages)): ?>
            <div class="empty-messages">
                <div class="empty-messages-icon"></div>
                <h3>Aucun message pour le moment</h3>
                <p>Soyez le premier à participer à cette discussion !</p>
            </div>
        <?php else: ?>
            <?php foreach ($messages as $msg): ?>
            <div class="message-card" id="message-<?= $msg['idMessage'] ?>">
                <div class="message-header">
                    <div class="message-author">
                        <div class="author-avatar"><?= strtoupper(substr($msg['nom_utilisateur'], 0, 1)) ?></div>
                        <div>
                            <div class="author-name"><?= htmlspecialchars($msg['nom_utilisateur']) ?></div>
                            <div class="message-date"><?= date('d/m/Y à H:i', strtotime($msg['dateMessage'])) ?></div>
                        </div>
                    </div>
                    <div class="message-actions">
                        <button class="btn-modifier" onclick="ouvrirModalModification(<?= $msg['idMessage'] ?>, '<?= htmlspecialchars(addslashes($msg['message'])) ?>')">✏️ Modifier</button>
                        <button class="btn-signaler" onclick="signalerMessage(<?= $msg['idMessage'] ?>)">🚩 Signaler</button>
                    </div>
                </div>
                <div class="message-content" id="message-content-<?= $msg['idMessage'] ?>">
                    <?= nl2br(htmlspecialchars($msg['message'])) ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="message-form">
        <h3>✏️ Répondre à la discussion</h3>
        <form id="messageForm" method="POST" action="/ProjetWeb/Esprit-PW-2A22-2526-Devcore/Controleur/forumC.php?action=ajouter_message&id=<?= $forum['idForum'] ?>" onsubmit="return validerMessage(event)">
            <textarea name="message" id="messageText" rows="4" placeholder="Écrivez votre message ici... (max 2000 caractères)" required></textarea>
            <button type="submit" class="btn-submit">📤 Publier le message</button>
        </form>
    </div>
</div>

<div id="modalModification" class="modal-modification">
    <div class="modal-modification-content">
        <div class="modal-modification-header">
            <h3>✏️ Modifier le message</h3>
            <button class="modal-modification-close" onclick="fermerModalModification()">&times;</button>
        </div>
        <div class="modal-modification-body">
            <textarea id="editMessageText" rows="5" placeholder="Modifiez votre message..."></textarea>
        </div>
        <div class="modal-modification-footer">
            <button class="btn-annuler" onclick="fermerModalModification()">Annuler</button>
            <button class="btn-enregistrer" onclick="enregistrerModification(event)">Enregistrer</button>
        </div>
    </div>
</div>

<div id="toast" class="toast"></div>

<script>
    let currentMessageId = null;

    const motsInterdits = ['con', 'connard', 'connasse', 'pute', 'putain', 'merde', 'enculé', 'enculer', 'bâtard', 'salope', 'fuck', 'shit', 'bitch', 'whore', 'nigger', 'fag', 'idiot', 'imbécile', 'crétin', 'abruti', 'trou du cul', 'fils de pute', 'nique', 'niquer', 'salaud', 'salopard', 'garce', 'catin', 'traînée', 'pétasse', 'asshole', 'bastard', 'cock', 'dick', 'pussy', 'cunt', 'slut', 'puta'];

    function filtrerMessage(message) {
        let messageModifie = message;
        let motsTrouves = [];
        for (let mot of motsInterdits) {
            let regex = new RegExp('\\b' + mot + '\\b', 'gi');
            if (regex.test(messageModifie)) {
                motsTrouves.push(mot);
                let remplacement = '*'.repeat(mot.length);
                messageModifie = messageModifie.replace(regex, remplacement);
            }
        }
        return { message: messageModifie, mots: motsTrouves };
    }

    function validerMessage(event) {
        const textarea = document.getElementById('messageText');
        let message = textarea.value.trim();
        if (message === '') {
            showToast('Veuillez écrire un message', true);
            textarea.focus();
            event.preventDefault();
            return false;
        }
        if (message.length > 2000) {
            showToast('Message trop long (maximum 2000 caractères)', true);
            event.preventDefault();
            return false;
        }
        const filtre = filtrerMessage(message);
        if (filtre.mots.length > 0) {
            if (confirm('⚠️ Votre message contient des mots inappropriés qui seront masqués.\n\nCliquez sur OK pour continuer ou Annuler pour modifier.')) {
                textarea.value = filtre.message;
                return true;
            } else {
                event.preventDefault();
                return false;
            }
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
        if (confirm('Voulez-vous signaler ce message à l\'administrateur ?')) {
            window.location.href = '/ProjetWeb/Esprit-PW-2A22-2526-Devcore/Controleur/forumC.php?action=signaler&id=' + idMessage;
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
        if (nouveauMessage === '') {
            showToast('Le message ne peut pas être vide', true);
            return;
        }
        if (nouveauMessage.length > 2000) {
            showToast('Message trop long (maximum 2000 caractères)', true);
            return;
        }
        const filtre = filtrerMessage(nouveauMessage);
        if (filtre.mots.length > 0) {
            if (confirm('⚠️ Votre message contient des mots inappropriés qui seront masqués.\n\nCliquez sur OK pour continuer ou Annuler pour modifier.')) {
                nouveauMessage = filtre.message;
            } else {
                return;
            }
        }
        const btn = event.target;
        const originalText = btn.textContent;
        btn.disabled = true;
        btn.textContent = 'Enregistrement...';
        fetch('/ProjetWeb/Esprit-PW-2A22-2526-Devcore/Controleur/forumC.php?action=modifier_message&id=' + currentMessageId, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'message=' + encodeURIComponent(nouveauMessage)
        })
        .then(response => response.json())
        .then(data => {
            showToast(data.message, !data.success);
            if (data.success) {
                document.getElementById('message-content-' + currentMessageId).innerHTML = nouveauMessage.replace(/\n/g, '<br>');
                fermerModalModification();
            }
            btn.disabled = false;
            btn.textContent = originalText;
        })
        .catch(() => {
            showToast('Erreur de connexion', true);
            btn.disabled = false;
            btn.textContent = originalText;
        });
    }

    document.getElementById('modalModification').addEventListener('click', function(e) {
        if (e.target === this) fermerModalModification();
    });

    function initTheme() {
        const savedTheme = localStorage.getItem('theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
            document.body.classList.add('dark-mode');
            const toggleBtn = document.getElementById('themeToggle');
            if (toggleBtn) toggleBtn.textContent = '◑';
        } else {
            document.body.classList.remove('dark-mode');
            const toggleBtn = document.getElementById('themeToggle');
            if (toggleBtn) toggleBtn.textContent = '◑';
        }
    }

    function toggleTheme() {
        if (document.body.classList.contains('dark-mode')) {
            document.body.classList.remove('dark-mode');
            localStorage.setItem('theme', 'light');
            document.getElementById('themeToggle').textContent = '◑';
        } else {
            document.body.classList.add('dark-mode');
            localStorage.setItem('theme', 'dark');
            document.getElementById('themeToggle').textContent = '◑';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        initTheme();
        const toggleBtn = document.getElementById('themeToggle');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', toggleTheme);
        }
    });
</script>

</body>
</html>