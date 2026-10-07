<?php
namespace App\Utils;

use PDO;

/**
 * Limite les essais de PIN pour empecher de deviner un code a 4 chiffres par force brute.
 *
 * Deux compteurs par profil :
 *  - par appareil (IP hachee) : 5 echecs en 15 min => 15 min de blocage
 *  - tous appareils confondus  : 20 echecs en 1 h  => 1 h de blocage (attaque repartie)
 * Les IP ne sont jamais stockees en clair.
 */
class LoginThrottle {
    private const RULES = [
        'device'  => ['max' => 5,  'window' => 900,  'lock' => 900],
        'profile' => ['max' => 20, 'window' => 3600, 'lock' => 3600],
    ];

    private static function db(): PDO {
        $db = Database::getConnection();
        $db->exec("CREATE TABLE IF NOT EXISTS login_attempts (
            key TEXT PRIMARY KEY,
            failures INTEGER NOT NULL DEFAULT 0,
            window_start INTEGER NOT NULL,
            locked_until INTEGER NOT NULL DEFAULT 0
        )");
        return $db;
    }

    private static function keys(int $profileId): array {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'inconnue';
        return [
            'device'  => 'device:' . hash('sha256', Security::getAppSecret() . '|' . $ip) . ':' . $profileId,
            'profile' => 'profile:' . $profileId,
        ];
    }

    /** Secondes de blocage restantes (0 si l'essai est autorise). */
    public static function secondsLocked(int $profileId): int {
        $db = self::db();
        $stmt = $db->prepare("SELECT MAX(locked_until) FROM login_attempts WHERE key IN (?, ?)");
        $stmt->execute(array_values(self::keys($profileId)));
        return max(0, (int)$stmt->fetchColumn() - time());
    }

    public static function registerFailure(int $profileId): void {
        $db = self::db();
        $now = time();
        foreach (self::keys($profileId) as $type => $key) {
            $rule = self::RULES[$type];
            $stmt = $db->prepare("SELECT failures, window_start FROM login_attempts WHERE key = ?");
            $stmt->execute([$key]);
            $row = $stmt->fetch();

            $failures = 1;
            $windowStart = $now;
            if ($row && $now - (int)$row['window_start'] < $rule['window']) {
                $failures = (int)$row['failures'] + 1;
                $windowStart = (int)$row['window_start'];
            }

            $lockedUntil = 0;
            if ($failures >= $rule['max']) {
                $lockedUntil = $now + $rule['lock'];
                $failures = 0;
                $windowStart = $now;
            }

            $db->prepare("INSERT INTO login_attempts (key, failures, window_start, locked_until) VALUES (?, ?, ?, ?)
                ON CONFLICT(key) DO UPDATE SET failures = excluded.failures, window_start = excluded.window_start,
                locked_until = MAX(login_attempts.locked_until, excluded.locked_until)")
                ->execute([$key, $failures, $windowStart, $lockedUntil]);
        }

        // Menage : les entrees expirees ne servent plus a rien
        $db->prepare("DELETE FROM login_attempts WHERE locked_until < ? AND window_start < ?")
            ->execute([$now, $now - 3600]);
    }

    /** Un PIN correct efface le compteur de l'appareil (pas celui du profil, pour ne pas aider une attaque repartie). */
    public static function registerSuccess(int $profileId): void {
        self::db()->prepare("DELETE FROM login_attempts WHERE key = ?")->execute([self::keys($profileId)['device']]);
    }
}
