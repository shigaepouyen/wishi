<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Utils\AdminAuth;

AdminAuth::start();

// Réservé aux membres déjà connectés avec leur PIN, comme le raccourci sur le hub
if (!AdminAuth::hasAnyAdminAccess()) {
    header('Location: hub.php');
    exit;
}

$controller = new \App\Controllers\ProfileController();
$sharedLists = $controller->sharedListsByProfile();

$title = "Wishi - Les listes de la famille";
$apple_mobile_web_app_title = 'Wishi';

ob_start();
include __DIR__ . '/../views/family_lists_view.php';
$content = ob_get_clean();

include __DIR__ . '/../views/layouts/main.php';
