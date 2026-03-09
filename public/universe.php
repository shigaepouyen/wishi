<?php
require_once __DIR__ . '/../vendor/autoload.php';

$slug = $_GET['slug'] ?? null;
if (!$slug) {
    header('Location: hub.php');
    exit;
}

$controller = new \App\Controllers\ProfileController();
$data = $controller->universe($slug);

if (!$data) {
    header('Location: hub.php');
    exit;
}

$profile = $data['profile'];
$lists = $data['lists'];
$color = $profile['color'] ?: 'indigo';

$title = "L'univers de " . htmlspecialchars($profile['name']) . " - Wishi";
$body_class = "bg-$color-50 p-4 md:p-10";

ob_start();
include __DIR__ . '/../views/universe_view.php';
$content = ob_get_clean();

include __DIR__ . '/../views/layouts/main.php';
