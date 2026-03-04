<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../vendor/autoload.php';

$input = json_decode(file_get_contents('php://input'), true);
$name = $input['name'] ?? null;

if (!$name) {
    echo json_encode(['success' => false, 'error' => 'Le nom est obligatoire']);
    exit;
}

// --- FONCTION POUR GÉNÉRER LE SLUG ---
function slugify($text) {
    // Remplace les accents par des lettres normales
    $text = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text);
    // Supprime tout ce qui n'est pas lettre ou chiffre
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    // Nettoie les tirets en début et fin
    return trim($text, '-');
}

$slug = slugify($name);
$emoji = $input['emoji'] ?? '👤';
$color = $input['color'] ?? 'indigo';

try {
    $db = \App\Utils\Database::getConnection();

    // On vérifie si le slug existe déjà (pour éviter les doublons)
    $check = $db->prepare("SELECT id FROM profiles WHERE slug = ?");
    $check->execute([$slug]);
    if ($check->fetch()) {
        // Si "zoe" existe déjà, on tente "zoe-1", etc.
        $slug = $slug . '-' . rand(1, 99);
    }

    $stmt = $db->prepare("INSERT INTO profiles (name, slug, emoji, color) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $slug, $emoji, $color]);

    echo json_encode([
        'success' => true, 
        'slug' => $slug,
        'id' => $db->lastInsertId()
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}