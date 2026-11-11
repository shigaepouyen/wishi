<?php
require_once __DIR__ . '/../vendor/autoload.php';

$is_public_surface = true;

$slug = $_GET['s'] ?? '';
$profileSlug = strtolower(trim($_GET['p'] ?? ''));
$hubSlug = strtolower(trim($_GET['l'] ?? ''));
$sort = $_GET['sort'] ?? 'position';
$catFilter = $_GET['cat'] ?? '';

$controller = new \App\Controllers\ListController();

// Deux facons d'arriver sur une liste :
//  - /<profil>/<liste> : navigation dans un hub public, on garde le contexte du profil
//  - view.php?s=<slug>  : lien unique partage pour cette liste seule, aucun contexte
$fromHub = $profileSlug !== '' && $hubSlug !== '';

if ($fromHub) {
    $data = $controller->showPublicFromHub($profileSlug, $hubSlug, $sort, $catFilter);

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
    $data = $controller->showPublic($slug, $sort, $catFilter);
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
            myGiftsOpen: false,
            selectedItemId: null,
            selectedItemTitle: "",
            donorName: "",
            donorEmail: "",
            myEmail: "",
            myGifts: [],
            myGiftsMessage: "",
            openModal(id, title) {
                this.selectedItemId = id;
                this.selectedItemTitle = title;
                this.modalOpen = true;
            },
            openMyGifts() {
                this.myGiftsOpen = true;
                this.myGiftsMessage = "";
                // Les cadeaux reserves depuis ce navigateur remontent sans email (cookie).
                this.loadMyGifts(true);
            },
            async loadMyGifts(silent = false) {
                if (!silent && !this.myEmail) return;
                const response = await fetch("/api/my_reservations.php", {
                    method: "POST",
                    headers: {"Content-Type": "application/json"},
                    body: JSON.stringify({ list_slug: ' . json_encode($data['list']['slug_public']) . ', email: this.myEmail })
                });
                const result = await response.json();
                if (result.error) { this.myGiftsMessage = result.error; if (!silent) this.myGifts = []; return; }
                this.myGifts = result.items;
                if (!silent) this.myGiftsMessage = result.items.length ? "" : "Aucune réservation trouvée avec cet email.";
            },
            async cancelMyGift(id) {
                if (!confirm("Annuler cette réservation ? Le cadeau redeviendra disponible.")) return;
                const response = await fetch("/api/cancel_reservation.php", {
                    method: "POST",
                    headers: {"Content-Type": "application/json"},
                    body: JSON.stringify({ item_id: id, email: this.myEmail || undefined })
                });
                const result = await response.json();
                if (result.success) { this.myGifts = this.myGifts.filter(g => g.id !== id); window.location.reload(); }
                else alert(result.error || "Une erreur est survenue");
            },
            async confirmGift() {
                if(!this.donorName) return alert("S\'il vous plaît, indiquez votre prénom !");
                if(!this.donorEmail) return alert("L\'email est nécessaire pour retrouver ou annuler votre cadeau.");
                const response = await fetch("/api/mark_taken.php", {
                    method: "POST",
                    headers: {"Content-Type": "application/json"},
                    body: JSON.stringify({ item_id: this.selectedItemId, name: this.donorName, email: this.donorEmail })
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
