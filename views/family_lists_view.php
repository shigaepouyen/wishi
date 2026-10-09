<div class="max-w-6xl mx-auto py-16 px-4">
    <a href="hub.php" class="text-indigo-600 font-bold flex items-center gap-2 hover:-translate-x-1 transition-all uppercase text-[10px] tracking-widest mb-10">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="3" d="M15 19l-7-7 7-7"/></svg>
        Retour au Hub
    </a>

    <header class="mb-10">
        <h1 class="text-3xl font-black text-slate-900 tracking-tight">🎁 Les listes de la famille</h1>
        <p class="text-slate-400 mt-1 text-[10px] font-bold uppercase tracking-widest">Les listes marquées « Visible par la famille »</p>
    </header>

    <?php if (empty($sharedLists)): ?>
        <p class="text-slate-400 text-sm">Aucune liste partagée pour l'instant.</p>
    <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($sharedLists as $group): ?>
                <?php $sp = $group['profile']; $sc = $sp['color'] ?: 'indigo'; ?>
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100">
                    <a href="/<?= rawurlencode($sp['slug']) ?>" class="flex items-center gap-3 mb-4 group">
                        <span class="text-3xl"><?= htmlspecialchars($sp['emoji'] ?: '👤') ?></span>
                        <span class="text-xl font-bold text-slate-800 tracking-tight hover:text-<?= $sc ?>-600 transition-colors"><?= htmlspecialchars($sp['name']) ?></span>
                    </a>
                    <div class="grid gap-2">
                        <?php foreach ($group['lists'] as $l): ?>
                            <a href="/<?= rawurlencode($sp['slug']) ?>/<?= rawurlencode($l['slug_hub']) ?>"
                               class="group flex justify-between items-center px-4 py-3 rounded-2xl bg-<?= $sc ?>-50 border border-transparent hover:border-<?= $sc ?>-200 transition-all">
                                <div class="min-w-0">
                                    <p class="font-bold text-slate-800 truncate"><?= htmlspecialchars($l['name']) ?></p>
                                    <p class="text-slate-400 text-[10px] font-bold uppercase tracking-widest">
                                        <?= (int)$l['count'] ?> souhait<?= $l['count'] > 1 ? 's' : '' ?>
                                    </p>
                                </div>
                                <svg class="w-4 h-4 shrink-0 text-slate-300 group-hover:text-<?= $sc ?>-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="3" d="M9 5l7 7-7 7"/></svg>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
