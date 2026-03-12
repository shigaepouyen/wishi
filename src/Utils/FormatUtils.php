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

    /**
     * Formate l'affichage double (EUR en priorité, original si différent)
     */
    public static function formatDualPrice(float $price, ?string $currency, ?float $priceEur): string {
        $currency = $currency ?? 'EUR';

        // Si c'est déjà de l'EUR ou si on n'a pas de conversion, on affiche normalement
        if ($currency === 'EUR' || !$priceEur) {
            return self::formatPrice($priceEur ?: $price, 'EUR');
        }

        // Affichage principal en EUR, rappel de l'original en petit
        $eurStr = self::formatPrice($priceEur, 'EUR');
        $origStr = self::formatPrice($price, $currency);

        return "<strong>$eurStr</strong> <i class='text-[0.7em] opacity-60 ml-1'>($origStr)</i>";
    }
}
