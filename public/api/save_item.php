<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Controllers\ItemController;

header('Content-Type: application/json');

\App\Utils\AdminAuth::start();
if ($error = \App\Utils\AdminAuth::ensureValidCsrfJson()) {
    echo $error;
    exit;
}

$controller = new ItemController();
echo $controller->save();
