<?php
require_once __DIR__ . '/../vendor/autoload.php';

$controller = new \App\Controllers\ProfileController();
try {
    $data = $controller->hub();
    $profiles = $data['profiles'];

    $title = "Wishi - Le Hub Familial";
    $extra_css = ".profile-card { transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); }";

    ob_start();
    include __DIR__ . '/../views/hub_view.php';
    $content = ob_get_clean();

    include __DIR__ . '/../views/layouts/main.php';
} catch (Exception $e) {
    die($e->getMessage());
}
