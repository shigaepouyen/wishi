<?php
namespace App\Services;

use Embed\Embed;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\RequestException;

class ScraperService {
    
    protected $client;

    public function __construct() {
        $this->client = new Client([
            'timeout' => 20,
            'cookies' => true,
            'allow_redirects' => [
                'max' => 10,
            ],
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
                'Accept-Language' => 'fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7',
                'Cache-Control' => 'no-cache',
                'Pragma' => 'no-cache',
                'Upgrade-Insecure-Requests' => '1',
            ],
            'curl' => [
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_AUTOREFERER => true,
            ]
        ]);
    }

    public function getLinkData(string $url) {
        try {
            // Pour AliExpress on évite le Referer google qui peut déclencher des redirections infinies
            $headers = [];
            if (!str_contains($url, 'aliexpress.com')) {
                $headers['Referer'] = 'https://www.google.com/';
            }

            $response = $this->client->get($url, [
                'headers' => $headers,
                'http_errors' => false
            ]);

            $statusCode = $response->getStatusCode();
            $html = (string)$response->getBody();

            // Extraction OpenGraph précoce (souvent présent même sur les pages de redirection/captcha)
            $ogData = $this->extractOpenGraphData($html);

            if ($statusCode === 403 || $statusCode === 429) {
                if (str_contains($html, 'Just a moment...') || str_contains($html, 'challenges.cloudflare.com')) {
                    throw new \Exception("Le site est protégé par Cloudflare (anti-bot). Veuillez remplir le formulaire manuellement.");
                }
                throw new \Exception("Accès refusé par le site (Erreur $statusCode).");
            }

            // Cas spécifique Amazon WAF / Challenge (souvent 202)
            if ($statusCode === 202 || (str_contains($html, 'aws-waf-token') && strlen($html) < 5000)) {
                throw new \Exception("Le site demande une vérification humaine (WAF). Veuillez remplir le formulaire manuellement.");
            }

            if (!$html || strlen($html) < 200) throw new \Exception("La page n'a pas pu être chargée correctement.");

            // On vérifie si on n'est pas tombé sur un Captcha (Sauf si on a déjà des données OG utiles)
            if (empty($ogData['title']) && (str_contains($html, 'api-services-support@amazon.com') || str_contains($html, 'captcha'))) {
                 throw new \Exception("Le site a bloqué la requête (Captcha détecté).");
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
                $title = $ogData['title'] ?? '';
                $description = $ogData['description'] ?? '';
                $image = $ogData['image'] ?? '';
            }

            // Fallbacks robustes via OpenGraph si Embed a échoué
            $title = $title ?: ($ogData['title'] ?? '');
            $description = $description ?: ($ogData['description'] ?? '');
            $image = $image ?: ($ogData['image'] ?? '');

            // Extraction structurée via JSON-LD (très fiable sur Decathlon, Apple, AliExpress, etc.)
            if (preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/is', $html, $matches)) {
                foreach ($matches[1] as $jsonText) {
                    $jsonData = json_decode(trim($jsonText), true);
                    if (!$jsonData) continue;

                    // Support des tableaux d'objets au top-level ou via @graph
                    $items = [];
                    if (isset($jsonData['@graph'])) {
                        $items = $jsonData['@graph'];
                    } elseif (isset($jsonData[0])) {
                        $items = $jsonData;
                    } else {
                        $items = [$jsonData];
                    }

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
            $priceData = $this->extractPrice($html);

            // Si le prix n'a pas été trouvé (non-Amazon) et qu'on a du JSON-LD, on cherche dedans
            if ($priceData['amount'] <= 0 && preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/is', $html, $matches)) {
                foreach ($matches[1] as $jsonText) {
                    $jsonData = json_decode(trim($jsonText), true);
                    if (!$jsonData) continue;

                    $items = [];
                    if (isset($jsonData['@graph'])) {
                        $items = $jsonData['@graph'];
                    } elseif (isset($jsonData[0])) {
                        $items = $jsonData;
                    } else {
                        $items = [$jsonData];
                    }

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

            $amazonImages = $this->extractAmazonImages($html);
            $amazonTitle = $this->extractAmazonTitle($html);
            $aliexpressImages = $this->extractAliexpressImages($html);

            // On agrège les images candidates
            $images = [];
            if ($image) $images[] = $image;
            if (!empty($amazonImages)) $images = array_merge($images, $amazonImages);
            if (!empty($aliexpressImages)) $images = array_merge($images, $aliexpressImages);

            // On enlève les doublons et on s'assure que les URLs sont valides
            $images = array_values(array_unique(array_filter($images)));

            // Filtrage par mots-clés (logos, icônes, pixels, etc.)
            $blacklist = ['logo', 'sprite', 'pixel', 'icon', 'nav', 'menu', 'button', 'loading', 'spacer', 'banner', 'ads'];
            $images = array_filter($images, function($url) use ($blacklist) {
                $urlLower = strtolower($url);
                // Exclure les extensions non-photo
                if (preg_match('/\.(svg|gif|webp)$/i', $url)) return false;
                // Exclure si contient un mot de la blacklist
                foreach ($blacklist as $word) {
                    if (str_contains($urlLower, $word)) return false;
                }
                return true;
            });
            $images = array_values($images);

            // On trie pour mettre les images sans suffixes de redimensionnement en premier
            usort($images, function($a, $b) {
                $aClean = !preg_match('/\._[A-Z0-9,._-]+_\./i', $a);
                $bClean = !preg_match('/\._[A-Z0-9,._-]+_\./i', $b);
                if ($aClean && !$bClean) return -1;
                if (!$aClean && $bClean) return 1;
                return 0;
            });

            return [
                'title'       => $amazonTitle ?: (($title && $title !== 'Sans titre') ? $title : ''),
                'description' => $description ?: '',
                'image'       => $images[0] ?? '', // Image par défaut
                'images'      => $images,         // Toutes les images candidates
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

    protected function extractPrice($html) {
        // STRATÉGIE 1 : Patterns JSON e-commerce (Amazon, AliExpress, etc.)
        $jsonPatterns = [
            '/"priceAmount":\s*([0-9.]+)/',                          // Amazon
            '/"price":\s*\{[^}]*?"amount":\s*([0-9.]+)/i',           // AliExpress v1
            '/"price":\s*"([0-9.]+)"/i',                            // Schema.org simple string
            '/"actPriceDisplay":\s*"([^"]+)"/i',                     // AliExpress v2
            '/"minPriceDisplay":\s*"([^"]+)"/i',                     // AliExpress v3
            '/customerVisiblePrice\]\[amount\]" value="([^"]+)"/'    // Amazon inputs
        ];

        foreach ($jsonPatterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $val = str_replace([' ', ','], ['', '.'], $matches[1]);
                $val = preg_replace('/[^0-9.]/', '', $val);
                return [
                    'amount' => floatval($val),
                    'currency' => $this->detectCurrency($html)
                ];
            }
        }

        // STRATÉGIE 2 : Meta tags spécifiques au prix
        if (preg_match('/<meta.*?property=["\']product:price:amount["\'].*?content=["\'](.*?)["\']/is', $html, $matches)) {
            return [
                'amount' => floatval($matches[1]),
                'currency' => $this->detectCurrency($html)
            ];
        }

        // STRATÉGIE 3 : Fallback sur la balise visuelle a-offscreen (Amazon) ou classes prix communes
        $visualPatterns = [
            '/<span class="a-offscreen">([^<]+)<\/span>/',
            '/<span[^>]*class="[^"]*price[^"]*"[^>]*>([^<]+)<\/span>/i'
        ];

        foreach ($visualPatterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $priceStr = html_entity_decode($matches[1]);
                $currency = (str_contains($priceStr, '€')) ? 'EUR' : ((str_contains($priceStr, '$')) ? 'USD' : 'GBP');

                $clean = preg_replace('/[^\d,.]/', '', $priceStr);
                $clean = str_replace(',', '.', $clean);

                $amount = floatval($clean);
                if ($amount > 0) {
                    return [
                        'amount' => $amount,
                        'currency' => $currency
                    ];
                }
            }
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

    private function extractAmazonImages($html) {
        $found = [];
        if (preg_match('/data-a-dynamic-image="([^"]+)"/', $html, $matches)) {
            $json = html_entity_decode($matches[1]);
            $images = json_decode($json, true);
            if ($images) {
                // On trie par taille (la clé est l'URL, la valeur est un array de dimensions)
                // Mais ici on veut juste les URLs
                $found = array_keys($images);
            }
        }

        // Fallback sur d'autres patterns Amazon (images secondaires)
        if (preg_match_all('/"hiRes":"([^"]+)"/i', $html, $matches)) {
            $found = array_merge($found, $matches[1]);
        }
        if (preg_match_all('/"large":"([^"]+)"/i', $html, $matches)) {
            $found = array_merge($found, $matches[1]);
        }

        // Patterns génériques pour trouver de grandes images dans les balises img (I/ pattern)
        if (preg_match_all('/https:\/\/m\.media-amazon\.com\/images\/I\/([a-zA-Z0-9+_.-]+)\.jpg/i', $html, $matches)) {
            foreach ($matches[1] as $imageId) {
                // On privilégie les images sans suffixes de redimensionnement (_SS, _AC, etc.)
                if (!preg_match('/\._[A-Z0-9,._-]+_$/i', $imageId)) {
                    $found[] = "https://m.media-amazon.com/images/I/{$imageId}.jpg";
                }
            }
        }

        return $found;
    }

    private function extractAmazonTitle($html) {
        if (preg_match('/id="productTitle"[^>]*>\s*(.*?)\s*<\/span>/is', $html, $matches)) {
            return trim(html_entity_decode($matches[1]));
        }
        return null;
    }

    private function extractAliexpressImages($html) {
        $found = [];
        // AliExpress stocke souvent ses images dans window._d_c_.DCData ou imagePathList
        if (preg_match('/"imagePathList":\s*(\[.*?\])/is', $html, $matches)) {
            $list = json_decode($matches[1], true);
            if ($list) $found = array_merge($found, $list);
        }
        return $found;
    }

    private function extractOpenGraphData($html) {
        $data = [];
        // Support property="og:..." et name="og:..."
        if (preg_match('/<meta.*?(?:property|name)=["\']og:title["\'].*?content=["\'](.*?)["\']/is', $html, $m)) $data['title'] = html_entity_decode(trim($m[1]));
        if (preg_match('/<meta.*?(?:property|name)=["\']og:description["\'].*?content=["\'](.*?)["\']/is', $html, $m)) $data['description'] = html_entity_decode(trim($m[1]));
        if (preg_match('/<meta.*?(?:property|name)=["\']og:image["\'].*?content=["\'](.*?)["\']/is', $html, $m)) $data['image'] = trim($m[1]);

        // Fallback si content est avant property
        if (empty($data['title']) && preg_match('/<meta.*?content=["\'](.*?)["\'].*?(?:property|name)=["\']og:title["\']/is', $html, $m)) $data['title'] = html_entity_decode(trim($m[1]));

        return $data;
    }
}