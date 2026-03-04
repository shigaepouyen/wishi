<?php
require_once __DIR__ . '/../vendor/autoload.php';

$listController = new \App\Controllers\ListController();

// On récupère le slug admin
$slug = $_GET['slug'] ?? null;
$catFilter = $_GET['cat'] ?? ''; 

if (!$slug) {
    header('Location: hub.php');
    exit;
}

// Le controller cherche maintenant par slug
$data = $listController->show($slug, $catFilter);

if (!$data) {
    die("Désolé, cette liste est introuvable.");
}

include __DIR__ . '/../views/list_view.php';