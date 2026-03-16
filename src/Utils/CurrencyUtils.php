<?php
namespace App\Utils;

use GuzzleHttp\Client;

class CurrencyUtils {
    private static $rates = null;
    private static $client = null;

    private static function getClient() {
        if (self::$client === null) {
            self::$client = new Client(['timeout' => 5]);
        }
        return self::$client;
    }

    /**
     * Convertit un montant vers l'Euro.
     */
    public static function convertToEur(float $amount, string $currency): float {
        $currency = strtoupper($currency);
        if ($currency === 'EUR' || $amount <= 0) {
            return $amount;
        }

        $rates = self::getRates();
        if (isset($rates[$currency])) {
            return round($amount / $rates[$currency], 2);
        }

        // Fallbacks statiques au cas où l'API est down
        $fallbacks = [
            'USD' => 1.09,
            'GBP' => 0.85,
            'CHF' => 0.95,
            'CAD' => 1.45,
            'AUD' => 1.65,
            'JPY' => 160.0,
        ];

        if (isset($fallbacks[$currency])) {
            return round($amount / $fallbacks[$currency], 2);
        }

        return $amount;
    }

    /**
     * Récupère les taux de change depuis l'API Frankfurter
     */
    public static function getRates(): array {
        if (self::$rates !== null) {
            return self::$rates;
        }

        try {
            $response = self::getClient()->get('https://api.frankfurter.app/latest?from=EUR');
            $data = json_decode($response->getBody(), true);
            if (isset($data['rates'])) {
                self::$rates = $data['rates'];
                return self::$rates;
            }
        } catch (\Exception $e) {
            // Log error if needed
        }

        return [];
    }
}