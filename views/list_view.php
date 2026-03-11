<div class="max-w-5xl mx-auto py-10 px-4">
    
    <header class="mb-10 flex flex-col md:flex-row justify-between items-center gap-6">
        <div class="flex items-center gap-4">
            <a href="universe.php?slug=<?= $profileSlug ?>" class="p-2 bg-white border border-slate-200 text-slate-400 rounded-xl hover:text-<?= $color ?>-600 hover:border-<?= $color ?>-200 transition-all shadow-sm group" title="Retour à l'Univers">
                <svg class="w-5 h-5 transform group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 15l-3-3m0 0l3-3m-3 3h8M3 12a9 9 0 1118 0 9 9 0 01-18 0z"/>
                </svg>
            </a>
            
            <div class="flex flex-col">
                <h1 class="text-2xl font-black text-slate-900 tracking-tight italic">
                    <?= htmlspecialchars($list['name']) ?>
                </h1>
                <p class="text-slate-400 text-[9px] font-bold uppercase tracking-widest">🔐 Admin : <?= htmlspecialchars($ownerName) ?></p>
            </div>
        </div>
        
        <div class="flex gap-3">
            <button @click="settingsModalOpen = true" class="p-2.5 rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-<?= $color ?>-600 hover:border-<?= $color ?>-200 transition-all shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </button>
            <button @click="shareModalOpen = true" class="px-4 py-2.5 rounded-xl font-bold text-<?= $color ?>-600 bg-white border border-<?= $color ?>-200 hover:bg-<?= $color ?>-50 transition-all flex items-center gap-2 shadow-sm text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                Partager
            </button>
            <a href="index.php?slug=<?= $list['slug_admin'] ?>" class="px-4 py-2.5 rounded-xl font-bold text-white bg-slate-900 hover:bg-slate-800 transition-all flex items-center gap-2 shadow-sm text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Ajouter
            </a>
        </div>
    </header>

    <div class="mb-8 overflow-x-auto pb-2">
        <div class="flex items-center gap-4">
            <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest shrink-0">Filtrer par catégorie :</span>
            <div class="flex gap-2">
                <?php 
                    $activeClass = "bg-$color-600 text-white shadow-md shadow-$color-100";
                    $inactiveClass = "bg-white text-slate-400 border border-slate-200 hover:border-$color-300";
                ?>
                <a href="?slug=<?= $list['slug_admin'] ?>" 
                   class="px-4 py-1.5 rounded-full text-[10px] font-bold uppercase tracking-wider transition-all <?= $currentCat === '' ? $activeClass : $inactiveClass ?>">
                   Tous
                </a>
                
                <?php foreach($categories as $cat): ?>
                    <a href="?slug=<?= $list['slug_admin'] ?>&cat=<?= urlencode($cat) ?>" 
                       class="px-4 py-1.5 rounded-full text-[10px] font-bold uppercase tracking-wider transition-all <?= $currentCat === $cat ? $activeClass : $inactiveClass ?>">
                        <?= htmlspecialchars($cat) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div id="items-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (empty($items)): ?>
            <div class="lg:col-span-3 text-center py-20 bg-white rounded-3xl border-2 border-dashed border-slate-100">
                <p class="text-slate-300 font-bold uppercase text-[10px] tracking-widest">Cette liste est vide</p>
            </div>
        <?php else: ?>
            <?php foreach ($items as $item): ?>
                <div data-id="<?= $item['id'] ?>" class="bg-white rounded-2xl p-4 shadow-sm border border-slate-100 flex flex-col group relative transition-all hover:shadow-md <?= $item['priority'] == 3 ? "ring-1 ring-amber-400" : '' ?>">
                    
                    <div class="flex justify-between items-start mb-4">
                        <div class="flex items-center cursor-move text-slate-200 hover:text-<?= $color ?>-500 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/></svg>
                        </div>
                        <?php if ($item['is_taken']): ?>
                            <span class="bg-green-50 text-green-600 text-[8px] font-bold px-2 py-0.5 rounded-full uppercase tracking-tighter">Réservé</span>
                        <?php endif; ?>
                    </div>

                    <div class="flex gap-4 mb-4">
                        <div class="w-20 h-20 shrink-0 bg-slate-50 rounded-xl overflow-hidden border border-slate-50">
                            <img src="<?= htmlspecialchars($item['image_url'] ?: 'assets/img/placeholder.png') ?>" class="w-full h-full object-cover">
                        </div>
                        <div class="min-w-0 flex-1">
                            <span class="text-[8px] font-black text-<?= $color ?>-500 uppercase tracking-widest truncate block"><?= htmlspecialchars($item['category'] ?: 'Divers') ?></span>
                            <h3 class="font-bold text-slate-800 leading-tight truncate-2-lines mt-0.5 text-sm"><?= htmlspecialchars($item['title']) ?></h3>
                            <?php if (!empty($item['description'])): ?>
                                <p class="text-[10px] text-slate-400 mt-1 leading-tight line-clamp-2 italic"><?= htmlspecialchars($item['description']) ?></p>
                            <?php endif; ?>
                            <div class="mt-1 text-lg">
                                <?= \App\Utils\FormatUtils::formatDualPrice($item['price_eur'], $item['price'], $item['currency']) ?>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-4 mt-auto pt-3 border-t border-slate-50">
                        <button @click="editItem(<?= htmlspecialchars(json_encode($item)) ?>)" class="text-[9px] font-bold text-slate-400 hover:text-<?= $color ?>-600 uppercase tracking-widest">Modifier</button>
                        <?php if (!empty($item['url'])): ?>
                            <a href="<?= htmlspecialchars($item['url']) ?>" target="_blank" class="text-[9px] font-bold text-slate-400 hover:text-<?= $color ?>-600 uppercase tracking-widest">Voir</a>
                        <?php endif; ?>
                        <button @click="confirmDeletion(<?= $item['id'] ?>)" class="text-[9px] font-bold text-slate-300 hover:text-red-500 uppercase tracking-widest ml-auto">Supprimer</button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Edit Modal -->
    <div x-show="editModalOpen" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-[100] flex items-center justify-center p-4" x-cloak x-transition>
        <div class="bg-white rounded-3xl p-8 max-w-lg w-full shadow-2xl overflow-y-auto max-h-[90vh]" @click.away="editModalOpen = false">
            <h2 class="text-xl font-bold text-slate-900 mb-8">Modifier le vœu</h2>
            <div class="space-y-6">
                <div>
                    <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-1 block">Titre</label>
                    <textarea x-model="form.title" rows="2" class="w-full border-b border-slate-200 py-2 outline-none focus:border-<?= $color ?>-500 font-bold resize-none bg-transparent"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-8">
                    <div>
                        <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-1 block">Prix</label>
                        <div class="flex items-center gap-2">
                            <input type="number" step="0.01" x-model="form.price" class="w-20 border-b border-slate-200 py-2 outline-none focus:border-<?= $color ?>-500 font-bold text-lg bg-transparent">
                            <select x-model="form.currency" class="w-12 border-b border-slate-200 py-2 outline-none focus:border-<?= $color ?>-500 font-bold bg-transparent cursor-pointer">
                                <option value="EUR">€</option>
                                <option value="USD">$</option>
                                <option value="GBP">£</option>
                            </select>
                            <div x-show="form.currency !== 'EUR'" class="flex items-center gap-1">
                                <span class="text-slate-400 font-bold text-xs">≈</span>
                                <input type="number" step="0.01" x-model="form.price_eur" class="w-20 border-b border-slate-200 py-2 outline-none focus:border-<?= $color ?>-500 font-bold text-lg bg-transparent">
                                <span class="font-bold text-slate-400">€</span>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-1 block">Priorité</label>
                        <select x-model="form.priority" class="w-full border-b border-slate-200 py-2 outline-none focus:border-<?= $color ?>-500 font-bold bg-transparent">
                            <option value="1">Normal</option>
                            <option value="2">Important</option>
                            <option value="3">🔥 Coup de coeur</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-1 block">Catégorie</label>
                    <input type="text" x-model="form.category" list="cat-edit-list" class="w-full border-b border-slate-200 py-2 outline-none focus:border-<?= $color ?>-500 font-bold bg-transparent">
                    <datalist id="cat-edit-list"><?php foreach($categories as $cat): ?><option value="<?= htmlspecialchars($cat) ?>"><?php endforeach; ?></datalist>
                </div>
                <div>
                    <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-1 block">Description</label>
                    <textarea x-model="form.description" rows="3" class="w-full border border-slate-100 rounded-xl p-3 outline-none focus:border-<?= $color ?>-500 font-medium text-sm bg-slate-50 transition-all" placeholder="Détails, taille, couleur..."></textarea>
                </div>
                <div>
                    <label for="product_url" class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-1 block">Lien du produit</label>
                    <div class="flex gap-2">
                        <input id="product_url" type="url" x-model="form.url" class="flex-1 border-b border-slate-200 py-2 outline-none focus:border-<?= $color ?>-500 font-mono text-[10px] text-slate-500 bg-transparent">
                        <button @click="scrapeUrl()" :disabled="loading" class="px-3 py-1 bg-slate-100 text-slate-500 rounded-lg text-[9px] font-bold uppercase tracking-wider hover:bg-<?= $color ?>-100 hover:text-<?= $color ?>-600 transition-all disabled:opacity-50">
                            <span x-show="!loading">Mettre à jour ✨</span>
                            <span x-show="loading">...</span>
                        </button>
                    </div>
                </div>

                <div>
                    <div class="flex justify-between items-end mb-1">
                        <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block">Image</label>
                        <template x-if="images.length > 1">
                            <div class="flex items-center gap-2">
                                <button @click.prevent="prevImage()" class="p-1 text-slate-400 hover:text-<?= $color ?>-600 transition-colors"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="3" d="M15 19l-7-7 7-7"/></svg></button>
                                <span class="text-[9px] font-bold text-slate-400"><span x-text="currentImageIndex + 1"></span>/<span x-text="images.length"></span></span>
                                <button @click.prevent="nextImage()" class="p-1 text-slate-400 hover:text-<?= $color ?>-600 transition-colors"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="3" d="M9 5l7 7-7 7"/></svg></button>
                            </div>
                        </template>
                    </div>
                    <div class="flex gap-4 items-start">
                        <div class="w-16 h-16 shrink-0 bg-slate-50 rounded-xl overflow-hidden border border-slate-100">
                            <template x-if="form.image_url">
                                <img :src="form.image_url" class="w-full h-full object-cover">
                            </template>
                        </div>
                        <input type="text" x-model="form.image_url" class="flex-1 border-b border-slate-200 py-2 outline-none focus:border-<?= $color ?>-500 font-mono text-[10px] text-slate-500 bg-transparent" placeholder="URL de l'image">
                    </div>
                </div>
            </div>
            <div class="mt-10 flex gap-3">
                <button @click="saveEdit()" class="flex-1 py-4 bg-<?= $color ?>-600 text-white rounded-xl font-bold hover:brightness-110 transition-all">Enregistrer</button>
                <button @click="editModalOpen = false" class="px-6 py-4 text-slate-400 font-bold uppercase tracking-widest text-xs">Annuler</button>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div x-show="deleteModalOpen" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-[110] flex items-center justify-center p-4" x-cloak x-transition>
        <div class="bg-white rounded-3xl p-8 max-w-sm w-full shadow-2xl text-center">
            <h3 class="text-lg font-bold text-slate-900 mb-2">Supprimer ce vœu ?</h3>
            <p class="text-slate-400 text-xs mb-8 italic">Cette action est irréversible.</p>
            <div class="flex flex-col gap-2">
                <button @click="executeDelete()" class="w-full py-4 bg-red-500 text-white rounded-xl font-bold hover:bg-red-600 transition-all">Oui, supprimer</button>
                <button @click="deleteModalOpen = false" class="w-full py-3 text-slate-400 font-bold text-[10px] uppercase tracking-widest">Annuler</button>
            </div>
        </div>
    </div>

    <!-- Share Modal -->
    <div x-show="shareModalOpen" x-cloak class="fixed inset-0 z-[120] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm" x-transition>
        <div class="bg-white rounded-3xl p-8 max-w-md w-full shadow-2xl relative border border-slate-100" @click.away="shareModalOpen = false">
            <button @click="shareModalOpen = false" class="absolute top-6 right-6 text-slate-300 hover:text-slate-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            
            <div class="text-center mb-8">
                <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Partager la liste</h2>
                <p class="text-slate-400 text-xs mt-1">Lien public pour vos proches.</p>
            </div>

            <div class="bg-slate-50 rounded-xl p-4 mb-8 flex items-center justify-between border border-slate-100" x-data="{ showUrl: false, shareUrl: window.location.origin + '/view.php?s=<?= $list['slug_public'] ?>' }">
                <div class="truncate font-mono text-[10px] text-slate-500 flex-1 mr-4">
                    <span x-show="!showUrl">••••••••••••••••••••••••••••••</span>
                    <span x-show="showUrl" x-text="shareUrl"></span>
                </div>
                <button @click="showUrl = !showUrl" class="text-slate-400 hover:text-<?= $color ?>-600 transition-colors">
                    <svg x-show="!showUrl" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <svg x-show="showUrl" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.025 10.025 0 014.132-5.411m0 0L21.241 21M17.89 12.003a3.315 3.315 0 01-1.025 2.411m-1.782-1.782a3.315 3.315 0 012.807-2.807M9.6 9.6L21.241 21"/></svg>
                </button>
            </div>

            <div class="grid grid-cols-3 gap-4" x-data="{ copied: false, shareUrl: window.location.origin + '/view.php?s=<?= $list['slug_public'] ?>' }">
                <button @click="navigator.clipboard.writeText(shareUrl); copied = true; setTimeout(() => copied = false, 2000)" class="flex flex-col items-center gap-2 group">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center transition-all" :class="copied ? 'bg-green-500 text-white' : 'bg-slate-50 text-slate-400 group-hover:bg-slate-900 group-hover:text-white'">
                        <svg x-show="!copied" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2"/></svg>
                        <svg x-show="copied" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <span class="text-[8px] font-bold uppercase tracking-widest text-slate-400" x-text="copied ? 'Copié' : 'Copier'"></span>
                </button>
                <a :href="'https://wa.me/?text=' + encodeURIComponent('Ma liste Wishi : ' + shareUrl)" target="_blank" class="flex flex-col items-center gap-2 group">
                    <div class="w-12 h-12 rounded-xl bg-slate-50 text-slate-400 group-hover:bg-[#25D366] group-hover:text-white flex items-center justify-center transition-all"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg></div>
                    <span class="text-[8px] font-bold uppercase tracking-widest text-slate-400">WhatsApp</span>
                </a>
                <a :href="'mailto:?subject=Ma liste de souhaits&body=' + encodeURIComponent('Coucou ! Voici ma liste : ' + shareUrl)" class="flex flex-col items-center gap-2 group">
                    <div class="w-12 h-12 rounded-xl bg-slate-50 text-slate-400 group-hover:bg-slate-900 group-hover:text-white flex items-center justify-center transition-all"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" stroke-width="2"/></svg></div>
                    <span class="text-[8px] font-bold uppercase tracking-widest text-slate-400">Email</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Settings Modal -->
    <div x-show="settingsModalOpen" x-cloak class="fixed inset-0 z-[130] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm" x-transition>
        <div class="bg-white rounded-3xl p-8 max-w-lg w-full shadow-2xl relative border border-slate-100" @click.away="settingsModalOpen = false">
            <h2 class="text-xl font-bold text-slate-900 mb-8">Réglages de la liste</h2>

            <div class="space-y-6">
                <div>
                    <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-2">Titre de l'événement</label>
                    <input type="text" x-model="listSettings.name" class="w-full border-b border-slate-200 py-2 outline-none focus:border-<?= $color ?>-500 font-bold text-lg bg-transparent">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <button @click="resetReservations()" class="py-2.5 px-4 rounded-xl bg-slate-50 text-slate-600 text-[10px] font-bold uppercase tracking-wider hover:bg-slate-100 transition-all">
                        🔄 Reset réservations
                    </button>
                    <button @click="deleteList()" class="py-2.5 px-4 rounded-xl bg-red-50 text-red-500 text-[10px] font-bold uppercase tracking-wider hover:bg-red-100 transition-all">
                        🗑️ Supprimer la liste
                    </button>
                </div>
            </div>

            <div class="mt-10 flex gap-3 pt-6 border-t border-slate-50">
                <button @click="saveSettings()" class="flex-1 py-4 bg-<?= $color ?>-600 text-white rounded-xl font-bold hover:brightness-110 shadow-lg shadow-<?= $color ?>-100">Sauvegarder</button>
                <button @click="settingsModalOpen = false" class="px-6 py-4 text-slate-400 font-bold uppercase tracking-widest text-[10px]">Annuler</button>
            </div>
        </div>
    </div>

</div>
