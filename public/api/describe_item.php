<?php
// Redige par IA la description courte d'un article (formulaire d'ajout ou de modification).
// Rien n'est enregistre ici : le texte revient dans le champ, le proprietaire le relit.
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 0);
set_time_limit(90);

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Services\GeminiService;
use App\Services\GiftDescriptionService;
use App\Services\LlmException;
use App\Services\ScraperService;
use App\Utils\AdminAuth;
use App\Utils\AiConfig;
use App\Utils\AiQuota;
use App\Utils\Database;
use App\Utils\Security;

header('Content-Type: application/json');

function describe_fail(string $message, int $status = 400): void {
    http_response_code($status);
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    describe_fail('Méthode non autorisée.', 405);
}

AdminAuth::start();
if ($error = AdminAuth::ensureAdminJson()) { echo $error; exit; }
if ($error = AdminAuth::ensureValidCsrfJson()) { echo $error; exit; }

$input = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($input)) {
    describe_fail('Requête invalide.');
}

$listId = (int) ($input['list_id'] ?? 0);
$itemId = (int) ($input['item_id'] ?? 0);
if ($listId <= 0) {
    describe_fail('Liste manquante.');
}
if ($error = AdminAuth::ensureListAccessJson($listId)) { echo $error; exit; }
if ($itemId > 0 && ($error = AdminAuth::ensureItemAccessJson($itemId))) { echo $error; exit; }

if (!AiConfig::isEnabled()) {
    describe_fail("L'IA n'est pas configurée sur ce Wishi.", 503);
}

$title = trim((string) ($input['title'] ?? ''));
if ($title === '') {
    describe_fail("Donne d'abord un titre à l'article.");
}

$db = Database::getConnection();

$listStmt = $db->prepare("SELECT name FROM lists WHERE id = ?");
$listStmt->execute([$listId]);
$listName = (string) $listStmt->fetchColumn();

// Les autres envies de la liste aident le modele a situer l'article (age, univers…).
$siblingStmt = $db->prepare("SELECT title FROM items WHERE list_id = ? AND id != ? ORDER BY position ASC, id DESC LIMIT 12");
$siblingStmt->execute([$listId, $itemId]);
$siblings = $siblingStmt->fetchAll(PDO::FETCH_COLUMN);

$url = trim((string) ($input['url'] ?? ''));
$pageDescription = trim((string) ($input['page_description'] ?? ''));

// Depuis le bouton de modification, la page n'a pas forcement ete relue : on va chercher
// son texte de presentation pour donner de la matiere au modele. Echec silencieux.
if ($pageDescription === '' && $url !== '' && filter_var($url, FILTER_VALIDATE_URL)) {
    try {
        Security::assertSafeExternalUrl($url);
        $page = (new ScraperService())->getLinkData($url);
        if (!isset($page['error'])) {
            $pageDescription = trim((string) ($page['description'] ?? ''));
        }
    } catch (\Throwable $e) {
        $pageDescription = '';
    }
}

if (!AiQuota::tryConsume($db)) {
    describe_fail("L'IA a assez travaillé pour aujourd'hui. Réessaie demain.", 429);
}

try {
    $service = new GiftDescriptionService(new GeminiService(AiConfig::geminiKey()));
    $description = $service->describe([
        'title' => $title,
        'url' => $url,
        'price' => $input['price'] ?? null,
        'currency' => (string) ($input['currency'] ?? 'EUR'),
        'category' => (string) ($input['category'] ?? ''),
        'notes' => (string) ($input['notes'] ?? ''),
        'page_description' => $pageDescription,
        'list_name' => $listName,
        'siblings' => $siblings,
    ]);
} catch (LlmException $e) {
    describe_fail($e->getMessage(), 502);
} catch (\Throwable $e) {
    error_log('Wishi describe_item : ' . $e->getMessage());
    describe_fail("L'IA n'a pas pu rédiger la description.", 500);
}

echo json_encode(['success' => true, 'description' => $description]);
