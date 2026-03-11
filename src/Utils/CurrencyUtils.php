<?php

namespace App\Utils;

use GuzzleHttp\Client;

class CurrencyUtils {
    public static function convertToEur(float $amount, string $fromCurrency): float {
        if ($fromCurrency === 'EUR') return $amount;

        try {
            $client = new Client(['timeout' => 5]);
            $response = $client->get("https://api.frankfurter.app/latest?amount=$amount&from=$fromCurrency&to=EUR");
            $data = json_decode($response->getBody(), true);
            return (float)($data['rates']['EUR'] ?? $amount);
        } catch (\Exception $e) {
            // Fallback simple si l'API est down
            $rates = ['USD' => 0.92, 'GBP' => 1.17];
            return $amount * ($rates[$fromCurrency] ?? 1);
        }
    }
}
