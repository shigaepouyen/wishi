<?php
declare(strict_types=1);

namespace App\Utils;

/**
 * Compteur journalier des appels a l'IA, pour ne pas epuiser le quota gratuit de Gemini
 * (un bug cote navigateur ou un clic frenetique ne doit pas bloquer la journee).
 */
final class AiQuota
{
    public static function tryConsume(\PDO $db, int $limit = AiConfig::DAILY_LIMIT): bool
    {
        $day = date('Y-m-d');

        $db->exec("CREATE TABLE IF NOT EXISTS ai_usage (
            day TEXT PRIMARY KEY,
            calls INTEGER NOT NULL DEFAULT 0
        )");

        $db->beginTransaction();
        try {
            $stmt = $db->prepare("SELECT calls FROM ai_usage WHERE day = ?");
            $stmt->execute([$day]);
            $calls = (int) $stmt->fetchColumn();

            if ($calls >= $limit) {
                $db->rollBack();
                return false;
            }

            $db->prepare("INSERT INTO ai_usage (day, calls) VALUES (?, 1)
                          ON CONFLICT(day) DO UPDATE SET calls = calls + 1")->execute([$day]);
            $db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }
}
