<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../layout/avatar_helper.php';

$scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
if (($pos = strpos($scriptPath, '/Vue/')) !== false) {
    $BASE = substr($scriptPath, 0, $pos);
} elseif (($pos = strpos($scriptPath, '/Controleur/')) !== false) {
    $BASE = substr($scriptPath, 0, $pos);
} else {
    $BASE = rtrim(dirname(dirname($scriptPath)), '/');
}
$BASE = rtrim($BASE, '/');
if (!isset($forum) || !isset($messages)) {
    header('Location: ' . $BASE . '/Controleur/forumC.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <?php require_once __DIR__ . '/../layout/front-theme-bootstrap.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($forum['TitreForum'] ?? 'Forum') ?> - Cre8Connect</title>
    <link rel="icon" type="image/png" sizes="16x16" href="<?= $BASE ?>/Vue/public/images/favicon-16.png">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $BASE ?>/Vue/public/images/favicon-32.png">
    <link rel="shortcut icon" type="image/png" href="<?= $BASE ?>/Vue/public/images/favicon-32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= $BASE ?>/Vue/public/images/apple-touch-icon.png">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,700;9..144,800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #5b4fff;
            --primary-light: #ece9ff;
            --primary-hover: #4438e0;
            --primary-glow: rgba(91,79,255,0.15);
            --primary-border: rgba(91,79,255,0.2);
            --text-main: #0f0e1a;
            --text-sub: #6b6f80;
            --text-dim: #a0a4b2;
            --border: #e8e8f0;
            --bg: #f4f4fb;
            --white: #ffffff;
            --danger: #f43f5e;
            --success: #0ea370;
            --radius: 16px;
            --radius-sm: 10px;
            --shadow: 0 1px 4px rgba(15,14,26,0.07), 0 4px 16px rgba(91,79,255,0.05);
            --shadow-hover: 0 8px 32px rgba(91,79,255,0.13);
        }

        html[data-theme="dark"], body.dark-mode {
            --primary: #7c6eff;
            --primary-light: #2a2648;
            --primary-hover: #8f82ff;
            --text-main: #e6edf3;
            --text-sub: #8b949e;
            --text-dim: #8b949e;
            --border: #30363d;
            --bg: #0d1117;
            --white: #161b22;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--text-main);
            min-height: 100vh;
        }

        .disc-page {
            max-width: 860px;
            margin: 0 auto;
            padding: 32px 24px 80px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 28px;
        }
        .back-link:hover { gap: 14px; }

        .forum-header-card {
            background: var(--white);
            border-radius: var(--radius);
            padding: 28px 32px;
            margin-bottom: 28px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
        }
        .forum-header-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), #7c3aed);
        }
        .forum-title {
            font-family: 'Fraunces', serif;
            font-size: 22px;
            font-weight: 800;
            margin-bottom: 16px;
            color: var(--text-main);
        }
        .forum-meta-row {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            font-size: 13px;
            color: var(--text-sub);
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 16px;
        }
        .forum-sujet-box {
            background: var(--bg);
            padding: 14px 16px;
            border-radius: var(--radius-sm);
            border-left: 4px solid var(--primary);
            font-size: 14px;
            color: var(--text-sub);
        }

        .message-card {
            background: var(--white);
            border-radius: var(--radius);
            padding: 20px 24px;
            margin-bottom: 14px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
        }
        .message-card:hover {
            border-color: var(--primary-border);
            box-shadow: var(--shadow-hover);
        }
        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 14px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border);
            flex-wrap: wrap;
            gap: 8px;
        }
        .message-author-wrap {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .author-avatar {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary), #7c3aed);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 700;
            font-size: 15px;
        }
        .author-name {
            font-weight: 700;
            font-size: 14px;
            color: var(--text-main);
        }
        .message-date {
            font-size: 12px;
            color: var(--text-dim);
        }
        .message-content {
            color: var(--text-sub);
            line-height: 1.7;
            font-size: 14px;
        }

        /* ── MESSAGE ACTIONS ── */
        .message-actions {
            display: flex;
            gap: 8px;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid var(--border);
        }
        .btn-msg-action {
            padding: 5px 14px;
            border-radius: 20px;
            border: 1px solid var(--border);
            background: transparent;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s;
            font-family: 'DM Sans', sans-serif;
        }
        .btn-msg-edit   { color: var(--primary); border-color: var(--primary-border); }
        .btn-msg-edit:hover { background: var(--primary-light); }
        .btn-msg-report { color: var(--danger); border-color: rgba(244,63,94,0.25); }
        .btn-msg-report:hover { background: rgba(244,63,94,0.08); }

        /* ── EDIT INLINE ── */
        .edit-area {
            display: none;
            margin-top: 10px;
        }
        .edit-area textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1.5px solid var(--primary);
            border-radius: var(--radius-sm);
            background: var(--bg);
            color: var(--text-main);
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            resize: vertical;
            min-height: 80px;
        }
        .edit-area-btns { display: flex; gap: 8px; margin-top: 8px; }
        .btn-save-edit {
            padding: 6px 18px;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: var(--radius-sm);
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
        }
        .btn-cancel-edit {
            padding: 6px 18px;
            background: transparent;
            color: var(--text-sub);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
        }

        .empty-messages {
            text-align: center;
            padding: 60px 24px;
            background: var(--white);
            border-radius: var(--radius);
            border: 1px solid var(--border);
        }
        .empty-messages .empty-icon { font-size: 48px; margin-bottom: 16px; }
        .empty-messages h3 { font-size: 18px; font-weight: 700; margin-bottom: 8px; }

        .reply-form-card {
            background: var(--white);
            border-radius: var(--radius);
            padding: 24px 28px;
            margin-top: 24px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
        }
        .reply-form-card h3 {
            margin-bottom: 16px;
            font-size: 16px;
            font-weight: 700;
        }
        .reply-form-card textarea {
            width: 100%;
            padding: 13px 14px;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            background: var(--bg);
            color: var(--text-main);
            resize: vertical;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            min-height: 100px;
        }
        .reply-form-card textarea:focus {
            border-color: var(--primary);
            outline: none;
        }
        .btn-submit {
            margin-top: 14px;
            padding: 11px 28px;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: var(--radius-sm);
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
        }
        .btn-submit:hover { background: var(--primary-hover); }
        .btn-submit:disabled { opacity: 0.6; cursor: not-allowed; }

        .toast-notification {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: var(--success);
            color: white;
            padding: 12px 24px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .toast-notification.error { background: var(--danger); }
        .toast-notification.show { opacity: 1; }

        /* ── LANGUAGE TOGGLE ── */
        @media (max-width: 768px) {
            .disc-page { padding: 20px 16px; }
            .forum-header-card { padding: 20px; }
            .message-card { padding: 16px; }
        }
    </style>
</head>
<body>
<div class="disc-page">

    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:28px; flex-wrap:wrap; gap:12px;">
        <a href="<?= $BASE ?>/Controleur/forumC.php" class="back-link" style="margin-bottom:0;">← <span data-i18n="back_to_forums">Retour aux forums</span></a>
    </div>

    <div class="forum-header-card">
        <h1 class="forum-title"><?= htmlspecialchars($forum['TitreForum'] ?? 'Discussion') ?></h1>
        <div class="forum-meta-row">
            <span>🎯 <?= htmlspecialchars($forum['nom_evenement'] ?? 'Événement') ?></span>
            <div style="display:inline-flex;align-items:center;gap:.35rem;">
                <?= cre8_render_avatar($forum['idUtilisateur'] ?? 0, (string)($forum['nom_utilisateur'] ?? 'Admin'), 'cre8-avatar-sm') ?>
                <?= htmlspecialchars($forum['nom_utilisateur'] ?? 'Admin') ?>
            </div>
            <span>📅 <?= date('d/m/Y', strtotime($forum['dateCreation'] ?? 'now')) ?></span>
            <span>👁️ <?= (int)($forum['vues'] ?? 0) ?> vues</span>
        </div>
        <div class="forum-sujet-box">
            <strong>📌 <span data-i18n="subject">Sujet</span> :</strong> <?= htmlspecialchars($forum['sujet'] ?? 'Discussion générale') ?>
        </div>
    </div>

    <div id="messagesContainer">
        <?php if (empty($messages)): ?>
            <div class="empty-messages">
                <div class="empty-icon">💬</div>
                <h3 data-i18n="no_messages">Aucun message pour le moment</h3>
                <p data-i18n="be_first">Soyez le premier à participer à cette discussion !</p>
            </div>
        <?php else: ?>
            <?php 
            $currentUserId = $_SESSION['utilisateur']['id'] ?? $_SESSION['user']['id'] ?? $_SESSION['id'] ?? 0;
            foreach ($messages as $msg): 
                $isOwner = ((int)$msg['idUtilisateur'] === (int)$currentUserId);
            ?>
            <div class="message-card" data-message-id="<?= $msg['idMessage'] ?>">
                <div class="message-header">
                    <div class="message-author-wrap">
                        <?= cre8_render_avatar($msg['idUtilisateur'] ?? 0, (string)($msg['nom_utilisateur'] ?? 'Utilisateur'), 'author-avatar') ?>
                        <div>
                            <div class="author-name"><?= htmlspecialchars($msg['nom_utilisateur'] ?? 'Utilisateur') ?></div>
                            <div class="message-date"><?= date('d/m/Y H:i', strtotime($msg['dateMessage'] ?? 'now')) ?></div>
                        </div>
                    </div>
                </div>
                <div class="message-content" id="content-<?= $msg['idMessage'] ?>"><?= nl2br(htmlspecialchars($msg['message'] ?? '')) ?></div>

                <div class="message-actions">
                    <?php if ($isOwner): ?>
                    <button class="btn-msg-action btn-msg-edit" onclick="toggleEdit(<?= $msg['idMessage'] ?>)">✏️ <span data-i18n="modify">Modifier</span></button>
                    <?php else: ?>
                    <button class="btn-msg-action btn-msg-report" onclick="reportMessage(<?= $msg['idMessage'] ?>)">🚩 <span data-i18n="report">Signaler</span></button>
                    <?php endif; ?>
                </div>

                <div class="edit-area" id="edit-<?= $msg['idMessage'] ?>">
                    <textarea id="edit-text-<?= $msg['idMessage'] ?>"><?= htmlspecialchars($msg['message'] ?? '') ?></textarea>
                    <div class="edit-area-btns">
                        <button class="btn-save-edit" onclick="saveEdit(<?= $msg['idMessage'] ?>)">💾 Sauvegarder</button>
                        <button class="btn-cancel-edit" onclick="toggleEdit(<?= $msg['idMessage'] ?>)">Annuler</button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php 
    $userId = $_SESSION['utilisateur']['id'] ?? $_SESSION['user']['id'] ?? $_SESSION['id'] ?? $_SESSION['user_id'] ?? 0;
    if ($userId > 0): 
    ?>
        <div class="reply-form-card">
            <h3>✏️ <span data-i18n="reply_to_discussion">Répondre à la discussion</span></h3>
            <textarea id="newMessage" rows="4" data-i18n-placeholder="write_message" placeholder="Écrivez votre message ici..."></textarea>
            <button id="sendMessageBtn" class="btn-submit"><span data-i18n="publish_message">📤 Publier le message</span></button>
        </div>
    <?php else: ?>
        <div class="reply-form-card" style="text-align:center;">
            <p>🔒 Connectez-vous pour participer à la discussion</p>
            <a href="<?= $BASE ?>/Vue/FrontOffice/utilisateur/login.php" class="btn-submit" style="display:inline-block; text-decoration:none;">Se connecter</a>
        </div>
    <?php endif; ?>
</div>

<div id="toastMsg" class="toast-notification"></div>

<script>
    (function() {
        try {
            var theme = localStorage.getItem('cre8_theme') || 'light';
            if (theme === 'dark') {
                document.documentElement.setAttribute('data-theme', 'dark');
                document.body.classList.add('dark-mode');
            }
        } catch(e) {}
    })();

    // ── Translations ─────────────────────────────────────────────────────────
    const discTranslations = {
        fr: {
            back_to_forums: 'Retour aux forums',
            subject: 'Sujet',
            no_messages: 'Aucun message pour le moment',
            be_first: 'Soyez le premier à participer à cette discussion !',
            reply_to_discussion: 'Répondre à la discussion',
            write_message: 'Écrivez votre message ici... (max 2000 caractères)',
            publish_message: '📤 Publier le message',
            empty_message: 'Veuillez écrire un message',
            connection_error: 'Erreur de connexion',
            modify: 'Modifier',
            report: 'Signaler'
        },
        en: {
            back_to_forums: 'Back to forums',
            subject: 'Subject',
            no_messages: 'No messages yet',
            be_first: 'Be the first to participate in this discussion!',
            reply_to_discussion: 'Reply to discussion',
            write_message: 'Write your message here... (max 2000 characters)',
            publish_message: '📤 Publish message',
            empty_message: 'Please write a message',
            connection_error: 'Connection error',
            modify: 'Edit',
            report: 'Report'
        }
    };

    function applyDiscTranslation(lang) {
        const safe = (lang === 'en') ? 'en' : 'fr';
        const t = discTranslations[safe];

        document.querySelectorAll('[data-i18n]').forEach(el => {
            const key = el.getAttribute('data-i18n');
            if (t[key] !== undefined) el.textContent = t[key];
        });
        document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
            const key = el.getAttribute('data-i18n-placeholder');
            if (t[key] !== undefined) el.placeholder = t[key];
        });
    }

    function readDiscussionLang() {
        try {
            const sharedLang = localStorage.getItem('cre8_front_lang');
            if (sharedLang === 'en' || sharedLang === 'fr') return sharedLang;
            const legacyLang = localStorage.getItem('cre8_lang');
            if (legacyLang === 'en' || legacyLang === 'fr') return legacyLang;
        } catch(e) {}
        return 'en';
    }

    // ── Profanity filter ─────────────────────────────────────────────────────
    const BAD_WORDS = [
        'fuck','shit','bitch','asshole','bastard','cunt','dick','pussy','cock',
        'merde','putain','connard','salope','enculé','bordel','con','bite','chier',
        'niquer','pute','fdp','tg','va te faire'
    ];

    function containsProfanity(text) {
        const lower = text.toLowerCase();
        return BAD_WORDS.some(w => lower.includes(w));
    }

    function maskProfanity(text) {
        let result = text;
        BAD_WORDS.forEach(w => {
            const regex = new RegExp(w.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'gi');
            result = result.replace(regex, '*'.repeat(w.length));
        });
        return result;
    }

    // ── Edit / Report ─────────────────────────────────────────────────────────
    function toggleEdit(id) {
        const area = document.getElementById('edit-' + id);
        area.style.display = area.style.display === 'block' ? 'none' : 'block';
        if (area.style.display === 'block') {
            document.getElementById('edit-text-' + id).focus();
        }
    }

    async function saveEdit(id) {
        const text = document.getElementById('edit-text-' + id).value.trim();
        if (!text) { showToast('Message vide', true); return; }

        let finalText = text;
        if (containsProfanity(text)) {
            if (!confirm('⚠️ Votre message contient des mots inappropriés qui seront masqués.\n\nCliquez OK pour continuer ou Annuler pour modifier.')) return;
            finalText = maskProfanity(text);
        }

        try {
            const fd = new FormData();
            fd.append('message', finalText);
            const res = await fetch('<?= $BASE ?>/Controleur/forumC.php?action=modifier_message&id=' + id, {
                method: 'POST', body: fd
            });
            const data = await res.json();
            if (data.success) {
                document.getElementById('content-' + id).innerHTML = finalText.replace(/\n/g, '<br>');
                toggleEdit(id);
                showToast('Message modifié !');
            } else {
                showToast(data.message || 'Erreur', true);
            }
        } catch(e) {
            showToast('Erreur de connexion', true);
        }
    }

    async function reportMessage(id) {
        if (!confirm('Voulez-vous signaler ce message à l\'administrateur ?')) return;
        try {
            const res = await fetch('<?= $BASE ?>/Controleur/forumC.php?action=signaler&id=' + id, {
                method: 'POST'
            });
            showToast('Message signalé. Merci !');
        } catch(e) {
            showToast('Erreur de connexion', true);
        }
    }

    function showToast(message, isError = false) {
        const toast = document.getElementById('toastMsg');
        toast.textContent = message;
        toast.className = 'toast-notification' + (isError ? ' error' : '');
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 3000);
    }

    const sendBtn = document.getElementById('sendMessageBtn');
    if (sendBtn) {
        sendBtn.addEventListener('click', async function() {
            let message = document.getElementById('newMessage').value.trim();
            if (!message) {
                showToast('Veuillez écrire un message', true);
                return;
            }

            // Profanity check
            if (containsProfanity(message)) {
                if (!confirm('⚠️ Votre message contient des mots inappropriés qui seront masqués.\n\nCliquez OK pour continuer ou Annuler pour modifier.')) {
                    return;
                }
                message = maskProfanity(message);
            }
            
            sendBtn.disabled = true;
            sendBtn.textContent = 'Envoi...';
            
            try {
                const formData = new FormData();
                formData.append('message', message);
                
                const response = await fetch('<?= $BASE ?>/Controleur/forumC.php?action=ajouter_message&id=<?= $forum['idForum'] ?>', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('newMessage').value = '';
                    showToast('Message envoyé !');
                    location.reload();
                } else {
                    showToast(data.message || 'Erreur', true);
                    sendBtn.disabled = false;
                    sendBtn.textContent = '📤 Publier le message';
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Erreur de connexion', true);
                sendBtn.disabled = false;
                sendBtn.textContent = '📤 Publier le message';
            }
        });
    }

    // ── Init ──────────────────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function() {
        applyDiscTranslation(readDiscussionLang());
    });
</script>
</body>
</html>
