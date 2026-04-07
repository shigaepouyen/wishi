<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Utils\Database;

header('Content-Type: application/manifest+json; charset=UTF-8');

$profileId = isset($_GET['profile_id']) ? (int)$_GET['profile_id'] : 0;

$scriptName = $_SERVER['SCRIPT_NAME'] ?? '/manifest.php';
$basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
if ($basePath === '' || $basePath === '.') {
    $basePath = '';
}

$hubUrl = ($basePath !== '' ? $basePath : '') . '/hub.php';
$manifestId = ($basePath !== '' ? $basePath : '') . '/app';
$icon192 = ($basePath !== '' ? $basePath : '') . '/assets/img/icon-192.png';
$icon512 = ($basePath !== '' ? $basePath : '') . '/assets/img/icon-512.png';

$name = 'Wishi - Listes de Souhaits';
$shortName = 'Wishi';
$description = "L'espace famille pour partager ses vœux.";
$startUrl = $hubUrl;
$themeColor = '#4f46e5';
$scope = ($basePath !== '' ? $basePath : '') . '/';

$colorMap = [
    'indigo' => '#4f46e5',
    'rose' => '#f43f5e',
    'sky' => '#0ea5e9',
    'emerald' => '#10b981',
    'amber' => '#f59e0b',
];

if ($profileId > 0) {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT name, color FROM profiles WHERE id = ?");
    $stmt->execute([$profileId]);
    $profile = $stmt->fetch();

    if ($profile) {
        $profileName = trim((string)$profile['name']);
        $name = 'Wishi - ' . $profileName;
        $shortName = $profileName !== '' ? $profileName : 'Wishi';
        $description = "L'univers Wishi de " . $profileName . '.';
        $startUrl = ($basePath !== '' ? $basePath : '') . '/universe.php?id=' . $profileId;
        $themeColor = $colorMap[$profile['color'] ?? 'indigo'] ?? '#4f46e5';
        $manifestId = ($basePath !== '' ? $basePath : '') . '/app/profile-' . $profileId;
    }
}

echo json_encode([
    'id' => $manifestId,
    'name' => $name,
    'short_name' => $shortName,
    'description' => $description,
    'start_url' => $startUrl,
    'scope' => $scope,
    'display' => 'standalone',
    'background_color' => '#f8fafc',
    'theme_color' => $themeColor,
    'icons' => [
        [
            'src' => $icon192,
            'sizes' => '192x192',
            'type' => 'image/png',
            'purpose' => 'any maskable',
        ],
        [
            'src' => $icon512,
            'sizes' => '512x512',
            'type' => 'image/png',
        ],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
