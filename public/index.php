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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un souhait - Wishi</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <link rel="manifest" href="manifest.json">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

    <style>
        [x-cloak] { display: none !important; }
        .glass { background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(12px); }
    </style>
</head>
<body class="bg-<?= $color ?>-50/30 min-h-screen font-sans text-slate-900" x-data="addGiftForm()">

    <div class="max-w-4xl mx-auto py-10 px-4">
        
        <div class="flex justify-between items-center mb-10">
            <a href="list.php?slug=<?= $list['slug_admin'] ?>" class="text-<?= $color ?>-600 font-black flex items-center gap-2 hover:-translate-x-2 transition-transform uppercase text-xs tracking-widest">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="3" d="M15 19l-7-7 7-7"/></svg>
                Retour
            </a>
            <div class="flex items-center gap-3 bg-white px-4 py-2 rounded-2xl shadow-sm border border-slate-50">
                <span class="text-xl"><?= $list['owner_emoji'] ?></span>
                <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Pour <span class="text-<?= $color ?>-600"><?= htmlspecialchars($list['owner_name']) ?></span></span>
            </div>
        </div>

        <header class="mb-10 text-center">
            <h1 class="text-5xl font-black text-slate-900 tracking-tighter italic">Nouveau souhait ✨</h1>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            <!-- Colonne de Gauche : Outil de Scraping & Image -->
            <div class="lg:col-span-4 space-y-6">

                <div class="bg-white rounded-[2.5rem] p-8 shadow-xl shadow-<?= $color ?>-100/50 border border-white">
                    <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-4 px-1 text-center">Remplissage automatique 🪄</label>
                    <div class="relative">
                        <input type="url" x-model="form.url" @input.debounce.500ms="scrapeUrl()" placeholder="Collez un lien..." class="w-full bg-slate-50 border-2 border-slate-50 rounded-2xl px-5 py-4 outline-none focus:border-<?= $color ?>-500 focus:bg-white transition-all font-medium text-sm">

                        <div x-show="loading" class="absolute right-4 top-4">
                            <svg class="animate-spin h-5 w-5 text-<?= $color ?>-500" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        </div>
                    </div>
                    <p class="text-[9px] text-slate-400 mt-3 text-center px-4 leading-relaxed">Collez le lien d'un article pour récupérer automatiquement ses infos.</p>
                </div>

                <div class="bg-white rounded-[2.5rem] p-6 shadow-xl shadow-<?= $color ?>-100/50 border border-white overflow-hidden group">
                    <div class="aspect-square bg-slate-50 rounded-3xl overflow-hidden mb-4 flex items-center justify-center text-5xl relative border-2 border-dashed border-slate-100">
                        <template x-if="form.image_url">
                            <img :src="form.image_url" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        </template>
                        <template x-if="!form.image_url">
                            <span class="opacity-20">🎁</span>
                        </template>

                        <!-- Image Selector Overlay -->
                        <template x-if="images.length > 1">
                            <div class="absolute inset-x-0 bottom-4 flex justify-center gap-2 px-4">
                                <button @click.prevent="prevImage()" class="bg-white/90 hover:bg-white text-slate-900 w-10 h-10 rounded-full flex items-center justify-center shadow-lg transition-all active:scale-90">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="3" d="M15 19l-7-7 7-7"/></svg>
                                </button>
                                <div class="bg-white/90 backdrop-blur-sm px-3 py-2 rounded-full text-[10px] font-black text-slate-900 shadow-lg flex items-center">
                                    <span x-text="currentImageIndex + 1"></span> / <span x-text="images.length"></span>
                                </div>
                                <button @click.prevent="nextImage()" class="bg-white/90 hover:bg-white text-slate-900 w-10 h-10 rounded-full flex items-center justify-center shadow-lg transition-all active:scale-90">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="3" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            </div>
                        </template>
                    </div>
                    <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-2 px-1">Lien de l'image</label>
                    <input type="text" x-model="form.image_url" placeholder="http://image-url.jpg" class="w-full bg-slate-50 border-none rounded-xl px-4 py-2 text-[10px] text-slate-500 outline-none focus:ring-1 focus:ring-<?= $color ?>-400">
                </div>
            </div>

            <!-- Colonne de Droite : Formulaire Détails -->
            <div class="lg:col-span-8">
                <div class="bg-white rounded-[3rem] p-8 md:p-12 shadow-xl shadow-<?= $color ?>-100/50 border border-white">
                    <div class="space-y-10">
                        <div>
                            <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-2 px-1">Quel est ce cadeau ? *</label>
                            <input type="text" x-model="form.title" placeholder="Ex: Une magnifique montre..." class="w-full border-b-4 border-slate-50 py-3 outline-none focus:border-<?= $color ?>-500 font-black text-3xl bg-transparent transition-all placeholder:opacity-20">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                            <div>
                                <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-2 px-1">Prix Estimé (€)</label>
                                <div class="flex items-center gap-3">
                                    <input type="number" step="0.01" x-model="form.price" placeholder="0.00" class="w-full border-b-4 border-slate-50 py-3 outline-none focus:border-<?= $color ?>-500 font-black text-2xl bg-transparent transition-all placeholder:opacity-20">
                                    <span class="text-2xl font-black text-slate-200">€</span>
                                </div>
                            </div>
                            <div>
                                <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-2 px-1">Catégorie</label>
                                <input type="text" x-model="form.category" placeholder="Ex: Mode, Maison..." class="w-full border-b-4 border-slate-50 py-3 outline-none focus:border-<?= $color ?>-500 font-bold text-xl bg-transparent transition-all placeholder:opacity-20">
                            </div>
                        </div>

                        <div>
                            <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-2 px-1">Description / Notes</label>
                            <textarea x-model="form.description" rows="4" class="w-full bg-slate-50 border-2 border-slate-50 rounded-3xl px-6 py-5 outline-none focus:border-<?= $color ?>-500 focus:bg-white transition-all font-medium text-base resize-none" placeholder="Ajoutez des détails, la taille, la couleur ou pourquoi vous voulez ce cadeau..."></textarea>
                        </div>

                        <div class="pt-4">
                            <button @click="submitForm()" :disabled="!form.title || submitting" class="w-full py-7 bg-<?= $color ?>-600 text-white rounded-[2rem] font-black text-2xl shadow-2xl shadow-<?= $color ?>-200/50 hover:scale-[1.02] active:scale-95 transition-all disabled:opacity-50 disabled:scale-100">
                                <span x-show="!submitting">Enregistrer le vœu ✨</span>
                                <span x-show="submitting" class="flex items-center justify-center gap-3">
                                    <svg class="animate-spin h-6 w-6 text-white" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    Enregistrement...
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function addGiftForm() {
        return {
            loading: false,
            submitting: false,
            images: [],
            currentImageIndex: 0,
            form: {
                list_id: <?= $list['id'] ?>, // Gardé pour la base de données
                url: '',
                title: '',
                price: '',
                category: '',
                image_url: '',
                description: ''
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
                if (!this.form.url.startsWith('http')) return;
                this.loading = true;
                try {
                    // On appelle ton API de scraping
                    const res = await fetch('api/scrape.php?url=' + encodeURIComponent(this.form.url));
                    const data = await res.json();
                    if (data.success) {
                        if (data.is_generic) {
                            // On informe l'utilisateur que le site bloque
                            alert("Ce site semble bloquer l'accès automatique. Vous devrez remplir le formulaire manuellement.");
                        } else {
                            // On remplit les champs avec ce qu'on a trouvé
                            this.form.title = data.title || this.form.title;
                            this.form.price = (data.price && data.price.amount) ? data.price.amount : this.form.price;
                            this.form.description = data.description || this.form.description;

                        // Gestion des images
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
                    const res = await fetch('api/add_item.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify(this.form)
                    });
                    if ((await res.json()).success) {
                        // On repart vers la liste en utilisant le slug
                        window.location.href = 'list.php?slug=<?= $list['slug_admin'] ?>';
                    }
                } catch (e) { alert("Erreur lors de l'enregistrement"); }
                this.submitting = false;
            }
        }
    }
    </script>
</body>
</html>