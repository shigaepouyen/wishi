<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Utils\CurrencyUtils;

header('Content-Type: application/json');

echo json_encode([
    'success' => true,
    'rates' => CurrencyUtils::getRates()
]);