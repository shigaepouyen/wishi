<div class="max-w-5xl mx-auto py-12 px-4">

    <div class="mb-12 text-center">
        <div class="text-6xl mb-4 transform hover:scale-110 transition-transform duration-500 inline-block">
            <?= $ownerEmoji ?>
        </div>
        <h1 class="text-4xl font-black text-slate-900 tracking-tight">
            La Liste de <?= htmlspecialchars($ownerName) ?>
        </h1>
        <p class="text-slate-400 mt-1 text-[10px] font-bold uppercase tracking-widest">
            Offrez un cadeau pour faire plaisir !
        </p>
    </div>

    <div class="mb-10">
        <div class="flex flex-wrap justify-center gap-2">
            <a href="?s=<?= $slug ?>&sort=<?= $sort ?><?= $showTaken ? '&show_taken=1' : '' ?>"
               class="px-5 py-2.5 rounded-full text-[10px] uppercase tracking-wider font-bold transition-all <?= $catFilter == '' ? "bg-$color-600 text-white shadow-md shadow-$color-100" : 'bg-white text-slate-400 border border-slate-200 hover:border-'.$color.'-300' ?>">
                Tous
            </a>
            <?php foreach($allCategories as $c): ?>
                <a href="?s=<?= $slug ?>&cat=<?= urlencode($c) ?>&sort=<?= $sort ?><?= $showTaken ? '&show_taken=1' : '' ?>"
                   class="px-5 py-2.5 rounded-full text-[10px] uppercase tracking-wider font-bold transition-all <?= $catFilter == $c ? "bg-$color-600 text-white shadow-md shadow-$color-100" : 'bg-white text-slate-400 border border-slate-200 hover:border-'.$color.'-300' ?>">
                    <?= htmlspecialchars($c) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="mb-8 flex flex-col md:flex-row justify-between items-center gap-4">
        <div class="flex items-center gap-3">
            <div class="relative inline-block w-10 mr-2 align-middle select-none transition duration-200 ease-in">
                <input type="checkbox" id="show_taken" <?= $showTaken ? 'checked' : '' ?>
                       onchange="window.location.href='?s=<?= $slug ?>&cat=<?= urlencode($catFilter) ?>&sort=<?= $sort ?>&show_taken=' + (this.checked ? '1' : '0')"
                       class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer"/>
                <label for="show_taken" class="toggle-label block overflow-hidden h-6 rounded-full bg-gray-300 cursor-pointer"></label>
            </div>
            <label for="show_taken" class="text-[10px] font-bold text-slate-400 uppercase tracking-widest cursor-pointer">Afficher les réservés</label>
        </div>

        <style>
            .toggle-checkbox:checked { right: 0; border-color: #4f46e5; }
            .toggle-checkbox:checked + .toggle-label { background-color: #4f46e5; }
            .toggle-checkbox { right: 4px; transition: all 0.3s; }
            .toggle-label { background-color: #cbd5e1; }
        </style>

        <div class="flex items-center gap-3">
            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Trier par :</label>
            <select onchange="window.location.href='?s=<?= $slug ?>&cat=<?= urlencode($catFilter) ?>&sort=' + this.value + '<?= $showTaken ? '&show_taken=1' : '' ?>'"
                    class="bg-white border border-slate-200 text-slate-600 text-[10px] uppercase tracking-wider font-bold rounded-xl px-4 py-2 outline-none focus:ring-2 focus:ring-<?= $color ?>-500 shadow-sm cursor-pointer transition-all">
                <option value="priority" <?= ($data['currentSort'] == 'priority' || $data['currentSort'] == 'position') ? 'selected' : '' ?>>✨ Ordre de <?= htmlspecialchars($ownerName) ?></option>
                <option value="price_asc" <?= $data['currentSort'] == 'price_asc' ? 'selected' : '' ?>>Prix croissant</option>
                <option value="price_desc" <?= $data['currentSort'] == 'price_desc' ? 'selected' : '' ?>>Prix décroissant</option>
            </select>
        </div>
    </div>

    <?php if (empty($data['items'])): ?>
        <div class="max-w-md mx-auto bg-white rounded-3xl p-12 shadow-sm border border-slate-100 text-center">
            <div class="text-5xl mb-6 opacity-20">🎁</div>
            <h2 class="text-xl font-bold text-slate-800 mb-2">Tout a été trouvé !</h2>
            <p class="text-slate-400 text-xs font-medium italic">Rien ne correspond ou tout a déjà été réservé.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-6">
            <?php foreach ($data['items'] as $item): ?>
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 flex flex-col justify-between hover:border-<?= $color ?>-200 hover:shadow-md transition-all duration-300 group <?= $item['priority'] == 3 ? "ring-2 ring-amber-400 ring-offset-2" : '' ?> <?= $item['is_taken'] ? 'opacity-60 grayscale-[0.3]' : '' ?>">
                    <div class="flex gap-5 mb-5">
                        <div class="w-24 h-24 shrink-0 bg-slate-50 rounded-xl overflow-hidden border border-slate-100">
                            <img src="<?= htmlspecialchars($item['image_url'] ?: 'assets/img/placeholder.png') ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        </div>
                        <div class="flex-1 min-w-0 flex flex-col justify-center">
                            <span class="text-[9px] font-black text-<?= $color ?>-500 uppercase tracking-widest mb-1"><?= htmlspecialchars($item['category'] ?: 'Souhait') ?></span>
                            <h3 class="font-bold text-slate-800 text-lg leading-tight truncate-2-lines"><?= htmlspecialchars($item['title']) ?></h3>
                            <?php if (!empty($item['description'])): ?>
                                <p class="text-[11px] text-slate-500 mt-1.5 leading-snug line-clamp-3"><?= nl2br(htmlspecialchars($item['description'])) ?></p>
                            <?php endif; ?>
                            <p class="font-black text-xl mt-2 text-slate-900 tracking-tight">
                                <?= number_format($item['price'], 2, ',', ' ') ?>
                                <?php
                                    echo \App\Utils\FormatUtils::getCurrencySymbol($item['currency']);
                                ?>
                            </p>
                        </div>
                    </div>

                    <div class="flex gap-2 mt-auto">
                        <?php if($item['url']): ?>
                        <a href="<?= htmlspecialchars($item['url']) ?>" target="_blank" class="flex-1 py-3 bg-slate-50 text-slate-500 rounded-xl font-bold text-xs text-center hover:bg-slate-100 transition-colors">Voir</a>
                        <?php endif; ?>

                        <?php if($item['is_taken']): ?>
                            <div class="flex-[2] flex flex-col gap-1">
                                <span class="bg-slate-50 text-slate-400 py-3 rounded-xl font-bold text-xs text-center italic">Réservé par <?= htmlspecialchars($item['taken_by']) ?></span>
                                <template x-if="hasCookie(<?= $item['id'] ?>)">
                                    <button @click="cancelGift(<?= $item['id'] ?>)" class="text-[9px] font-bold text-red-400 hover:text-red-600 uppercase tracking-wider text-center mt-1">Annuler ma réservation</button>
                                </template>
                                <template x-if="!hasCookie(<?= $item['id'] ?>) && <?= $item['donor_email'] ? 'true' : 'false' ?>">
                                    <button @click="openCancelModal(<?= $item['id'] ?>, '<?= addslashes($item['title']) ?>')" class="text-[9px] font-bold text-slate-400 hover:text-slate-600 uppercase tracking-wider text-center mt-1">Annuler avec mon email</button>
                                </template>
                            </div>
                        <?php else: ?>
                            <button @click="openModal(<?= $item['id'] ?>, '<?= addslashes($item['title']) ?>')"
                                    class="flex-[2] py-3 bg-<?= $color ?>-600 text-white rounded-xl font-bold hover:brightness-110 shadow-md shadow-<?= $color ?>-100 transition-all text-xs uppercase tracking-wider">
                                Offrir
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Modal Réservation -->
    <div x-show="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm" x-cloak x-transition>
        <div class="bg-white rounded-3xl p-8 max-w-sm w-full shadow-2xl relative border border-slate-100" @click.away="modalOpen = false">
            <button @click="modalOpen = false" class="absolute top-6 right-6 text-slate-300 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>

            <h3 class="text-2xl font-bold text-slate-900 mb-2">Réserver ce vœu ?</h3>
            <p class="text-slate-400 text-[10px] font-bold uppercase tracking-widest leading-tight mb-8" x-text="selectedItemTitle"></p>

            <div class="space-y-6">
                <div>
                    <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-2 px-1">Votre Prénom *</label>
                    <input type="text" x-model="donorName" placeholder="Qui offre ?" class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 outline-none focus:border-<?= $color ?>-500 focus:bg-white font-bold transition-all">
                </div>
                <div>
                    <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-2 px-1">Votre Email (Optionnel)</label>
                    <input type="email" x-model="donorEmail" placeholder="secret@email.com" class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 outline-none focus:border-<?= $color ?>-500 focus:bg-white font-bold transition-all text-sm">
                    <p class="text-[9px] text-slate-400 mt-2 px-1 italic">L'email reste secret, utile uniquement pour annuler plus tard.</p>
                </div>
                <button @click="confirmGift()" class="w-full py-4 bg-<?= $color ?>-600 text-white rounded-xl font-bold shadow-lg shadow-<?= $color ?>-100 hover:brightness-110 active:scale-95 transition-all text-lg">
                    Confirmer
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Annulation -->
    <div x-show="cancelModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm" x-cloak x-transition>
        <div class="bg-white rounded-3xl p-8 max-w-sm w-full shadow-2xl relative border border-slate-100" @click.away="cancelModalOpen = false">
            <button @click="cancelModalOpen = false" class="absolute top-6 right-6 text-slate-300 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>

            <h3 class="text-2xl font-bold text-slate-900 mb-2">Annuler la réservation ?</h3>
            <p class="text-slate-400 text-[10px] font-bold uppercase tracking-widest leading-tight mb-8" x-text="selectedItemTitle"></p>

            <div class="space-y-6">
                <div>
                    <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-2 px-1">Votre Email de réservation</label>
                    <input type="email" x-model="cancelEmail" placeholder="Votre email..." class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 outline-none focus:border-red-500 focus:bg-white font-bold transition-all text-sm">
                </div>
                <button @click="cancelGift(null, true)" class="w-full py-4 bg-red-500 text-white rounded-xl font-bold shadow-lg shadow-red-100 hover:brightness-110 active:scale-95 transition-all text-lg">
                    Confirmer l'annulation
                </button>
            </div>
        </div>
    </div>
</div>
