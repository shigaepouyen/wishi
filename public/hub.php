<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Utils\AdminAuth;
use App\Utils\Security;

AdminAuth::start();

$controller = new \App\Controllers\ProfileController();
try {
    $authorizedProfileIds = AdminAuth::getAuthorizedProfileIds();
    $data = $controller->hub();
    $profiles = $data['profiles'];
    $csrf_token = Security::csrfToken();
    $hasAdminAccess = AdminAuth::hasAnyAdminAccess();

    $title = "Wishi - Le Hub Familial";
    $apple_mobile_web_app_title = 'Wishi';
    $extra_css = ".profile-card { transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); }";

    ob_start();
    include __DIR__ . '/../views/hub_view.php';
    $content = ob_get_clean();

    include __DIR__ . '/../views/layouts/main.php';
} catch (Exception $e) {
    die($e->getMessage());
}
