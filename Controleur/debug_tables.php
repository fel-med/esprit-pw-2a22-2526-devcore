<?php
require_once __DIR__ . '/../config.php';
$pdo = config::getConnexion();

$tables = ['campagne','contrat','produit','offre','candidature','reclamation'];
foreach ($tables as $t) {
    echo "\n=== $t ===\n";
    try {
        foreach ($pdo->query("DESCRIBE $t") as $r) {
            echo $r['Field'] . "\n";
        }
    } catch (Exception $e) { echo "ERROR: " . $e->getMessage() . "\n"; }
}
