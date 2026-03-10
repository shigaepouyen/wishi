<?php

namespace App\Utils;

class UrlUtils {
    /**
     * Nettoie une URL Amazon pour ne garder que l'essentiel et ajouter le tag parrainage.
     *
     * @param string $url L'URL à nettoyer
     * @return string L'URL nettoyée ou l'URL d'origine si non reconnue
     */
    public static function cleanAmazonUrl(string $url): string {
        if (!str_contains($url, 'amazon.')) {
            return $url;
        }

        $parsedUrl = parse_url($url);
        if (!$parsedUrl || !isset($parsedUrl['host'])) {
            return $url;
        }

        $asin = null;
        // Patterns courants pour l'ASIN chez Amazon
        $patterns = [
            '/(?:\/dp\/|\/gp\/product\/|\/gp\/aw\/d\/|\/o\/|\/aw\/d\/|\/product\/)([A-Z0-9]{10})/i',
            '/\/d\/([A-Z0-9]{10})/i'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                $asin = $matches[1];
                break;
            }
        }

        if ($asin) {
            $host = $parsedUrl['host'];
            // On essaie d'extraire le domaine exact (ex: amazon.fr, amazon.com)
            if (preg_match('/amazon\.(fr|com|co\.uk|de|it|es|ca|co\.jp|nl|se|pl|com\.be)/i', $host, $hostMatches)) {
                $domain = "www.amazon." . $hostMatches[1];
            } else {
                $domain = $host;
            }
            return "https://{$domain}/dp/{$asin}?tag=shig-21";
        }

        // Si on n'a pas trouvé d'ASIN mais que c'est Amazon, on s'assure au moins d'avoir le tag
        $query = isset($parsedUrl['query']) ? $parsedUrl['query'] : '';
        parse_str($query, $params);
        $params['tag'] = 'shig-21';
        $newQuery = http_build_query($params);

        $newUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . ($parsedUrl['path'] ?? '');
        $newUrl .= '?' . $newQuery;
        if (isset($parsedUrl['fragment'])) {
            $newUrl .= '#' . $parsedUrl['fragment'];
        }

        return $newUrl;
    }
}
