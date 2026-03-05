<?php
namespace App\Services;

use Embed\Embed;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class ScraperService {
    
    private $client;

    public function __construct() {
        $this->client = new Client([
            'timeout' => 10,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
                'Accept-Language' => 'fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7',
                'Cache-Control' => 'no-cache',
                'Pragma' => 'no-cache',
                'Upgrade-Insecure-Requests' => '1',
                'Sec-Fetch-Dest' => 'document',
                'Sec-Fetch-Mode' => 'navigate',
                'Sec-Fetch-Site' => 'none',
                'Sec-Fetch-User' => '?1',
            ],
            'curl' => [
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_AUTOREFERER => true,
            ]
        ]);
    }

    public function getLinkData(string $url) {
        try {
            $response = $this->client->get($url, [
                'headers' => [
                    'Referer' => 'https://www.google.com/',
                ],
                'http_errors' => false
            ]);

            $statusCode = $response->getStatusCode();
            $html = (string)$response->getBody();

            if ($statusCode === 403 || $statusCode === 429) {
                if (str_contains($html, 'Just a moment...') || str_contains($html, 'challenges.cloudflare.com')) {
                    throw new \Exception("Le site est protégé par Cloudflare (anti-bot). Veuillez remplir le formulaire manuellement.");
                }
                throw new \Exception("Accès refusé par le site (Erreur $statusCode).");
            }

            if (!$html) throw new \Exception("La page est vide.");

            // On vérifie si on n'est pas tombé sur un Captcha
            if (str_contains($html, 'api-services-support@amazon.com') || str_contains($html, 'captcha')) {
                 throw new \Exception("Le site a bloqué la requête (Captcha détecté)");
            }

            $embed = new Embed();
            try {
                // Pour éviter un second appel réseau bloqué, on passe directement la réponse Guzzle à Embed
                $uri = $embed->getCrawler()->createUri($url);
                $request = $embed->getCrawler()->createRequest('GET', $uri);
                $info = $embed->getExtractorFactory()->createExtractor($uri, $request, $response, $embed->getCrawler());

                $title = $info->title;
                $description = $info->description;
                $image = (string)$info->image;
            } catch (\Exception $e) {
                $title = '';
                $description = '';
                $image = '';
            }

            // Extraction structurée via JSON-LD (très fiable sur Decathlon, Apple, etc.)
            if (preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/is', $html, $matches)) {
                foreach ($matches[1] as $jsonText) {
                    $jsonData = json_decode(trim($jsonText), true);
                    if (!$jsonData) continue;

                    $items = isset($jsonData['@graph']) ? $jsonData['@graph'] : [$jsonData];
                    foreach ($items as $item) {
                        $type = $item['@type'] ?? '';
                        if (str_contains($type, 'Product') || $type === 'Offer') {
                            $title = $item['name'] ?? $title;
                            $description = $item['description'] ?? $description;
                            if (isset($item['image'])) {
                                $image = is_array($item['image']) ? ($item['image'][0] ?? $image) : $item['image'];
                            }
                        }
                    }
                }
            }

            // Fallbacks manuels via Meta Tags si nécessaire
            if (!$title && preg_match('/<title>(.*?)<\/title>/is', $html, $matches)) {
                $title = html_entity_decode(trim($matches[1]));
            }
            if (!$description) {
                if (preg_match('/<meta.*?name=["\']description["\'].*?content=["\'](.*?)["\']/is', $html, $matches) ||
                    preg_match('/<meta.*?content=["\'](.*?)["\'].*?name=["\']description["\']/is', $html, $matches)) {
                    $description = html_entity_decode(trim($matches[1]));
                }
            }
            if (!$description) {
                if (preg_match('/<meta.*?property=["\']og:description["\'].*?content=["\'](.*?)["\']/is', $html, $matches) ||
                    preg_match('/<meta.*?content=["\'](.*?)["\'].*?property=["\']og:description["\']/is', $html, $matches)) {
                    $description = html_entity_decode(trim($matches[1]));
                }
            }
            if (!$image) {
                if (preg_match('/<meta.*?property=["\']og:image["\'].*?content=["\'](.*?)["\']/is', $html, $matches) ||
                    preg_match('/<meta.*?content=["\'](.*?)["\'].*?property=["\']og:image["\']/is', $html, $matches)) {
                    $image = trim($matches[1]);
                }
            }

            // Nettoyage final
            $title = trim(str_replace(["\n", "\r"], ' ', $title));
            $description = mb_strimwidth(strip_tags(html_entity_decode($description)), 0, 500, "...");

            // Extraction avancée du prix
            $priceData = $this->extractAmazonPrice($html);

            // Si le prix n'a pas été trouvé (non-Amazon) et qu'on a du JSON-LD, on cherche dedans
            if ($priceData['amount'] <= 0 && preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/is', $html, $matches)) {
                foreach ($matches[1] as $jsonText) {
                    $jsonData = json_decode(trim($jsonText), true);
                    if (!$jsonData) continue;
                    $items = isset($jsonData['@graph']) ? $jsonData['@graph'] : [$jsonData];
                    foreach ($items as $item) {
                        if (isset($item['offers'])) {
                            $offers = is_array($item['offers']) && !isset($item['offers']['price']) ? $item['offers'] : [$item['offers']];
                            foreach ($offers as $offer) {
                                if (isset($offer['price'])) {
                                    $priceData['amount'] = floatval($offer['price']);
                                    $priceData['currency'] = $offer['priceCurrency'] ?? $priceData['currency'];
                                    break 3;
                                }
                            }
                        }
                    }
                }
            }
            
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