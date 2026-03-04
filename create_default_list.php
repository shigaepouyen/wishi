<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Utils\Database;

try {
    $db = Database::getConnection();
    
    // On vérifie si la liste 1 existe déjà
    $check = $db->query("SELECT id FROM lists WHERE id = 1")->fetch();
    
    if (!$check) {
        $stmt = $db->prepare("INSERT INTO lists (id, name, slug_admin, slug_public) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            1, 
            'Ma première liste', 
            'admin-' . bin2hex(random_bytes(4)), // Un slug admin aléatoire
            'public-' . bin2hex(random_bytes(4)) // Un slug public aléatoire
        ]);
        echo "Liste par défaut créée avec succès ! 🎉";
    } else {
        echo "La liste existe déjà.";
    }
} catch (\Exception $e) {
    echo "Erreur : " . $e->getMessage();
}