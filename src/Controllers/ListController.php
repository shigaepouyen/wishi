<?php
namespace App\Controllers;

use App\Utils\Database;
use PDO;

class ListController {
    
    /**
     * Affiche une liste spécifique avec ses items et ses catégories
     */
    public function show($slug, $category = '') {
        $db = \App\Utils\Database::getConnection();
        $stmt = $db->prepare("
            SELECT l.*, p.color, p.name as owner_name, p.slug as profile_slug 
            FROM lists l JOIN profiles p ON l.profile_id = p.id 
            WHERE l.slug_admin = ?
        ");
        $stmt->execute([$slug]);
        $list = $stmt->fetch();

        if (!$list) return null;

        $itemsQuery = "SELECT * FROM items WHERE list_id = ?";
        $params = [$list['id']];
        if ($category) {
            $itemsQuery .= " AND category = ?";
            $params[] = $category;
        }
        
        $items = $db->prepare($itemsQuery . " ORDER BY position ASC");
        $items->execute($params);

        $categories = $db->prepare("SELECT DISTINCT category FROM items WHERE list_id = ?");
        $categories->execute([$list['id']]);

        return [
            'list' => $list,
            'items' => $items->fetchAll(),
            'categories' => $categories->fetchAll(\PDO::FETCH_COLUMN),
            'currentCategory' => $category // Indispensable pour l'image 1 !
        ];
    }

    /**
     * Vue Publique : Utilise 'position' comme tri par défaut
     */
    public function showPublic(string $slug, string $sort = 'position', string $category = '') {
        $db = \App\Utils\Database::getConnection();
        
        // 1. Récupérer la liste
        $stmt = $db->prepare("SELECT * FROM lists WHERE slug_public = ?");
        $stmt->execute([$slug]);
        $list = $stmt->fetch();
        if (!$list) return null;

        // 2. Construire la requête des articles
        $query = "SELECT * FROM items WHERE list_id = ? AND is_taken = 0";
        $params = [$list['id']];

        if ($category !== '') {
            $query .= " AND category = ?";
            $params[] = $category;
        }

        // 3. Appliquer le tri
        switch ($sort) {
            case 'price_asc':  $orderBy = "price ASC"; break;
            case 'price_desc': $orderBy = "price DESC"; break;
            case 'priority':   $orderBy = "priority DESC, position ASC"; break;
            default:           $orderBy = "position ASC"; break;
        }
        $query .= " ORDER BY $orderBy";

        $stmtItems = $db->prepare($query);
        $stmtItems->execute($params);

        return [
            'list' => $list,
            'items' => $stmtItems->fetchAll(),
            'currentSort' => $sort,
            'currentCategory' => $category
        ];
    }

    /**
     * Récupère les catégories uniques pour l'auto-complétion
     */
    public function getCategories(int $listId) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT DISTINCT category FROM items WHERE list_id = ? AND category != ''");
        $stmt->execute([$listId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function updateSettings() {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        $newName = $input['name'] ?? null;
        $regenSlug = $input['regen_slug'] ?? false;

        if (!$id || !$newName) return json_encode(['error' => 'Données manquantes']);

        $db = \App\Utils\Database::getConnection();
        
        if ($regenSlug) {
            $newSlug = bin2hex(random_bytes(8));
            $stmt = $db->prepare("UPDATE lists SET name = ?, slug_public = ? WHERE id = ?");
            $stmt->execute([$newName, $newSlug, $id]);
        } else {
            $stmt = $db->prepare("UPDATE lists SET name = ? WHERE id = ?");
            $stmt->execute([$newName, $id]);
        }

        return json_encode(['success' => true]);
    }

    public function resetReservations(int $id) {
        $db = \App\Utils\Database::getConnection();
        $stmt = $db->prepare("UPDATE items SET is_taken = 0, taken_by = NULL WHERE list_id = ?");
        $stmt->execute([$id]);
        return json_encode(['success' => true]);
    }

    public function deleteList(int $id) {
        $db = \App\Utils\Database::getConnection();
        // On supprime les items puis la liste
        $stmt = $db->prepare("DELETE FROM items WHERE list_id = ?");
        $stmt->execute([$id]);
        $stmt = $db->prepare("DELETE FROM lists WHERE id = ?");
        $stmt->execute([$id]);
        return json_encode(['success' => true]);
    }

    public function getAllLists() {
        $db = \App\Utils\Database::getConnection();
        $stmt = $db->query("SELECT l.*, COUNT(i.id) as item_count FROM lists l LEFT JOIN items i ON l.id = i.list_id GROUP BY l.id ORDER BY l.id ASC");
        return $stmt->fetchAll();
    }

    public function createList(string $name) {
        $db = \App\Utils\Database::getConnection();
        $slug = bin2hex(random_bytes(8)); // Génère un lien public unique
        $stmt = $db->prepare("INSERT INTO lists (name, slug_public) VALUES (?, ?)");
        $stmt->execute([$name, $slug]);
        return json_encode(['success' => true, 'id' => $db->lastInsertId()]);
    }

    // Récupérer tous les profils (pour le Hub)
    public function getAllProfiles() {
        $db = \App\Utils\Database::getConnection();
        $stmt = $db->query("SELECT * FROM profiles ORDER BY name ASC");
        return $stmt->fetchAll();
    }

    // Récupérer un profil et ses listes (pour l'Univers)
    public function getProfileUniverse(int $profileId) {
        $db = \App\Utils\Database::getConnection();
        
        $profile = $db->prepare("SELECT * FROM profiles WHERE id = ?");
        $profile->execute([$profileId]);
        $profileData = $profile->fetch();

        $lists = $db->prepare("SELECT l.*, COUNT(i.id) as count FROM lists l LEFT JOIN items i ON l.id = i.list_id WHERE l.profile_id = ? GROUP BY l.id");
        $lists->execute([$profileId]);

        return [
            'profile' => $profileData,
            'lists' => $lists->fetchAll()
        ];
    }
}