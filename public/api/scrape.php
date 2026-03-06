<?php
// On désactive l'affichage des erreurs pour ne pas polluer le JSON
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 0);

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Services\ScraperService;

header('Content-Type: application/json');

// On récupère l'URL proprement
$url = $_GET['url'] ?? null;

if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
    echo json_encode(['error' => 'URL invalide ou absente']);
    exit;
}

try {
    $scraper = new ScraperService();
    $data = $scraper->getLinkData($url);

    if (isset($data['error'])) {
        echo json_encode([
            'success' => false,
            'error' => $data['error']
        ]);
    } else {
        // On vérifie qu'on n'a pas un résultat vide/générique (site qui bloque silencieusement)
        $isGeneric = ($data['title'] === 'Sans titre' && empty($data['description']) && empty($data['image']));

        echo json_encode(array_merge([
            'success' => true,
            'is_generic' => $isGeneric
        ], $data));
    }
} catch (\Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Erreur technique : ' . $e->getMessage()
    ]);
}