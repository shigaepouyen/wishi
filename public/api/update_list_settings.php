<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../vendor/autoload.php';

try {
    $controller = new \App\Controllers\ListController();
    echo $controller->updateSettings();
} catch (\Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}