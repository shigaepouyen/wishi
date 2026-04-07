<?php
namespace App\Controllers;

use App\Utils\AdminAuth;
use App\Utils\Database;
use App\Utils\Security;
use Exception;

class ProfileController {
    public function hub(?array $authorizedProfileIds = null) {
        try {
            $db = Database::getConnection();

            if ($authorizedProfileIds !== null) {
                $authorizedProfileIds = array_values(array_filter(array_map('intval', $authorizedProfileIds)));
                if (empty($authorizedProfileIds)) {
                    return ['profiles' => []];
                }

                $placeholders = implode(',', array_fill(0, count($authorizedProfileIds), '?'));
                $stmt = $db->prepare("SELECT * FROM profiles WHERE id IN ($placeholders) ORDER BY name ASC");
                $stmt->execute($authorizedProfileIds);
                $profiles = $stmt->fetchAll();
            } else {
                $profiles = $db->query("SELECT * FROM profiles ORDER BY name ASC")->fetchAll();
            }

            return ['profiles' => $profiles];
        } catch (Exception $e) {
            throw new Exception("Erreur de connexion : " . $e->getMessage());
        }
    }

    public function universe($slug) {
        $db = Database::getConnection();

        $stmt = $db->prepare("SELECT * FROM profiles WHERE slug = ?");
        $stmt->execute([$slug]);
        $profile = $stmt->fetch();

        if (!$profile) {
            return null;
        }

        $stmtLists = $db->prepare("
            SELECT l.*,
            (SELECT COUNT(*) FROM items WHERE list_id = l.id) as count
            FROM lists l
            WHERE l.profile_id = ?
            ORDER BY l.created_at DESC
        ");
        $stmtLists->execute([$profile['id']]);
        $lists = $stmtLists->fetchAll();

        return [
            'profile' => $profile,
            'lists' => $lists
        ];
    }

    public function universeById(int $profileId) {
        $db = Database::getConnection();

        $stmt = $db->prepare("SELECT * FROM profiles WHERE id = ?");
        $stmt->execute([$profileId]);
        $profile = $stmt->fetch();

        if (!$profile) {
            return null;
        }

        $stmtLists = $db->prepare("
            SELECT l.*,
            (SELECT COUNT(*) FROM items WHERE list_id = l.id) as count
            FROM lists l
            WHERE l.profile_id = ?
            ORDER BY l.created_at DESC
        ");
        $stmtLists->execute([$profile['id']]);
        $lists = $stmtLists->fetchAll();

        return [
            'profile' => $profile,
            'lists' => $lists
        ];
    }

    private function slugify($text) {
        $text = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        return trim($text, '-');
    }

    public function create() {
        AdminAuth::start();

        if (!AdminAuth::isBootstrapMode() && ($error = AdminAuth::ensureAdminJson())) {
            return $error;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $name = Security::sanitizeName($input['name'] ?? null, 80);
        $emoji = Security::sanitizeEmoji($input['emoji'] ?? '👤');
        $color = Security::validateColor($input['color'] ?? 'indigo');
        $pin = $input['pin'] ?? Security::defaultAdminPin();

        if (!$name) return json_encode(['error' => 'Nom requis']);

        $pin = Security::normalizePin($pin);
        if (!Security::isValidAdminPin($pin)) {
            return json_encode(['error' => 'Le PIN initial doit contenir exactement 4 chiffres.']);
        }

        $db = Database::getConnection();
        $slug = $this->slugify($name);

        // Check if slug exists
        $stmt = $db->prepare("SELECT COUNT(*) FROM profiles WHERE slug = ?");
        $stmt->execute([$slug]);
        if ($stmt->fetchColumn() > 0) {
            $slug .= '-' . rand(100, 999);
        }

        try {
            $adminSlug = bin2hex(random_bytes(16));
            $adminPinHash = Security::hashAdminPin($pin);
            $stmt = $db->prepare("INSERT INTO profiles (name, slug, admin_slug, admin_pin_hash, emoji, color) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $slug, $adminSlug, $adminPinHash, $emoji, $color]);

            $profileId = (int)$db->lastInsertId();
            AdminAuth::grantProfileAccess($profileId);

            return json_encode([
                'success' => true,
                'profile_id' => $profileId,
                'admin_url' => 'universe.php?token=' . $adminSlug,
            ]);
        } catch (Exception $e) {
            return json_encode(['error' => $e->getMessage()]);
        }
    }

    public function update() {
        AdminAuth::start();

        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        $name = Security::sanitizeName($input['name'] ?? null, 80);
        $emoji = Security::sanitizeEmoji($input['emoji'] ?? null);
        $color = Security::validateColor($input['color'] ?? null);
        $pin = $input['pin'] ?? '';

        if (!$id || !$name) return json_encode(['error' => 'Données manquantes']);
        if ($error = AdminAuth::ensureProfileAccessJson((int)$id)) return $error;

        $pin = Security::normalizePin($pin);
        if ($pin !== '' && !Security::isValidAdminPin($pin)) {
            return json_encode(['error' => 'Le PIN doit contenir exactement 4 chiffres.']);
        }

        $db = Database::getConnection();
        $newSlug = $this->slugify($name);

        // Check if slug exists and belongs to another profile
        $stmt = $db->prepare("SELECT COUNT(*) FROM profiles WHERE slug = ? AND id != ?");
        $stmt->execute([$newSlug, $id]);
        if ($stmt->fetchColumn() > 0) {
            $newSlug .= '-' . rand(100, 999);
        }

        try {
            $sql = "UPDATE profiles SET name = ?, slug = ?, emoji = ?, color = ?";
            $params = [$name, $newSlug, $emoji, $color];

            if ($pin !== '') {
                $sql .= ", admin_pin_hash = ?";
                $params[] = Security::hashAdminPin($pin);
            }

            $sql .= " WHERE id = ?";
            $params[] = $id;

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return json_encode(['success' => true, 'new_slug' => $newSlug]);
        } catch (Exception $e) {
            return json_encode(['error' => $e->getMessage()]);
        }
    }

    public function loginWithPin() {
        AdminAuth::start();

        $input = json_decode(file_get_contents('php://input'), true);
        $profileId = isset($input['profile_id']) ? (int)$input['profile_id'] : 0;
        $pin = $input['pin'] ?? '';

        if (!$profileId || !Security::isValidAdminPin($pin)) {
            http_response_code(422);
            return json_encode(['success' => false, 'error' => 'Code PIN invalide.']);
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT admin_pin_hash FROM profiles WHERE id = ?");
        $stmt->execute([$profileId]);
        $hash = $stmt->fetchColumn();

        if (!Security::verifyAdminPin($pin, is_string($hash) ? $hash : null)) {
            http_response_code(403);
            return json_encode(['success' => false, 'error' => 'PIN incorrect.']);
        }

        AdminAuth::grantProfileAccess($profileId);

        return json_encode([
            'success' => true,
            'redirect' => 'universe.php?id=' . $profileId,
        ]);
    }

    public function delete() {
        AdminAuth::start();

        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;

        if (!$id) return json_encode(['error' => 'ID manquant']);
        if ($error = AdminAuth::ensureProfileAccessJson((int)$id)) return $error;

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("DELETE FROM profiles WHERE id = ?");
            $stmt->execute([$id]);
            return json_encode(['success' => true]);
        } catch (Exception $e) {
            return json_encode(['error' => $e->getMessage()]);
        }
    }
}
