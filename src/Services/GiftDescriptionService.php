<?php
declare(strict_types=1);

namespace App\Services;

use App\Utils\UrlUtils;

/**
 * Redige la description courte (2 a 3 phrases) d'un article de liste, a partir de ce que
 * le proprietaire a saisi, de ce que la page produit a renvoye et du contexte de la liste.
 *
 * Ton vivant : on presente l'objet comme a un proche (l'histoire pour un livre ou un film,
 * ce qu'on en fait pour un jeu), sans prenom ni formule de catalogue. La liste publique
 * l'affiche sous le titre, la famille doit comprendre en un coup d'oeil de quoi il s'agit.
 */
final class GiftDescriptionService
{
    /** Au-dela, on coupe a la derniere phrase complete : la carte n'affiche que 3 lignes. */
    public const MAX_LENGTH = 420;

    private const SYSTEM = <<<TXT
Tu rédiges la description d'un article dans une liste de cadeaux familiale.
La description s'affiche sous le titre de l'article, pour les proches qui vont l'offrir.

Le ton : celui de quelqu'un qui présente l'objet à un ami, avec envie. Vivant, simple, concret. Donne envie d'offrir, sans jargon ni ton de catalogue.

Selon le type d'article :
- Livre, BD, manga, film, série, jeu vidéo : raconte l'histoire. De quoi ça parle, qui est le héros, quelle aventure ou quel mystère, l'ambiance (drôle, frissons, magique…). Comme une quatrième de couverture : on pose le point de départ et l'enjeu, sans aucun spoiler (ni rebondissement, ni révélation, ni fin, ni sort des personnages). Pas de « cet ouvrage », pas de « appartient au genre », pas de fiche de bibliothèque.
- Jeu, jouet, loisir créatif : ce qu'on fait avec et ce qui est amusant dedans.
- Vêtement, objet du quotidien, matériel : ce que c'est et ce qui le rend chouette ou pratique.

Règles :
- 2 à 3 phrases courtes, en français correct avec tous les accents et apostrophes, 300 caractères au maximum.
- Commence directement par le cœur du sujet (l'histoire, le jeu, l'objet), jamais par « Ce livre est », « Cet ouvrage » ou « Il s'agit de ».
- Pas de prénom, pas de « je », pas de « tu » ni de « vous », pas de prix, pas d'emoji, pas de nom de boutique, pas de formules creuses (« incontournable », « parfait pour tous », « idéal pour »).
- Pour une œuvre connue (livre, film, série, jeu célèbre), tu peux t'appuyer sur ce que tu en sais avec certitude. Si tu ne connais pas l'œuvre et que la page ne dit rien de l'histoire, reste bref plutôt que d'inventer une intrigue.
- N'invente aucune caractéristique matérielle (taille, matière, contenu, âge conseillé) absente des informations fournies.
- Si des notes ont été saisies (taille, couleur, modèle, pointure…), garde ces précisions dans la description.
- Le nom de la liste et les autres envies servent seulement à comprendre le contexte : ne les cite pas.
TXT;

    private const SCHEMA = [
        'type' => 'OBJECT',
        'properties' => [
            'description' => ['type' => 'STRING'],
        ],
        'required' => ['description'],
    ];

    public function __construct(private GeminiService $gemini)
    {
    }

    /**
     * @param array{title?:string, url?:string, price?:mixed, currency?:string, category?:string,
     *              notes?:string, previous_description?:string, page_description?:string, list_name?:string, siblings?:string[]} $context
     */
    public function describe(array $context): string
    {
        if (trim((string) ($context['title'] ?? '')) === '') {
            throw new LlmException("Donne d'abord un titre à l'article.");
        }

        $payload = $this->gemini->generateJson(self::SYSTEM, self::buildPrompt($context), self::SCHEMA, 0.7);
        $text = self::clean((string) ($payload['description'] ?? ''));
        if ($text === '') {
            throw new LlmException("L'IA n'a pas su décrire cet article.");
        }

        return $text;
    }

    /** Le message envoye au modele : uniquement les informations connues, une par ligne. */
    public static function buildPrompt(array $context): string
    {
        $lines = ['Article : ' . self::cut($context['title'] ?? '', 300)];

        $shop = UrlUtils::getDomainLabel((string) ($context['url'] ?? ''));
        if ($shop !== '') {
            $lines[] = 'Vendu sur : ' . $shop . ' (ne pas le citer)';
        }

        $price = is_numeric($context['price'] ?? null) ? (float) $context['price'] : 0.0;
        if ($price > 0) {
            $currency = self::cut($context['currency'] ?? 'EUR', 3) ?: 'EUR';
            $lines[] = 'Prix indicatif : ' . number_format($price, 2, ',', ' ') . ' ' . $currency . ' (pour situer la gamme, ne pas le citer)';
        }

        $category = self::cut($context['category'] ?? '', 100);
        if ($category !== '') {
            $lines[] = 'Catégorie : ' . $category;
        }

        $listName = self::cut($context['list_name'] ?? '', 100);
        if ($listName !== '') {
            $lines[] = 'Nom de la liste : « ' . $listName . ' »';
        }

        $page = self::cut($context['page_description'] ?? '', 2000);
        if ($page !== '') {
            $lines[] = '';
            $lines[] = 'Texte trouvé sur la page du produit :';
            $lines[] = $page;
        }

        $notes = self::cut($context['notes'] ?? '', 1000);
        if ($notes !== '') {
            $lines[] = '';
            $lines[] = 'Notes déjà saisies par le propriétaire de la liste :';
            $lines[] = $notes;
        }

        $previous = self::cut($context['previous_description'] ?? '', 1000);
        if ($previous !== '') {
            $lines[] = '';
            $lines[] = 'Ancienne description, à réécrire entièrement (n\'en garde que les précisions concrètes comme taille, couleur, modèle ; ni sa formulation ni son ton) :';
            $lines[] = $previous;
        }

        $siblings = array_values(array_filter(array_map(
            fn ($t) => self::cut((string) $t, 120),
            array_slice((array) ($context['siblings'] ?? []), 0, 12)
        )));
        if ($siblings !== []) {
            $lines[] = '';
            $lines[] = 'Autres envies de la même liste (contexte seulement) : ' . implode(' ; ', $siblings);
        }

        return implode("\n", $lines);
    }

    /** Espaces normalises, guillemets d'encadrement retires, longueur bornee a une phrase complete. */
    public static function clean(string $text): string
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', $text));
        $text = trim($text, " \"'«»“”");

        if (mb_strlen($text) <= self::MAX_LENGTH) {
            return $text;
        }

        $head = mb_substr($text, 0, self::MAX_LENGTH);
        $end = max(mb_strrpos($head, '. ') ?: 0, mb_strrpos($head, '! ') ?: 0, mb_strrpos($head, '? ') ?: 0);

        return $end > 0 ? mb_substr($head, 0, $end + 1) : rtrim($head) . '…';
    }

    private static function cut(mixed $value, int $max): string
    {
        $value = trim((string) preg_replace('/\s+/u', ' ', strip_tags((string) $value)));

        return mb_strlen($value) > $max ? mb_substr($value, 0, $max) . '…' : $value;
    }
}
