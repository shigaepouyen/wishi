<?php

namespace App\Utils;

class AdminAuth {
    private const SESSION_KEY = 'wishi_admin';
    private const SESSION_LIFETIME = 2592000;

    public static function start(): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        session_set_cookie_params([
            'lifetime' => self::SESSION_LIFETIME,
            'path' => '/',
            'domain' => '',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();

        if (!isset($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = [
                'profiles' => [],
                'lists' => [],
            ];
        }
    }

    public static function isBootstrapMode(): bool {
        $db = Database::getConnection();
        return (int)$db->query("SELECT COUNT(*) FROM profiles")->fetchColumn() === 0;
    }

    public static function hasAnyAdminAccess(): bool {
        self::start();

        if (self::isBootstrapMode()) {
            return true;
        }

        $state = $_SESSION[self::SESSION_KEY] ?? [];
        return !empty($state['profiles']) || !empty($state['lists']);
    }

    public static function getAuthorizedProfileIds(): array {
        self::start();
        return array_map('intval', array_keys($_SESSION[self::SESSION_KEY]['profiles'] ?? []));
    }

    public static function authorizeListBySlug(string $slug): ?array {
        self::start();

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT id, profile_id, name FROM lists WHERE slug_admin = ?");
        $stmt->execute([$slug]);
        $list = $stmt->fetch();

        if (!$list) {
            return null;
        }

        self::grantListAccess((int)$list['id'], (int)$list['profile_id']);
        return $list;
    }

    public static function authorizeProfileByToken(string $token): ?array {
        self::start();

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT id, name FROM profiles WHERE admin_slug = ?");
        $stmt->execute([$token]);
        $profile = $stmt->fetch();

        if (!$profile) {
            return null;
        }

        self::grantProfileAccess((int)$profile['id']);
        return $profile;
    }

    public static function grantProfileAccess(int $profileId): void {
        self::start();

        if (!self::canAccessProfileId($profileId)) {
            session_regenerate_id(true);
        }

        $_SESSION[self::SESSION_KEY]['profiles'][$profileId] = true;
    }

    public static function grantListAccess(int $listId, int $profileId): void {
        self::grantProfileAccess($profileId);
        $_SESSION[self::SESSION_KEY]['lists'][$listId] = true;
    }

    public static function canAccessProfileId(int $profileId): bool {
        self::start();

        if (self::isBootstrapMode()) {
            return true;
        }

        return !empty($_SESSION[self::SESSION_KEY]['profiles'][$profileId]);
    }

    public static function canAccessListId(int $listId): bool {
        self::start();

        if (self::isBootstrapMode()) {
            return true;
        }

        if (!empty($_SESSION[self::SESSION_KEY]['lists'][$listId])) {
            return true;
        }

        $profileId = self::getProfileIdForList($listId);
        return $profileId !== null && self::canAccessProfileId($profileId);
    }

    public static function canAccessItemId(int $itemId): bool {
        $listId = self::getListIdForItem($itemId);
        return $listId !== null && self::canAccessListId($listId);
    }

    public static function requireProfilePage(int $profileId): void {
        if (!self::canAccessProfileId($profileId)) {
            header('Location: hub.php');
            exit;
        }
    }

    public static function requireListPage(int $listId): void {
        if (!self::canAccessListId($listId)) {
            $profileId = self::getProfileIdForList($listId);
            if ($profileId !== null) {
                header('Location: universe.php?id=' . $profileId);
            } else {
                header('Location: hub.php');
            }
            exit;
        }
    }

    public static function ensureAdminJson(): ?string {
        if (self::hasAnyAdminAccess()) {
            return null;
        }

        http_response_code(403);
        return json_encode(['success' => false, 'error' => 'Accès admin requis.']);
    }

    public static function ensureProfileAccessJson(int $profileId): ?string {
        if (self::canAccessProfileId($profileId)) {
            return null;
        }

        http_response_code(403);
        return json_encode(['success' => false, 'error' => 'Accès admin refusé pour ce profil.']);
    }

    public static function ensureListAccessJson(int $listId): ?string {
        if (self::canAccessListId($listId)) {
            return null;
        }

        http_response_code(403);
        return json_encode(['success' => false, 'error' => 'Accès admin refusé pour cette liste.']);
    }

    public static function ensureItemAccessJson(int $itemId): ?string {
        if (self::canAccessItemId($itemId)) {
            return null;
        }

        http_response_code(403);
        return json_encode(['success' => false, 'error' => 'Accès admin refusé pour cet élément.']);
    }

    public static function ensureValidCsrfJson(): ?string {
        self::start();

        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (Security::verifyCsrfToken($token)) {
            return null;
        }

        http_response_code(419);
        return json_encode(['success' => false, 'error' => 'Session expirée, recharge la page admin.']);
    }

    public static function getProfileIdForList(int $listId): ?int {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT profile_id FROM lists WHERE id = ?");
        $stmt->execute([$listId]);
        $profileId = $stmt->fetchColumn();
        return $profileId !== false ? (int)$profileId : null;
    }

    public static function getListIdForItem(int $itemId): ?int {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT list_id FROM items WHERE id = ?");
        $stmt->execute([$itemId]);
        $listId = $stmt->fetchColumn();
        return $listId !== false ? (int)$listId : null;
    }
}
