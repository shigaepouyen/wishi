<?php
require_once __DIR__ . '/../../vendor/autoload.php';
header('Content-Type: application/json');
echo (new \App\Controllers\ItemController())->reorder();