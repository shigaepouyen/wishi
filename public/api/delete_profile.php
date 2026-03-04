<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../vendor/autoload.php';

$input = json_decode(file_get_contents('php://input'), true);

try {
    $db = \App\Utils\Database::getConnection();
    // On supprime le profil (les listes devraient suivre si les clés étrangères sont là, sinon il faut les supprimer aussi)
    $stmt = $db->prepare("DELETE FROM profiles WHERE id = ?");
    $stmt->execute([$input['id']]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}