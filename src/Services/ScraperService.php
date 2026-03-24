<?php
namespace App\Services;

use App\Utils\UrlUtils;
use Embed\Embed;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\RequestException;

class ScraperService {
    
    protected $client;

    private const TIGER_PROTECT_MAX_CANDIDATE = 2000;
    private const TIGER_PROTECT_MAX_HASH_CHAIN_LENGTH = 4;

    public function __construct() {
        $this->client = new Client([
            'timeout' => 20,
            'cookies' => true,
            'allow_redirects' => [
                'max' => 30,
                'track_redirects' => true
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
                CURLOPT_AUTOREFERER => true,
            ]
        ]);
    }

    public function getLinkData(string $url) {
        try {
            // Pour AliExpress et Etsy on évite le Referer google qui peut déclencher des erreurs ou redirections
            $headers = [];
            if (!str_contains($url, 'aliexpress.com') && !str_contains($url, 'etsy.com')) {
                $headers['Referer'] = 'https://www.google.com/';
            }

            // Pour Shopify et d'autres plateformes, on tente de forcer la devise en EUR via l'URL
            // si aucune devise n'est déjà spécifiée.
            // On limite cette pratique aux domaines qui ne semblent pas être des moteurs de recherche
            // ou des URLs complexes pour éviter de casser des signatures.
            if (!str_contains($url, 'currency=') && !str_contains($url, 'amazon.') && !str_contains($url, 'google.') && !str_contains($url, 'aliexpress.')) {
                $separator = str_contains($url, '?') ? '&' : '?';
                $url = $url . $separator . 'currency=EUR';
            }

            $response = $this->client->get($url, [
                'headers' => $headers,
                'http_errors' => false,
                'curl' => [
                    CURLOPT_MAXREDIRS => 30,
                ]
            ]);

            $statusCode = $response->getStatusCode();
            // On récupère l'URL finale (après éventuelles redirections, important pour amzn.to)
            $finalUrl = $url;
            if ($response->hasHeader('X-GUZZLE-REDIRECT-HISTORY')) {
                $history = $response->getHeader('X-GUZZLE-REDIRECT-HISTORY');
                $finalUrl = end($history);
            }

            $html = (string)$response->getBody();

            if ($this->isTigerProtectChallenge($statusCode, $html)) {
                $solvedPage = $this->solveTigerProtectChallenge($finalUrl, $html);
                if (!$solvedPage) {
                    throw new \Exception("Le site est protégé par un contrôle JavaScript anti-bot. Veuillez remplir le formulaire manuellement.");
                }

                $response = $solvedPage['response'];
                $statusCode = $response->getStatusCode();
                $finalUrl = $solvedPage['final_url'];
                $html = $solvedPage['html'];
            }

            // Nettoyage de l'URL Amazon
            $url = UrlUtils::cleanAmazonUrl($finalUrl);

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
            if (empty($ogData['title']) && (str_contains($html, 'api-services-support@amazon.com') || str_contains($html, 'captcha') || str_contains($html, 'punish'))) {
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
            $ldData = $this->extractJsonLdData($html, $url);
            $title = $ldData['title'] ?: $title;
            $description = $ldData['description'] ?: $description;
            $image = $ldData['image'] ?: $image;

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
            // Fallback pour Pop Mart et autres structures SPA sans meta description utile
            if ((!$description || strlen($description) < 50) && preg_match('/<pre[^>]*class="[^"]*Desc[^"]*"[^>]*>(.*?)<\/pre>/is', $html, $matches)) {
                $description = html_entity_decode(trim($matches[1]));
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
            $description = mb_strimwidth(strip_tags(html_entity_decode($description)), 0, 2000, "...");

            // Extraction avancée du prix
            $priceData = $this->extractPrice($html);

            // Priorité absolue aux données JSON-LD si elles correspondent à la variante
            // ou si le prix trouvé par regex est suspect (ex: $0.00 ou $0.01 souvent liés au panier/frais)
            if ($ldData['price'] && ($priceData['amount'] <= 0.01 || $ldData['currency'] !== $priceData['currency'])) {
                $priceData['amount'] = $ldData['price'];
                $priceData['currency'] = $ldData['currency'] ?: $priceData['currency'];
            }

            $amazonImages = $this->extractAmazonImages($html);
            $amazonTitle = $this->extractAmazonTitle($html);
            $aliexpressImages = $this->extractAliexpressImages($html);
            $etsyImages = $this->extractEtsyImages($html);

            // On agrège les images candidates
            $images = [];
            if ($image) $images[] = $image;
            if (!empty($amazonImages)) $images = array_merge($images, $amazonImages);
            if (!empty($aliexpressImages)) $images = array_merge($images, $aliexpressImages);
            if (!empty($etsyImages)) $images = array_merge($images, $etsyImages);

            // On enlève les doublons et on s'assure que les URLs sont valides
            $images = array_values(array_unique(array_filter($images)));

            // Filtrage par mots-clés (logos, icônes, pixels, etc.)
            $blacklist = ['logo', 'sprite', 'pixel', 'icon', 'nav', 'menu', 'button', 'loading', 'spacer', 'banner', 'ads'];
            $images = array_filter($images, function($url) use ($blacklist) {
                $urlLower = strtolower($url);
                // Exclure les extensions non-photo
                if (preg_match('/\.(svg|gif)$/i', $url)) return false;
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
                    'amount'   => $priceData['amount'] > 0 ? round($priceData['amount'], 2) : '',
                    'currency' => $priceData['currency'] ?: 'EUR'
                ]
            ];

        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    protected function extractPrice($html) {
        // STRATÉGIE 0 : Nettoyage préalable pour éviter de matcher les publicités sponsorisées
        $cleanHtml = $this->cleanHtml($html);

        // On détecte la devise une fois pour toute la page
        $detectedCurrency = $this->detectCurrency($cleanHtml);

        // STRATÉGIE 1 : Patterns JSON e-commerce (Amazon, AliExpress, Shopify etc.)
        $jsonPatterns = [
            '/customerVisiblePrice\]\[amount\]" value="([^"]+)"/',   // Amazon inputs (Priorité car spécifique au produit principal)
            '/"priceAmount":\s*([0-9.]+)/',                          // Amazon
            '/"price":\s*\{[^}]*?"amount":\s*([0-9.]+)/i',           // AliExpress v1
            '/Price:\s*"[^"]*?([0-9][0-9,.]*)"/i',                   // Viewed Product script (Shopify/Klaviyo)
            '/Value:\s*"([0-9][0-9,.]*)"/i',                         // Viewed Product script (Shopify/Klaviyo)
            '/"price":\s*"([^"]+)"/i',                               // Schema.org simple string
            '/"lowPrice":\s*"([^"]+)"/i',                            // Schema.org AggregateOffer
            '/"actPriceDisplay":\s*"([^"]+)"/i',                     // AliExpress v2
            '/"minPriceDisplay":\s*"([^"]+)"/i',                     // AliExpress v3
            '/"formatedPrice":\s*"([^"]+)"/i',                       // AliExpress v4
            '/"salePrice":\s*"([^"]+)"/i',                           // AliExpress v5
            '/"priceText":\s*"([^"]+)"/i',                           // AliExpress v6
            '/"value":\s*([0-9.]+),\s*"currency":/i',                // AliExpress v7 (Price module)
            '/"actPrice":\s*"([^"]+)"/i',                            // AliExpress v8
            '/"appPrice":\s*"([^"]+)"/i',                            // AliExpress v9
            '/"discountPrice":\s*"([^"]+)"/i',                       // AliExpress v10
            '/"actPriceDisplay":"([^"]+)"/i',                        // AliExpress v11
            '/"minPrice":"([^"]+)"/i',                               // AliExpress v12
            '/"maxPrice":"([^"]+)"/i',                               // AliExpress v13
            '/"price":"([^"]+)"/i',                                  // AliExpress v14
            '/"price":\s*([0-9.]+)/i',                               // Generic JSON numeric price
            '/"price":\s*"([^"]+)"/i',                               // Generic JSON string price
            '/"skuAmount":\s*\{[^}]*?"value":\s*([0-9.]+)/i',        // AliExpress SKU amount
            '/"skuActivityAmount":\s*\{[^}]*?"value":\s*([0-9.]+)/i',// AliExpress SKU promo
        ];

        foreach ($jsonPatterns as $pattern) {
            if (preg_match($pattern, $cleanHtml, $matches)) {
                $val = $this->normalizePriceAmount($matches[1]);
                return [
                    'amount' => $val,
                    'currency' => $detectedCurrency
                ];
            }
        }

        // STRATÉGIE 2 : Meta tags spécifiques au prix
        if (preg_match('/<meta.*?property=["\']product:price:amount["\'].*?content=["\'](.*?)["\']/is', $cleanHtml, $matches)) {
            $amount = $this->normalizePriceAmount($matches[1]);
            return [
                'amount' => $amount,
                'currency' => $detectedCurrency
            ];
        }

        // STRATÉGIE 3 : Fallback sur la balise visuelle a-offscreen (Amazon) ou classes prix communes
        $visualPatterns = [
            '/<span class="a-offscreen">([^<]+)<\/span>/',
            '/<span[^>]*class="[^"]*price(?:-default--current)?[^"]*"[^>]*>([^<]+)<\/span>/i',
            '/<div[^>]*class="[^"]*price(?:-default--current)?[^"]*"[^>]*>([^<]+)<\/div>/i'
        ];

        foreach ($visualPatterns as $pattern) {
            if (preg_match($pattern, $cleanHtml, $matches)) {
                $priceStr = html_entity_decode($matches[1]);
                $currency = $detectedCurrency;
                if (str_contains($priceStr, '€')) $currency = 'EUR';
                elseif (str_contains($priceStr, '$')) $currency = 'USD';
                elseif (str_contains($priceStr, '£')) $currency = 'GBP';

                $amount = $this->normalizePriceAmount($priceStr);
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
        // STRATÉGIE 1 : Chercher dans les balises meta (plus fiable que le contenu global)
        if (preg_match('/<meta.*?property=["\']og:price:currency["\'].*?content=["\'](.*?)["\']/is', $html, $m)) return strtoupper(trim($m[1]));
        if (preg_match('/<meta.*?name=["\']currency["\'].*?content=["\'](.*?)["\']/is', $html, $m)) return strtoupper(trim($m[1]));

        // STRATÉGIE 2 : Plateformes spécifiques
        if (preg_match('/Shopify\.currency\s*=\s*\{"active":"([^"]+)"/i', $html, $m)) return strtoupper(trim($m[1]));
        if (preg_match('/"(?:currencyCode|priceCurrency)":\s*"([^"]+)"/i', $html, $m)) return strtoupper(trim($m[1])); // AliExpress/Generic JSON
        if (preg_match('/Price:\s*"([^"]*?)(?:EUR|USD|GBP|€|\$|£|[\d,.]+)"/i', $html, $m)) {
            if (str_contains($m[0], '€') || str_contains($m[0], 'EUR')) return 'EUR';
            if (str_contains($m[0], '$') || str_contains($m[0], 'USD')) return 'USD';
            if (str_contains($m[0], '£') || str_contains($m[0], 'GBP')) return 'GBP';
        }

        // On check les indices dans le code source global avec support des guillemets encodés
        if (preg_match('/(?:currencyCode|priceCurrency)["\']?\s*[:=]\s*["\']?(EUR)["\']?/i', $html, $m)) return strtoupper($m[1]);
        if (preg_match('/(?:currencyCode|priceCurrency)["\']?\s*[:=]\s*["\']?(USD)["\']?/i', $html, $m)) return strtoupper($m[1]);
        if (preg_match('/(?:currencyCode|priceCurrency)["\']?\s*[:=]\s*["\']?(GBP)["\']?/i', $html, $m)) return strtoupper($m[1]);

        // Support pour les formats &quot;
        if (str_contains($html, 'currencyCode&quot;:&quot;EUR&quot;') || str_contains($html, 'priceCurrency&quot;:&quot;EUR&quot;') || str_contains($html, 'currencySymbol&quot;:&quot;€&quot;')) return 'EUR';
        if (str_contains($html, 'currencyCode&quot;:&quot;USD&quot;') || str_contains($html, 'priceCurrency&quot;:&quot;USD&quot;') || str_contains($html, 'currencySymbol&quot;:&quot;$&quot;')) return 'USD';
        if (str_contains($html, 'currencyCode&quot;:&quot;GBP&quot;') || str_contains($html, 'priceCurrency&quot;:&quot;GBP&quot;') || str_contains($html, 'currencySymbol&quot;:&quot;£&quot;')) return 'GBP';

        if (preg_match('/(?:€|&euro;)\s*\d|\d[\d\s,.]*\s*(?:€|&euro;)/iu', $html)) return 'EUR';
        if (preg_match('/(?:US?\$|&#36;|\$)\s*\d|\d[\d\s,.]*\s*(?:US?\$|&#36;|\$)/iu', $html)) return 'USD';
        if (preg_match('/(?:£|&pound;)\s*\d|\d[\d\s,.]*\s*(?:£|&pound;)/iu', $html)) return 'GBP';

        return 'EUR';
    }

    private function normalizePriceAmount(string $rawAmount): float {
        $normalized = html_entity_decode(trim($rawAmount));
        $normalized = preg_replace('/[^\d,.\s]/u', '', $normalized);
        $normalized = preg_replace('/\s+/', '', $normalized);

        if ($normalized === '') {
            return 0.0;
        }

        $lastComma = strrpos($normalized, ',');
        $lastDot = strrpos($normalized, '.');

        if ($lastComma !== false && $lastDot !== false) {
            if ($lastComma > $lastDot) {
                $normalized = str_replace('.', '', $normalized);
                $normalized = str_replace(',', '.', $normalized);
            } else {
                $normalized = str_replace(',', '', $normalized);
            }
        } elseif ($lastComma !== false || $lastDot !== false) {
            $separator = $lastComma !== false ? ',' : '.';
            $count = substr_count($normalized, $separator);
            $lastPos = strrpos($normalized, $separator);
            $digitsAfter = strlen($normalized) - $lastPos - 1;

            if ($count > 1) {
                $parts = explode($separator, $normalized);
                $decimalPart = array_pop($parts);
                if ($digitsAfter === 2 && $decimalPart !== '') {
                    $normalized = implode('', $parts) . '.' . $decimalPart;
                } else {
                    $normalized = implode('', $parts) . $decimalPart;
                }
            } elseif ($digitsAfter === 3) {
                $normalized = str_replace($separator, '', $normalized);
            } elseif ($separator === ',') {
                $normalized = str_replace(',', '.', $normalized);
            }
        }

        return is_numeric($normalized) ? (float)$normalized : 0.0;
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

    private function extractEtsyImages($html) {
        $found = [];
        // On cherche les images haute résolution dans le HTML (pattern fullxfull)
        if (preg_match_all('/https:\/\/i\.etsystatic\.com\/[a-zA-Z0-9\/._-]*il_fullxfull\.[a-zA-Z0-9\/._-]*/i', $html, $matches)) {
            $found = array_merge($found, $matches[0]);
        }
        return $found;
    }

    /**
     * Extrait les données structurées JSON-LD
     */
    private function extractJsonLdData($html, $targetUrl = null) {
        $data = ['title' => null, 'description' => null, 'image' => null, 'price' => null, 'currency' => null];
        $targetId = $targetUrl ? $this->getProductId($targetUrl) : null;
        $matchedTargetItem = false;

        if (preg_match_all('/<script\b[^>]*type\s*=\s*["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $matches)) {
            foreach ($matches[1] as $jsonText) {
                $jsonData = json_decode(trim($jsonText), true);
                if (!$jsonData) continue;

                $items = isset($jsonData['@graph']) ? $jsonData['@graph'] : (isset($jsonData[0]) ? $jsonData : [$jsonData]);

                foreach ($items as $item) {
                    if (!is_array($item)) {
                        continue;
                    }

                    // On ignore les produits sponsorisés dans le JSON-LD
                    $itemString = json_encode($item);
                    if (is_string($itemString) && (str_contains(strtolower($itemString), 'sponsored') || str_contains(strtolower($itemString), 'sponsorisé'))) {
                        continue;
                    }

                    $type = $item['@type'] ?? '';
                    if (is_array($type)) {
                        $type = implode(' ', $type);
                    }
                    $isProduct = str_contains($type, 'Product') || $type === 'Offer';
                    $itemMatchesTarget = $this->jsonLdItemMatchesTarget($item, $targetUrl, $targetId);

                    if ($isProduct && ($itemMatchesTarget || !$matchedTargetItem)) {
                        $this->mergeJsonLdItemData($data, $item, $itemMatchesTarget);
                    }

                    if ($itemMatchesTarget) {
                        $matchedTargetItem = true;
                    }

                    // Extraction du prix dans le JSON-LD
                    if (isset($item['offers'])) {
                        $offers = is_array($item['offers']) && !isset($item['offers']['price']) && !isset($item['offers']['lowPrice']) ? $item['offers'] : [$item['offers']];
                        foreach ($offers as $offer) {
                            if (!is_array($offer)) {
                                continue;
                            }

                            // Si on a une URL cible (variante), on privilégie l'offre qui correspond
                            // On utilise getProductId pour une comparaison robuste (AliExpress change souvent de domaine .com/.us)
                            $offerUrl = $offer['url'] ?? null;
                            $offerId = $offerUrl ? $this->getProductId($offerUrl) : null;
                            $offerPrice = $this->normalizePriceAmount((string)($offer['price'] ?? $offer['lowPrice'] ?? ''));

                            if ($this->jsonLdUrlsMatch($offerUrl, $targetUrl, $targetId, $offerId)) {
                                $data['price'] = $offerPrice;
                                $data['currency'] = $offer['priceCurrency'] ?? $data['currency'];
                                $matchedTargetItem = true;
                                break;
                            }

                            if ($offerPrice > 0 && !$data['price']) {
                                $data['price'] = $offerPrice;
                                $data['currency'] = $offer['priceCurrency'] ?? $data['currency'];
                            } elseif (isset($offer['priceSpecification'])) {
                                $spec = is_array($offer['priceSpecification']) && !isset($offer['priceSpecification']['price'])
                                    ? $offer['priceSpecification'][0]
                                    : $offer['priceSpecification'];
                                if (isset($spec['price'])) {
                                    $specPrice = $this->normalizePriceAmount((string)$spec['price']);
                                    if ($specPrice <= 0) {
                                        continue;
                                    }

                                    if (!$data['price']) {
                                        $data['price'] = $specPrice;
                                    }
                                    $data['currency'] = $spec['priceCurrency'] ?? $data['currency'];
                                }
                            }
                        }
                    }
                }
            }
        }
        return $data;
    }

    private function mergeJsonLdItemData(array &$data, array $item, bool $overrideExisting = false): void {
        $itemData = [
            'title' => $item['name'] ?? null,
            'description' => $item['description'] ?? null,
            'image' => $this->extractJsonLdImage($item['image'] ?? null),
        ];

        foreach ($itemData as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            if ($overrideExisting || empty($data[$key])) {
                $data[$key] = $value;
            }
        }
    }

    private function extractJsonLdImage($image): ?string {
        if (!$image) {
            return null;
        }

        if (is_array($image)) {
            $firstImage = $image[0] ?? null;
            if (is_array($firstImage)) {
                return $firstImage['contentURL'] ?? $firstImage['url'] ?? null;
            }

            return is_string($firstImage) ? $firstImage : null;
        }

        return is_string($image) ? $image : null;
    }

    private function jsonLdItemMatchesTarget(array $item, ?string $targetUrl, ?string $targetId): bool {
        if ($targetUrl && isset($item['url']) && is_string($item['url'])) {
            $itemId = $this->getProductId($item['url']);
            if ($this->jsonLdUrlsMatch($item['url'], $targetUrl, $targetId, $itemId)) {
                return true;
            }
        }

        if (!isset($item['offers'])) {
            return false;
        }

        $offers = is_array($item['offers']) && !isset($item['offers']['price']) && !isset($item['offers']['lowPrice']) ? $item['offers'] : [$item['offers']];
        foreach ($offers as $offer) {
            if (!is_array($offer) || !isset($offer['url']) || !is_string($offer['url'])) {
                continue;
            }

            $offerId = $this->getProductId($offer['url']);
            if ($this->jsonLdUrlsMatch($offer['url'], $targetUrl, $targetId, $offerId)) {
                return true;
            }
        }

        return false;
    }

    private function jsonLdUrlsMatch(?string $candidateUrl, ?string $targetUrl, ?string $targetId, ?string $candidateId = null): bool {
        if (!$candidateUrl || !$targetUrl) {
            return false;
        }

        if ($targetId) {
            $candidateId = $candidateId ?: $this->getProductId($candidateUrl);
            if ($candidateId && $candidateId === $targetId) {
                return true;
            }
        }

        if ($candidateUrl === $targetUrl) {
            return true;
        }

        $candidateParts = parse_url($candidateUrl);
        $targetParts = parse_url($targetUrl);
        if (!$candidateParts || !$targetParts) {
            return false;
        }

        $candidateHost = strtolower($candidateParts['host'] ?? '');
        $targetHost = strtolower($targetParts['host'] ?? '');
        $candidatePath = rtrim($candidateParts['path'] ?? '', '/');
        $targetPath = rtrim($targetParts['path'] ?? '', '/');

        return $candidateHost !== '' && $candidateHost === $targetHost && $candidatePath !== '' && $candidatePath === $targetPath;
    }

    private function cleanHtml(string $html): string {
        // Supprime les blocs sponsorisés et recommandations qui polluent l'extraction du prix
        $patterns = [
            // Blocs Sponsored Products Amazon (Carrousels) - se terminent généralement par a-end
            '/<div[^>]*id="sp_detail\d?"[^>]*>.*?<span class="a-end aok-hidden"><\/span>.*?<\/div>/is',
            // Blocs APE Amazon (Amazon Placement Engine)
            '/<div[^>]*id="ape_Detail[^>]*>.*?<\/div>\s*<\/div>/is',
            // Attributs de feedback publicitaire
            '/data-adfeedbackdetails=["\'].*?["\']/is',
            // Blocs cart/header Shopify
            '/<div[^>]*id="cart-notification"[^>]*>.*?<\/div>/is',
            '/<div[^>]*class="[^"]*cart-drawer[^"]*"[^>]*>.*?<\/div>/is',
            '/<header[^>]*>.*?<\/header>/is',
            // AliExpress Recommandations et "More to love"
            '/<div[^>]*class="[^"]*(?:rcmd|recommend|MoreOtherSeller|fusion-card|rcmd-hover-more-action)[^"]*"[^>]*>.*?<\/div>/is',
            '/<div[^>]*id="[^"]*(?:nav-moretolove)[^"]*"[^>]*>.*?<\/div>/is',
            '/<div[^>]*data-spm="[^"]*MoreOtherSeller[^"]*"[^>]*>.*?<\/div>/is',
        ];

        foreach ($patterns as $pattern) {
            $html = preg_replace($pattern, '', $html);
        }

        return $html;
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

    private function getProductId(string $url) {
        if (str_contains($url, 'amazon.')) {
            if (preg_match('/(?:\/dp\/|\/gp\/product\/|\/gp\/aw\/d\/|\/o\/|\/aw\/d\/|\/product\/)([A-Z0-9]{10})/i', $url, $matches)) {
                return $matches[1];
            }
        }
        if (str_contains($url, 'aliexpress.')) {
            if (preg_match('/\/item\/(\d+)\.html/i', $url, $matches)) {
                return $matches[1];
            }
            if (preg_match('/productId=(\d+)/i', $url, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }

    private function isTigerProtectChallenge(int $statusCode, string $html): bool {
        if ($statusCode !== 503) {
            return false;
        }

        return str_contains($html, '/o2s-cgi/security-challenge')
            && str_contains($html, 'challengeHash')
            && (str_contains($html, 'Tiger Protect') || str_contains($html, 'o2switch'));
    }

    private function solveTigerProtectChallenge(string $challengeUrl, string $challengeHtml): ?array {
        $challengeHash = $this->extractTigerProtectChallengeHash($challengeHtml);
        if (!$challengeHash) {
            return null;
        }

        $challengeEndpoint = $this->buildTigerProtectChallengeEndpoint($challengeUrl);
        if (!$challengeEndpoint) {
            return null;
        }

        $challengeResponse = $this->client->post($challengeEndpoint . '?a=get-chl', [
            'http_errors' => false,
            'headers' => [
                'X-Requested-With' => 'XMLHttpRequest',
                'Referer' => $challengeUrl,
            ],
            'form_params' => [
                'chl-type' => 'js',
                'chl-hash' => $challengeHash,
            ],
        ]);

        $challengePayload = json_decode((string)$challengeResponse->getBody(), true);
        $payload = $challengePayload['payload'] ?? null;
        if (!$payload) {
            return null;
        }

        $challengeData = $this->extractTigerProtectChallengeData($payload);
        if (!$challengeData) {
            return null;
        }

        $verifyResponse = $this->client->post($challengeEndpoint . '?a=verify-response', [
            'http_errors' => false,
            'headers' => [
                'Referer' => $challengeUrl,
            ],
            'form_params' => [
                'chl-type' => 'js',
                'chl-hash' => $challengeHash,
                'chl-current-url' => $challengeUrl,
                'js-chl-response' => (string)$challengeData['response'],
                'js-chl-id' => $challengeData['id'],
            ],
        ]);

        // Certains challenges renvoient encore la page WAF ici; seule la requête suivante valide le cookie.
        $pageResponse = $this->client->get($challengeUrl, [
            'http_errors' => false,
            'headers' => [
                'Referer' => $challengeUrl,
            ],
            'curl' => [
                CURLOPT_MAXREDIRS => 30,
            ],
        ]);

        $finalUrl = $challengeUrl;
        if ($pageResponse->hasHeader('X-GUZZLE-REDIRECT-HISTORY')) {
            $history = $pageResponse->getHeader('X-GUZZLE-REDIRECT-HISTORY');
            $finalUrl = end($history);
        }

        $html = (string)$pageResponse->getBody();
        if ($this->isTigerProtectChallenge($pageResponse->getStatusCode(), $html)) {
            return null;
        }

        return [
            'response' => $pageResponse,
            'final_url' => $finalUrl,
            'html' => $html,
            'verify_status' => $verifyResponse->getStatusCode(),
        ];
    }

    private function extractTigerProtectChallengeHash(string $html): ?string {
        if (preg_match("/const challengeHash = '([^']+)'/", $html, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function buildTigerProtectChallengeEndpoint(string $challengeUrl): ?string {
        $parts = parse_url($challengeUrl);
        if (!$parts || empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }

        return $parts['scheme'] . '://' . $parts['host'] . '/o2s-cgi/security-challenge';
    }

    private function extractTigerProtectChallengeData(string $payload): ?array {
        if (!preg_match('/var\s+[A-Za-z]+\s*=\s*(.*?);\s*var\s+[A-Za-z]+\s*=\s*(.*?);\s*var\s+[A-Za-z]+\s*=\s*\(function\(\)/s', $payload, $matches)) {
            return null;
        }

        try {
            $challengeId = $this->evaluateTigerProtectExpression($matches[1]);
            $targetHash = strtolower($this->evaluateTigerProtectExpression($matches[2]));
        } catch (\RuntimeException $e) {
            return null;
        }

        if (!$challengeId || !preg_match('/^[a-f0-9]{6,}$/i', $challengeId)) {
            return null;
        }
        if (!preg_match('/^(?:[a-f0-9]{32}|[a-f0-9]{40}|[a-f0-9]{64})$/', $targetHash)) {
            return null;
        }

        $response = $this->computeTigerProtectResponse($targetHash);
        if ($response === null) {
            return null;
        }

        return [
            'id' => $challengeId,
            'target_hash' => $targetHash,
            'response' => $response,
        ];
    }

    private function computeTigerProtectResponse(string $targetHash): ?int {
        $hashChains = $this->buildTigerProtectHashChains($targetHash);
        if (empty($hashChains)) {
            return null;
        }

        for ($candidate = 0; $candidate <= self::TIGER_PROTECT_MAX_CANDIDATE; $candidate++) {
            foreach ($hashChains as $hashChain) {
                $currentHash = (string)$candidate;
                foreach ($hashChain as $algorithm) {
                    $currentHash = hash($algorithm, $currentHash);
                }

                if ($currentHash === $targetHash) {
                    return $candidate;
                }
            }
        }

        return null;
    }

    private function buildTigerProtectHashChains(string $targetHash): array {
        $finalAlgorithm = $this->detectTigerProtectHashAlgorithm($targetHash);
        if (!$finalAlgorithm) {
            return [];
        }

        $algorithms = ['md5', 'sha1', 'sha256'];
        $chains = [];

        for ($length = 1; $length <= self::TIGER_PROTECT_MAX_HASH_CHAIN_LENGTH; $length++) {
            $this->buildTigerProtectHashChainsRecursive($chains, [], $length, $algorithms, $finalAlgorithm);
        }

        return $chains;
    }

    private function buildTigerProtectHashChainsRecursive(array &$chains, array $currentChain, int $targetLength, array $algorithms, string $finalAlgorithm): void {
        if (count($currentChain) === $targetLength) {
            if (($currentChain[$targetLength - 1] ?? null) === $finalAlgorithm) {
                $chains[] = $currentChain;
            }
            return;
        }

        foreach ($algorithms as $algorithm) {
            $nextChain = $currentChain;
            $nextChain[] = $algorithm;
            $this->buildTigerProtectHashChainsRecursive($chains, $nextChain, $targetLength, $algorithms, $finalAlgorithm);
        }
    }

    private function detectTigerProtectHashAlgorithm(string $targetHash): ?string {
        return match (strlen($targetHash)) {
            32 => 'md5',
            40 => 'sha1',
            64 => 'sha256',
            default => null,
        };
    }

    private function evaluateTigerProtectExpression(string $expression): string {
        $expression = preg_replace('/\/\*.*?\*\//s', '', $expression);
        do {
            $previousExpression = $expression;
            $expression = preg_replace('/\(\s*function\s*\(\)\s*\{\s*return\s*(.*?);\s*\}\s*\)\s*\(\s*\)/s', '($1)', $expression);
        } while ($expression !== $previousExpression);

        $tokens = $this->tokenizeTigerProtectExpression($expression);
        $index = 0;
        $value = $this->parseTigerProtectConcat($tokens, $index);

        if ($index !== count($tokens)) {
            throw new \RuntimeException('Expression Tiger Protect incomplète.');
        }

        return $this->stringifyTigerProtectValue($value);
    }

    private function tokenizeTigerProtectExpression(string $expression): array {
        $tokens = [];
        $length = strlen($expression);
        $offset = 0;

        while ($offset < $length) {
            $char = $expression[$offset];

            if (ctype_space($char)) {
                $offset++;
                continue;
            }

            if (in_array($char, ['+', '.', '[', ']', '(', ')', ','], true)) {
                $tokens[] = ['type' => $char, 'value' => $char];
                $offset++;
                continue;
            }

            if (in_array($char, ["'", '"', '`'], true)) {
                [$value, $offset] = $this->readTigerProtectStringToken($expression, $offset);
                $tokens[] = ['type' => 'string', 'value' => $value];
                continue;
            }

            if (ctype_digit($char)) {
                $start = $offset;
                while ($offset < $length && ctype_digit($expression[$offset])) {
                    $offset++;
                }
                $tokens[] = ['type' => 'string', 'value' => substr($expression, $start, $offset - $start)];
                continue;
            }

            if (preg_match('/[A-Za-z_$]/', $char) === 1) {
                $start = $offset;
                while ($offset < $length && preg_match('/[A-Za-z0-9_$]/', $expression[$offset]) === 1) {
                    $offset++;
                }
                $tokens[] = ['type' => 'identifier', 'value' => substr($expression, $start, $offset - $start)];
                continue;
            }

            throw new \RuntimeException("Token Tiger Protect inattendu: {$char}");
        }

        return $tokens;
    }

    private function readTigerProtectStringToken(string $expression, int $offset): array {
        $delimiter = $expression[$offset];
        $offset++;
        $length = strlen($expression);
        $value = '';

        while ($offset < $length) {
            $char = $expression[$offset];
            if ($char === $delimiter) {
                return [$value, $offset + 1];
            }

            if ($char === '\\') {
                $offset++;
                if ($offset >= $length) {
                    break;
                }

                $escaped = $expression[$offset];
                if ($escaped === 'x' && $offset + 2 < $length) {
                    $hex = substr($expression, $offset + 1, 2);
                    $value .= chr((int)hexdec($hex));
                    $offset += 3;
                    continue;
                }

                $value .= match ($escaped) {
                    'n' => "\n",
                    'r' => "\r",
                    't' => "\t",
                    default => $escaped,
                };
                $offset++;
                continue;
            }

            $value .= $char;
            $offset++;
        }

        throw new \RuntimeException('Chaîne Tiger Protect non terminée.');
    }

    private function parseTigerProtectConcat(array $tokens, int &$index) {
        $value = $this->parseTigerProtectPostfix($tokens, $index);

        while ($index < count($tokens) && $tokens[$index]['type'] === '+') {
            $index++;
            $right = $this->parseTigerProtectPostfix($tokens, $index);
            $value = $this->stringifyTigerProtectValue($value) . $this->stringifyTigerProtectValue($right);
        }

        return $value;
    }

    private function parseTigerProtectPostfix(array $tokens, int &$index) {
        $value = $this->parseTigerProtectPrimary($tokens, $index);

        while ($index < count($tokens)) {
            $type = $tokens[$index]['type'];

            if ($type === '.') {
                $index++;
                $property = $this->expectTigerProtectToken($tokens, $index, 'identifier')['value'];
                $value = $this->getTigerProtectProperty($value, $property);
                continue;
            }

            if ($type === '[') {
                $index++;
                $property = $this->stringifyTigerProtectValue($this->parseTigerProtectConcat($tokens, $index));
                $this->expectTigerProtectToken($tokens, $index, ']');
                $value = $this->getTigerProtectProperty($value, $property);
                continue;
            }

            if ($type === '(') {
                $index++;
                $arguments = [];
                if ($index < count($tokens) && $tokens[$index]['type'] !== ')') {
                    $arguments[] = $this->parseTigerProtectConcat($tokens, $index);
                    while ($index < count($tokens) && $tokens[$index]['type'] === ',') {
                        $index++;
                        $arguments[] = $this->parseTigerProtectConcat($tokens, $index);
                    }
                }
                $this->expectTigerProtectToken($tokens, $index, ')');
                $value = $this->callTigerProtectValue($value, $arguments);
                continue;
            }

            break;
        }

        return $value;
    }

    private function parseTigerProtectPrimary(array $tokens, int &$index) {
        $token = $tokens[$index] ?? null;
        if (!$token) {
            throw new \RuntimeException('Expression Tiger Protect vide.');
        }

        if ($token['type'] === 'string') {
            $index++;
            return $token['value'];
        }

        if ($token['type'] === 'identifier') {
            $index++;
            return $this->resolveTigerProtectIdentifier($token['value']);
        }

        if ($token['type'] === '(') {
            $index++;
            $value = $this->parseTigerProtectConcat($tokens, $index);
            $this->expectTigerProtectToken($tokens, $index, ')');
            return $value;
        }

        throw new \RuntimeException('Expression Tiger Protect invalide.');
    }

    private function expectTigerProtectToken(array $tokens, int &$index, string $expectedType): array {
        $token = $tokens[$index] ?? null;
        if (!$token || $token['type'] !== $expectedType) {
            throw new \RuntimeException("Token Tiger Protect attendu: {$expectedType}");
        }

        $index++;
        return $token;
    }

    private function resolveTigerProtectIdentifier(string $identifier) {
        return match ($identifier) {
            'window' => ['kind' => 'window'],
            'String' => ['kind' => 'callable', 'name' => 'String'],
            default => throw new \RuntimeException("Identifiant Tiger Protect non supporté: {$identifier}"),
        };
    }

    private function getTigerProtectProperty($value, string $property) {
        if (is_array($value) && ($value['kind'] ?? null) === 'window' && $property === 'String') {
            return ['kind' => 'callable', 'name' => 'String'];
        }

        if (is_array($value) && ($value['kind'] ?? null) === 'callable' && $value['name'] === 'String' && $property === 'fromCharCode') {
            return ['kind' => 'callable', 'name' => 'String.fromCharCode'];
        }

        throw new \RuntimeException("Propriété Tiger Protect non supportée: {$property}");
    }

    private function callTigerProtectValue($value, array $arguments) {
        if (!is_array($value) || ($value['kind'] ?? null) !== 'callable') {
            throw new \RuntimeException('Appel Tiger Protect non supporté.');
        }

        return match ($value['name']) {
            'String' => isset($arguments[0]) ? $this->stringifyTigerProtectValue($arguments[0]) : '',
            'String.fromCharCode' => mb_chr((int)$this->stringifyTigerProtectValue($arguments[0] ?? '0')),
            default => throw new \RuntimeException("Callable Tiger Protect non supporté: {$value['name']}"),
        };
    }

    private function stringifyTigerProtectValue($value): string {
        if (is_string($value)) {
            return $value;
        }

        throw new \RuntimeException('Valeur Tiger Protect non sérialisable.');
    }
}
