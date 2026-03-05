<?php
namespace App\Controllers;

use App\Utils\Database;
use PDO;

class ItemController {
    
    public function save() {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input || !isset($input['title']) || !isset($input['list_id'])) {
            return json_encode(['error' => 'Données invalides']);
        }

        try {
            $db = Database::getConnection();
            
            // On récupère la position max actuelle pour mettre le nouveau cadeau à la fin
            $posStmt = $db->prepare("SELECT MAX(position) FROM items WHERE list_id = ?");
            $posStmt->execute([$input['list_id']]);
            $maxPos = (int)$posStmt->fetchColumn();

            $stmt = $db->prepare("INSERT INTO items (
                list_id, title, description, image_url, url, price, priority, category, position
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $stmt->execute([
                $input['list_id'],
                $input['title'],
                $input['description'] ?? '',
                $input['image_url'] ?? '',
                $input['url'] ?? '',
                (float)($input['price'] ?? 0),
                (int)($input['priority'] ?? 1),
                $input['category'] ?? 'Divers',
                $maxPos + 1
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
        $name = isset($input['name']) ? htmlspecialchars($input['name']) : 'Anonyme';

        if (!$itemId) {
            return json_encode(['error' => 'ID de l\'article manquant']);
        }

        try {
            $db = \App\Utils\Database::getConnection();
            $stmt = $db->prepare("UPDATE items SET is_taken = 1, taken_by = ? WHERE id = ?");
            $stmt->execute([$name, $itemId]);

            return json_encode(['success' => true]);
        } catch (\PDOException $e) {
            return json_encode(['error' => 'Erreur base de données : ' . $e->getMessage()]);
        }
    }

    public function update() {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;

        if (!$id) return json_encode(['error' => 'ID manquant']);

        try {
            $db = \App\Utils\Database::getConnection();
            $stmt = $db->prepare("UPDATE items SET 
                title = ?, price = ?, priority = ?, category = ?, description = ?, image_url = ? 
                WHERE id = ?");
            
            $stmt->execute([
                $input['title'],
                (float)$input['price'],
                (int)$input['priority'],
                $input['category'],
                $input['description'] ?? '',
                $input['image_url'] ?? '',
                $id
            ]);

            return json_encode(['success' => true]);
        } catch (\PDOException $e) {
            return json_encode(['error' => $e->getMessage()]);
        }
    }

    public function delete() {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;

        if (!$id) return json_encode(['error' => 'ID manquant']);

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
        $input = json_decode(file_get_contents('php://input'), true);
        $ids = $input['ids'] ?? []; // Tableau d'IDs dans le nouvel ordre

        if (empty($ids)) return json_encode(['error' => 'Aucun ID reçu']);

        try {
            $db = \App\Utils\Database::getConnection();
            $db->beginTransaction();

            $stmt = $db->prepare("UPDATE items SET position = ? WHERE id = ?");
            foreach ($ids as $index => $id) {
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