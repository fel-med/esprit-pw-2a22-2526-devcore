<?php
require_once '../../../config.php';

$db = config::getConnexion();

// Vérifier combien de statuts NULL existent
$checkNull = $db->query("SELECT COUNT(*) as count FROM utilisateur WHERE statut IS NULL OR statut = ''")->fetch()['count'];

if ($checkNull > 0) {
    // Mettre à jour tous les statuts NULL à 'actif'
    $db->query("UPDATE utilisateur SET statut='actif' WHERE statut IS NULL OR statut = ''");
    
    echo "<p style='color: green; font-size: 18px;'>✅ Succès! $checkNull utilisateurs ont eu leur statut défini à 'actif'</p>";
    
    // Vérifier le résultat
    $check = $db->query("SELECT COUNT(*) as count FROM utilisateur WHERE statut='actif'")->fetch()['count'];
    echo "<p>Total des utilisateurs actifs maintenant: $check</p>";
} else {
    echo "<p style='color: blue;'>ℹ️ Pas d'utilisateurs avec statut NULL ou vide trouvés</p>";
}

echo "<p><a href='../'>← Retour au tableau de bord</a></p>";
?>
