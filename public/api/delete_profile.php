<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../vendor/autoload.php';

$controller = new \App\Controllers\ProfileController();
echo $controller->delete();
