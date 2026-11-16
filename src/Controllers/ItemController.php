<?php
namespace App\Controllers;

use App\Utils\AdminAuth;
use App\Utils\Database;
use App\Utils\CurrencyUtils;
use App\Utils\Security;
use App\Utils\UrlUtils;
use PDO;

class ItemController {

    private function normalizeComparableUrl(string $url): string {
        $url = UrlUtils::cleanAmazonUrl($url);
        $url = UrlUtils::cleanAliExpressUrl($url);
        return $url;
    }
    
    private function normalizeCategory(?string $category): string {
        if (!$category) return 'Divers';
        $category = trim($category);
        if (empty($category)) return 'Divers';
        // Capitaliser la première lettre
        return mb_convert_case($category, MB_CASE_TITLE, "UTF-8");
    }

    /**
     * Vérifie si une URL existe déjà dans une liste donnée.
     */
    public function isDuplicate(int $listId, string $url, ?int $excludeId = null): bool {
        if (empty($url)) return false;

        $normalizedUrl = $this->normalizeComparableUrl($url);
        $db = Database::getConnection();

        $query = "SELECT url FROM items WHERE list_id = ?";
        $params = [$listId];

        if ($excludeId) {
            $query .= " AND id != ?";
            $params[] = $excludeId;
        }

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $existingUrl) {
            if ($this->normalizeComparableUrl((string)$existingUrl) === $normalizedUrl) {
                return true;
            }
        }

