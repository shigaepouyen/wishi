<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Controllers\ItemController;
use App\Utils\Security;

$is_public_surface = true;
$controller = new ItemController();

// Lien personnel ouvert sur un autre appareil : on rattache ce navigateur, puis on retire
// le secret de la barre d'adresse.
$linkInvalid = false;
if (isset($_GET['k'])) {
    if ($controller->adoptDonorToken($_GET['k'])) {
        header('Location: /mes-cadeaux', true, 302);
        exit;
    }
    $linkInvalid = true;
}

$token = Security::getDonorToken();
$gifts = $controller->myGifts($token);

// Regroupement par personne gâtée
$groups = [];
foreach ($gifts as $gift) {
    $key = $gift['profile_slug'];
    if (!isset($groups[$key])) {
        $groups[$key] = [
            'name' => $gift['owner_name'],
            'emoji' => $gift['owner_emoji'] ?: '🎁',
            'color' => $gift['color'] ?: 'indigo',
            'items' => [],
        ];
    }
    $gift['list_url'] = ($gift['hub_visible'] && $gift['slug_hub'])
        ? '/' . rawurlencode($gift['profile_slug']) . '/' . rawurlencode($gift['slug_hub'])
        : '/view.php?s=' . rawurlencode($gift['slug_public']);
    $groups[$key]['items'][] = $gift;
}

$myGiftsLink = ($token && $gifts) ? ItemController::myGiftsLink($token) : null;

$title = 'Wishi - Mes cadeaux à offrir';
$body_class = 'bg-slate-50';
$body_attrs = 'x-data="myGiftsPage()"';

ob_start();
include __DIR__ . '/../views/my_gifts_view.php';
$content = ob_get_clean();

ob_start();
include __DIR__ . '/../views/components/share_link_script.php';
?>
<script>
    function myGiftsPage() {
        return {
            email: "",
            emailMessage: "",
            searching: false,
            async findByEmail() {
                this.emailMessage = "";
                if (!this.email.trim()) { this.emailMessage = "Indique l'email utilisé au moment de réserver."; return; }
                this.searching = true;
                try {
                    const response = await fetch("/api/find_gifts.php", {
                        method: "POST",
                        headers: {"Content-Type": "application/json"},
                        body: JSON.stringify({ email: this.email })
                    });
                    const result = await response.json();
                    if (result.success) window.location.reload();
                    else this.emailMessage = result.error || "Une erreur est survenue.";
                } catch (e) {
                    this.emailMessage = "Impossible de joindre Wishi. Vérifie ta connexion.";
                } finally {
                    this.searching = false;
                }
            },
            async cancelGift(id, title) {
                if (!confirm("Annuler la réservation de « " + title + " » ?\n\nLe cadeau redeviendra disponible pour le reste de la famille.")) return;
                const response = await fetch("/api/cancel_reservation.php", {
                    method: "POST",
                    headers: {"Content-Type": "application/json"},
                    body: JSON.stringify({ item_id: id })
                });
                const result = await response.json();
                if (result.success) window.location.reload();
                else alert(result.error || "Une erreur est survenue");
            }
        }
    }
</script>
<?php
$extra_js = ob_get_clean();

include __DIR__ . '/../views/layouts/main.php';
