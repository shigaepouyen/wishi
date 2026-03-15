<?php
/**
 * Script de mise à jour de la base de données pour Wishi
 * Ajoute la colonne donor_email si elle n'existe pas.
 */

require_once __DIR__ . '/../vendor/autoload.php';

try {
    $db = \App\Utils\Database::getConnection();
    echo "Connexion à la base de données réussie.\n";

    // La colonne donor_email est maintenant automatiquement ajoutée
    // par la classe \App\Utils\Database::getConnection().

    // Vérifions tout de même manuellement
    $columns = $db->query("PRAGMA table_info(items)")->fetchAll();
    $hasEmail = false;
    foreach ($columns as $col) {
        if ($col['name'] === 'donor_email') {
            $hasEmail = true;
            break;
        }
    }

    if ($hasEmail) {
        echo "✅ La base de données est à jour (colonne 'donor_email' présente).\n";
    } else {
        echo "❌ Erreur lors de la mise à jour automatique.\n";
    }

    // Vérification de is_surprise
    $listColumns = $db->query("PRAGMA table_info(lists)")->fetchAll();
    $hasIsSurprise = false;
    foreach ($listColumns as $col) {
        if ($col['name'] === 'is_surprise') {
            $hasIsSurprise = true;
            break;
        }
    }

    if ($hasIsSurprise) {
        echo "✅ La base de données est à jour (colonne 'is_surprise' présente).\n";
    } else {
        echo "❌ Erreur : la colonne 'is_surprise' est manquante dans 'lists'.\n";
    }

} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
}
