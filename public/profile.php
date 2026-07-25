<?php
require_once __DIR__ . '/../vendor/autoload.php';

$slug = $_GET['slug'] ?? '';

if (!$slug) {
    http_response_code(404);
    die("Page introuvable.");
}

$controller = new \App\Controllers\ProfileController();
$data = $controller->publicHub($slug);

if (!$data) {
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
