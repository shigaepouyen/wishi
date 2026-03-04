<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../vendor/autoload.php';

$input = json_decode(file_get_contents('php://input'), true);
$id = $input['id'] ?? null;

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'ID manquant']);
    exit;
}

try {
    $controller = new \App\Controllers\ListController();
    echo $controller->resetReservations((int)$id);
} catch (\Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}