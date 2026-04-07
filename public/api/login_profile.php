<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../vendor/autoload.php';

\App\Utils\AdminAuth::start();
if ($error = \App\Utils\AdminAuth::ensureValidCsrfJson()) {
    echo $error;
    exit;
}

$controller = new \App\Controllers\ProfileController();
echo $controller->loginWithPin();
