<?php
namespace App\Services;

use Embed\Embed;

class ScraperService {
    
    public function getLinkData(string $url) {
        $options = [
            'http' => [
                'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36\r\n" .
                            "Accept-Language: fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7\r\n" .
                            "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8\r\n"
            ]
        ];
        $context = stream_context_create($options);

        try {
            $html = @file_get_contents($url, false, $context);
            if (!$html) throw new \Exception("Impossible de lire la page (blocage Amazon possible)");

            // On vérifie si on n'est pas tombé sur un Captcha
            if (str_contains($html, 'api-services-support@amazon.com')) {
                 throw new \Exception("Amazon a bloqué la requête (Captcha)");
            }

            $embed = new Embed();
            try {
                $info = $embed->get($url);
                $title = $info->title;
                $description = $info->description;
                $image = (string)$info->image;
            } catch (\Exception $e) {
                $title = 'Sans titre';
                $description = '';
                $image = '';
            }

            // Extraction avancée du prix
            $priceData = $this->extractAmazonPrice($html);
            
            // Conversion de devise si nécessaire
            $finalPrice = $this->convertToEur($priceData['amount'], $priceData['currency']);

            return [
                'title'       => $title ?: 'Sans titre',
                'description' => $description ?: '',
                'image'       => $this->extractAmazonImage($html) ?: $image,
                'url'         => $url,
                'price'       => [
                    'amount'   => $finalPrice > 0 ? $finalPrice : '',
                    'currency' => 'EUR'
                ]
            ];

        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function extractAmazonPrice($html) {
        // STRATÉGIE 1 : Chercher dans le bloc JSON de données d'achat (Le plus précis dans ton HTML)
        // On cherche "priceAmount":35.54
        if (preg_match('/"priceAmount":\s*([0-9.]+)/', $html, $matches)) {
            return [
                'amount' => floatval($matches[1]),
                'currency' => $this->detectCurrency($html)
            ];
        }

        // STRATÉGIE 2 : Chercher dans les inputs cachés du formulaire d'achat
        // <input type="hidden" ... value="35.54" ...>
        if (preg_match('/customerVisiblePrice\]\[amount\]" value="([^"]+)"/', $html, $matches)) {
            return [
                'amount' => floatval($matches[1]),
                'currency' => $this->detectCurrency($html)
            ];
        }

        // STRATÉGIE 3 : Fallback sur la balise visuelle a-offscreen
        if (preg_match('/<span class="a-offscreen">([^<]+)<\/span>/', $html, $matches)) {
            $priceStr = html_entity_decode($matches[1]);
            $currency = (str_contains($priceStr, '€')) ? 'EUR' : ((str_contains($priceStr, '$')) ? 'USD' : 'GBP');
            
            // Nettoyage des caractères non numériques (garde chiffres, points et virgules)
            $clean = preg_replace('/[^\d,.]/', '', $priceStr);
            $clean = str_replace(',', '.', $clean);
            
            return [
                'amount' => floatval($clean),
                'currency' => $currency
            ];
        }

        return ['amount' => 0, 'currency' => 'EUR'];
    }

    private function detectCurrency($html) {
        // On check les indices dans le code source global
        if (str_contains($html, '"currencySymbol":"€"') || str_contains($html, 'currencyCode":"EUR"')) return 'EUR';
        if (str_contains($html, '"currencySymbol":"$"') || str_contains($html, 'currencyCode":"USD"')) return 'USD';
        if (str_contains($html, '"currencySymbol":"£"') || str_contains($html, 'currencyCode":"GBP"')) return 'GBP';
        return 'EUR';
    }

    private function convertToEur($amount, $currency) {
        if ($currency === 'EUR' || $amount <= 0) return $amount;

        try {
            $url = "https://api.frankfurter.app/latest?amount={$amount}&from={$currency}&to=EUR";
            $response = @file_get_contents($url);
            if ($response) {
                $data = json_decode($response, true);
                return round($data['rates']['EUR'], 2);
            }
        } catch (\Exception $e) { return $amount; }
        
        return $amount;
    }

    private function extractAmazonImage($html) {
        if (preg_match('/data-a-dynamic-image="([^"]+)"/', $html, $matches)) {
            $json = html_entity_decode($matches[1]);
            $images = json_decode($json, true);
            if ($images) return array_key_first($images);
        }
        return null;
    }
}