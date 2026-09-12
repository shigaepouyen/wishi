<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Utils\Database;
use App\Utils\Security;

try {
    $db = Database::getConnection();
    echo "--- Initialisation de la base Wishi ---\n";

    // 1. Création des tables (source de vérité unique : Database::init())
    echo Database::init() . "\n";
    echo "[OK] Tables créées avec succès.\n";

    // 2. Insertion des données de base (si la table est vide)
    $count = $db->query("SELECT COUNT(*) FROM profiles")->fetchColumn();

    if ($count == 0) {
        $profiles = [
            ['Malcolm', 'malcolm', '🦄', 'rose'],
        ];

        $stmt = $db->prepare("INSERT INTO profiles (name, slug, admin_slug, admin_pin_hash, emoji, color) VALUES (?, ?, ?, ?, ?, ?)");
        $historyStmt = $db->prepare("INSERT OR IGNORE INTO profile_slug_history (slug, profile_id) VALUES (?, ?)");
        foreach ($profiles as $p) {
            $adminSlug = bin2hex(random_bytes(16));
            $adminPinHash = Security::hashAdminPin(Security::defaultAdminPin());
            $stmt->execute([$p[0], $p[1], $adminSlug, $adminPinHash, $p[2], $p[3]]);
            $historyStmt->execute([$p[1], (int)$db->lastInsertId()]);
        }
        echo "[OK] Profil de base insere.\n";
    }

    echo "--- Migration terminée ! ---\n";

} catch (Exception $e) {
    die("[ERREUR] Impossible de migrer la base : " . $e->getMessage() . "\n");
}
