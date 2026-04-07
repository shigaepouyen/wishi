<div class="min-h-screen flex items-center justify-center px-6 py-10" x-data="profileUnlock()">
    <div class="max-w-sm w-full bg-white rounded-3xl p-8 shadow-sm border border-slate-100">
        <div class="text-center mb-8">
            <div class="text-6xl mb-4"><?= htmlspecialchars($profile['emoji'] ?: '👤') ?></div>
            <h1 class="text-3xl font-black text-slate-900 tracking-tight"><?= htmlspecialchars($profile['name']) ?></h1>
            <p class="text-slate-400 mt-2 text-[10px] font-bold uppercase tracking-widest">Déverrouillage de l'univers</p>
        </div>

        <div class="mb-8">
            <div class="flex justify-center gap-3 mb-4">
                <template x-for="index in 4" :key="index">
                    <div class="w-4 h-4 rounded-full border-2 transition-all"
                         :class="pin.length >= index ? 'border-<?= $color ?>-500 bg-<?= $color ?>-500' : 'border-slate-200 bg-white'"></div>
                </template>
            </div>
            <p class="text-center text-sm text-slate-500">Entre ton PIN à 4 chiffres pour ouvrir directement cet univers.</p>
        </div>

        <div class="grid grid-cols-3 gap-3">
            <template x-for="digit in ['1','2','3','4','5','6','7','8','9']" :key="digit">
                <button @click="appendDigit(digit)" class="h-16 rounded-2xl bg-slate-50 border border-slate-100 text-2xl font-black text-slate-800 hover:border-<?= $color ?>-300 hover:text-<?= $color ?>-600 transition-all" x-text="digit"></button>
            </template>
            <button @click="clearPin()" class="h-16 rounded-2xl bg-slate-50 border border-slate-100 text-[10px] font-black uppercase tracking-widest text-slate-400 hover:border-slate-300 hover:text-slate-700 transition-all">Effacer</button>
            <button @click="appendDigit('0')" class="h-16 rounded-2xl bg-slate-50 border border-slate-100 text-2xl font-black text-slate-800 hover:border-<?= $color ?>-300 hover:text-<?= $color ?>-600 transition-all">0</button>
            <button @click="removeDigit()" class="h-16 rounded-2xl bg-slate-50 border border-slate-100 text-[10px] font-black uppercase tracking-widest text-slate-400 hover:border-slate-300 hover:text-slate-700 transition-all">Retour</button>
        </div>

        <div class="mt-6 space-y-3">
            <button @click="loginWithPin()" :disabled="loading || pin.length !== 4" class="w-full py-4 bg-<?= $color ?>-600 text-white rounded-2xl font-bold text-lg hover:brightness-110 transition-all disabled:opacity-50">
                <span x-show="!loading">Entrer</span>
                <span x-show="loading">Connexion...</span>
            </button>
            <a href="hub.php" class="block text-center text-slate-400 font-bold uppercase text-[10px] tracking-widest">Changer de profil</a>
        </div>

        <div class="mt-8 rounded-2xl bg-slate-50 border border-slate-100 p-4">
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Secours</p>
            <p class="text-xs text-slate-500 leading-relaxed">Si le PIN ne marche pas, ouvre le lien de secours du profil dans Safari ou demande une réinitialisation.</p>
        </div>
    </div>
</div>
