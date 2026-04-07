<?php
require_once __DIR__ . '/../../vendor/autoload.php';
header('Content-Type: application/json');

\App\Utils\AdminAuth::start();
if ($error = \App\Utils\AdminAuth::ensureValidCsrfJson()) {
    echo $error;
    exit;
}

echo (new \App\Controllers\ItemController())->reorder();
