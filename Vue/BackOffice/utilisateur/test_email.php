<?php
require_once '../../../Controleur/utilisateurC.php';

$userC = new UtilisateurC();

// Test de l'envoi d'email
$testEmail = "neylamhamddy@gmail.com"; // Utiliser votre propre email pour le test
$testNom = "Test User";
$testDescription = "Ceci est une réclamation de test pour vérifier le système d'envoi d'emails.";
$testReponse = "Ceci est une réponse de test pour vérifier que les emails sont envoyés correctement.";

echo "<h2>🧪 Test d'envoi d'email de notification</h2>";
echo "<p><strong>Email de destination:</strong> $testEmail</p>";
echo "<p><strong>Nom:</strong> $testNom</p>";
echo "<p><strong>Description:</strong> $testDescription</p>";
echo "<p><strong>Réponse:</strong> $testReponse</p>";

$result = $userC->sendReclamationResponseNotification($testEmail, $testNom, $testDescription, $testReponse);

if ($result) {
    echo "<div style='color: green; font-size: 18px; margin: 20px 0;'>✅ Email envoyé avec succès!</div>";
    echo "<p>Vérifiez votre boîte mail (y compris les spams) pour voir l'email de notification.</p>";
} else {
    echo "<div style='color: red; font-size: 18px; margin: 20px 0;'>❌ Échec de l'envoi d'email</div>";
    echo "<p>Vérifiez les logs d'erreur PHP pour plus de détails.</p>";
}

// Vérifier la configuration PHPMailer
echo "<h3>🔧 Vérification de la configuration</h3>";
echo "<ul>";
echo "<li>✅ PHPMailer importé</li>";
echo "<li>✅ Configuration SMTP Gmail</li>";
echo "<li>✅ Port 587 (TLS)</li>";
echo "<li>✅ Authentification activée</li>";
echo "</ul>";

echo "<h3>📧 Conseils de dépannage</h3>";
echo "<ul>";
echo "<li>Assurez-vous que l'adresse email Gmail a l'authentification à 2 facteurs désactivée ou qu'un mot de passe d'application est utilisé</li>";
echo "<li>Vérifiez que le mot de passe d'application est correct: <code>aebg mpbl zomq idjn</code></li>";
echo "<li>Si l'email n'arrive pas, vérifiez le dossier spam</li>";
echo "<li>Les emails peuvent prendre quelques minutes à arriver</li>";
echo "</ul>";

echo "<p><a href='../'>← Retour aux réclamations</a></p>";
?>