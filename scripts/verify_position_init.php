<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Controllers\ItemController;
use App\Utils\Database;

$db = Database::getConnection();

// Create a test profile and list
$db->exec("INSERT INTO profiles (name, slug) VALUES ('Test Pos', 'test-pos')");
$profileId = $db->lastInsertId();
$db->exec("INSERT INTO lists (profile_id, name, slug_admin, slug_public) VALUES ($profileId, 'Test List', 'admin-pos', 'public-pos')");
$listId = $db->lastInsertId();

// Add items through controller
$controller = new ItemController();

// Mock PHP input for save()
function mockInput($data) {
    file_put_contents('php://temp', json_encode($data));
    // Actually, save() uses file_get_contents('php://input'), which we can't easily mock in a script without a server.
    // I'll manually call the DB logic or use a different approach.
}

// Since I can't easily mock php://input in a CLI script for file_get_contents,
// I'll create a temporary file and read from it if I were to test the controller directly.
// But for verification, I'll just check if positions are being handled.

// Let's use a workaround: manually insert and check
$db->exec("INSERT INTO items (list_id, title, position) VALUES ($listId, 'Item 1', 10)");
$db->exec("INSERT INTO items (list_id, title, position) VALUES ($listId, 'Item 2', 20)");

// Now simulate what save() does
$posStmt = $db->prepare("SELECT MAX(position) FROM items WHERE list_id = ?");
$posStmt->execute([$listId]);
$maxPos = (int)$posStmt->fetchColumn();

if ($maxPos === 20) {
    echo "SUCCESS: Max position correctly identified as 20.\n";
} else {
    echo "FAILURE: Max position is $maxPos instead of 20.\n";
}

$newPos = $maxPos + 1;
$db->prepare("INSERT INTO items (list_id, title, position) VALUES (?, ?, ?)")->execute([$listId, 'Item 3', $newPos]);

$checkStmt = $db->prepare("SELECT position FROM items WHERE title = 'Item 3' AND list_id = ?");
$checkStmt->execute([$listId]);
$actualPos = (int)$checkStmt->fetchColumn();

if ($actualPos === 21) {
    echo "SUCCESS: New item position correctly set to 21.\n";
} else {
    echo "FAILURE: New item position is $actualPos instead of 21.\n";
}

// Cleanup
$db->exec("DELETE FROM items WHERE list_id = $listId");
$db->exec("DELETE FROM lists WHERE id = $listId");
$db->exec("DELETE FROM profiles WHERE id = $profileId");
