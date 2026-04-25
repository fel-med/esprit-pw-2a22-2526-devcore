<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($forum['TitreForum']) ?> - Cre8Connect</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%);
            color: #0f172a;
            min-height: 100vh;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 16px 32px;
            border-bottom: 1px solid rgba(226, 232, 240, 0.5);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.3rem;
            font-weight: 700;
            text-decoration: none;
        }

        .logo-img {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            object-fit: cover;
            box-shadow: 0 4px 10px rgba(79, 70, 229, 0.2);
        }

        .logo-text {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 32px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #4f46e5;
            text-decoration: none;
            margin-bottom: 24px;
            font-weight: 500;
            transition: gap 0.2s;
        }

        .back-link:hover {
            gap: 12px;
        }

        .forum-header {
            background: white;
            border-radius: 28px;
            padding: 32px;
            margin-bottom: 28px;
            border: 1px solid rgba(226, 232, 240, 0.6);
            box-shadow: 0 4px 6px -4px rgba(0,0,0,0.02);
        }

        .forum-header h1 {
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 16px;
        }

        .forum-header-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 24px;
            font-size: 0.8rem;
            color: #64748b;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid #e2e8f0;
        }

        .forum-header-meta span {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .forum-sujet {
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            padding: 20px;
            border-radius: 20px;
            color: #334155;
            line-height: 1.6;
            border-left: 4px solid #4f46e5;
        }

        .messages-container {
            margin-bottom: 32px;
        }

        .message-card {
            background: white;
            border-radius: 24px;
            padding: 24px;
            margin-bottom: 16px;
            border: 1px solid rgba(226, 232, 240, 0.6);
            transition: all 0.2s;
        }

        .message-card:hover {
            border-color: #c7d2fe;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e2e8f0;
        }

        .message-author {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .author-avatar {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1rem;
            box-shadow: 0 4px 8px rgba(79, 70, 229, 0.2);
        }

        .author-info {
            display: flex;
            flex-direction: column;
        }

        .author-name {
            font-weight: 700;
            color: #0f172a;
        }

        .message-date {
            font-size: 0.7rem;
            color: #94a3b8;
        }

        .message-content {
            color: #334155;
            line-height: 1.7;
            margin-bottom: 16px;
            font-size: 0.95rem;
        }

        .btn-signaler {
            background: none;
            border: none;
            color: #94a3b8;
            font-size: 0.7rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 30px;
            transition: all 0.2s;
        }

        .btn-signaler:hover {
            background: #fef2f2;
            color: #ef4444;
        }

        .message-form {
            background: white;
            border-radius: 28px;
            padding: 28px;
            border: 1px solid rgba(226, 232, 240, 0.6);
            position: sticky;
            bottom: 20px;
        }

        .message-form h3 {
            margin-bottom: 16px;
            font-size: 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .message-form textarea {
            width: 100%;
            padding: 16px;
            border: 1.5px solid #e2e8f0;
            border-radius: 20px;
            font-family: inherit;
            font-size: 0.9rem;
            resize: vertical;
            transition: all 0.2s;
        }

        .message-form textarea:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .btn-submit {
            margin-top: 16px;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: white;
            border: none;
            padding: 12px 28px;
            border-radius: 40px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(79, 70, 229, 0.35);
        }

        .empty-messages {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 28px;
            border: 1px solid #e2e8f0;
        }

        @media (max-width: 768px) {
            .container { padding: 20px; }
            .forum-header { padding: 20px; }
            .forum-header h1 { font-size: 1.3rem; }
            .message-card { padding: 16px; }
            .message-form { padding: 20px; }
            .header { flex-direction: column; gap: 12px; padding: 16px 20px; }
        }
    </style>
</head>
<body>

<header class="header">
    <a href="/ProjetWeb/Esprit-PW-2A22-2526-Devcore/Controleur/forumC.php" class="logo">
        <img src="/ProjetWeb/Esprit-PW-2A22-2526-Devcore/Vue/public/images/logo.png" alt="Cre8Connect" class="logo-img">
        <span class="logo-text">Cre8Connect</span>
    </a>
</header>

<div class="container">
    <a href="/ProjetWeb/Esprit-PW-2A22-2526-Devcore/Controleur/forumC.php" class="back-link">
        ← Retour aux forums
    </a>

    <div class="forum-header">
        <h1>💬 <?= htmlspecialchars($forum['TitreForum']) ?></h1>
        <div class="forum-header-meta">
            <span>🎯 <?= htmlspecialchars($forum['nom_evenement']) ?></span>
            <span>👤 Créé par <?= htmlspecialchars($forum['nom_utilisateur']) ?></span>
            <span>📅 <?= date('d/m/Y', strtotime($forum['dateCreation'])) ?></span>
            <span>💬 <?= count($messages) ?> messages</span>
            <span>👁️ <?= $forum['vues'] ?? 0 ?> vues</span>
        </div>
        <div class="forum-sujet">
            <strong>📌 Sujet :</strong> <?= htmlspecialchars($forum['sujet']) ?>
        </div>
    </div>
    
    <div class="messages-container">
        <?php if (empty($messages)): ?>
            <div class="empty-messages">
                <div style="font-size: 3rem; margin-bottom: 12px;">💬</div>
                <h3>Aucun message pour le moment</h3>
                <p>Soyez le premier à participer à cette discussion !</p>
            </div>
        <?php else: ?>
            <?php foreach ($messages as $msg): ?>
            <div class="message-card">
                <div class="message-header">
                    <div class="message-author">
                        <div class="author-avatar">
                            <?= strtoupper(substr($msg['nom_utilisateur'], 0, 1)) ?>
                        </div>
                        <div class="author-info">
                            <span class="author-name"><?= htmlspecialchars($msg['nom_utilisateur']) ?></span>
                            <span class="message-date"><?= date('d/m/Y à H:i', strtotime($msg['dateMessage'])) ?></span>
                        </div>
                    </div>
                    <button class="btn-signaler" onclick="signalerMessage(<?= $msg['idMessage'] ?>)">
                        🚩 Signaler
                    </button>
                </div>
                <div class="message-content">
                    <?= nl2br(htmlspecialchars($msg['message'])) ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="message-form">
        <h3>✏️ Répondre à la discussion</h3>
        <form method="POST" action="/ProjetWeb/Esprit-PW-2A22-2526-Devcore/Controleur/forumC.php?action=ajouter_message&id=<?= $forum['idForum'] ?>">
            <textarea name="message" rows="4" placeholder="Écrivez votre message ici..." required></textarea>
            <button type="submit" class="btn-submit">📤 Publier le message</button>
        </form>
    </div>
</div>

<script>
    function signalerMessage(idMessage) {
        if (confirm('Voulez-vous signaler ce message à l\'administrateur ?')) {
            window.location.href = '/ProjetWeb/Esprit-PW-2A22-2526-Devcore/Controleur/forumC.php?action=signaler&id=' + idMessage;
        }
    }
</script>

</body>
</html>