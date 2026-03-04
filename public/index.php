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

    <div class="max-w-2xl mx-auto py-10 px-4">
        
        <a href="list.php?slug=<?= $list['slug_admin'] ?>" class="text-<?= $color ?>-600 font-black flex items-center gap-2 mb-10 hover:-translate-x-2 transition-transform inline-flex uppercase text-xs tracking-widest">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="3" d="M15 19l-7-7 7-7"/></svg>
            Retour à la liste
        </a>

        <header class="mb-12">
            <div class="flex items-center gap-4 mb-2">
                <span class="text-3xl"><?= $list['owner_emoji'] ?></span>
                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-<?= $color ?>-500">Ajouter pour <?= htmlspecialchars($list['owner_name']) ?></span>
            </div>
            <h1 class="text-4xl font-black text-slate-900 tracking-tighter italic">Nouveau souhait ✨</h1>
        </header>

        <div class="bg-white rounded-[3rem] p-8 md:p-12 shadow-xl shadow-<?= $color ?>-100/50 border border-white">
            
            <div class="mb-10">
                <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-3 px-1">Lien du produit</label>
                <div class="relative">
                    <input type="url" x-model="form.url" @input.debounce.500ms="scrapeUrl()" placeholder="https://..." class="w-full bg-slate-50 border-2 border-slate-50 rounded-2xl px-6 py-4 outline-none focus:border-<?= $color ?>-500 focus:bg-white transition-all font-medium">
                    
                    <div x-show="loading" class="absolute right-4 top-4">
                        <svg class="animate-spin h-5 w-5 text-<?= $color ?>-500" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    </div>
                </div>
            </div>

            <hr class="border-slate-50 mb-10">

            <div class="space-y-8">
                <div>
                    <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-2 px-1">Titre du cadeau *</label>
                    <input type="text" x-model="form.title" class="w-full border-b-4 border-slate-50 py-3 outline-none focus:border-<?= $color ?>-500 font-black text-2xl bg-transparent transition-all">
                </div>

                <div class="grid grid-cols-2 gap-8">
                    <div>
                        <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-2 px-1">Prix (€)</label>
                        <input type="number" step="0.01" x-model="form.price" class="w-full border-b-4 border-slate-50 py-3 outline-none focus:border-<?= $color ?>-500 font-black text-xl bg-transparent transition-all">
                    </div>
                    <div>
                        <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-2 px-1">Catégorie</label>
                        <input type="text" x-model="form.category" class="w-full border-b-4 border-slate-50 py-3 outline-none focus:border-<?= $color ?>-500 font-bold text-lg bg-transparent transition-all">
                    </div>
                </div>

                <div>
                    <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-2 px-1">Description / Notes</label>
                    <textarea x-model="form.description" rows="3" class="w-full bg-slate-50 border-2 border-slate-50 rounded-2xl px-6 py-4 outline-none focus:border-<?= $color ?>-500 focus:bg-white transition-all font-medium text-sm resize-none" placeholder="Détails importants..."></textarea>
                </div>

                <div class="flex items-center gap-6 bg-slate-50 p-6 rounded-[2rem] border-2 border-dashed border-slate-200">
                    <div class="w-24 h-24 bg-white rounded-2xl overflow-hidden shadow-inner flex items-center justify-center text-3xl shrink-0">
                        <template x-if="form.image_url">
                            <img :src="form.image_url" class="w-full h-full object-cover">
                        </template>
                        <template x-if="!form.image_url">
                            <span>🎁</span>
                        </template>
                    </div>
                    <div class="flex-1">
                        <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-1">URL de l'image</label>
                        <input type="text" x-model="form.image_url" class="w-full bg-transparent border-b border-slate-200 py-1 outline-none focus:border-<?= $color ?>-500 text-xs text-slate-500">
                    </div>
                </div>

                <button @click="submitForm()" :disabled="!form.title || submitting" class="w-full py-6 bg-<?= $color ?>-600 text-white rounded-[2rem] font-black text-xl shadow-xl shadow-<?= $color ?>-100 hover:scale-[1.02] active:scale-95 transition-all disabled:opacity-50">
                    <span x-show="!submitting">Ajouter à la liste ✨</span>
                    <span x-show="submitting">Enregistrement...</span>
                </button>
            </div>
        </div>
    </div>

    <script>
    function addGiftForm() {
        return {
            loading: false,
            submitting: false,
            form: {
                list_id: <?= $list['id'] ?>, // Gardé pour la base de données
                url: '',
                title: '',
                price: '',
                category: '',
                image_url: '',
                description: ''
            },
            async scrapeUrl() {
                if (!this.form.url.startsWith('http')) return;
                this.loading = true;
                try {
                    // On appelle ton API de scraping
                    const res = await fetch('api/scrape.php?url=' + encodeURIComponent(this.form.url));
                    const data = await res.json();
                    if (data.success) {
                        // On remplit les champs avec ce qu'on a trouvé
                        this.form.title = data.title || this.form.title;
                        this.form.price = data.price || this.form.price;
                        this.form.image_url = data.image || this.form.image_url;
                        this.form.description = data.description || this.form.description;
                    }
                } catch (e) { console.error("Erreur de scraping"); }
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