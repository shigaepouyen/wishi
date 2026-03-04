<?php
// On empêche toute pollution visuelle par PHP
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 0);

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Controllers\ItemController;

header('Content-Type: application/json');

// Nettoyage de tout texte parasite qui aurait pu être envoyé avant
if (ob_get_length()) ob_clean();

try {
    $controller = new ItemController();
    echo $controller->markAsTaken();
} catch (\Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}