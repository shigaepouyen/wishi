<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Controllers\ListController;
use App\Utils\Database;

$db = Database::getConnection();

// Create a test profile
$db->exec("INSERT INTO profiles (name, slug, emoji, color) VALUES ('Test User', 'test-user', '🧪', 'emerald')");
$profileId = $db->lastInsertId();

// Create a test list
$publicSlug = bin2hex(random_bytes(8));
$db->exec("INSERT INTO lists (profile_id, name, slug_admin, slug_public) VALUES ($profileId, 'Test List', 'admin-test', '$publicSlug')");

$controller = new ListController();
$data = $controller->showPublic($publicSlug);

if ($data && isset($data['list']['owner_name']) && $data['list']['owner_name'] === 'Test User') {
    echo "SUCCESS: Profile info retrieved correctly.\n";
    echo "Color: " . $data['list']['color'] . "\n";
    echo "Emoji: " . $data['list']['owner_emoji'] . "\n";
} else {
    echo "FAILURE: Profile info NOT retrieved correctly.\n";
    print_r($data['list']);
}

// Cleanup
$db->exec("DELETE FROM items WHERE list_id IN (SELECT id FROM lists WHERE profile_id = $profileId)");
$db->exec("DELETE FROM lists WHERE profile_id = $profileId");
$db->exec("DELETE FROM profiles WHERE id = $profileId");
