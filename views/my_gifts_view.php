<div class="max-w-2xl mx-auto py-12 px-4">
    <header class="text-center mb-10">
        <div class="text-6xl mb-4">🎁</div>
        <h1 class="text-4xl font-black text-slate-900 tracking-tight">Mes cadeaux</h1>
        <p class="text-slate-500 mt-3 text-sm leading-snug max-w-md mx-auto">
            Les cadeaux que vous avez réservés, sur toutes les listes.
            <strong class="text-slate-700">Wishi ne commande rien&nbsp;:</strong> c'est à vous de les acheter.
        </p>
    </header>

    <?php if ($linkInvalid): ?>
        <div class="mb-8 rounded-2xl bg-amber-50 border border-amber-200 p-4 text-sm text-amber-900">
            Ce lien ne contient plus aucun cadeau réservé (ils ont peut-être été annulés). Vous pouvez les rechercher avec votre email ci-dessous.
        </div>
    <?php endif; ?>

    <?php if (empty($groups)): ?>
        <div class="bg-white rounded-3xl p-10 shadow-sm border border-slate-100 text-center mb-8">
            <h2 class="text-xl font-bold text-slate-800 mb-2">Aucun cadeau pour l'instant</h2>
            <p class="text-slate-500 text-sm leading-snug">Vous n'avez rien réservé depuis ce téléphone ou cet ordinateur. Sur une liste, appuyez sur <strong>«&nbsp;Je réserve ce cadeau&nbsp;»</strong>&nbsp;: il apparaîtra ici.</p>
        </div>
    <?php else: ?>
        <div class="space-y-10 mb-10">
            <?php foreach ($groups as $group): $c = $group['color']; ?>
                <section>
                    <h2 class="flex items-center gap-3 text-xl font-black text-slate-900 mb-4">
                        <span class="text-3xl"><?= htmlspecialchars($group['emoji']) ?></span>
                        <span>Pour <span class="text-<?= $c ?>-600"><?= htmlspecialchars($group['name']) ?></span></span>
                    </h2>
                    <ul class="space-y-3">
                        <?php foreach ($group['items'] as $gift): ?>
                            <li class="bg-white rounded-2xl p-4 shadow-sm border border-slate-100">
                                <div class="flex gap-4">
                                    <img src="<?= htmlspecialchars($gift['image_url'] ?: '/assets/img/wishi-placeholder.png') ?>" alt=""
                                         class="w-20 h-20 shrink-0 rounded-xl object-cover bg-slate-50 border border-slate-100">
                                    <div class="flex-1 min-w-0">
                                        <p class="font-bold text-slate-800 leading-tight"><?= htmlspecialchars($gift['title']) ?></p>
                                        <?php $priceHtml = \App\Utils\FormatUtils::formatDualPrice($gift['price'], $gift['currency'], $gift['price_eur']); ?>
                                        <?php if ($priceHtml !== ''): ?>
                                            <p class="font-black text-slate-900 mt-1"><?= $priceHtml ?></p>
                                        <?php endif; ?>
                                        <a href="<?= htmlspecialchars($gift['list_url']) ?>" class="inline-block mt-1 text-xs font-bold text-slate-400 hover:text-<?= $c ?>-600">
                                            Liste «&nbsp;<?= htmlspecialchars($gift['list_name']) ?>&nbsp;»
                                        </a>
                                    </div>
                                </div>
                                <div class="flex gap-2 mt-4">
                                    <?php if ($gift['url']): ?>
                                        <a href="<?= htmlspecialchars($gift['url']) ?>" target="_blank" rel="noreferrer noopener"
                                           class="flex-[2] py-3 bg-<?= $c ?>-600 text-white rounded-xl font-bold text-sm text-center hover:brightness-110 transition-all">
                                            🛒 Voir sur <?= htmlspecialchars(\App\Utils\UrlUtils::getDomainLabel($gift['url'])) ?>
                                        </a>
                                    <?php endif; ?>
                                    <button type="button" @click="cancelGift(<?= (int)$gift['id'] ?>, <?= htmlspecialchars(json_encode($gift['title']), ENT_QUOTES, 'UTF-8') ?>)"
                                            class="flex-1 py-3 bg-slate-50 text-slate-500 rounded-xl font-bold text-sm hover:bg-red-50 hover:text-red-600 transition-colors">
                                        Annuler
                                    </button>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endforeach; ?>
        </div>

        <?php if ($myGiftsLink): ?>
            <section class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100 mb-8">
                <h2 class="font-black text-slate-900 mb-1">📲 Sur un autre téléphone ou ordinateur</h2>
                <p class="text-sm text-slate-600 leading-snug mb-4">Envoyez-vous ce lien (par SMS, email ou WhatsApp). En l'ouvrant ailleurs, vous retrouverez cette page. Gardez-le pour vous&nbsp;: il permet d'annuler vos réservations.</p>
                <button type="button" @click="wishiShareLink(<?= htmlspecialchars(json_encode($myGiftsLink), ENT_QUOTES, 'UTF-8') ?>)"
                        class="w-full py-3 rounded-xl bg-slate-900 text-white font-bold text-sm hover:bg-slate-700 transition-colors">
                    📤 M'envoyer ce lien
                </button>
            </section>
        <?php endif; ?>
    <?php endif; ?>

    <section class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100">
        <h2 class="font-black text-slate-900 mb-1">Vous avez réservé ailleurs ?</h2>
        <p class="text-sm text-slate-600 leading-snug mb-4">Si vous aviez donné votre email en réservant, tapez-le ici pour retrouver vos cadeaux sur cet appareil.</p>
        <form class="flex flex-col sm:flex-row gap-2" @submit.prevent="findByEmail()">
            <input type="email" x-model="email" placeholder="vous@email.com" autocomplete="email" aria-label="Votre email"
                   class="flex-1 min-w-0 px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 outline-none focus:border-indigo-500 focus:bg-white font-bold transition-all">
            <button type="submit" :disabled="searching" class="px-6 py-3 bg-indigo-600 text-white rounded-xl font-bold text-sm hover:brightness-110 transition-all disabled:opacity-60">Retrouver</button>
        </form>
        <p x-show="emailMessage" x-text="emailMessage" class="text-sm font-bold text-red-600 mt-3"></p>
    </section>
</div>
