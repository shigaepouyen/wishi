<?php

namespace App\Utils;

class Security {
    private const COLOR_PALETTE = ['indigo', 'rose', 'sky', 'emerald', 'amber'];
    private const DEFAULT_ADMIN_PIN = '0000';

    public static function getAppSecret(): string {
        $secretFile = __DIR__ . '/../../data/app_secret.key';

        if (is_file($secretFile)) {
            $secret = trim((string)file_get_contents($secretFile));
            if ($secret !== '') {
                return $secret;
            }
        }

        if (!is_dir(dirname($secretFile))) {
            mkdir(dirname($secretFile), 0700, true);
        }

        $secret = bin2hex(random_bytes(32));
        file_put_contents($secretFile, $secret, LOCK_EX);
        @chmod($secretFile, 0600);

        return $secret;
    }

    public static function csrfToken(): string {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new \RuntimeException('La session doit être démarrée avant le CSRF.');
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrfToken(?string $token): bool {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }

        $expected = $_SESSION['csrf_token'] ?? '';
        return is_string($token) && $expected !== '' && hash_equals($expected, $token);
    }

    public static function validateColor(?string $color): string {
        $color = strtolower(trim((string)$color));
        return in_array($color, self::COLOR_PALETTE, true) ? $color : 'indigo';
    }

    public static function sanitizeEmoji(?string $emoji): string {
        $emoji = trim(strip_tags((string)$emoji));
        $emoji = preg_replace('/[\x00-\x1F\x7F]/u', '', $emoji);
        $emoji = mb_substr($emoji, 0, 8);
        return $emoji !== '' ? $emoji : '👤';
    }

    public static function sanitizeName(?string $value, int $maxLength = 80, string $default = ''): string {
        $value = trim(strip_tags((string)$value));
        $value = preg_replace('/\s+/u', ' ', $value);
        $value = mb_substr($value, 0, $maxLength);
        return $value !== '' ? $value : $default;
    }

    public static function sanitizeOptionalEmail(?string $email): ?string {
        $email = trim((string)$email);
        if ($email === '') {
            return null;
        }

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    public static function defaultAdminPin(): string {
        return self::DEFAULT_ADMIN_PIN;
    }

    public static function normalizePin(?string $pin): string {
        return preg_replace('/\D+/', '', (string)$pin);
    }

    public static function isValidAdminPin(?string $pin): bool {
        $pin = self::normalizePin($pin);
        return preg_match('/^\d{4}$/', $pin) === 1;
    }

    public static function hashAdminPin(string $pin): string {
        return password_hash(self::normalizePin($pin), PASSWORD_DEFAULT);
    }

    public static function verifyAdminPin(?string $pin, ?string $hash): bool {
        $pin = self::normalizePin($pin);
        return $pin !== '' && is_string($hash) && $hash !== '' && password_verify($pin, $hash);
    }

    public static function makeReservationCookieValue(int $itemId): string {
        return hash_hmac('sha256', 'reservation:' . $itemId, self::getAppSecret());
    }

    public static function hasValidReservationCookie(int $itemId, ?string $cookieValue): bool {
        if (!$cookieValue) {
            return false;
        }

        return hash_equals(self::makeReservationCookieValue($itemId), $cookieValue);
    }

    public static function assertSafeExternalUrl(string $url): void {
        $parts = parse_url($url);

        if (!$parts || empty($parts['scheme']) || empty($parts['host'])) {
            throw new \InvalidArgumentException("URL invalide.");
        }

        $scheme = strtolower((string)$parts['scheme']);
        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new \InvalidArgumentException("Seules les URLs http(s) sont autorisées.");
        }

        if (!empty($parts['user']) || !empty($parts['pass'])) {
            throw new \InvalidArgumentException("Les URLs avec authentification intégrée sont interdites.");
        }

        $port = isset($parts['port']) ? (int)$parts['port'] : null;
        if ($port !== null && !in_array($port, [80, 443], true)) {
            throw new \InvalidArgumentException("Port interdit pour le scraping.");
        }

        $host = strtolower((string)$parts['host']);
        if (in_array($host, ['localhost', 'localhost.localdomain'], true) || str_ends_with($host, '.local') || str_ends_with($host, '.internal')) {
            throw new \InvalidArgumentException("Hôte interdit pour le scraping.");
        }

        self::assertPublicIp($host);

        $records = [];
        if (function_exists('dns_get_record') && !filter_var($host, FILTER_VALIDATE_IP)) {
            $records = array_merge(
                dns_get_record($host, DNS_A) ?: [],
                dns_get_record($host, DNS_AAAA) ?: []
            );
        }

        foreach ($records as $record) {
            $ip = $record['ip'] ?? $record['ipv6'] ?? null;
            if ($ip) {
                self::assertPublicIp($ip);
            }
        }
    }

    private static function assertPublicIp(string $hostOrIp): void {
        if (!filter_var($hostOrIp, FILTER_VALIDATE_IP)) {
            return;
        }

        $isPublic = filter_var(
            $hostOrIp,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );

        if ($isPublic === false) {
            throw new \InvalidArgumentException("Les adresses internes ne sont pas autorisées.");
        }
    }
}
