<?php
declare(strict_types=1);

namespace App\Utils;

/**
 * Reglages de l'IA (Gemini).
 *
 * La cle vit dans data/gemini.key, comme app_secret.key : data/ n'est jamais envoye
 * par le deploiement et reste protege par son .htaccess. config/config.php ne convient
 * pas, il est versionne et ecrase a chaque deploiement.
 * Se regle avec scripts/set_gemini_key.sh (ou la variable d'environnement GEMINI_API_KEY).
 */
final class AiConfig
{
    /** Appels Gemini autorises par jour, toutes listes confondues (garde-fou du quota gratuit). */
    public const DAILY_LIMIT = 200;

    private static ?string $key = null;

    public static function keyFile(): string
    {
        return __DIR__ . '/../../data/gemini.key';
    }

    public static function geminiKey(): string
    {
        if (self::$key !== null) {
            return self::$key;
        }

        $key = '';
        $file = self::keyFile();
        if (is_file($file) && is_readable($file)) {
            $key = trim((string) file_get_contents($file));
        }
        if ($key === '') {
            $key = trim((string) getenv('GEMINI_API_KEY'));
        }

        return self::$key = $key;
    }

    public static function isEnabled(): bool
    {
        return self::geminiKey() !== '';
    }
}
