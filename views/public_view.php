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
            <?= !empty($hubUrl) ? 'Une liste de ' . htmlspecialchars($ownerName) : 'Offre un cadeau pour faire plaisir !' ?>
        </p>
    </div>

    <section class="mb-10 max-w-3xl mx-auto bg-white rounded-3xl border border-<?= $color ?>-200 p-6 shadow-sm">
        <h2 class="text-base font-black text-slate-800 mb-1 text-center">Comment ça marche&nbsp;?</h2>
        <p class="text-sm text-slate-500 text-center mb-5">Quatre étapes, et zéro cadeau en double 🎉</p>
        <ol class="grid gap-5 sm:grid-cols-2 text-sm text-slate-600 leading-snug">
            <li class="flex items-start gap-3">
                <span class="shrink-0 w-9 h-9 rounded-full bg-<?= $color ?>-50 text-<?= $color ?>-600 flex items-center justify-center font-black">1</span>
                <span><strong class="text-slate-800 block mb-0.5">Trouve ton bonheur</strong>Parcours la liste et repère le cadeau qui te tente.</span>
            </li>
            <li class="flex items-start gap-3">
                <span class="shrink-0 w-9 h-9 rounded-full bg-<?= $color ?>-50 text-<?= $color ?>-600 flex items-center justify-center font-black">2</span>
                <span><strong class="text-slate-800 block mb-0.5">Réserve-le</strong>Appuie sur «&nbsp;Je réserve ce cadeau&nbsp;»&nbsp;: il disparaît de la liste, personne d'autre ne l'offrira.</span>
            </li>
            <li class="flex items-start gap-3">
                <span class="shrink-0 w-9 h-9 rounded-full bg-<?= $color ?>-50 text-<?= $color ?>-600 flex items-center justify-center font-black">3</span>
                <span><strong class="text-slate-800 block mb-0.5">Achète-le de ton côté</strong>En magasin ou en ligne, comme tu préfères&nbsp;: Wishi ne commande et ne paie rien. Pas à l'aise pour commander&nbsp;? Demande à Elodie ou JC, ils s'en occupent avec plaisir 🙂</span>
            </li>
            <li class="flex items-start gap-3">
                <span class="shrink-0 w-9 h-9 rounded-full bg-<?= $color ?>-50 text-<?= $color ?>-600 flex items-center justify-center font-black">4</span>
                <span><strong class="text-slate-800 block mb-0.5">Retrouve tes cadeaux</strong>Le bouton «&nbsp;🎁 Mes cadeaux&nbsp;», juste en dessous, liste tout ce que tu as réservé, avec le lien du magasin. Sur un autre téléphone&nbsp;? Envoie-toi le lien depuis cette page.</span>
            </li>
        </ol>
    </section>

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
        <?php include __DIR__ . '/components/my_gifts_button.php'; ?>

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
                        <a href="<?= htmlspecialchars($item['url']) ?>" target="_blank" rel="noreferrer noopener" class="flex-1 py-3 bg-slate-50 text-slate-500 rounded-xl font-bold text-sm text-center hover:bg-slate-100 transition-colors">Voir le produit</a>
                        <?php endif; ?>

                            <button @click="openModal(<?= $item['id'] ?>, <?= htmlspecialchars(json_encode($item['title']), ENT_QUOTES, 'UTF-8') ?>)"
                                    class="flex-[2] py-3 bg-<?= $color ?>-600 text-white rounded-xl font-bold hover:brightness-110 shadow-md shadow-<?= $color ?>-100 transition-all text-sm">
                                🎁 Je réserve ce cadeau
                            </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Fenêtre de réservation : formulaire, puis écran « C'est noté » -->
    <div x-show="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm" x-cloak x-transition>
        <div class="bg-white rounded-3xl p-8 max-w-md w-full max-h-[90vh] overflow-y-auto shadow-2xl relative border border-slate-100" @click.away="closeModal()">
            <button @click="closeModal()" class="absolute top-6 right-6 text-slate-300 hover:text-slate-600" aria-label="Fermer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>

            <!-- Étape 1 : réserver -->
            <div x-show="!reserved">
                <h3 class="text-2xl font-bold text-slate-900 mb-1 pr-8">Réserver ce cadeau&nbsp;?</h3>
                <p class="text-slate-500 text-sm font-bold leading-snug mb-5" x-text="selectedItemTitle"></p>

                <div class="rounded-2xl bg-amber-50 border border-amber-200 p-4 mb-6 text-sm text-amber-900 leading-snug">
                    <p class="font-bold mb-1">Bon à savoir</p>
                    <p>Wishi <strong>ne commande rien et ne paie rien</strong>. En réservant, tu préviens simplement la famille que tu t'occupes de ce cadeau. C'est ensuite à toi de l'acheter, où tu veux.</p>
                </div>

                <form class="space-y-5" @submit.prevent="confirmGift()">
                    <div>
                        <label for="donor_name" class="text-sm font-bold text-slate-700 block mb-2 px-1">Ton prénom</label>
                        <input id="donor_name" type="text" x-model="donorName" placeholder="Ex : Mamie Jo" required autocomplete="given-name" class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 outline-none focus:border-<?= $color ?>-500 focus:bg-white font-bold transition-all">
                    </div>
                    <div>
                        <label for="donor_email" class="text-sm font-bold text-slate-700 block mb-2 px-1">Ton email <span class="font-medium text-slate-400">(facultatif, conseillé)</span></label>
                        <input id="donor_email" type="email" x-model="donorEmail" placeholder="toi@email.com" autocomplete="email" class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 outline-none focus:border-<?= $color ?>-500 focus:bg-white font-bold transition-all">
                        <p class="text-xs text-slate-500 mt-2 px-1 leading-snug">Il sert seulement à retrouver tes cadeaux si tu changes de téléphone ou d'ordinateur. Personne ne le voit, et tu ne recevras aucun message.</p>
                    </div>
                    <p x-show="errorMessage" x-text="errorMessage" class="text-sm font-bold text-red-600"></p>
                    <button type="submit" :disabled="sending" class="w-full py-4 bg-<?= $color ?>-600 text-white rounded-xl font-bold shadow-lg shadow-<?= $color ?>-100 hover:brightness-110 active:scale-95 transition-all text-lg disabled:opacity-60">
                        Oui, je m'en occupe
                    </button>
                </form>
            </div>

            <!-- Étape 2 : c'est noté -->
            <div x-show="reserved" class="text-center">
                <div class="text-5xl mb-3">✅</div>
                <h3 class="text-2xl font-bold text-slate-900 mb-2">C'est noté&nbsp;!</h3>
                <p class="text-slate-600 text-sm leading-snug mb-6">
                    <strong x-text="selectedItemTitle"></strong> est mis de côté pour toi. Personne d'autre ne pourra le réserver.
                </p>

                <div class="rounded-2xl bg-<?= $color ?>-50 border border-<?= $color ?>-200 p-5 mb-6 text-left">
                    <p class="font-black text-slate-900 mb-1">🛒 Maintenant, à toi de l'acheter</p>
                    <p class="text-sm text-slate-600 leading-snug">Wishi n'a rien commandé. Achète ce cadeau de ton côté, en magasin ou sur internet. Besoin d'un coup de main pour le commander&nbsp;? Demande à Elodie ou JC.</p>
                    <a x-show="shopUrl" :href="shopUrl" target="_blank" rel="noreferrer noopener"
                       class="mt-4 block w-full py-3 bg-<?= $color ?>-600 text-white rounded-xl font-bold text-center hover:brightness-110 transition-all"
                       x-text="shopLabel ? 'Voir le produit sur ' + shopLabel : 'Voir le produit'"></a>
                </div>

                <div class="rounded-2xl border border-slate-200 p-5 mb-6 text-left">
                    <p class="font-bold text-slate-800 mb-1">📋 Retrouver mes cadeaux plus tard</p>
                    <p class="text-sm text-slate-600 leading-snug">Tous les cadeaux que tu réserves sont rangés dans <strong>«&nbsp;Mes cadeaux&nbsp;»</strong>, en haut de chaque liste. Pour les retrouver sur un autre appareil, envoie-toi ce lien.</p>
                    <div class="mt-4 grid gap-2 sm:grid-cols-2">
                        <a href="/mes-cadeaux" class="py-3 rounded-xl bg-slate-50 text-slate-700 font-bold text-sm text-center hover:bg-slate-100 transition-colors">Voir mes cadeaux</a>
                        <button type="button" @click="shareMyGiftsLink(myGiftsLink)" class="py-3 rounded-xl bg-slate-50 text-slate-700 font-bold text-sm text-center hover:bg-slate-100 transition-colors">📤 M'envoyer ce lien</button>
                    </div>
                </div>

                <button type="button" @click="closeModal()" class="text-sm font-bold text-slate-500 hover:text-slate-800 underline underline-offset-4">Revenir à la liste</button>
            </div>
        </div>
    </div>
</div>
