<div class="max-w-6xl mx-auto py-16 px-4" x-data="{ createModal: false }">

    <header class="text-center mb-16">
        <h1 class="text-6xl font-black text-indigo-600 tracking-tight mb-2">Wishi.</h1>
        <p class="text-slate-400 font-bold uppercase tracking-widest text-xs">Le Hub Familial</p>
    </header>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">

        <?php foreach($profiles as $p): ?>
            <?php $c = $p['color'] ?: 'indigo'; ?>

            <a href="universe.php?slug=<?= $p['slug'] ?>" class="group">
                <div class="profile-card bg-white rounded-3xl p-10 text-center shadow-sm border border-slate-100 hover:border-<?= $c ?>-400 hover:shadow-md transition-all duration-300 h-full">
                    <div class="text-7xl mb-6 transform group-hover:scale-110 transition-transform duration-300">
                        <?= $p['emoji'] ?: '👤' ?>
                    </div>
                    <h2 class="text-3xl font-bold text-slate-800 tracking-tight">
                        <?= htmlspecialchars($p['name']) ?>
                    </h2>
                    <div class="mt-6 inline-flex items-center gap-2 px-5 py-2 bg-<?= $c ?>-50 text-<?= $c ?>-600 rounded-full font-bold text-xs uppercase tracking-wider opacity-0 group-hover:opacity-100 transition-all">
                        Entrer
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="3" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>

        <button @click="createModal = true" class="group border-2 border-dashed border-slate-200 rounded-3xl p-10 flex flex-col items-center justify-center transition-all hover:border-indigo-300 hover:bg-white min-h-[300px]">
            <div class="w-16 h-16 bg-slate-50 text-slate-300 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-indigo-50 group-hover:text-indigo-600 transition-all duration-300">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4" stroke-width="3" stroke-linecap="round"/></svg>
            </div>
            <span class="font-bold text-slate-400 group-hover:text-indigo-600 uppercase text-xs tracking-widest">Nouveau Membre</span>
        </button>
    </div>

    <!-- Create Modal -->
    <div x-show="createModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm" x-transition.opacity>
        <div class="bg-white rounded-3xl p-10 max-w-md w-full shadow-2xl relative border border-slate-100" @click.away="createModal = false">

            <h2 class="text-2xl font-bold text-slate-900 mb-8">Ajouter un profil</h2>

            <div class="space-y-6" x-data="{ name: '', emoji: '👤', color: 'indigo', loading: false }">
                <div>
                    <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-2">Prénom</label>
                    <input type="text" x-model="name" placeholder="Ex: Maman..." class="w-full border-b-2 border-slate-100 py-2 outline-none focus:border-indigo-500 font-bold text-lg bg-transparent">
                </div>

                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-2">Emoji</label>
                        <input type="text" x-model="emoji" class="w-full border-b-2 border-slate-100 py-2 outline-none focus:border-indigo-500 text-2xl bg-transparent">
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

                <div class="pt-4">
                    <button
                        @click="
                            if(!name) return alert('Prénom requis !');
                            loading = true;
                            const res = await fetch('api/create_profile.php', {
                                method: 'POST',
                                headers: {'Content-Type': 'application/json'},
                                body: JSON.stringify({ name, emoji, color })
                            });
                            const data = await res.json();
                            if(data.success) window.location.reload();
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
