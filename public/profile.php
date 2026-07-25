<?php
require_once __DIR__ . '/../vendor/autoload.php';

$slug = strtolower(trim($_GET['slug'] ?? ''));
$is_public_surface = true;

$controller = new \App\Controllers\ProfileController();
$data = $slug !== '' ? $controller->publicHub($slug) : null;

if (!$data) {
    // Le slug ne correspond à aucun hub actif : peut-être un ancien slug renommé.
    if ($slug !== '') {
        $historical = $controller->resolveHistoricalSlug($slug);
        if ($historical && $historical['current_slug'] !== $slug) {
            header('Location: /' . $historical['current_slug'], true, 301);
            exit;
        }
    }

    http_response_code(404);
    $title = "Wishi";
    $body_class = "bg-slate-50";
    ob_start();
    include __DIR__ . '/../views/profile_public_view.php';
    $content = ob_get_clean();
    include __DIR__ . '/../views/layouts/main.php';
    exit;
}

$profile = $data['profile'];
$lists = $data['lists'];
$color = $profile['color'] ?: 'indigo';

$title = "Wishi - L'univers de " . htmlspecialchars($profile['name']);
$body_class = "bg-$color-50/30";

ob_start();
include __DIR__ . '/../views/profile_public_view.php';
$content = ob_get_clean();

include __DIR__ . '/../views/layouts/main.php';
