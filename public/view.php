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
$myGiftsCount = (new \App\Controllers\ItemController())->countMyGifts(\App\Utils\Security::getDonorToken());

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

ob_start();
include __DIR__ . '/../views/components/share_link_script.php';
?>
<script>
    function publicList() {
        return {
            modalOpen: false,
            reserved: false,
            sending: false,
            errorMessage: "",
            selectedItemId: null,
            selectedItemTitle: "",
            donorName: "",
            donorEmail: "",
            shopUrl: null,
            shopLabel: null,
            myGiftsLink: null,
            openModal(id, title) {
                this.selectedItemId = id;
                this.selectedItemTitle = title;
                this.reserved = false;
                this.errorMessage = "";
                this.modalOpen = true;
            },
            closeModal() {
                this.modalOpen = false;
                // Le cadeau reserve doit disparaitre de la liste et le compteur « Mes cadeaux » se mettre a jour
                if (this.reserved) window.location.reload();
            },
            shareMyGiftsLink(url) {
                window.wishiShareLink(url);
            },
            async confirmGift() {
                this.errorMessage = "";
                if (!this.donorName.trim()) { this.errorMessage = "Indiquez votre prénom, pour que la famille sache qui s'en occupe."; return; }
                this.sending = true;
                try {
                    const response = await fetch("/api/mark_taken.php", {
                        method: "POST",
                        headers: {"Content-Type": "application/json"},
                        body: JSON.stringify({ item_id: this.selectedItemId, name: this.donorName, email: this.donorEmail })
                    });
                    const result = await response.json();
                    if (!result.success) { this.errorMessage = result.error || "Une erreur est survenue, réessayez."; return; }
                    this.shopUrl = result.shop_url;
                    this.shopLabel = result.shop_label;
                    this.myGiftsLink = result.my_gifts_link;
                    this.reserved = true;
                } catch (e) {
                    this.errorMessage = "Impossible de joindre Wishi. Vérifiez votre connexion et réessayez.";
                } finally {
                    this.sending = false;
                }
            }
        }
    }
</script>
<?php
$extra_js = ob_get_clean();

include __DIR__ . '/../views/layouts/main.php';
