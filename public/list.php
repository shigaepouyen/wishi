<?php
require_once __DIR__ . '/../vendor/autoload.php';

$listController = new \App\Controllers\ListController();

// On récupère le slug admin
$slug = $_GET['slug'] ?? null;
$catFilter = $_GET['cat'] ?? ''; 

if (!$slug) {
    header('Location: hub.php');
    exit;
}

// Le controller cherche maintenant par slug
$data = $listController->show($slug, $catFilter);

if (!$data) {
    die("Désolé, cette liste est introuvable.");
}

$list = $data['list'];
$items = $data['items'];
$categories = $data['categories'];
$color = $list['color'] ?? 'indigo';
$ownerName = $list['owner_name'] ?? 'Utilisateur';
$profileSlug = $list['profile_slug'] ?? '';
$currentCat = $data['currentCategory'] ?? '';

$title = "Wishi - " . htmlspecialchars($list['name']) . " (Admin)";
$body_class = "bg-$color-50/30";
$body_attrs = 'x-data="adminList()" x-init="initSortable()"';
$extra_css = '
    .truncate-2-lines { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .sortable-ghost { opacity: 0.3; background: #f8fafc; border: 2px dashed #cbd5e1; }
';
$extra_js = '
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
function adminList() {
    return {
        editModalOpen: false,
        deleteModalOpen: false,
        shareModalOpen: false,
        settingsModalOpen: false,
        loading: false,
        images: [],
        currentImageIndex: 0,
        itemToDelete: null,
        form: {},
        listSettings: ' . json_encode([
            'id' => $list['id'],
            'name' => $list['name']
        ]) . ',

        initSortable() {
            const el = document.getElementById("items-grid");
            if(!el) return;
            Sortable.create(el, {
                animation: 250,
                handle: ".cursor-move",
                ghostClass: "sortable-ghost",
                onEnd: async (evt) => {
                    const ids = Array.from(el.querySelectorAll("[data-id]"))
                                     .map(item => item.getAttribute("data-id"));
                    await fetch("api/reorder.php", { method: "POST", headers: {"Content-Type": "application/json"}, body: JSON.stringify({ ids: ids }) });
                }
            });
        },

        editItem(item) {
            this.form = {
                currency: "EUR", // Default fallback
                ...item
            };
            this.images = item.image_url ? [item.image_url] : [];
            this.currentImageIndex = 0;
            this.loading = false;
            this.editModalOpen = true;
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
            if (!this.form.url || !this.form.url.startsWith("http")) return;
            this.loading = true;
            try {
                const res = await fetch("api/scrape.php?url=" + encodeURIComponent(this.form.url));
                const data = await res.json();
                if (data.success) {
                    if (data.is_generic) {
                        window.dispatchEvent(new CustomEvent("notify", { detail: { message: "Ce site semble bloquer l\'accès automatique. Remplissage manuel nécessaire.", type: "info" } }));
                    } else {
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
                        window.dispatchEvent(new CustomEvent("notify", { detail: { message: "Informations mises à jour !", type: "success" } }));
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

        async saveEdit() {
            const response = await fetch("api/update_item.php", { method: "POST", headers: {"Content-Type": "application/json"}, body: JSON.stringify(this.form) });
            const result = await response.json();
            if(result.success) {
                window.dispatchEvent(new CustomEvent("notify", { detail: { message: "Modification enregistrée !", type: "success" } }));
                setTimeout(() => window.location.reload(), 500);
            } else {
                window.dispatchEvent(new CustomEvent("notify", { detail: { message: "Erreur : " + result.error, type: "error" } }));
            }
        },

        confirmDeletion(id) {
            this.itemToDelete = id;
            this.deleteModalOpen = true;
        },

        async executeDelete() {
            const response = await fetch("api/delete_item.php", { method: "POST", headers: {"Content-Type": "application/json"}, body: JSON.stringify({ id: this.itemToDelete }) });
            const result = await response.json();
            if(result.success) {
                window.dispatchEvent(new CustomEvent("notify", { detail: { message: "Souhait supprimé !", type: "success" } }));
                window.location.reload();
            } else {
                window.dispatchEvent(new CustomEvent("notify", { detail: { message: "Erreur : " + result.error, type: "error" } }));
            }
        },

        async saveSettings() {
            const response = await fetch("api/update_list_settings.php", {
                method: "POST",
                headers: {"Content-Type": "application/json"},
                body: JSON.stringify(this.listSettings)
            });
            const result = await response.json();
            if(result.success) window.location.reload();
            else alert(result.error);
        },

        async resetReservations() {
            if(!confirm("Veux-tu vraiment rendre tous les cadeaux de nouveau disponibles ?")) return;
            const response = await fetch("api/reset_list.php", {
                method: "POST",
                headers: {"Content-Type": "application/json"},
                body: JSON.stringify({ id: this.listSettings.id })
            });
            const result = await response.json();
            if(result.success) window.location.reload();
        },

        async deleteList() {
            if(!confirm("ALERTE : Supprimer définitivement cette liste et tous ses cadeaux ?")) return;
            const response = await fetch("api/delete_list.php", {
                method: "POST",
                headers: {"Content-Type": "application/json"},
                body: JSON.stringify({ id: this.listSettings.id })
            });
            const result = await response.json();
            if(result.success) window.location.href = "universe.php?slug=' . $profileSlug . '";
        }
    }
}
</script>
';

ob_start();
include __DIR__ . '/../views/list_view.php';
$content = ob_get_clean();

include __DIR__ . '/../views/layouts/main.php';