        return false;
    }

    public function save() {
        AdminAuth::start();

        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input || !isset($input['title']) || !isset($input['list_id'])) {
            return json_encode(['error' => 'Données invalides']);
        }
        if ($error = AdminAuth::ensureListAccessJson((int)$input['list_id'])) return $error;

        $url = $input['url'] ?? '';
        if (!empty($url) && $this->isDuplicate((int)$input['list_id'], $url)) {
            return json_encode(['error' => 'Ce lien existe déjà dans votre liste.']);
        }

        $category = $this->normalizeCategory($input['category'] ?? null);

        try {
            $db = Database::getConnection();
            
            // On récupère la position min actuelle pour mettre le nouveau cadeau en tête de liste
            $posStmt = $db->prepare("SELECT MIN(position) FROM items WHERE list_id = ?");
            $posStmt->execute([$input['list_id']]);
            $minPos = (int)$posStmt->fetchColumn();

            $price = (float)($input['price'] ?? 0);
            $currency = $input['currency'] ?? 'EUR';
            $priceEur = CurrencyUtils::convertToEur($price, $currency);

            $stmt = $db->prepare("INSERT INTO items (
                list_id, title, description, image_url, url, price, currency, price_eur, priority, category, position
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $stmt->execute([
                $input['list_id'],
                $input['title'],
                $input['description'] ?? '',
                $input['image_url'] ?? '',
                $this->normalizeComparableUrl($input['url'] ?? ''),
                $price,
                $currency,
                $priceEur,
                (int)($input['priority'] ?? 1),
                $category,
                $minPos - 1
            ]);

            return json_encode(['success' => true, 'id' => $db->lastInsertId()]);

        } catch (\PDOException $e) {
            // Renvoie l'erreur proprement au JavaScript au lieu de faire un Fatal Error
            return json_encode(['error' => 'Erreur base de données : ' . $e->getMessage()]);
        }
    }

    public function markAsTaken() {
        $input = json_decode(file_get_contents('php://input'), true);

        $itemId = isset($input['item_id']) ? (int)$input['item_id'] : null;
        $name = Security::sanitizeName($input['name'] ?? null, 80, 'Anonyme');
        $rawEmail = trim((string)($input['email'] ?? ''));
        $email = Security::sanitizeOptionalEmail($rawEmail);

        if (!$itemId) {
            return json_encode(['error' => 'ID de l\'article manquant']);
        }

        // L'email est facultatif (le navigateur garde deja la trace du cadeau), mais s'il est saisi il doit etre valide.
        if ($rawEmail !== '' && $email === null) {
            return json_encode(['error' => 'Cet email ne semble pas valide. Corrigez-le ou laissez la case vide.']);
        }

        try {
            $db = \App\Utils\Database::getConnection();
            $token = Security::ensureDonorToken();
            $stmt = $db->prepare("UPDATE items SET is_taken = 1, taken_by = ?, donor_email = ?, donor_token = ? WHERE id = ? AND is_taken = 0");
            $stmt->execute([$name, $email, $token, $itemId]);

            if ($stmt->rowCount() === 0) {
                return json_encode(['error' => 'Ce cadeau vient d\'être réservé par quelqu\'un d\'autre.']);
            }

            $stmt = $db->prepare("SELECT url FROM items WHERE id = ?");
            $stmt->execute([$itemId]);
            $url = (string)$stmt->fetchColumn();

            return json_encode([
                'success' => true,
                'shop_url' => $url !== '' ? $url : null,
                'shop_label' => $url !== '' ? UrlUtils::getDomainLabel($url) : null,
                'my_gifts_count' => $this->countMyGifts($token),
                'my_gifts_link' => self::myGiftsLink($token),
            ]);
        } catch (\PDOException $e) {
            return json_encode(['error' => 'Erreur base de données : ' . $e->getMessage()]);
        }
    }

    /** Lien personnel vers « Mes cadeaux », a garder pour les retrouver depuis un autre appareil. */
    public static function myGiftsLink(string $token): string {
        $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return ($https ? 'https' : 'http') . '://' . $host . '/mes-cadeaux?k=' . $token;
    }

    /**
     * Condition SQL « ce cadeau appartient a ce donateur » : reserve depuis ce navigateur,
     * ou avec un email deja utilise depuis ce navigateur (reservations faites sur un autre appareil).
     */
    private const MY_GIFTS_WHERE = "i.is_taken = 1 AND (
            i.donor_token = :token
            OR (i.donor_email IS NOT NULL AND LOWER(i.donor_email) IN (
                SELECT LOWER(donor_email) FROM items WHERE donor_token = :token AND donor_email IS NOT NULL
            ))
        )";

    public function countMyGifts(?string $token): int {
        if (!Security::isValidDonorToken($token)) return 0;
        $db = \App\Utils\Database::getConnection();
        $stmt = $db->prepare("SELECT COUNT(*) FROM items i WHERE " . self::MY_GIFTS_WHERE);
        $stmt->execute(['token' => $token]);
        return (int)$stmt->fetchColumn();
    }

    /** Les cadeaux de ce donateur, toutes personnes et toutes listes confondues. */
    public function myGifts(?string $token): array {
        if (!Security::isValidDonorToken($token)) return [];
        $db = \App\Utils\Database::getConnection();
        $stmt = $db->prepare("
            SELECT i.id, i.title, i.image_url, i.url, i.price, i.currency, i.price_eur,
                   l.name AS list_name, l.slug_public, l.slug_hub, l.hub_visible,
                   p.name AS owner_name, p.emoji AS owner_emoji, p.slug AS profile_slug, p.color
            FROM items i
            JOIN lists l ON i.list_id = l.id
            JOIN profiles p ON l.profile_id = p.id
            WHERE " . self::MY_GIFTS_WHERE . "
            ORDER BY p.name ASC, l.name ASC, i.id ASC
        ");
        $stmt->execute(['token' => $token]);
        return $stmt->fetchAll();
    }

    /** Le lien personnel ouvert sur un autre appareil : on rattache ce navigateur au meme donateur. */
    public function adoptDonorToken(?string $token): bool {
        if (!Security::isValidDonorToken($token)) return false;
        $db = \App\Utils\Database::getConnection();
        $stmt = $db->prepare("SELECT 1 FROM items WHERE donor_token = ? AND is_taken = 1 LIMIT 1");
        $stmt->execute([$token]);
        if (!$stmt->fetchColumn()) return false;
        $this->switchDonorToken($token);
        return true;
    }

    /**
     * Rattache ce navigateur a un autre identifiant. Les cadeaux deja reserves depuis ce navigateur
     * suivent (fusion), pour ne pas disparaitre de sa page « Mes cadeaux ».
     */
    private function switchDonorToken(string $token): void {
        $current = Security::getDonorToken();
        if ($current !== null && $current !== $token) {
            $db = \App\Utils\Database::getConnection();
            $stmt = $db->prepare("UPDATE items SET donor_token = ? WHERE donor_token = ?");
            $stmt->execute([$token, $current]);
        }
        Security::setDonorCookie($token);
    }

    /**
     * « J'ai change de telephone » : retrouve les cadeaux reserves avec cet email
     * et les rattache a ce navigateur. Limite d'essais contre la devinette d'emails.
     */
    public function findGiftsByEmail() {
        $input = json_decode(file_get_contents('php://input'), true);
        $email = Security::sanitizeOptionalEmail($input['email'] ?? null);
        if ($email === null) {
            return json_encode(['error' => 'Indiquez l\'email utilisé au moment de réserver.']);
        }

        if (\App\Utils\LoginThrottle::secondsLocked(0, 'gifts') > 0) {
            return json_encode(['error' => 'Trop d\'essais. Réessayez dans quelques minutes.']);
        }

        $db = \App\Utils\Database::getConnection();
        $stmt = $db->prepare("SELECT id, donor_token FROM items WHERE is_taken = 1 AND LOWER(donor_email) = LOWER(?) ORDER BY id DESC");
        $stmt->execute([$email]);
        $rows = $stmt->fetchAll();
        if (!$rows) {
            \App\Utils\LoginThrottle::registerFailure(0, 'gifts');
            return json_encode(['error' => 'Aucun cadeau réservé avec cet email.']);
        }

        // On reprend l'identifiant d'un appareil deja utilise (les deux appareils voient alors la meme page),
        // sinon (anciennes reservations) on en cree un et on y rattache les cadeaux.
        $existing = array_values(array_filter(array_column($rows, 'donor_token'), [Security::class, 'isValidDonorToken']));
        if ($existing) {
            $this->switchDonorToken($existing[0]);
        } else {
            $token = Security::ensureDonorToken();
            $stmt = $db->prepare("UPDATE items SET donor_token = ? WHERE is_taken = 1 AND donor_token IS NULL AND LOWER(donor_email) = LOWER(?)");
            $stmt->execute([$token, $email]);
        }

        return json_encode(['success' => true, 'count' => count($rows)]);
    }

    public function cancelReservation() {
        $input = json_decode(file_get_contents('php://input'), true);
        $itemId = isset($input['item_id']) ? (int)$input['item_id'] : null;

        if (!$itemId) {
            return json_encode(['error' => 'ID de l\'article manquant']);
        }

        try {
            $db = \App\Utils\Database::getConnection();

            // Seul le donateur (meme navigateur, ou navigateur rattache par lien/email) peut annuler.
            $stmt = $db->prepare("SELECT i.id FROM items i WHERE i.id = :id AND " . self::MY_GIFTS_WHERE);
            $stmt->execute(['id' => $itemId, 'token' => Security::getDonorToken() ?? '']);

            if (!$stmt->fetchColumn()) {
                return json_encode(['error' => 'Ce cadeau n\'est pas (ou plus) réservé à votre nom.']);
            }

            $stmt = $db->prepare("UPDATE items SET is_taken = 0, taken_by = NULL, donor_email = NULL, donor_token = NULL WHERE id = ?");
            $stmt->execute([$itemId]);

            return json_encode(['success' => true]);
        } catch (\PDOException $e) {
            return json_encode(['error' => 'Erreur base de données : ' . $e->getMessage()]);
        }
    }

    public function update() {
        AdminAuth::start();

        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;

        if (!$id) return json_encode(['error' => 'ID manquant']);
        if ($error = AdminAuth::ensureItemAccessJson((int)$id)) return $error;

        $url = $input['url'] ?? '';

        try {
            $db = Database::getConnection();

            // On doit retrouver le list_id pour l'item actuel si non fourni
            $listId = $input['list_id'] ?? null;
            if (!$listId) {
                $stmt = $db->prepare("SELECT list_id FROM items WHERE id = ?");
                $stmt->execute([$id]);
                $listId = $stmt->fetchColumn();
            }

            if (!empty($url) && $this->isDuplicate((int)$listId, $url, (int)$id)) {
                return json_encode(['error' => 'Ce lien existe déjà dans votre liste.']);
            }

            $category = $this->normalizeCategory($input['category'] ?? null);
            $price = (float)($input['price'] ?? 0);
            $currency = $input['currency'] ?? 'EUR';
            $priceEur = CurrencyUtils::convertToEur($price, $currency);

            $stmt = $db->prepare("UPDATE items SET 
                title = ?, price = ?, currency = ?, price_eur = ?, priority = ?, category = ?, description = ?, image_url = ?, url = ?
                WHERE id = ?");
            
            $stmt->execute([
                $input['title'],
                $price,
                $currency,
                $priceEur,
                (int)$input['priority'],
                $category,
                $input['description'] ?? '',
                $input['image_url'] ?? '',
                $this->normalizeComparableUrl($input['url'] ?? ''),
                $id
            ]);

            return json_encode(['success' => true]);
        } catch (\PDOException $e) {
            return json_encode(['error' => $e->getMessage()]);
        }
    }

    public function delete() {
        AdminAuth::start();

        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;

        if (!$id) return json_encode(['error' => 'ID manquant']);
        if ($error = AdminAuth::ensureItemAccessJson((int)$id)) return $error;

        try {
            $db = \App\Utils\Database::getConnection();
            $stmt = $db->prepare("DELETE FROM items WHERE id = ?");
            $stmt->execute([$id]);
            return json_encode(['success' => true]);
        } catch (\PDOException $e) {
            return json_encode(['error' => $e->getMessage()]);
        }
    }

    public function reorder() {
        AdminAuth::start();

        $input = json_decode(file_get_contents('php://input'), true);
        $ids = $input['ids'] ?? []; // Tableau d'IDs dans le nouvel ordre

        if (empty($ids)) return json_encode(['error' => 'Aucun ID reçu']);

        try {
            $db = \App\Utils\Database::getConnection();
            $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
            if (empty($ids)) {
                return json_encode(['error' => 'Aucun ID valide reçu']);
            }

            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $listStmt = $db->prepare("SELECT DISTINCT list_id FROM items WHERE id IN ($placeholders)");
            $listStmt->execute($ids);
            $listIds = array_map('intval', $listStmt->fetchAll(PDO::FETCH_COLUMN));

            if (count($listIds) !== 1) {
                return json_encode(['error' => 'Réorganisation invalide.']);
            }
            if ($error = AdminAuth::ensureListAccessJson($listIds[0])) {
                return $error;
            }

            $db->beginTransaction();

            // Avec un filtre de catégorie, seuls les items visibles sont envoyés : on les replace
            // dans les emplacements qu'ils occupaient parmi toute la liste, puis on renumérote tout.
            $allStmt = $db->prepare("SELECT id FROM items WHERE list_id = ? ORDER BY position ASC, id DESC");
            $allStmt->execute([$listIds[0]]);
            $fullOrder = array_map('intval', $allStmt->fetchAll(PDO::FETCH_COLUMN));

            $moved = array_flip($ids);
            $queue = $ids;
            foreach ($fullOrder as $slot => $id) {
                if (isset($moved[$id])) {
                    $fullOrder[$slot] = array_shift($queue);
                }
            }

            $stmt = $db->prepare("UPDATE items SET position = ? WHERE id = ?");
            foreach ($fullOrder as $index => $id) {
                $stmt->execute([$index, $id]);
            }

            $db->commit();
            return json_encode(['success' => true]);
        } catch (\Exception $e) {
            $db->rollBack();
            return json_encode(['error' => $e->getMessage()]);
        }
    }
}
