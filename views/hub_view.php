<div class="max-w-6xl mx-auto py-16 px-4" x-data="{
    createModal: false,
    loginModal: false,
    selectedProfileId: null,
    selectedProfileName: '',
    pin: '',
    loading: false,
    unlockedProfiles: <?= htmlspecialchars(json_encode(array_values($authorizedProfileIds ?? [])), ENT_QUOTES, 'UTF-8') ?>,
    openProfile(profileId, profileName) {
        if (this.unlockedProfiles.includes(profileId)) {
            window.location.href = 'universe.php?id=' + profileId;
            return;
        }
        this.selectedProfileId = profileId;
        this.selectedProfileName = profileName;
        this.pin = '';
        this.loginModal = true;
    },
    appendDigit(digit) {
        if (this.pin.length >= 4 || this.loading) return;
        this.pin += digit;
        if (this.pin.length === 4) {
            this.loginWithPin();
        }
    },
    removeDigit() {
        if (this.loading) return;
        this.pin = this.pin.slice(0, -1);
    },
    clearPin() {
        if (this.loading) return;
        this.pin = '';
    },
    async loginWithPin() {
        if (!/^\d{4}$/.test(this.pin)) return alert('Entre un PIN à 4 chiffres.');
        this.loading = true;
        const res = await fetch('api/login_profile.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-Token': window.WISHI_CSRF},
            body: JSON.stringify({ profile_id: this.selectedProfileId, pin: this.pin })
        });
        const data = await res.json();
        if (data.success) {
            window.location.href = data.redirect;
            return;
        }
        this.loading = false;
        alert(data.error || 'Connexion impossible');
    }
}">

    <header class="text-center mb-16">
        <h1 class="text-6xl font-black text-indigo-600 tracking-tight mb-2">Wishi.</h1>
        <p class="text-slate-400 font-bold uppercase tracking-widest text-xs">Le Hub Familial</p>
        <?php if (!$hasAdminAccess && !empty($profiles)): ?>
            <p class="text-slate-500 mt-4 max-w-xl mx-auto text-sm leading-relaxed">Choisis ton profil puis entre ton PIN à 4 chiffres. Le PIN par défaut des profils existants est <span class="font-black text-slate-900">0000</span>.</p>
        <?php endif; ?>
    </header>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">

        <?php foreach($profiles as $p): ?>
            <?php $c = $p['color'] ?: 'indigo'; ?>

            <button @click="openProfile(<?= (int)$p['id'] ?>, <?= htmlspecialchars(json_encode($p['name']), ENT_QUOTES, 'UTF-8') ?>)" class="group text-left">
                <div class="profile-card bg-white rounded-3xl p-10 text-center shadow-sm border border-slate-100 hover:border-<?= $c ?>-400 hover:shadow-md transition-all duration-300 h-full relative">
                    <div class="text-7xl mb-6 transform group-hover:scale-110 transition-transform duration-300">
                        <?= htmlspecialchars($p['emoji'] ?: '👤') ?>
                    </div>
                    <h2 class="text-3xl font-bold text-slate-800 tracking-tight">
                        <?= htmlspecialchars($p['name']) ?>
                    </h2>
                    <div class="mt-6 inline-flex items-center gap-2 px-5 py-2 bg-<?= $c ?>-50 text-<?= $c ?>-600 rounded-full font-bold text-xs uppercase tracking-wider opacity-0 group-hover:opacity-100 transition-all">
                        <span x-text="unlockedProfiles.includes(<?= (int)$p['id'] ?>) ? 'Ouvrir' : 'PIN'"></span>
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="3" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </div>
            </button>
        <?php endforeach; ?>

        <?php if ($hasAdminAccess || empty($profiles)): ?>
        <button @click="createModal = true" class="group border-2 border-dashed border-slate-200 rounded-3xl p-10 flex flex-col items-center justify-center transition-all hover:border-indigo-300 hover:bg-white min-h-[300px]">
            <div class="w-16 h-16 bg-slate-50 text-slate-300 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-indigo-50 group-hover:text-indigo-600 transition-all duration-300">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4" stroke-width="3" stroke-linecap="round"/></svg>
            </div>
            <span class="font-bold text-slate-400 group-hover:text-indigo-600 uppercase text-xs tracking-widest">Nouveau Membre</span>
        </button>
        <?php endif; ?>
    </div>

    <div x-show="loginModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm" x-transition.opacity>
        <div class="bg-white rounded-3xl p-10 max-w-sm w-full shadow-2xl relative border border-slate-100" @click.away="loginModal = false">
            <h2 class="text-2xl font-bold text-slate-900 mb-2">Entrer dans l'univers</h2>
            <p class="text-slate-400 text-[10px] font-bold uppercase tracking-widest mb-8" x-text="selectedProfileName"></p>
            <div class="space-y-6">
                <div>
                    <div class="flex justify-center gap-3 mb-4">
                        <template x-for="index in 4" :key="index">
                            <div class="w-4 h-4 rounded-full border-2 transition-all"
                                 :class="pin.length >= index ? 'border-indigo-500 bg-indigo-500' : 'border-slate-200 bg-white'"></div>
                        </template>
                    </div>
                    <p class="text-center text-sm text-slate-500">Entre ton PIN à 4 chiffres.</p>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <template x-for="digit in ['1','2','3','4','5','6','7','8','9']" :key="digit">
                        <button @click="appendDigit(digit)" class="h-16 rounded-2xl bg-slate-50 border border-slate-100 text-2xl font-black text-slate-800 hover:border-indigo-300 hover:text-indigo-600 transition-all" x-text="digit"></button>
                    </template>
                    <button @click="clearPin()" class="h-16 rounded-2xl bg-slate-50 border border-slate-100 text-[10px] font-black uppercase tracking-widest text-slate-400 hover:border-slate-300 hover:text-slate-700 transition-all">Effacer</button>
                    <button @click="appendDigit('0')" class="h-16 rounded-2xl bg-slate-50 border border-slate-100 text-2xl font-black text-slate-800 hover:border-indigo-300 hover:text-indigo-600 transition-all">0</button>
                    <button @click="removeDigit()" class="h-16 rounded-2xl bg-slate-50 border border-slate-100 text-[10px] font-black uppercase tracking-widest text-slate-400 hover:border-slate-300 hover:text-slate-700 transition-all">Retour</button>
                </div>

                <button
                    @click="loginWithPin()"
                    :disabled="loading || pin.length !== 4"
                    class="w-full py-4 bg-slate-900 text-white rounded-2xl font-bold text-lg hover:brightness-110 transition-all disabled:opacity-50"
                >
                    <span x-show="!loading">Entrer</span>
                    <span x-show="loading">Connexion...</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Create Modal -->
    <div x-show="createModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm" x-transition.opacity>
        <div class="bg-white rounded-3xl p-10 max-w-md w-full shadow-2xl relative border border-slate-100" @click.away="createModal = false">

            <h2 class="text-2xl font-bold text-slate-900 mb-8">Ajouter un profil</h2>

            <div class="space-y-6" x-data="{ name: '', emoji: '👤', color: 'indigo', pin: '0000', loading: false }">
                <div>
                    <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-2">Prénom</label>
                    <input type="text" x-model="name" placeholder="Ex: Maman..." class="w-full border-b-2 border-slate-100 py-2 outline-none focus:border-indigo-500 font-bold text-lg bg-transparent">
                </div>

                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-2">Emoji</label>
                        <?php $xModel = 'emoji'; include __DIR__ . '/components/emoji_picker.php'; ?>
                    </div>
                    <div>
                        <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-2">Couleur</label>
                        <select x-model="color" class="w-full border-b-2 border-slate-100 py-2 outline-none focus:border-indigo-500 font-bold bg-transparent">
                            <option value="indigo">Indigo</option>
                            <option value="rose">Rose</option>
                            <option value="sky">Bleu</option>
                            <option value="emerald">Vert</option>
                            <option value="amber">Orange</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-2">PIN Initial</label>
                    <input type="password" x-model="pin" inputmode="numeric" pattern="[0-9]*" maxlength="4" placeholder="0000" class="w-full border-b-2 border-slate-100 py-2 outline-none focus:border-indigo-500 font-bold text-xl tracking-[0.25em] bg-transparent">
                    <p class="text-[10px] text-slate-400 mt-2">4 chiffres. Par défaut : <span class="font-black text-slate-700">0000</span>.</p>
                </div>

                <div class="pt-4">
                    <button
                        @click="
                            if(!name) return alert('Prénom requis !');
                            if(!/^\d{4}$/.test(pin)) return alert('Entre un PIN initial à 4 chiffres.');
                            loading = true;
                            const res = await fetch('api/create_profile.php', {
                                method: 'POST',
                                headers: {'Content-Type': 'application/json', 'X-CSRF-Token': window.WISHI_CSRF},
                                body: JSON.stringify({ name, emoji, color, pin })
                            });
                            const data = await res.json();
                            if(data.success) window.location.href = data.admin_url || 'hub.php';
                            else { alert(data.error); loading = false; }
                        "
                        :disabled="loading"
                        class="w-full py-4 bg-indigo-600 text-white rounded-2xl font-bold text-lg shadow-lg shadow-indigo-100 hover:brightness-110 active:scale-95 transition-all"
                    >
                        <span x-show="!loading">Créer l'univers</span>
                        <span x-show="loading">Chargement...</span>
                    </button>

                    <button @click="createModal = false" class="w-full mt-4 text-slate-400 font-bold uppercase text-[10px] tracking-widest">Annuler</button>
                </div>
            </div>
        </div>
    </div>
</div>
