<?php if (!isset($profile)): ?>
    <div class="max-w-md mx-auto py-24 px-4 text-center">
        <div class="text-6xl mb-6 opacity-20">🔍</div>
        <h1 class="text-2xl font-bold text-slate-800 mb-2">Rien à voir ici</h1>
        <p class="text-slate-400 text-sm font-medium">Ce lien n'existe pas ou ne contient aucune liste partagée.</p>
    </div>
<?php else: ?>
    <?php $color = $profile['color'] ?: 'indigo'; ?>
    <div class="max-w-3xl mx-auto py-12 px-4">
        <header class="flex flex-col items-center text-center mb-12">
            <div class="text-6xl mb-4"><?= htmlspecialchars($profile['emoji'] ?: '👤') ?></div>
            <h1 class="text-3xl font-black text-slate-900 tracking-tight">
                L'univers de <span class="text-<?= $color ?>-600"><?= htmlspecialchars($profile['name']) ?></span>
            </h1>
            <p class="text-slate-400 mt-1 text-[10px] font-bold uppercase tracking-widest">
                Choisissez une liste pour offrir un cadeau
            </p>
        </header>

        <div class="grid gap-4">
            <?php foreach ($lists as $l): ?>
                <a href="view.php?s=<?= htmlspecialchars($l['slug_public']) ?>"
                   class="group bg-white p-6 rounded-2xl flex justify-between items-center shadow-sm border border-slate-100 hover:border-<?= $color ?>-200 hover:shadow-md transition-all">
                    <div class="flex items-center gap-5">
                        <div class="w-12 h-12 bg-<?= $color ?>-50 text-<?= $color ?>-500 rounded-xl flex items-center justify-center text-2xl">
                            🎁
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-800 text-xl tracking-tight"><?= htmlspecialchars($l['name']) ?></h3>
                            <p class="text-slate-400 text-[10px] font-bold uppercase tracking-widest">
                                <?= (int)$l['count'] ?> souhait<?= $l['count'] > 1 ? 's' : '' ?>
                            </p>
                        </div>
                    </div>
                    <div class="text-slate-300 group-hover:text-<?= $color ?>-500 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="3" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>
