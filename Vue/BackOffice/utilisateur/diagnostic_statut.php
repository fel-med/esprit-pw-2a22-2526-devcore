<?php
require_once '../../../config.php';

$db = config::getConnexion();

// Vérifier les colonnes de la table
echo "<h3>🔍 Structure de la table utilisateur:</h3>";
$columns = $db->query("DESCRIBE utilisateur")->fetchAll(PDO::FETCH_ASSOC);
foreach ($columns as $col) {
    if ($col['Field'] === 'statut') {
        echo "✅ Colonne 'statut' trouvée<br>";
        echo "   Type: " . $col['Type'] . "<br>";
        echo "   Null: " . ($col['Null'] === 'YES' ? 'OUI' : 'NON') . "<br>";
        echo "   Défaut: " . ($col['Default'] ?? 'Aucun') . "<br>";
    }
}

// Vérifier les valeurs actuelles
echo "<h3>📊 Valeurs actuelles dans la BD:</h3>";
$users = $db->query("SELECT id, nom, statut FROM utilisateur LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Nom</th><th>Statut</th><th>Statut = 'actif'?</th><th>Statut = 'inactif'?</th></tr>";
foreach ($users as $user) {
    echo "<tr>";
    echo "<td>" . $user['id'] . "</td>";
    echo "<td>" . $user['nom'] . "</td>";
    echo "<td>" . ($user['statut'] === null ? '❌ NULL' : "'" . $user['statut'] . "'") . "</td>";
    echo "<td>" . ($user['statut'] === 'actif' ? '✅' : '❌') . "</td>";
    echo "<td>" . ($user['statut'] === 'inactif' ? '✅' : '❌') . "</td>";
    echo "</tr>";
}
echo "</table>";

// Compter par statut
echo "<h3>📈 Comptage par statut:</h3>";
$stats = [
    'actif' => $db->query("SELECT COUNT(*) as count FROM utilisateur WHERE statut='actif'")->fetch()['count'],
    'inactif' => $db->query("SELECT COUNT(*) as count FROM utilisateur WHERE statut='inactif'")->fetch()['count'],
    'NULL' => $db->query("SELECT COUNT(*) as count FROM utilisateur WHERE statut IS NULL")->fetch()['count'],
    'total' => $db->query("SELECT COUNT(*) as count FROM utilisateur")->fetch()['count']
];

echo "✅ Actif: " . $stats['actif'] . "<br>";
echo "❌ Inactif: " . $stats['inactif'] . "<br>";
echo "⚠️ NULL: " . $stats['NULL'] . "<br>";
echo "📊 Total: " . $stats['total'] . "<br>";

// Si y'a des NULL, les mettre à 'actif' par défaut
if ($stats['NULL'] > 0) {
    echo "<h3>⚠️ Attention: Il y a " . $stats['NULL'] . " utilisateurs avec statut NULL</h3>";
    echo "Voulez-vous les mettre à 'actif' par défaut? <a href='?fix=1'>OUI</a>";
    
    if (isset($_GET['fix']) && $_GET['fix'] === '1') {
        $db->query("UPDATE utilisateur SET statut='actif' WHERE statut IS NULL");
        echo "<p style='color: green;'>✅ Statut des utilisateurs NULL mis à jour à 'actif'</p>";
        echo "<a href='?'>Recharger</a>";
    }
}
