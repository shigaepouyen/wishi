<?php
require_once __DIR__ . '/../vendor/autoload.php';

// 1. On récupère le slug admin pour savoir dans quelle liste on ajoute
$slug = $_GET['slug'] ?? null;
if (!$slug) {
    header('Location: hub.php');
    exit;
}

$db = \App\Utils\Database::getConnection();

// 2. On récupère la liste ET les infos de l'univers (couleur, emoji)
$stmt = $db->prepare("
    SELECT l.*, p.name as owner_name, p.color, p.emoji as owner_emoji
    FROM lists l
    JOIN profiles p ON l.profile_id = p.id
    WHERE l.slug_admin = ?
");
$stmt->execute([$slug]);
$list = $stmt->fetch();

// Sécurité : si la liste n'existe pas, on ne tente pas d'afficher le formulaire
if (!$list) {
    header('Location: hub.php');
    exit;
}

$color = $list['color'] ?? 'indigo';
$title = "Ajouter un souhait - Wishi";
$body_class = "bg-$color-50/30";

ob_start();
include __DIR__ . '/../views/add_item_view.php';
$content = ob_get_clean();

$extra_js = '
<script>
function addGiftForm() {
    return {
        loading: false,
        submitting: false,
        images: [],
        currentImageIndex: 0,
        form: {
            list_id: ' . (int)$list['id'] . ',
            url: "",
            title: "",
            price: "",
            category: "",
            image_url: "",
            description: ""
        },
        nextImage() {
            this.currentImageIndex = (this.currentImageIndex + 1) % this.images.length;
            this.form.image_url = this.images[this.currentImageIndex];
        },
        prevImage() {
            this.currentImageIndex = (this.currentImageIndex - 1 + this.images.length) % this.images.length;
            this.form.image_url = this.images[this.currentImageIndex];
        },
        async scrapeUrl() {
            if (!this.form.url.startsWith("http")) return;
            this.loading = true;
            try {
                const res = await fetch("api/scrape.php?url=" + encodeURIComponent(this.form.url));
                const data = await res.json();
                if (data.success) {
                    if (data.is_generic) {
                        alert("Ce site semble bloquer l\'accès automatique. Vous devrez remplir le formulaire manuellement.");
                    } else {
                        this.form.title = data.title || this.form.title;
                        this.form.price = (data.price && data.price.amount) ? data.price.amount : this.form.price;
                        this.form.description = data.description || this.form.description;

                        if (data.images && data.images.length > 0) {
                            this.images = data.images;
                            this.currentImageIndex = 0;
                            this.form.image_url = this.images[0];
                        } else {
                            this.form.image_url = data.image || this.form.image_url;
                            this.images = this.form.image_url ? [this.form.image_url] : [];
                        }
                    }
                } else if (data.error) {
                    alert("Erreur de récupération : " + data.error);
                }
            } catch (e) {
                console.error("Erreur de scraping");
                alert("Impossible de récupérer les informations du lien.");
            }
            this.loading = false;
        },
        async submitForm() {
            this.submitting = true;
            try {
                const res = await fetch("api/add_item.php", {
                    method: "POST",
                    headers: {"Content-Type": "application/json"},
                    body: JSON.stringify(this.form)
                });
                if ((await res.json()).success) {
                    window.location.href = "list.php?slug=' . $list['slug_admin'] . '";
                }
            } catch (e) { alert("Erreur lors de l\'enregistrement"); }
            this.submitting = false;
        }
    }
}
</script>
';

include __DIR__ . '/../views/layouts/main.php';
