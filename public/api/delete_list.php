<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../vendor/autoload.php';

\App\Utils\AdminAuth::start();
if ($error = \App\Utils\AdminAuth::ensureValidCsrfJson()) {
    echo $error;
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$id = $input['id'] ?? null;

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'ID manquant']);
    exit;
}

try {
    $controller = new \App\Controllers\ListController();
    echo $controller->deleteList((int)$id);
} catch (\Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
