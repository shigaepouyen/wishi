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

// Récupération des catégories existantes pour les suggestions
$catStmt = $db->prepare("SELECT DISTINCT category FROM items WHERE list_id = ? AND category != '' ORDER BY category ASC");
$catStmt->execute([$list['id']]);
$existingCategories = $catStmt->fetchAll(PDO::FETCH_COLUMN);

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
            currency: "EUR",
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
                        window.dispatchEvent(new CustomEvent("notify", { detail: { message: "Ce site semble bloquer l\'accès automatique. Remplissage manuel nécessaire.", type: "info" } }));
                    } else {
                        // Mise à jour de l\'URL si elle a été nettoyée (ex: Amazon)
                        if (data.url) this.form.url = data.url;

                        this.form.title = data.title || this.form.title;
                        this.form.price = (data.price && data.price.amount) ? data.price.amount : this.form.price;
                        this.form.currency = (data.price && data.price.currency) ? data.price.currency : this.form.currency;
                        this.form.description = data.description || this.form.description;

                        if (data.images && data.images.length > 0) {
                            this.images = data.images;
                            this.currentImageIndex = 0;
                            this.form.image_url = this.images[0];
                        } else {
                            this.form.image_url = data.image || this.form.image_url;
                            this.images = this.form.image_url ? [this.form.image_url] : [];
                        }
                        window.dispatchEvent(new CustomEvent("notify", { detail: { message: "Informations récupérées avec succès !", type: "success" } }));
                    }
                } else if (data.error) {
                    window.dispatchEvent(new CustomEvent("notify", { detail: { message: "Erreur : " + data.error, type: "error" } }));
                }
            } catch (e) {
                console.error("Erreur de scraping");
                window.dispatchEvent(new CustomEvent("notify", { detail: { message: "Impossible de récupérer les informations du lien.", type: "error" } }));
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
                const result = await res.json();
                if (result.success) {
                    window.location.href = "list.php?slug=' . $list['slug_admin'] . '";
                } else {
                    window.dispatchEvent(new CustomEvent("notify", { detail: { message: "Erreur : " + result.error, type: "error" } }));
                }
            } catch (e) {
                window.dispatchEvent(new CustomEvent("notify", { detail: { message: "Erreur lors de l\'enregistrement", type: "error" } }));
            }
            this.submitting = false;
        }
    }
}
</script>
';

include __DIR__ . '/../views/layouts/main.php';
