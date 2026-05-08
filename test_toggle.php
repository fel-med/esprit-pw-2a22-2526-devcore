<?php
// Test script to verify the toggle functionality

session_start();
// Simulate admin session
$_SESSION['role'] = 'admin';

// Simulate request parameters
$_GET['id'] = 1;
$_GET['newStatus'] = 'inactif';

// Test the toggleStatus logic
require_once '../config.php';

$id = intval($_GET['id']);
$newStatus = $_GET['newStatus'];

echo "=== TEST TOGGLE STATUS ===\n";
echo "ID: $id\n";
echo "New Status: $newStatus\n";

// Check validation
if (!in_array($newStatus, ['actif', 'inactif'])) {
    echo "❌ Statut invalide\n";
    exit;
}

echo "✅ Validation passed\n";

// Test database connection
try {
    $db = config::getConnexion();
    
    // Check current status
    $check = $db->prepare("SELECT statut FROM utilisateur WHERE id = ?");
    $check->execute([$id]);
    $current = $check->fetch();
    
    if (!$current) {
        echo "❌ Utilisateur introuvable\n";
        exit;
    }
    
    echo "Current status: " . ($current['statut'] ?? 'NULL') . "\n";
    
    // Test update
    $sql = "UPDATE utilisateur SET statut = :statut WHERE id = :id";
    $stmt = $db->prepare($sql);
    $result = $stmt->execute([
        ':statut' => $newStatus,
        ':id' => $id
    ]);
    
    if ($result) {
        echo "✅ UPDATE query executed\n";
        echo "Rows affected: " . $stmt->rowCount() . "\n";
        
        // Verify change
        $verify = $db->prepare("SELECT statut FROM utilisateur WHERE id = ?");
        $verify->execute([$id]);
        $after = $verify->fetch();
        echo "Status after update: " . $after['statut'] . "\n";
        
        if ($after['statut'] === $newStatus) {
            echo "✅ Status updated successfully\n";
        } else {
            echo "❌ Status not updated\n";
        }
    } else {
        echo "❌ UPDATE query failed\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
