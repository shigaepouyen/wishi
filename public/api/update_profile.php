<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../vendor/autoload.php';

$input = json_decode(file_get_contents('php://input'), true);

function slugify($text) {
    $text = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

$new_slug = slugify($input['name']);

try {
    $db = \App\Utils\Database::getConnection();
    $stmt = $db->prepare("UPDATE profiles SET name = ?, emoji = ?, color = ?, slug = ? WHERE id = ?");
    $stmt->execute([
        $input['name'],
        $input['emoji'],
        $input['color'],
        $new_slug,
        $input['id']
    ]);

    echo json_encode(['success' => true, 'new_slug' => $new_slug]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}