<?php
require_once __DIR__ . '/../vendor/autoload.php';

$is_public_surface = true;

$slug = $_GET['s'] ?? '';
$profileSlug = strtolower(trim($_GET['p'] ?? ''));
$hubSlug = strtolower(trim($_GET['l'] ?? ''));
$sort = $_GET['sort'] ?? 'position';
$catFilter = $_GET['cat'] ?? '';
$showTaken = isset($_GET['show_taken']) && $_GET['show_taken'] == '1';

$controller = new \App\Controllers\ListController();

// Deux facons d'arriver sur une liste :
//  - /<profil>/<liste> : navigation dans un hub public, on garde le contexte du profil
//  - view.php?s=<slug>  : lien unique partage pour cette liste seule, aucun contexte
$fromHub = $profileSlug !== '' && $hubSlug !== '';

if ($fromHub) {
    $data = $controller->showPublicFromHub($profileSlug, $hubSlug, $sort, $catFilter, $showTaken);

    if (!$data) {
        // Le profil a peut-etre ete renomme : on rejoue le lien sur son slug actuel.
        $historical = (new \App\Controllers\ProfileController())->resolveHistoricalSlug($profileSlug);
        if ($historical && $historical['current_slug'] !== $profileSlug) {
            header('Location: /' . rawurlencode($historical['current_slug']) . '/' . rawurlencode($hubSlug), true, 301);
            exit;
        }

        http_response_code(404);
        die("Cette liste n'existe pas.");
    }
} else {
    if (!$slug) die("Lien invalide.");
    $data = $controller->showPublic($slug, $sort, $catFilter, $showTaken);
    if (!$data) {
        http_response_code(404);
        die("Cette liste n'existe pas.");
    }
}

$allCategories = $controller->getCategories($data['list']['id']);

// Contexte de navigation passe a la vue : base des liens internes et listes voisines
if ($fromHub) {
    $publicBaseUrl = '/' . rawurlencode($profileSlug) . '/' . rawurlencode($hubSlug);
    $hubUrl = '/' . rawurlencode($profileSlug);
    $hubSiblings = $controller->getHubSiblings((int)$data['list']['profile_id'], (int)$data['list']['id']);
} else {
    $publicBaseUrl = 'view.php?s=' . rawurlencode($data['list']['slug_public']);
    $hubUrl = null;
    $hubSiblings = [];
}

// Extraction des infos de l'univers
$color = $data['list']['color'] ?? 'indigo';
$ownerName = $data['list']['owner_name'] ?? $data['list']['name'];
$ownerEmoji = $data['list']['owner_emoji'] ?? '🎁';

$title = "Wishi - Liste de " . htmlspecialchars($ownerName);
$body_class = "bg-$color-50/30";
$body_attrs = 'x-data="publicList()"';
$extra_css = '
    .truncate-2-lines { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .line-clamp-3 { display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
';

ob_start();
include __DIR__ . '/../views/public_view.php';
$content = ob_get_clean();

$extra_js = '
<script>
    function publicList() {
        return {
            modalOpen: false,
            cancelModalOpen: false,
            selectedItemId: null,
            selectedItemTitle: "",
            donorName: "",
            donorEmail: "",
            cancelEmail: "",
            openModal(id, title) {
                this.selectedItemId = id;
                this.selectedItemTitle = title;
                this.modalOpen = true;
            },
            openCancelModal(id, title) {
                this.selectedItemId = id;
                this.selectedItemTitle = title;
                this.cancelModalOpen = true;
            },
            async confirmGift() {
                if(!this.donorName) return alert("S\'il vous plaît, indiquez votre nom !");
                const response = await fetch("/api/mark_taken.php", {
                    method: "POST",
                    headers: {"Content-Type": "application/json"},
                    body: JSON.stringify({ item_id: this.selectedItemId, name: this.donorName, email: this.donorEmail })
                });
                const result = await response.json();
                if(result.success) window.location.reload();
                else alert(result.error || "Une erreur est survenue");
            },
            async cancelGift(id, useEmail = false) {
                const body = { item_id: parseInt(id || this.selectedItemId) };
                if (useEmail) {
                    if (!this.cancelEmail) return alert("Veuillez entrer votre email.");
                    body.email = this.cancelEmail;
                }
                const response = await fetch("/api/cancel_reservation.php", {
                    method: "POST",
                    headers: {"Content-Type": "application/json"},
                    body: JSON.stringify(body)
                });
                const result = await response.json();
                if(result.success) window.location.reload();
                else alert(result.error || "Une erreur est survenue");
            }
        }
    }
</script>
';

include __DIR__ . '/../views/layouts/main.php';
