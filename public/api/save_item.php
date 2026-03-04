<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Controllers\ItemController;

header('Content-Type: application/json');

$controller = new ItemController();
echo $controller->save();