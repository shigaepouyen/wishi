<?php
namespace App\Controllers;

use App\Utils\AdminAuth;
use App\Utils\Database;
use App\Utils\Security;
use Exception;

class ProfileController {
    /**
     * Slugs réservés par l'infrastructure publique (fichiers/scripts/dossiers existants
     * ou noms trop proches de routes internes) : jamais attribuables à un profil.
     */
    private const RESERVED_SLUGS = [
        'api', 'assets', 'hub', 'list', 'view', 'index', 'universe', 'profile',
        'manifest', 'sw', 'data', 'vendor', 'robots', 'config', 'src', 'views', 'scripts',
    ];

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

    /**
     * Hub public : profil + uniquement ses listes marquées hub_visible=1.
     * Retourne null si profil introuvable OU si aucune liste visible,
     * pour ne jamais laisser deviner l'existence d'un slug par la réponse.
     */
    public function publicHub(string $slug): ?array {
        $db = Database::getConnection();

        $stmt = $db->prepare("SELECT id, name, slug, emoji, color FROM profiles WHERE slug = ?");
        $stmt->execute([$slug]);
        $profile = $stmt->fetch();

        if (!$profile) {
            return null;
        }

        $stmtLists = $db->prepare("
            SELECT l.id, l.name, l.slug_public,
            (SELECT COUNT(*) FROM items WHERE list_id = l.id) as count
            FROM lists l
            WHERE l.profile_id = ? AND l.hub_visible = 1
            ORDER BY l.created_at DESC
        ");
        $stmtLists->execute([$profile['id']]);
        $lists = $stmtLists->fetchAll();

        if (empty($lists)) {
            return null;
        }

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

    /**
     * Vérifie si un slug candidat est déjà pris : par un profil existant,
     * par l'historique des anciens slugs (jamais réattribuables), ou réservé
     * par l'infrastructure publique.
     */
    private function slugIsTaken($db, string $slug, ?int $excludeProfileId = null): bool {
        if (in_array($slug, self::RESERVED_SLUGS, true)) {
            return true;
        }

        $sql = "SELECT COUNT(*) FROM profiles WHERE slug = ?";
        $params = [$slug];
        if ($excludeProfileId !== null) {
            $sql .= " AND id != ?";
            $params[] = $excludeProfileId;
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        if ((int)$stmt->fetchColumn() > 0) {
            return true;
        }

        $stmt = $db->prepare("SELECT COUNT(*) FROM profile_slug_history WHERE slug = ?");
        $stmt->execute([$slug]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Résout un ancien slug de profil (via profile_slug_history) vers le profil
     * actuel, s'il existe encore. Utilisé pour rediriger vers la forme actuelle
     * d'une URL publique de hub après un renommage.
     */
    public function resolveHistoricalSlug(string $slug): ?array {
        $db = Database::getConnection();

        $stmt = $db->prepare("
            SELECT p.slug
            FROM profile_slug_history h
            JOIN profiles p ON p.id = h.profile_id
            WHERE h.slug = ?
        ");
        $stmt->execute([$slug]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return ['current_slug' => $row['slug']];
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

        // Collision (profil existant, slug historique, ou slug réservé) : suffixe numérique
        if ($this->slugIsTaken($db, $slug)) {
            $slug .= '-' . rand(100, 999);
        }

        try {
            $adminSlug = bin2hex(random_bytes(16));
            $adminPinHash = Security::hashAdminPin($pin);
            $stmt = $db->prepare("INSERT INTO profiles (name, slug, admin_slug, admin_pin_hash, emoji, color) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $slug, $adminSlug, $adminPinHash, $emoji, $color]);

            $profileId = (int)$db->lastInsertId();

            $historyStmt = $db->prepare("INSERT OR IGNORE INTO profile_slug_history (slug, profile_id) VALUES (?, ?)");
            $historyStmt->execute([$slug, $profileId]);

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

        // Le slug public est figé à la création : un renommage ne le modifie jamais
        // (c'est une URL publique déjà partagée, cf. profile_slug_history).
        try {
            $sql = "UPDATE profiles SET name = ?, emoji = ?, color = ?";
            $params = [$name, $emoji, $color];

            if ($pin !== '') {
                $sql .= ", admin_pin_hash = ?";
                $params[] = Security::hashAdminPin($pin);
            }

            $sql .= " WHERE id = ?";
            $params[] = $id;

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return json_encode(['success' => true]);
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
