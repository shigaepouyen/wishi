<?php
/**
 * Script de mise à jour de la base de données pour Wishi.
 * Ce script déclenche les migrations automatiques (colonnes manquantes).
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Utils\Database;

try {
    echo "--- Mise à jour de la base de données Wishi ---\n";

    // La simple connexion déclenche les migrations automatiques définies dans Database::getConnection()
    $db = Database::getConnection();
    echo "[OK] Connexion établie et migrations automatiques exécutées.\n";

    // Vérification des colonnes critiques
    $columns = $db->query("PRAGMA table_info(items)")->fetchAll();
    $requiredColumns = [
        'currency' => false,
        'price_eur' => false,
        'donor_email' => false,
        'taken_by' => false
    ];

    foreach ($columns as $col) {
        if (array_key_exists($col['name'], $requiredColumns)) {
            $requiredColumns[$col['name']] = true;
        }
    }

    $allOk = true;
    foreach ($requiredColumns as $colName => $exists) {
        if ($exists) {
            echo "✅ Colonne '$colName' présente.\n";
        } else {
            echo "❌ Colonne '$colName' MANQUANTE !\n";
            $allOk = false;
        }
    }

    if ($allOk) {
        echo "--- Base de données à jour ! ---\n";
    } else {
        echo "--- Des erreurs ont été détectées. ---\n";
    }

} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
}
