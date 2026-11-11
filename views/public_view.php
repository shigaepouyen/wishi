<?php
    // Construit un lien interne (filtre, tri, reservations) en respectant la forme d'URL
    // par laquelle le visiteur est arrive : /<profil>/<liste> ou view.php?s=<slug>.
    $publicLink = function (array $params = []) use ($publicBaseUrl) {
        $params = array_filter($params, function ($value) { return $value !== '' && $value !== null; });
        if (empty($params)) return $publicBaseUrl;
        $separator = str_contains($publicBaseUrl, '?') ? '&' : '?';
        return $publicBaseUrl . $separator . http_build_query($params);
    };
?>
<div class="max-w-5xl mx-auto py-12 px-4">

    <?php if (!empty($hubUrl)): ?>
        <nav class="mb-8 flex flex-wrap items-center gap-2">
            <a href="<?= htmlspecialchars($hubUrl) ?>"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-full bg-white border border-slate-200 text-[10px] font-bold uppercase tracking-widest text-slate-500 hover:border-<?= $color ?>-300 hover:text-<?= $color ?>-600 transition-all">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="3" d="M15 19l-7-7 7-7"/></svg>
                L'univers de <?= htmlspecialchars($ownerName) ?>
            </a>
            <?php foreach ($hubSiblings as $sibling): ?>
                <a href="/<?= rawurlencode($data['list']['profile_slug']) ?>/<?= rawurlencode($sibling['slug_hub']) ?>"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-full bg-white border border-slate-200 text-[10px] font-bold uppercase tracking-widest text-slate-400 hover:border-<?= $color ?>-300 hover:text-<?= $color ?>-600 transition-all">
                    <?= htmlspecialchars($sibling['name']) ?>
                    <span class="text-slate-300"><?= (int)$sibling['count'] ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>

    <div class="mb-12 text-center">
        <div class="text-6xl mb-4 transform hover:scale-110 transition-transform duration-500 inline-block">
            <?= $ownerEmoji ?>
        </div>
        <h1 class="text-4xl font-black text-slate-900 tracking-tight">
            <?= !empty($hubUrl) ? htmlspecialchars($data['list']['name']) : 'La Liste de ' . htmlspecialchars($ownerName) ?>
        </h1>
        <p class="text-slate-400 mt-1 text-[10px] font-bold uppercase tracking-widest">
            <?= !empty($hubUrl) ? 'Une liste de ' . htmlspecialchars($ownerName) : 'Offrez un cadeau pour faire plaisir !' ?>
        </p>
    </div>

    <div class="mb-10">
        <div class="flex flex-wrap justify-center gap-2">
            <a href="<?= htmlspecialchars($publicLink(['sort' => $sort])) ?>"
               class="px-5 py-2.5 rounded-full text-[10px] uppercase tracking-wider font-bold transition-all <?= $catFilter == '' ? "bg-$color-600 text-white shadow-md shadow-$color-100" : 'bg-white text-slate-400 border border-slate-200 hover:border-'.$color.'-300' ?>">
                Tous
            </a>
            <?php foreach($allCategories as $c): ?>
                <a href="<?= htmlspecialchars($publicLink(['cat' => $c, 'sort' => $sort])) ?>"
                   class="px-5 py-2.5 rounded-full text-[10px] uppercase tracking-wider font-bold transition-all <?= $catFilter == $c ? "bg-$color-600 text-white shadow-md shadow-$color-100" : 'bg-white text-slate-400 border border-slate-200 hover:border-'.$color.'-300' ?>">
                    <?= htmlspecialchars($c) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="mb-8 flex flex-col md:flex-row justify-between items-center gap-4">
        <button @click="openMyGifts()" type="button"
                class="px-5 py-2.5 rounded-full text-[10px] uppercase tracking-wider font-bold bg-white text-slate-500 border border-slate-200 hover:border-<?= $color ?>-300 hover:text-<?= $color ?>-600 transition-all">
            🎁 Mes réservations
        </button>

        <div class="flex items-center gap-3">
            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Trier par :</label>
            <select onchange="window.location.href='<?= htmlspecialchars($publicLink(['cat' => $catFilter])) ?><?= str_contains($publicLink(['cat' => $catFilter]), '?') ? '&' : '?' ?>sort=' + this.value"
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
            <p class="text-slate-400 text-xs font-medium italic">Rien ne correspond, ou tout a déjà été réservé.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-6">
            <?php foreach ($data['items'] as $item): ?>
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 flex flex-col justify-between hover:border-<?= $color ?>-200 hover:shadow-md transition-all duration-300 group <?= $item['priority'] == 3 ? "ring-2 ring-amber-400 ring-offset-2" : '' ?>">
                    <div class="flex gap-5 mb-5">
                        <div class="w-24 h-24 shrink-0 bg-slate-50 rounded-xl overflow-hidden border border-slate-100">
                            <img src="<?= htmlspecialchars($item['image_url'] ?: '/assets/img/wishi-placeholder.png') ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        </div>
                        <div class="flex-1 min-w-0 flex flex-col justify-center">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-[9px] font-black text-<?= $color ?>-500 uppercase tracking-widest"><?= htmlspecialchars($item['category'] ?: 'Souhait') ?></span>
                                <?php if($item['url']): ?>
                                    <span class="text-[8px] font-bold text-slate-300 uppercase tracking-widest ml-1 opacity-60 italic">
                                        via <?= \App\Utils\UrlUtils::getDomainLabel($item['url']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <h3 class="font-bold text-slate-800 text-lg leading-tight truncate-2-lines"><?= htmlspecialchars($item['title']) ?></h3>
                            <?php if (!empty($item['description'])): ?>
                                <p class="text-[11px] text-slate-500 mt-1.5 leading-snug line-clamp-3"><?= nl2br(htmlspecialchars($item['description'])) ?></p>
                            <?php endif; ?>
                            <?php $priceHtml = \App\Utils\FormatUtils::formatDualPrice($item['price'], $item['currency'], $item['price_eur']); ?>
                            <?php if ($priceHtml !== ''): ?>
                                <p class="font-black text-xl mt-2 text-slate-900 tracking-tight"><?= $priceHtml ?></p>
                            <?php else: ?>
                                <p class="text-[11px] font-bold uppercase tracking-widest text-slate-300 mt-2">Prix non indiqué</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="flex gap-2 mt-auto">
                        <?php if($item['url']): ?>
                        <a href="<?= htmlspecialchars($item['url']) ?>" target="_blank" rel="noreferrer noopener" class="flex-1 py-3 bg-slate-50 text-slate-500 rounded-xl font-bold text-xs text-center hover:bg-slate-100 transition-colors">Voir</a>
                        <?php endif; ?>

                            <button @click="openModal(<?= $item['id'] ?>, <?= htmlspecialchars(json_encode($item['title']), ENT_QUOTES, 'UTF-8') ?>)"
                                    class="flex-[2] py-3 bg-<?= $color ?>-600 text-white rounded-xl font-bold hover:brightness-110 shadow-md shadow-<?= $color ?>-100 transition-all text-xs uppercase tracking-wider">
                                Offrir
                            </button>
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
                    <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-2 px-1">Votre Email *</label>
                    <input type="email" x-model="donorEmail" placeholder="vous@email.com" required class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 outline-none focus:border-<?= $color ?>-500 focus:bg-white font-bold transition-all text-sm">
                    <p class="text-[9px] text-slate-400 mt-2 px-1 italic">Jamais affiché ni utilisé autrement : il sert uniquement à retrouver ou annuler vos cadeaux plus tard (sur toutes les listes de <?= htmlspecialchars($ownerName) ?>).</p>
                </div>
                <button @click="confirmGift()" class="w-full py-4 bg-<?= $color ?>-600 text-white rounded-xl font-bold shadow-lg shadow-<?= $color ?>-100 hover:brightness-110 active:scale-95 transition-all text-lg">
                    Confirmer
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Mes réservations -->
    <div x-show="myGiftsOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm" x-cloak x-transition>
        <div class="bg-white rounded-3xl p-8 max-w-md w-full max-h-[90vh] overflow-y-auto shadow-2xl relative border border-slate-100" @click.away="myGiftsOpen = false">
            <button @click="myGiftsOpen = false" class="absolute top-6 right-6 text-slate-300 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>

            <h3 class="text-2xl font-bold text-slate-900 mb-2">Mes réservations</h3>
            <p class="text-slate-400 text-[10px] font-bold uppercase tracking-widest leading-tight mb-6">Tout ce que vous offrez à <?= htmlspecialchars($ownerName) ?></p>

            <form class="flex gap-2 mb-6" @submit.prevent="loadMyGifts()">
                <input type="email" x-model="myEmail" placeholder="Votre email de réservation" required
                       class="flex-1 min-w-0 px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 outline-none focus:border-<?= $color ?>-500 focus:bg-white font-bold transition-all text-sm">
                <button type="submit" class="px-5 py-3 bg-<?= $color ?>-600 text-white rounded-xl font-bold text-xs uppercase tracking-wider hover:brightness-110 transition-all">Voir</button>
            </form>

            <p x-show="myGiftsMessage" x-text="myGiftsMessage" class="text-xs text-slate-500 italic mb-4"></p>

            <ul class="space-y-3">
                <template x-for="gift in myGifts" :key="gift.id">
                    <li class="flex items-center gap-3 p-3 rounded-2xl border border-slate-100 bg-slate-50/50">
                        <img :src="gift.image_url || '/assets/img/wishi-placeholder.png'" class="w-14 h-14 rounded-xl object-cover bg-white border border-slate-100 shrink-0" alt="">
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-slate-800 text-sm leading-tight truncate-2-lines" x-text="gift.title"></p>
                            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mt-1" x-text="gift.list_name"></p>
                        </div>
                        <button @click="cancelMyGift(gift.id)" class="text-[9px] font-bold text-red-400 hover:text-red-600 uppercase tracking-wider shrink-0">Annuler</button>
                    </li>
                </template>
            </ul>
        </div>
    </div>
</div>
