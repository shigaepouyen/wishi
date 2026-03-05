<?php
require_once __DIR__ . '/../vendor/autoload.php';

$slug = $_GET['s'] ?? '';
$sort = $_GET['sort'] ?? 'position';
$catFilter = $_GET['cat'] ?? '';

if (!$slug) die("Lien invalide.");

$controller = new \App\Controllers\ListController();
$data = $controller->showPublic($slug, $sort, $catFilter);

if (!$data) die("Cette liste n'existe pas.");

$allCategories = $controller->getCategories($data['list']['id']);

// Extraction des infos de l'univers
$color = $data['list']['color'] ?? 'indigo';
$ownerName = $data['list']['owner_name'] ?? $data['list']['name'];
$ownerEmoji = $data['list']['owner_emoji'] ?? '🎁';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wishi - Liste de <?= htmlspecialchars($ownerName) ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <link rel="manifest" href="manifest.json">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

    <style>
        [x-cloak] { display: none !important; }
        .truncate-2-lines { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .glass { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(12px); }
    </style>
</head>
<body class="bg-<?= $color ?>-50/30 min-h-screen font-sans text-slate-900" x-data="publicList()">

    <div class="max-w-4xl mx-auto py-10 px-4">
        
        <div class="mb-12 text-center">
            <div class="text-6xl mb-4 transform hover:scale-110 transition-transform duration-500 inline-block">
                <?= $ownerEmoji ?>
            </div>
            <h1 class="text-4xl font-black text-slate-900 tracking-tighter italic">
                La Liste de <?= htmlspecialchars($ownerName) ?>
            </h1>
            <p class="text-slate-400 mt-2 text-xs font-bold uppercase tracking-widest">
                Cochez un cadeau pour le réserver
            </p>
        </div>

        <div class="mb-10">
            <div class="flex flex-wrap justify-center gap-2">
                <a href="?s=<?= $slug ?>&sort=<?= $sort ?>" 
                   class="px-5 py-2.5 rounded-full text-xs font-bold transition-all <?= $catFilter == '' ? "bg-$color-600 text-white shadow-lg shadow-$color-100" : 'bg-white text-slate-500 border border-slate-200 hover:border-'.$color.'-300' ?>">
                    Tous
                </a>
                <?php foreach($allCategories as $c): ?>
                    <a href="?s=<?= $slug ?>&cat=<?= urlencode($c) ?>&sort=<?= $sort ?>" 
                       class="px-5 py-2.5 rounded-full text-xs font-bold transition-all <?= $catFilter == $c ? "bg-$color-600 text-white shadow-lg shadow-$color-100" : 'bg-white text-slate-500 border border-slate-200 hover:border-'.$color.'-300' ?>">
                        <?= htmlspecialchars($c) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="mb-8 flex justify-end items-center gap-3">
            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Trier par :</label>
            <select onchange="window.location.href='?s=<?= $slug ?>&cat=<?= urlencode($catFilter) ?>&sort=' + this.value" 
                    class="bg-white border border-slate-200 text-slate-600 text-xs font-bold rounded-2xl px-4 py-2.5 outline-none focus:ring-2 focus:ring-<?= $color ?>-500 shadow-sm cursor-pointer transition-all">
                <option value="priority" <?= ($data['currentSort'] == 'priority' || $data['currentSort'] == 'position') ? 'selected' : '' ?>>✨ Ordre de <?= htmlspecialchars($ownerName) ?></option>
                <option value="price_asc" <?= $data['currentSort'] == 'price_asc' ? 'selected' : '' ?>>Prix croissant</option>
                <option value="price_desc" <?= $data['currentSort'] == 'price_desc' ? 'selected' : '' ?>>Prix décroissant</option>
                <option value="manual" <?= $data['currentSort'] == 'manual' ? 'selected' : '' ?>>🎯 Manuel uniquement</option>
            </select>
        </div>

        <?php if (empty($data['items'])): ?>
            <div class="max-w-md mx-auto bg-white rounded-[3rem] p-12 shadow-xl border border-slate-100 text-center">
                <div class="text-5xl mb-6 opacity-40">🎁</div>
                <h2 class="text-2xl font-black text-slate-800 mb-2">Tout a été trouvé !</h2>
                <p class="text-slate-400 text-sm font-medium italic">Rien ne correspond ou tout a déjà été réservé.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <?php foreach ($data['items'] as $item): ?>
                    <div class="bg-white rounded-[2.5rem] p-6 shadow-sm border border-slate-100 flex flex-col justify-between hover:shadow-xl transition-all duration-300 group <?= $item['priority'] == 3 ? "ring-2 ring-amber-400 ring-offset-4" : '' ?>">
                        <div class="flex gap-5 mb-6">
                            <div class="w-28 h-28 shrink-0 bg-slate-50 rounded-3xl overflow-hidden border border-slate-50 shadow-inner">
                                <img src="<?= htmlspecialchars($item['image_url'] ?: 'assets/img/placeholder.png') ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                            </div>
                            <div class="flex-1 min-w-0 flex flex-col justify-center">
                                <span class="text-[10px] font-black text-<?= $color ?>-500 uppercase tracking-widest mb-1"><?= htmlspecialchars($item['category'] ?: 'Souhait') ?></span>
                                <h3 class="font-black text-slate-800 text-xl leading-tight truncate-2-lines"><?= htmlspecialchars($item['title']) ?></h3>
                                <p class="font-black text-2xl mt-2 text-slate-900 tracking-tight"><?= number_format($item['price'], 2, ',', ' ') ?> €</p>
                            </div>
                        </div>
                        
                        <div class="flex gap-2">
                            <?php if($item['url']): ?>
                            <a href="<?= htmlspecialchars($item['url']) ?>" target="_blank" class="flex-1 py-4 bg-slate-100 text-slate-600 rounded-2xl font-bold text-sm text-center hover:bg-slate-200 transition-colors">Voir l'objet</a>
                            <?php endif; ?>
                            <button @click="openModal(<?= $item['id'] ?>, '<?= addslashes($item['title']) ?>')" 
                                    class="flex-[2] py-4 bg-<?= $color ?>-600 text-white rounded-2xl font-black hover:brightness-110 shadow-lg shadow-<?= $color ?>-100 transition-all text-sm tracking-tight uppercase">
                                Offrir ce cadeau
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div x-show="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 glass" x-cloak x-transition>
            <div class="bg-white rounded-[3.5rem] p-10 max-w-sm w-full shadow-2xl relative border border-<?= $color ?>-100" @click.away="modalOpen = false">
                <button @click="modalOpen = false" class="absolute top-8 right-8 text-slate-300 hover:text-slate-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>

                <h3 class="text-2xl font-black text-center mb-6 tracking-tighter italic">Réserver ce vœu ?</h3>
                <p class="text-slate-400 text-xs font-bold text-center mb-8 uppercase tracking-widest leading-relaxed" x-text="selectedItemTitle"></p>
                
                <div class="space-y-6">
                    <div>
                        <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-2 px-1">Votre Prénom</label>
                        <input type="text" x-model="donorName" placeholder="Qui offre ?" class="w-full px-6 py-4 rounded-2xl border-2 border-slate-50 bg-slate-50 outline-none focus:border-<?= $color ?>-500 focus:bg-white font-bold transition-all">
                    </div>
                    <button @click="confirmGift()" class="w-full py-5 bg-<?= $color ?>-600 text-white rounded-2xl font-black shadow-xl shadow-<?= $color ?>-100 hover:scale-[1.02] active:scale-95 transition-all text-lg tracking-tight">
                        Confirmer ✨
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function publicList() {
            return {
                modalOpen: false,
                selectedItemId: null,
                selectedItemTitle: '',
                donorName: '',
                openModal(id, title) {
                    this.selectedItemId = id;
                    this.selectedItemTitle = title;
                    this.modalOpen = true;
                },
                async confirmGift() {
                    if(!this.donorName) return alert("S'il vous plaît, indiquez votre nom !");
                    const response = await fetch('api/mark_taken.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({ item_id: this.selectedItemId, name: this.donorName })
                    });
                    const result = await response.json();
                    if(result.success) window.location.reload();
                    else alert(result.error || "Une erreur est survenue");
                }
            }
        }

        // PWA Service Worker Registration
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js').catch(err => console.log('PWA Error', err));
            });
        }
    </script>
</body>
</html>