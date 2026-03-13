<?php
namespace App\Utils;

use PDO;

class Database {
    private static $instance = null;

    public static function getConnection() {
        if (self::$instance === null) {
            // Utilisation du chemin absolu pour garantir que CLI et Web utilisent le même fichier
            $path = __DIR__ . '/../../data/database.sqlite';
            self::$instance = new PDO("sqlite:$path");
            self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            // Activer les clés étrangères pour la suppression en cascade
            self::$instance->exec('PRAGMA foreign_keys = ON;');

            // Migration automatique : assure l'existence de la colonne 'taken_by'
            try {
                $columns = self::$instance->query("PRAGMA table_info(items)")->fetchAll();
                $hasOld = false;
                $hasNew = false;
                foreach ($columns as $col) {
                    if ($col['name'] === 'taken_by_name') $hasOld = true;
                    if ($col['name'] === 'taken_by') $hasNew = true;
                }

                if (!$hasNew) {
                    if ($hasOld) {
                        self::$instance->exec("ALTER TABLE items RENAME COLUMN taken_by_name TO taken_by;");
                    } else {
                        self::$instance->exec("ALTER TABLE items ADD COLUMN taken_by TEXT;");
                    }
                }

                // Migration : assure l'existence de la colonne 'donor_email'
                $hasEmail = false;
                foreach ($columns as $col) {
                    if ($col['name'] === 'donor_email') $hasEmail = true;
                }
                if (!$hasEmail) {
                    self::$instance->exec("ALTER TABLE items ADD COLUMN donor_email TEXT;");
                }

                // Migration : assure l'existence de la colonne 'currency'
                $hasCurrency = false;
                $hasPriceEur = false;
                foreach ($columns as $col) {
                    if ($col['name'] === 'currency') $hasCurrency = true;
                    if ($col['name'] === 'price_eur') $hasPriceEur = true;
                }
                if (!$hasCurrency) {
                    self::$instance->exec("ALTER TABLE items ADD COLUMN currency TEXT DEFAULT 'EUR';");
                }
                if (!$hasPriceEur) {
                    self::$instance->exec("ALTER TABLE items ADD COLUMN price_eur REAL;");
                }

                // Migration : assure l'existence de la colonne 'is_surprise' dans 'lists'
                $listColumns = self::$instance->query("PRAGMA table_info(lists)")->fetchAll();
                $hasIsSurprise = false;
                foreach ($listColumns as $col) {
                    if ($col['name'] === 'is_surprise') $hasIsSurprise = true;
                }
                if (!$hasIsSurprise) {
                    self::$instance->exec("ALTER TABLE lists ADD COLUMN is_surprise INTEGER DEFAULT 1;");
                }
            } catch (\Exception $e) {}
        }
        return self::$instance;
    }

    public static function init() {
        $db = self::getConnection();

        // NOUVEAU : Table des profils (Zoé, Chloé, etc.)
        $db->exec("CREATE TABLE IF NOT EXISTS profiles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            slug TEXT UNIQUE NOT NULL,
            emoji TEXT,
            color TEXT DEFAULT 'indigo'
        )");

        // Table des listes (Mise à jour avec profile_id)
        $db->exec("CREATE TABLE IF NOT EXISTS lists (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            profile_id INTEGER, -- Lien vers la personne
            name TEXT NOT NULL,
            slug_admin TEXT UNIQUE NOT NULL,
            slug_public TEXT UNIQUE NOT NULL,
            is_surprise INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE
        )");

        // Table des articles (items)
        $db->exec("CREATE TABLE IF NOT EXISTS items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            list_id INTEGER NOT NULL,
            title TEXT NOT NULL,
            description TEXT,
            image_url TEXT,
            url TEXT,
            price REAL,
            currency TEXT DEFAULT 'EUR',
            price_eur REAL,
            category TEXT,
            priority INTEGER DEFAULT 1,
            position INTEGER DEFAULT 0,
            is_taken INTEGER DEFAULT 0,
            taken_by TEXT,
            donor_email TEXT,
            FOREIGN KEY (list_id) REFERENCES lists(id) ON DELETE CASCADE
        )");

        return "La base de données Wishi est prête !";
    }
}