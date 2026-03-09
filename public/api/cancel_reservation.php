<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../vendor/autoload.php';

$controller = new \App\Controllers\ItemController();
echo $controller->cancelReservation();
