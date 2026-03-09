<div x-data="{
    open: false,
    selectEmoji(e) {
        <?= $xModel ?> = e;
        this.open = false;
    }
}" class="relative">
    <button @click="open = !open" type="button" class="w-full border-b-2 border-slate-100 py-2 outline-none focus:border-<?= $color ?? 'indigo' ?>-500 text-3xl bg-transparent text-left flex items-center justify-between transition-colors">
        <span x-text="<?= $xModel ?>"></span>
        <svg class="w-4 h-4 text-slate-300 transition-transform duration-300" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="3" d="M19 9l-7 7-7-7"/></svg>
    </button>

    <div x-show="open"
         @click.away="open = false"
         x-cloak
         x-transition
         class="absolute left-0 top-full z-[100] mt-2 w-72 bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-visible">

        <div class="max-h-60 overflow-y-auto p-3 custom-scrollbar">
            <?php foreach(\App\Utils\Emojis::getAll() as $category => $list): ?>
                <div>
                    <h4 class="text-[9px] font-black uppercase text-slate-400 tracking-widest mb-2 mt-3"><?= $category ?></h4>
                    <div class="grid grid-cols-6 gap-1">
                        <?php foreach($list as $emoji): ?>
                            <button @click="selectEmoji(<?= json_encode($emoji) ?>)"
                                    type="button"
                                    class="text-2xl hover:bg-slate-50 p-1 rounded-lg transition-colors flex items-center justify-center aspect-square">
                                <span><?= $emoji ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<style>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }
</style>
