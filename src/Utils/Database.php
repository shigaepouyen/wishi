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
            category TEXT,
            priority INTEGER DEFAULT 1,
            position INTEGER DEFAULT 0,
            is_taken INTEGER DEFAULT 0,
            taken_by TEXT,
            FOREIGN KEY (list_id) REFERENCES lists(id) ON DELETE CASCADE
        )");

        return "La base de données Wishi est prête !";
    }
}