<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Utils\Database;

try {
    echo Database::init();
} catch (\Exception $e) {
    echo "Erreur lors de l'initialisation : " . $e->getMessage();
}