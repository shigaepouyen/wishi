<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Utils\Database;

header('Content-Type: application/manifest+json; charset=UTF-8');

$profileId = isset($_GET['profile_id']) ? (int)$_GET['profile_id'] : 0;

$name = 'Wishi - Listes de Souhaits';
$shortName = 'Wishi';
$description = "L'espace famille pour partager ses vœux.";
$startUrl = 'hub.php';
$themeColor = '#4f46e5';

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
        $startUrl = 'universe.php?id=' . $profileId;
        $themeColor = $colorMap[$profile['color'] ?? 'indigo'] ?? '#4f46e5';
    }
}

echo json_encode([
    'name' => $name,
    'short_name' => $shortName,
    'description' => $description,
    'start_url' => $startUrl,
    'scope' => './',
    'display' => 'standalone',
    'background_color' => '#f8fafc',
    'theme_color' => $themeColor,
    'icons' => [
        [
            'src' => 'assets/img/icon-192.png',
            'sizes' => '192x192',
            'type' => 'image/png',
            'purpose' => 'any maskable',
        ],
        [
            'src' => 'assets/img/icon-512.png',
            'sizes' => '512x512',
            'type' => 'image/png',
        ],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
