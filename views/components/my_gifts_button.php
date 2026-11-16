<?php
// Bouton « Mes cadeaux » des pages publiques. Attend $myGiftsCount (int) et $color.
$myGiftsCount = (int)($myGiftsCount ?? 0);
?>
<a href="/mes-cadeaux"
   class="inline-flex items-center gap-2 px-5 py-3 rounded-full text-sm font-bold transition-all <?= $myGiftsCount > 0 ? "bg-$color-600 text-white shadow-md shadow-$color-100 hover:brightness-110" : "bg-white text-slate-500 border border-slate-200 hover:border-$color-300 hover:text-$color-600" ?>">
    🎁 Mes cadeaux<?php if ($myGiftsCount > 0): ?>
        <span class="inline-flex items-center justify-center min-w-6 h-6 px-1.5 rounded-full bg-white text-<?= $color ?>-600 text-xs font-black"><?= $myGiftsCount ?></span>
    <?php endif; ?>
</a>
