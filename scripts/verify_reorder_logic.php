<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Utils\Database;

$db = Database::getConnection();

// Setup
$db->exec("INSERT INTO profiles (name, slug) VALUES ('Test Reorder', 'test-reorder')");
$profileId = $db->lastInsertId();
$db->exec("INSERT INTO lists (profile_id, name, slug_admin, slug_public) VALUES ($profileId, 'Test List', 'admin-reorder', 'public-reorder')");
$listId = $db->lastInsertId();

$db->exec("INSERT INTO items (list_id, title, position) VALUES ($listId, 'A', 0)");
$idA = $db->lastInsertId();
$db->exec("INSERT INTO items (list_id, title, position) VALUES ($listId, 'B', 1)");
$idB = $db->lastInsertId();
$db->exec("INSERT INTO items (list_id, title, position) VALUES ($listId, 'C', 2)");
$idC = $db->lastInsertId();

echo "Initial positions: A=0, B=1, C=2\n";

// Simulate reorder: C, A, B
$newOrder = [$idC, $idA, $idB];

$db->beginTransaction();
$stmt = $db->prepare("UPDATE items SET position = ? WHERE id = ?");
foreach ($newOrder as $index => $id) {
    $stmt->execute([$index, $id]);
}
$db->commit();

// Verify
$results = $db->query("SELECT title, position FROM items WHERE list_id = $listId ORDER BY position ASC")->fetchAll(PDO::FETCH_KEY_PAIR);

if ($results['C'] == 0 && $results['A'] == 1 && $results['B'] == 2) {
    echo "SUCCESS: Reorder logic verified (C=0, A=1, B=2).\n";
} else {
    echo "FAILURE: Reorder logic failed.\n";
    print_r($results);
}

// Cleanup
$db->exec("DELETE FROM items WHERE list_id = $listId");
$db->exec("DELETE FROM lists WHERE id = $listId");
$db->exec("DELETE FROM profiles WHERE id = $profileId");
