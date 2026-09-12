<?php

namespace App\Utils;

use PDO;

class Slug {
    /**
     * Transforme un texte libre en slug utilisable dans une URL.
     * Retourne une chaîne vide si le texte ne contient aucun caractère slugifiable
     * (cas des listes nommées uniquement avec un emoji).
     */
    public static function make(?string $text): string {
        $text = trim((string)$text);
        if ($text === '') {
            return '';
        }

        if (function_exists('transliterator_transliterate')) {
            $transliterated = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text);
            if ($transliterated !== false) {
                $text = $transliterated;
            }
        }

        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);

        return trim((string)$text, '-');
    }

    /**
     * Slug court d'une liste, utilisé dans les URLs de navigation /<profil>/<liste>.
     * Unique au sein d'un profil : deux profils peuvent chacun avoir leur liste "livres".
     */
    public static function uniqueListHubSlug(PDO $db, int $profileId, ?string $name, ?int $excludeListId = null): string {
        $base = self::make($name);
        if ($base === '') {
            $base = 'liste';
        }

        $sql = "SELECT id FROM lists WHERE profile_id = ? AND slug_hub = ?";
        $params = [$profileId, null];
        if ($excludeListId !== null) {
            $sql .= " AND id != ?";
        }

        $candidate = $base;
        $suffix = 1;
        while (true) {
            $params[1] = $candidate;
            $stmt = $db->prepare($sql);
            $stmt->execute($excludeListId !== null ? array_merge($params, [$excludeListId]) : $params);
            if (!$stmt->fetch()) {
                return $candidate;
            }
            $suffix++;
            $candidate = $base . '-' . $suffix;
        }
    }
}
