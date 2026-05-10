<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$BASE = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');

// DEBUG: Check if messages are being passed
error_log("Number of messages: " . count($messages));
error_log("Forum data: " . print_r($forum, true));

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
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $BASE ?>/Vue/public/images/logo.png">
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

        @media (max-width: 768px) {
            .disc-page { padding: 20px 16px; }
            .forum-header-card { padding: 20px; }
            .message-card { padding: 16px; }
        }
    </style>
</head>
<body>
<div class="disc-page">

    <a href="<?= $BASE ?>/Controleur/forumC.php" class="back-link">← Retour aux forums</a>

    <div class="forum-header-card">
        <h1 class="forum-title"><?= htmlspecialchars($forum['TitreForum'] ?? 'Discussion') ?></h1>
        <div class="forum-meta-row">
            <span>🎯 <?= htmlspecialchars($forum['nom_evenement'] ?? 'Événement') ?></span>
            <span>👤 <?= htmlspecialchars($forum['nom_utilisateur'] ?? 'Admin') ?></span>
            <span>📅 <?= date('d/m/Y', strtotime($forum['dateCreation'] ?? 'now')) ?></span>
            <span>👁️ <?= (int)($forum['vues'] ?? 0) ?> vues</span>
        </div>
        <div class="forum-sujet-box">
            <strong>📌 Sujet :</strong> <?= htmlspecialchars($forum['sujet'] ?? 'Discussion générale') ?>
        </div>
    </div>

    <div id="messagesContainer">
        <?php if (empty($messages)): ?>
            <div class="empty-messages">
                <div class="empty-icon">💬</div>
                <h3>Aucun message pour le moment</h3>
                <p>Soyez le premier à participer à cette discussion !</p>
            </div>
        <?php else: ?>
            <?php foreach ($messages as $msg): ?>
            <div class="message-card" data-message-id="<?= $msg['idMessage'] ?>">
                <div class="message-header">
                    <div class="message-author-wrap">
                        <div class="author-avatar"><?= strtoupper(substr($msg['nom_utilisateur'] ?? 'U', 0, 1)) ?></div>
                        <div>
                            <div class="author-name"><?= htmlspecialchars($msg['nom_utilisateur'] ?? 'Utilisateur') ?></div>
                            <div class="message-date"><?= date('d/m/Y H:i', strtotime($msg['dateMessage'] ?? 'now')) ?></div>
                        </div>
                    </div>
                </div>
                <div class="message-content"><?= nl2br(htmlspecialchars($msg['message'] ?? '')) ?></div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php 
    $userId = $_SESSION['utilisateur']['id'] ?? $_SESSION['user_id'] ?? 0;
    if ($userId > 0): 
    ?>
        <div class="reply-form-card">
            <h3>✏️ Répondre à la discussion</h3>
            <textarea id="newMessage" rows="4" placeholder="Écrivez votre message ici..."></textarea>
            <button id="sendMessageBtn" class="btn-submit">📤 Publier le message</button>
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
            const message = document.getElementById('newMessage').value.trim();
            if (!message) {
                showToast('Veuillez écrire un message', true);
                return;
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
</script>
</body>
</html>