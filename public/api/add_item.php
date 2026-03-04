<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../vendor/autoload.php';

// Récupération des données envoyées par le formulaire (JSON)
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['title']) || empty($input['list_id'])) {
    echo json_encode(['success' => false, 'error' => 'Données incomplètes']);
    exit;
}

try {
    $db = \App\Utils\Database::getConnection();

    // Préparation de l'insertion
    $stmt = $db->prepare("
        INSERT INTO items (
            list_id, 
            title, 
            price, 
            url, 
            image_url, 
            description, 
            category, 
            position
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    // On récupère la position max actuelle pour mettre le nouveau cadeau à la fin
    $posStmt = $db->prepare("SELECT MAX(position) FROM items WHERE list_id = ?");
    $posStmt->execute([$input['list_id']]);
    $maxPos = (int)$posStmt->fetchColumn();

    $stmt->execute([
        $input['list_id'],
        $input['title'],
        $input['price'] ?: 0,
        $input['url'] ?: null,
        $input['image_url'] ?: null,
        $input['description'] ?: null,
        $input['category'] ?: 'Divers',
        $maxPos + 1
    ]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}