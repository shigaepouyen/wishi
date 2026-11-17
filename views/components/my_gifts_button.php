<?php
// Bouton « Mes cadeaux à offrir » des pages publiques : mene a la page des cadeaux que le
// visiteur a reserves. Attend $myGiftsCount (int) et $color.
$myGiftsCount = (int)($myGiftsCount ?? 0);
?>
<a href="/mes-cadeaux"
   class="inline-flex items-center gap-3 pl-4 pr-5 py-2.5 rounded-full text-left transition-all <?= $myGiftsCount > 0 ? "bg-$color-600 text-white shadow-md shadow-$color-100 hover:brightness-110" : "bg-white text-slate-500 border border-slate-200 hover:border-$color-300 hover:text-$color-600" ?>">
    <span class="text-xl leading-none">🎁</span>
    <span class="flex flex-col leading-tight">
        <span class="text-sm font-bold">Mes cadeaux à offrir</span>
        <span class="text-xs font-medium opacity-80">
            <?php if ($myGiftsCount > 0): ?>
                <?= $myGiftsCount ?> cadeau<?= $myGiftsCount > 1 ? 'x' : '' ?> que j'ai réservé<?= $myGiftsCount > 1 ? 's' : '' ?>, à acheter
            <?php else: ?>
                Ceux que j'ai réservés apparaîtront ici
            <?php endif; ?>
        </span>
    </span>
</a>
