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
     * Affiche le prix en EUR en principal et le prix d'origine en secondaire.
     *
     * @param float|null $priceEur
     * @param float $originalPrice
     * @param string|null $originalCurrency
     * @return string (HTML)
     */
    public static function formatDualPrice(?float $priceEur, float $originalPrice, ?string $originalCurrency): string {
        $originalCurrency = $originalCurrency ?? 'EUR';

        if ($originalCurrency === 'EUR') {
            return '<span class="font-black text-slate-900">' . self::formatPrice($originalPrice, 'EUR') . '</span>';
        }

        // Si on n'a pas le prix en EUR (vieux items), on affiche le prix d'origine
        if (empty($priceEur)) {
            return '<span class="font-black text-slate-900">' . self::formatPrice($originalPrice, $originalCurrency) . '</span>';
        }

        $html = '<div class="flex flex-col">';
        $html .= '<span class="font-black text-slate-900">' . self::formatPrice($priceEur, 'EUR') . '</span>';
        $html .= '<span class="text-[10px] text-slate-400 font-bold -mt-1 italic">(' . self::formatPrice($originalPrice, $originalCurrency) . ')</span>';
        $html .= '</div>';

        return $html;
    }
}
