<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../vendor/autoload.php';

$input = json_decode(file_get_contents('php://input'), true);
$name = $input['name'] ?? null;
$profile_id = $input['profile_id'] ?? null;

function slugify($text) {
    $text = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

try {
    $db = \App\Utils\Database::getConnection();

    // 1. On récupère le prénom du profil pour l'inclure dans le slug
    $stmtP = $db->prepare("SELECT name FROM profiles WHERE id = ?");
    $stmtP->execute([$profile_id]);
    $profileName = $stmtP->fetchColumn();

    // 2. On génère le slug public lisible (ex: noel-de-zoe)
    $slug_public = slugify($name . '-de-' . $profileName);
    
    // Sécurité : si le slug existe déjà, on ajoute un chiffre aléatoire
    $check = $db->prepare("SELECT id FROM lists WHERE slug_public = ?");
    $check->execute([$slug_public]);
    if ($check->fetch()) {
        $slug_public .= '-' . rand(10, 99);
    }

    // 3. On garde un token pour le slug_admin (plus sûr pour gérer la liste)
    $slug_admin = bin2hex(random_bytes(16));

    $stmt = $db->prepare("INSERT INTO lists (profile_id, name, slug_admin, slug_public, is_surprise) VALUES (?, ?, ?, ?, 1)");
    $stmt->execute([$profile_id, $name, $slug_admin, $slug_public]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}