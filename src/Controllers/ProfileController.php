<?php
namespace App\Controllers;

use App\Utils\Database;
use Exception;

class ProfileController {
    public function hub() {
        try {
            $db = Database::getConnection();
            $profiles = $db->query("SELECT * FROM profiles ORDER BY name ASC")->fetchAll();
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

    private function slugify($text) {
        $text = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        return trim($text, '-');
    }

    public function create() {
        $input = json_decode(file_get_contents('php://input'), true);
        $name = $input['name'] ?? null;
        $emoji = $input['emoji'] ?? '👤';
        $color = $input['color'] ?? 'indigo';

        if (!$name) return json_encode(['error' => 'Nom requis']);

        $db = Database::getConnection();
        $slug = $this->slugify($name);

        // Check if slug exists
        $stmt = $db->prepare("SELECT COUNT(*) FROM profiles WHERE slug = ?");
        $stmt->execute([$slug]);
        if ($stmt->fetchColumn() > 0) {
            $slug .= '-' . rand(100, 999);
        }

        try {
            $stmt = $db->prepare("INSERT INTO profiles (name, slug, emoji, color) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $slug, $emoji, $color]);
            return json_encode(['success' => true]);
        } catch (Exception $e) {
            return json_encode(['error' => $e->getMessage()]);
        }
    }

    public function update() {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        $name = $input['name'] ?? null;
        $emoji = $input['emoji'] ?? null;
        $color = $input['color'] ?? null;

        if (!$id || !$name) return json_encode(['error' => 'Données manquantes']);

        $db = Database::getConnection();
        $newSlug = $this->slugify($name);

        // Check if slug exists and belongs to another profile
        $stmt = $db->prepare("SELECT COUNT(*) FROM profiles WHERE slug = ? AND id != ?");
        $stmt->execute([$newSlug, $id]);
        if ($stmt->fetchColumn() > 0) {
            $newSlug .= '-' . rand(100, 999);
        }

        try {
            $stmt = $db->prepare("UPDATE profiles SET name = ?, slug = ?, emoji = ?, color = ? WHERE id = ?");
            $stmt->execute([$name, $newSlug, $emoji, $color, $id]);
            return json_encode(['success' => true, 'new_slug' => $newSlug]);
        } catch (Exception $e) {
            return json_encode(['error' => $e->getMessage()]);
        }
    }

    public function delete() {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;

        if (!$id) return json_encode(['error' => 'ID manquant']);

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
