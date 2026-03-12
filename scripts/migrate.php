<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Utils\Database;

try {
    $db = Database::getConnection();
    echo "--- Initialisation de la base Wishi ---\n";

    // 1. Création des tables
    $sql = "
    -- Table des PROFILS
    CREATE TABLE IF NOT EXISTS profiles (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        slug TEXT UNIQUE NOT NULL,
        emoji TEXT,
        color TEXT DEFAULT 'indigo'
    );

    -- Table des LISTES
    CREATE TABLE IF NOT EXISTS lists (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        profile_id INTEGER NOT NULL,
        name TEXT NOT NULL,
        slug_admin TEXT UNIQUE NOT NULL,
        slug_public TEXT UNIQUE NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE
    );

    -- Table des SOUHAITS
    CREATE TABLE IF NOT EXISTS items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        list_id INTEGER NOT NULL,
        title TEXT NOT NULL,
        price DECIMAL(10, 2),
        currency TEXT DEFAULT 'EUR',
        price_eur REAL,
        url TEXT,
        image_url TEXT,
        description TEXT,
        category TEXT,
        priority INTEGER DEFAULT 1,
        position INTEGER DEFAULT 0,
        is_taken INTEGER DEFAULT 0,
        taken_by TEXT,
        donor_email TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (list_id) REFERENCES lists(id) ON DELETE CASCADE
    );";

    $db->exec($sql);
    echo "[OK] Tables créées avec succès.\n";

    // 2. Insertion des données de base (si la table est vide)
    $count = $db->query("SELECT COUNT(*) FROM profiles")->fetchColumn();

    if ($count == 0) {
        $profiles = [
            ['Zoé', 'zoe', '🦄', 'rose'],
        ];

        $stmt = $db->prepare("INSERT INTO profiles (name, slug, emoji, color) VALUES (?, ?, ?, ?)");
        foreach ($profiles as $p) {
            $stmt->execute($p);
        }
        echo "[OK] Profils de base (Zoé, Chloé, Papa) insérés.\n";
    }

    echo "--- Migration terminée ! ---\n";

} catch (Exception $e) {
    die("[ERREUR] Impossible de migrer la base : " . $e->getMessage() . "\n");
}