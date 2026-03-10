<?php

namespace App\Utils;

class FormatUtils {
    /**
     * Retourne le symbole correspondant au code de devise.
     *
     * @param string|null $currencyCode
     * @return string
     */
    public static function getCurrencySymbol(?string $currencyCode): string {
        return match($currencyCode ?? 'EUR') {
            'USD' => '$',
            'GBP' => '£',
            default => '€'
        };
    }

    /**
     * Formate un prix avec son symbole de devise.
     *
     * @param float $price
     * @param string|null $currencyCode
     * @return string
     */
    public static function formatPrice(float $price, ?string $currencyCode): string {
        $formatted = number_format($price, 2, ',', ' ');
        $symbol = self::getCurrencySymbol($currencyCode);
        return "$formatted $symbol";
    }
}
