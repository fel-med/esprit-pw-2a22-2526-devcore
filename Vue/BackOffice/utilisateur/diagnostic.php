<?php
session_start();
require_once '../../../config.php';

// Couleurs pour affichage
$check = '✅';
$cross = '❌';
$warning = '⚠️';

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Diagnostic - Crea8Connect Features</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f5f5; padding: 30px; }
        .feature { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #9B5DE0; }
        .feature.ok { border-left-color: #28a745; }
        .feature.missing { border-left-color: #dc3545; }
        .feature.partial { border-left-color: #ffc107; }
        h1 { color: #9B5DE0; margin-bottom: 30px; }
        h3 { margin-top: 15px; margin-bottom: 10px; }
        .code-block { background: #f8f9fa; padding: 10px; border-radius: 4px; font-family: monospace; font-size: 12px; margin: 5px 0; }
    </style>
<link rel="icon" type="image/png" sizes="32x32" href="../../public/images/logo.png">
<link rel="shortcut icon" type="image/png" href="../../public/images/logo.png">
<link rel="apple-touch-icon" href="../../public/images/logo.png">
</head>
<body>
    <div class="container">
        <h1>🔍 Diagnostic des Fonctionnalités</h1>

        <!-- Feature 1: User Status Management -->
        <div class="feature">
            <h3>1️⃣ Gestion du Statut Utilisateur (Admin - Suspend/Activate)</h3>
            <div class="code-block">Fichier: Vue/BackOffice/utilisateur/index.php</div>
            <div class="code-block">Fichier: Controleur/utilisateurC.php</div>
            <?php
            $db = config::getConnexion();
            
            // Check if table has statut column
            $query = $db->query("DESCRIBE utilisateur")->fetchAll(PDO::FETCH_ASSOC);
            $hasStatut = false;
            foreach ($query as $col) {
                if ($col['Field'] === 'statut') {
                    $hasStatut = true;
                    break;
                }
            }
            
            echo $hasStatut ? "$check DB Column EXISTS" : "$cross DB Column MISSING";
            ?>
            <br>
            <div class="code-block">UPDATE utilisateur SET statut = 'actif'|'inactif'</div>
            <p><small>Status: Partiellement implémenté - la colonne existe mais le bouton toggle UI n'est pas visible sur la page admin.</small></p>
        </div>

        <!-- Feature 2: Normal Login - Inactive Account Popup -->
        <div class="feature">
            <h3>2️⃣ Login Normal - Popup Compte Suspendu</h3>
            <div class="code-block">Fichier: Vue/FrontOffice/utilisateur/login.php</div>
            <?php
            $loginContent = file_get_contents('../login.php');
            $hasPopup = strpos($loginContent, 'Compte suspendu') !== false || strpos($loginContent, 'compte inactif') !== false;
            echo $hasPopup ? "$check Message d'erreur présent" : "$cross Message d'erreur MANQUANT";
            ?>
            <br>
            <p><small>Status: ❌ NON IMPLÉMENTÉ - Le contrôleur doit envoyer un message d'erreur si statut != 'actif'</small></p>
        </div>

        <!-- Feature 3: Facial Login - Inactive Account Popup -->
        <div class="feature">
            <h3>3️⃣ Facial Login - Popup Compte Suspendu</h3>
            <div class="code-block">Fichier: Vue/FrontOffice/utilisateur/login_face.php</div>
            <?php
            $faceLoginContent = file_get_contents('../login_face.php');
            $hasStatutCheck = strpos($faceLoginContent, "statut") !== false;
            echo $hasStatutCheck ? "$check Vérification du statut" : "$cross Vérification du statut MANQUANTE";
            ?>
            <br>
            <p><small>Status: ❌ PARTIELLEMENT - Le fichier doit vérifier le statut avant de rediriger</small></p>
        </div>

        <!-- Feature 4: Email Notification on Response -->
        <div class="feature">
            <h3>4️⃣ Email Notification - Admin Répond à Réclamation</h3>
            <div class="code-block">Fichier: Controleur/utilisateurC.php (sendReclamationResponseNotification)</div>
            <div class="code-block">Fichier: Controleur/reponseC.php (ajouterReponse)</div>
            <?php
            $utilizContent = file_get_contents('../../Controleur/utilisateurC.php');
            $hasEmailMethod = strpos($utilizContent, 'sendReclamationResponseNotification') !== false;
            echo $hasEmailMethod ? "$check Email method EXISTS" : "$cross Email method MISSING";
            ?>
            <br>
            <?php
            $reponseContent = file_get_contents('../../Controleur/reponseC.php');
            $hasEmailCall = strpos($reponseContent, 'sendReclamationResponseNotification') !== false;
            echo $hasEmailCall ? "$check Email call dans ajouterReponse" : "$cross Email call MISSING";
            ?>
            <br>
            <p><small>Status: ✅ IMPLÉMENTÉ - Email envoyé quand admin répond</small></p>
        </div>

        <!-- Feature 5: Response Toast Notification -->
        <div class="feature">
            <h3>5️⃣ Notification Toast - Réponse Envoyée</h3>
            <div class="code-block">Fichier: Vue/BackOffice/utilisateur/reclamations.php</div>
            <div class="code-block">Fichier: Vue/BackOffice/utilisateur/ajouterReponse.php</div>
            <?php
            $reclContent = file_get_contents('reclamations.php');
            $hasToast = strpos($reclContent, 'reponse_envoyee') !== false || strpos($reclContent, 'alert-success') !== false;
            echo $hasToast ? "$check Toast notification EXISTS" : "$cross Toast notification MISSING";
            ?>
            <br>
            <p><small>Status: ✅ IMPLÉMENTÉ - Toast vert qui s'affiche 5 secondes</small></p>
        </div>

        <!-- Feature 6: User Statistics -->
        <div class="feature">
            <h3>6️⃣ Statistiques Utilisateurs par Statut</h3>
            <div class="code-block">Fichier: Controleur/utilisateurC.php (getStatistiquesUtilisateurs)</div>
            <?php
            $hasStatsMethod = strpos($utilizContent, 'getStatistiquesUtilisateurs') !== false;
            echo $hasStatsMethod ? "$check Stats method EXISTS" : "$cross Stats method MISSING";
            ?>
            <br>
            <p><small>Status: ✅ IMPLÉMENTÉ - Compte actif/inactif disponible</small></p>
        </div>

        <!-- Summary -->
        <div style="background: #f0f0f0; padding: 20px; margin-top: 30px; border-radius: 8px;">
            <h3>📊 Résumé</h3>
            <p>✅ 3 fonctionnalités complètement implémentées</p>
            <p>❌ 2 fonctionnalités manquantes ou partielles</p>
            <p>⚠️ 1 fonctionnalité partiellement implémentée</p>
        </div>

    </div>
</body>
</html>
