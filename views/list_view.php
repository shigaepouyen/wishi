<?php
$list = $data['list'];
$items = $data['items'];
$categories = $data['categories'];
$color = $list['color'] ?? 'indigo'; 
$ownerName = $list['owner_name'] ?? 'Utilisateur';
$profileSlug = $list['profile_slug'] ?? '';
$currentCat = $data['currentCategory'] ?? ''; // Sécurité pour le filtre
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wishi - <?= htmlspecialchars($list['name']) ?> (Admin)</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🎁</text></svg>">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

    <link rel="manifest" href="manifest.json">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Wishi">
    <link rel="apple-touch-icon" href="assets/img/icon-192.png">

    <style>
        [x-cloak] { display: none !important; }
        .glass { background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); }
        .truncate-2-lines { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .sortable-ghost { opacity: 0.3; background: #f8fafc; border: 2px dashed #cbd5e1; }
    </style>
</head>
<body class="bg-<?= $color ?>-50/30 min-h-screen font-sans text-slate-900">

<div class="max-w-4xl mx-auto py-10 px-4" x-data="adminList()" x-init="initSortable()">
    
    <header class="mb-12 flex flex-col md:flex-row justify-between items-center gap-4">
        <div class="flex items-center gap-4">
            <a href="universe.php?slug=<?= $profileSlug ?>" class="p-3 bg-white border border-slate-200 text-slate-400 rounded-2xl hover:text-<?= $color ?>-600 hover:border-<?= $color ?>-100 transition-all shadow-sm group" title="Retour à l'Univers">
                <svg class="w-6 h-6 transform group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 15l-3-3m0 0l3-3m-3 3h8M3 12a9 9 0 1118 0 9 9 0 01-18 0z"/>
                </svg>
            </a>
            
            <div class="flex flex-col items-center md:items-start text-center md:text-left">
                <h1 class="text-3xl font-black text-slate-900 tracking-tight italic">
                    <?= htmlspecialchars($list['name']) ?>
                </h1>
                <p class="text-slate-500 text-[10px] font-black uppercase tracking-widest">🔐 Admin : <?= htmlspecialchars($ownerName) ?></p>
            </div>
        </div>
        
        <div class="flex gap-2">
            <button @click="settingsModalOpen = true" class="p-3 rounded-2xl bg-white border border-slate-200 text-slate-400 hover:text-<?= $color ?>-600 hover:border-<?= $color ?>-100 transition-all shadow-sm">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </button>
            <button @click="shareModalOpen = true" class="px-5 py-3 rounded-2xl font-bold text-<?= $color ?>-600 bg-white border border-<?= $color ?>-100 hover:bg-<?= $color ?>-50 transition-all flex items-center gap-2 shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                Partager
            </button>
            <a href="index.php?slug=<?= $list['slug_admin'] ?>" class="px-5 py-3 rounded-2xl font-bold text-white bg-slate-900 hover:bg-slate-800 transition-all flex items-center gap-2 shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Ajouter
            </a>
        </div>
    </header>

    <div class="mb-10 overflow-x-auto pb-2">
        <div class="flex items-center gap-3">
            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest shrink-0">Filtrer :</span>
            <div class="flex gap-2">
                <?php 
                    $activeClass = "bg-$color-600 text-white shadow-md shadow-$color-100";
                    $inactiveClass = "bg-white text-slate-500 border border-slate-200";
                ?>
                <a href="?slug=<?= $list['slug_admin'] ?>" 
                   class="px-4 py-2 rounded-full text-xs font-bold transition-all <?= $currentCat === '' ? $activeClass : $inactiveClass ?>">
                   Tous
                </a>
                
                <?php foreach($categories as $cat): ?>
                    <a href="?slug=<?= $list['slug_admin'] ?>&cat=<?= urlencode($cat) ?>" 
                       class="px-4 py-2 rounded-full text-xs font-bold transition-all <?= $currentCat === $cat ? $activeClass : $inactiveClass ?>">
                        <?= htmlspecialchars($cat) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div id="items-grid" class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <?php if (empty($items)): ?>
            <div class="md:col-span-2 text-center py-20 bg-white rounded-[2.5rem] border-2 border-dashed border-slate-200">
                <p class="text-slate-400 font-medium tracking-tight italic">Cette liste est encore vierge. 🪄</p>
            </div>
        <?php else: ?>
            <?php foreach ($items as $item): ?>
                <div data-id="<?= $item['id'] ?>" class="bg-white rounded-3xl p-5 shadow-sm border border-slate-100 flex gap-4 group relative transition-all hover:shadow-xl <?= $item['priority'] == 3 ? "ring-2 ring-amber-400 ring-offset-2" : '' ?>">
                    
                    <div class="flex items-center cursor-move text-slate-200 hover:text-<?= $color ?>-500 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/></svg>
                    </div>

                    <div class="w-24 h-24 shrink-0 bg-slate-50 rounded-2xl overflow-hidden border border-slate-50">
                        <img src="<?= htmlspecialchars($item['image_url'] ?: 'assets/img/placeholder.png') ?>" class="w-full h-full object-cover">
                    </div>
                    
                    <div class="flex-1 min-w-0 flex flex-col justify-between">
                        <div>
                            <div class="flex justify-between items-start">
                                <span class="text-[10px] font-black text-<?= $color ?>-500 uppercase tracking-widest truncate"><?= htmlspecialchars($item['category'] ?: 'Divers') ?></span>
                                <?php if ($item['is_taken']): ?>
                                    <span class="bg-green-100 text-green-600 text-[9px] font-bold px-2 py-0.5 rounded-full uppercase">Réservé</span>
                                <?php endif; ?>
                            </div>
                            <h3 class="font-bold text-slate-800 leading-tight truncate-2-lines mt-1"><?= htmlspecialchars($item['title']) ?></h3>
                            <p class="font-black text-xl text-slate-900 mt-1"><?= number_format($item['price'], 2, ',', ' ') ?> €</p>
                        </div>
                        
                        <div class="flex gap-4 mt-3">
                            <button @click="editItem(<?= htmlspecialchars(json_encode($item)) ?>)" class="text-[10px] font-black text-<?= $color ?>-600 hover:underline uppercase tracking-widest">Modifier</button>
                            <button @click="confirmDeletion(<?= $item['id'] ?>)" class="text-[10px] font-black text-red-300 hover:text-red-600 uppercase tracking-widest">Supprimer</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div x-show="editModalOpen" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[100] flex items-center justify-center p-4" x-cloak x-transition>
        <div class="bg-white rounded-[2.5rem] p-8 max-w-lg w-full shadow-2xl overflow-y-auto max-h-[90vh]" @click.away="editModalOpen = false">
            <h2 class="text-2xl font-black text-slate-900 mb-8 tracking-tighter">Modifier le vœu</h2>
            <div class="space-y-6">
                <div>
                    <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-1 block">Titre</label>
                    <textarea x-model="form.title" rows="2" class="w-full border-b-2 border-slate-100 py-2 outline-none focus:border-<?= $color ?>-500 text-lg font-bold resize-none bg-transparent"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-8">
                    <div>
                        <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-1 block">Prix (€)</label>
                        <input type="number" step="0.01" x-model="form.price" class="w-full border-b-2 border-slate-100 py-2 outline-none focus:border-<?= $color ?>-500 font-black text-xl bg-transparent">
                    </div>
                    <div>
                        <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-1 block">Priorité</label>
                        <select x-model="form.priority" class="w-full border-b-2 border-slate-100 py-2 outline-none focus:border-<?= $color ?>-500 font-bold bg-transparent">
                            <option value="1">Normal</option>
                            <option value="2">Important</option>
                            <option value="3">🔥 Coup de coeur</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-1 block">Catégorie</label>
                    <input type="text" x-model="form.category" list="cat-edit-list" class="w-full border-b-2 border-slate-100 py-2 outline-none focus:border-<?= $color ?>-500 font-bold bg-transparent">
                    <datalist id="cat-edit-list"><?php foreach($categories as $cat): ?><option value="<?= htmlspecialchars($cat) ?>"><?php endforeach; ?></datalist>
                </div>
            </div>
            <div class="mt-10 flex gap-3">
                <button @click="saveEdit()" class="flex-1 py-4 bg-<?= $color ?>-600 text-white rounded-2xl font-bold hover:brightness-110 transition-all shadow-lg shadow-<?= $color ?>-100">Enregistrer</button>
                <button @click="editModalOpen = false" class="px-6 py-4 bg-slate-50 text-slate-400 rounded-2xl font-bold">Annuler</button>
            </div>
        </div>
    </div>

    <div x-show="deleteModalOpen" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[110] flex items-center justify-center p-4" x-cloak x-transition>
        <div class="bg-white rounded-[2.5rem] p-8 max-w-sm w-full shadow-2xl text-center">
            <div class="w-16 h-16 bg-red-50 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
            </div>
            <h3 class="text-xl font-bold text-slate-900">Supprimer ce vœu ?</h3>
            <p class="text-slate-500 text-sm mt-2 mb-8 italic">Cette action est irréversible.</p>
            <div class="flex flex-col gap-2">
                <button @click="executeDelete()" class="w-full py-4 bg-red-500 text-white rounded-2xl font-bold hover:bg-red-600 shadow-lg shadow-red-100 transform active:scale-95 transition-all">Oui, supprimer</button>
                <button @click="deleteModalOpen = false" class="w-full py-3 text-slate-400 font-bold text-sm uppercase tracking-widest">Annuler</button>
            </div>
        </div>
    </div>

    <div x-show="shareModalOpen" x-cloak class="fixed inset-0 z-[120] flex items-center justify-center p-4 glass" x-transition>
        <div class="bg-white rounded-[3rem] p-8 max-w-md w-full shadow-2xl relative border border-<?= $color ?>-50" @click.away="shareModalOpen = false">
            <button @click="shareModalOpen = false" class="absolute top-6 right-6 text-slate-300 hover:text-slate-600"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-<?= $color ?>-50 text-<?= $color ?>-600 rounded-2xl flex items-center justify-center mx-auto mb-4"><svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg></div>
                <h2 class="text-2xl font-black text-slate-900 tracking-tight italic">Partager</h2>
                <p class="text-slate-400 text-sm mt-1">Lien public pour la famille.</p>
            </div>

            <div class="bg-slate-50 rounded-2xl p-4 mb-8 flex items-center justify-between border border-slate-100" x-data="{ showUrl: false, shareUrl: 'http://<?= $_SERVER['HTTP_HOST'] ?>/view.php?s=<?= $list['slug_public'] ?>' }">
                <div class="truncate font-mono text-[10px] text-<?= $color ?>-600 flex-1 mr-4">
                    <span x-show="!showUrl">••••••••••••••••••••••••••••••</span>
                    <span x-show="showUrl" x-text="shareUrl"></span>
                </div>
                <button @click="showUrl = !showUrl" class="text-slate-400 hover:text-<?= $color ?>-600 transition-colors">
                    <svg x-show="!showUrl" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <svg x-show="showUrl" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.025 10.025 0 014.132-5.411m0 0L21.241 21M17.89 12.003a3.315 3.315 0 01-1.025 2.411m-1.782-1.782a3.315 3.315 0 012.807-2.807M9.6 9.6L21.241 21"/></svg>
                </button>
            </div>

            <div class="grid grid-cols-3 gap-4" x-data="{ copied: false, shareUrl: 'http://<?= $_SERVER['HTTP_HOST'] ?>/view.php?s=<?= $list['slug_public'] ?>' }">
                <button @click="navigator.clipboard.writeText(shareUrl); copied = true; setTimeout(() => copied = false, 2000)" class="flex flex-col items-center gap-2 group">
                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center transition-all" :class="copied ? 'bg-green-500 text-white shadow-lg' : 'bg-slate-50 text-slate-400 group-hover:bg-<?= $color ?>-600 group-hover:text-white'">
                        <svg x-show="!copied" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2"/></svg>
                        <svg x-show="copied" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <span class="text-[8px] font-black uppercase tracking-widest text-slate-400" x-text="copied ? 'Copié' : 'Copier'"></span>
                </button>
                <a :href="'https://wa.me/?text=' + encodeURIComponent('Ma liste Wishi : ' + shareUrl)" target="_blank" class="flex flex-col items-center gap-2 group">
                    <div class="w-12 h-12 rounded-2xl bg-slate-50 text-slate-400 group-hover:bg-[#25D366] group-hover:text-white flex items-center justify-center transition-all"><svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg></div>
                    <span class="text-[8px] font-black uppercase tracking-widest text-slate-400">WhatsApp</span>
                </a>
                <a :href="'mailto:?subject=Ma liste de souhaits&body=' + encodeURIComponent('Coucou ! Voici ma liste : ' + shareUrl)" class="flex flex-col items-center gap-2 group">
                    <div class="w-12 h-12 rounded-2xl bg-slate-50 text-slate-400 group-hover:bg-slate-900 group-hover:text-white flex items-center justify-center transition-all"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" stroke-width="2"/></svg></div>
                    <span class="text-[8px] font-black uppercase tracking-widest text-slate-400">Email</span>
                </a>
            </div>
        </div>
    </div>

    <div x-show="settingsModalOpen" x-cloak class="fixed inset-0 z-[130] flex items-center justify-center p-4 glass" x-transition>
        <div class="bg-white rounded-[3rem] p-8 max-w-lg w-full shadow-2xl relative border border-slate-100" @click.away="settingsModalOpen = false">
            <h2 class="text-2xl font-black text-slate-900 mb-8 flex items-center gap-3 tracking-tighter italic">
                <span class="p-2 bg-slate-100 rounded-xl">⚙️</span>
                Réglages
            </h2>

            <div class="space-y-8">
                <div>
                    <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-2">Titre de l'événement</label>
                    <input type="text" x-model="listSettings.name" class="w-full border-b-2 border-slate-100 py-2 outline-none focus:border-<?= $color ?>-500 font-bold text-lg bg-transparent">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <button @click="resetReservations()" class="py-3 px-4 rounded-2xl bg-slate-50 text-slate-600 text-xs font-bold hover:bg-slate-100 transition-all">
                        🔄 Reset réservations
                    </button>
                    <button @click="deleteList()" class="py-3 px-4 rounded-2xl bg-red-50 text-red-500 text-xs font-bold hover:bg-red-100 transition-all">
                        🗑️ Supprimer la liste
                    </button>
                </div>
            </div>

            <div class="mt-10 flex gap-3">
                <button @click="saveSettings()" class="flex-1 py-4 bg-<?= $color ?>-600 text-white rounded-2xl font-bold hover:brightness-110 transition-all shadow-lg shadow-<?= $color ?>-100 shadow-xl">Sauvegarder</button>
                <button @click="settingsModalOpen = false" class="px-6 py-4 text-slate-400 font-bold uppercase tracking-widest text-xs">Annuler</button>
            </div>
        </div>
    </div>

</div>

<script>
function adminList() {
    return {
        editModalOpen: false, 
        deleteModalOpen: false, 
        shareModalOpen: false, 
        settingsModalOpen: false,
        itemToDelete: null, 
        form: {},
        listSettings: {
            id: <?= $list['id'] ?>,
            name: '<?= addslashes($list['name']) ?>'
        },

        initSortable() {
            const el = document.getElementById('items-grid');
            if(!el) return;
            Sortable.create(el, {
                animation: 250, 
                handle: '.cursor-move', 
                ghostClass: 'sortable-ghost',
                onEnd: async (evt) => {
                    const ids = Array.from(el.querySelectorAll('[data-id]'))
                                     .map(item => item.getAttribute('data-id'));
                    await fetch('api/reorder.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ ids: ids }) });
                }
            });
        },

        editItem(item) {
            this.form = {...item}; 
            this.editModalOpen = true;
        },

        async saveEdit() {
            if(this.form.category) {
                this.form.category = this.form.category.trim().charAt(0).toUpperCase() + this.form.category.trim().slice(1);
            }
            const response = await fetch('api/update_item.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(this.form) });
            if((await response.json()).success) window.location.reload();
        },

        confirmDeletion(id) {
            this.itemToDelete = id;
            this.deleteModalOpen = true;
        },

        async executeDelete() {
            const response = await fetch('api/delete_item.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ id: this.itemToDelete }) });
            if((await response.json()).success) window.location.reload();
        },

        async saveSettings() {
            const response = await fetch('api/update_list_settings.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(this.listSettings)
            });
            const result = await response.json();
            if(result.success) window.location.reload();
            else alert(result.error);
        },

        async resetReservations() {
            if(!confirm("Veux-tu vraiment rendre tous les cadeaux de nouveau disponibles ?")) return;
            const response = await fetch('api/reset_list.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ id: this.listSettings.id })
            });
            const result = await response.json();
            if(result.success) window.location.reload();
        },

        async deleteList() {
            if(!confirm("ALERTE : Supprimer définitivement cette liste et tous ses cadeaux ?")) return;
            const response = await fetch('api/delete_list.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ id: this.listSettings.id })
            });
            const result = await response.json();
            if(result.success) window.location.href = 'universe.php?slug=<?= $profileSlug ?>';
        }
    }
}
</script>

    <script>
      if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
          navigator.serviceWorker.register('sw.js')
            .then(reg => console.log('Wishi PWA Ready !'))
            .catch(err => console.log('PWA Error', err));
        });
      }
    </script>

</body>
</html>