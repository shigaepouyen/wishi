<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Utils\AdminAuth;
use App\Utils\Security;

AdminAuth::start();

$token = $_GET['token'] ?? null;
$profileId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$slug = $_GET['slug'] ?? null;

if ($token) {
    $profile = AdminAuth::authorizeProfileByToken($token);
    if (!$profile) {
        header('Location: hub.php');
        exit;
    }

    header('Location: universe.php?id=' . (int)$profile['id']);
    exit;
}

if (!$profileId && $slug && AdminAuth::hasAnyAdminAccess()) {
    $db = \App\Utils\Database::getConnection();
    $stmt = $db->prepare("SELECT id FROM profiles WHERE slug = ?");
    $stmt->execute([$slug]);
    $profileId = (int)$stmt->fetchColumn();
}

if (!$profileId) {
    header('Location: hub.php');
    exit;
}

$controller = new \App\Controllers\ProfileController();
$data = $controller->universeById($profileId);

if (!$data) {
    header('Location: hub.php');
    exit;
}

$profile = $data['profile'];
$lists = $data['lists'];
$color = $profile['color'] ?: 'indigo';
$csrf_token = Security::csrfToken();

if (!AdminAuth::canAccessProfileId((int)$profile['id'])) {
    $title = "Déverrouiller " . htmlspecialchars($profile['name']) . " - Wishi";
    $body_class = "bg-$color-50 p-4 md:p-10";

    ob_start();
    include __DIR__ . '/../views/profile_unlock_view.php';
    $content = ob_get_clean();

    $extra_js = '
    <script>
    function profileUnlock() {
        return {
            pin: "",
            loading: false,
            appendDigit(digit) {
                if (this.pin.length >= 4) return;
                this.pin += digit;
                if (this.pin.length === 4) {
                    this.loginWithPin();
                }
            },
            removeDigit() {
                this.pin = this.pin.slice(0, -1);
            },
            clearPin() {
                this.pin = "";
            },
            async loginWithPin() {
                if (!/^\\d{4}$/.test(this.pin) || this.loading) return;
                this.loading = true;
                const res = await fetch("api/login_profile.php", {
                    method: "POST",
                    headers: {"Content-Type": "application/json", "X-CSRF-Token": window.WISHI_CSRF},
                    body: JSON.stringify({ profile_id: ' . (int)$profile['id'] . ', pin: this.pin })
                });
                const data = await res.json();
                if (data.success) {
                    window.location.href = data.redirect;
                    return;
                }
                this.loading = false;
                this.pin = "";
                alert(data.error || "Connexion impossible");
            }
        }
    }
    </script>';

    include __DIR__ . '/../views/layouts/main.php';
    exit;
}

$title = "L'univers de " . htmlspecialchars($profile['name']) . " - Wishi";
$body_class = "bg-$color-50 p-4 md:p-10";

ob_start();
include __DIR__ . '/../views/universe_view.php';
$content = ob_get_clean();

include __DIR__ . '/../views/layouts/main.php';
