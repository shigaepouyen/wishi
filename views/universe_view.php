<?php
$color = $profile['color'] ?: 'indigo';
?>
<div class="max-w-4xl mx-auto" x-data="{
    createModal: false,
    adminModal: false,
    newListName: '',
    loading: false,
    profileForm: {
        id: <?= $profile['id'] ?>,
        name: '<?= addslashes($profile['name']) ?>',
        emoji: '<?= addslashes($profile['emoji']) ?>',
        color: '<?= $color ?>'
    }
}">

    <div class="flex justify-between items-center mb-12">
        <a href="hub.php" class="text-<?= $color ?>-600 font-bold flex items-center gap-2 hover:-translate-x-1 transition-all uppercase text-[10px] tracking-widest">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="3" d="M15 19l-7-7 7-7"/></svg>
            Retour au Hub
        </a>

        <button @click="adminModal = true" class="p-2 bg-white border border-slate-200 text-slate-400 rounded-xl hover:text-<?= $color ?>-600 hover:border-<?= $color ?>-200 transition-all shadow-sm">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
        </button>
    </div>

    <header class="flex items-center gap-6 mb-16">
        <div class="text-7xl drop-shadow-lg select-none">
            <?= $profile['emoji'] ?: '👤' ?>
        </div>
        <div>
            <h1 class="text-4xl font-black tracking-tight text-slate-900">
                L'univers de <span class="text-<?= $color ?>-600"><?= htmlspecialchars($profile['name']) ?></span>
            </h1>
            <p class="text-slate-400 font-bold uppercase tracking-widest text-[10px] mt-1">
                Gère tes envies et tes cadeaux
            </p>
        </div>
    </header>

    <div class="grid gap-4">
        <?php if (empty($lists)): ?>
            <div class="bg-white border-2 border-dashed border-slate-200 rounded-3xl p-12 text-center">
                <p class="text-slate-400 font-bold uppercase text-[10px] tracking-widest">Aucune liste pour le moment</p>
            </div>
        <?php else: ?>
            <?php foreach($lists as $l): ?>
            <a href="list.php?slug=<?= $l['slug_admin'] ?>" class="group bg-white p-6 rounded-2xl flex justify-between items-center shadow-sm border border-slate-100 hover:border-<?= $color ?>-200 hover:shadow-md transition-all">
                <div class="flex items-center gap-5">
                    <div class="w-12 h-12 bg-<?= $color ?>-50 text-<?= $color ?>-500 rounded-xl flex items-center justify-center text-2xl">
                        🎁
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-800 text-xl tracking-tight"><?= htmlspecialchars($l['name']) ?></h3>
                        <p class="text-slate-400 text-[10px] font-bold uppercase tracking-widest">
                            <?= $l['count'] ?> souhait<?= $l['count'] > 1 ? 's' : '' ?>
                        </p>
                    </div>
                </div>
                <div class="text-slate-300 group-hover:text-<?= $color ?>-500 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="3" d="M9 5l7 7-7 7"/></svg>
                </div>
            </a>
            <?php endforeach; ?>
        <?php endif; ?>

        <button @click="createModal = true" class="w-full mt-4 p-8 border-2 border-dashed border-slate-200 rounded-2xl text-slate-400 font-bold uppercase text-[10px] tracking-widest hover:bg-white hover:border-<?= $color ?>-300 hover:text-<?= $color ?>-500 transition-all flex items-center justify-center gap-3">
            <span class="text-xl">+</span>
            Créer une nouvelle liste
        </button>
    </div>

    <!-- Admin Modal -->
    <div x-show="adminModal" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm" x-transition>
        <div class="bg-white rounded-3xl p-10 max-w-md w-full shadow-2xl relative border border-slate-100" @click.away="adminModal = false">
            <h2 class="text-2xl font-bold text-slate-900 mb-8">Réglages Profil</h2>

            <div class="space-y-6">
                <div>
                    <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-2">Prénom</label>
                    <input type="text" x-model="profileForm.name" class="w-full border-b-2 border-slate-100 py-2 outline-none focus:border-<?= $color ?>-500 font-bold text-xl bg-transparent transition-all">
                </div>

                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-2">Emoji</label>
                        <?php $xModel = 'profileForm.emoji'; include __DIR__ . '/components/emoji_picker.php'; ?>
                    </div>
                    <div>
                        <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-2">Couleur</label>
                        <select x-model="profileForm.color" class="w-full border-b-2 border-slate-100 py-2 outline-none focus:border-<?= $color ?>-500 font-bold bg-transparent">
                            <option value="indigo">Indigo</option>
                            <option value="rose">Rose</option>
                            <option value="sky">Bleu</option>
                            <option value="emerald">Vert</option>
                            <option value="amber">Orange</option>
                        </select>
                    </div>
                </div>

                <div class="pt-6 space-y-3">
                    <button @click="
                        loading = true;
                        const res = await fetch('api/update_profile.php', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify(profileForm)
                        });
                        const data = await res.json();
                        if(data.success) window.location.href = 'universe.php?slug=' + data.new_slug;
                        else { alert(data.error); loading = false; }
                    " class="w-full py-4 bg-slate-900 text-white rounded-2xl font-bold text-lg hover:brightness-110 transition-all">
                        Sauvegarder
                    </button>

                    <button @click="
                        if(confirm('🚨 Supprimer définitivement cet univers et TOUTES ses listes ?')) {
                            fetch('api/delete_profile.php', {
                                method: 'POST',
                                headers: {'Content-Type': 'application/json'},
                                body: JSON.stringify({ id: profileForm.id })
                            }).then(() => window.location.href = 'hub.php');
                        }
                    " class="w-full py-2 text-red-400 font-bold uppercase text-[10px] tracking-widest hover:text-red-600 transition-colors">
                        Supprimer le profil
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Create List Modal -->
    <div x-show="createModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm" x-transition.opacity>
        <div class="bg-white rounded-3xl p-10 max-w-md w-full shadow-2xl border border-slate-100 relative" @click.away="createModal = false">
            <h2 class="text-2xl font-bold text-slate-900 mb-8">Nouvelle Liste</h2>
            <div class="space-y-6">
                <div>
                    <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-2">Nom de l'événement</label>
                    <input type="text" x-model="newListName" placeholder="Ex: Liste de Noël 🎄" class="w-full border-b-2 border-slate-100 py-2 outline-none focus:border-<?= $color ?>-500 font-bold text-xl bg-transparent transition-all">
                </div>
                <button @click="
                    if(!newListName) return alert('Donne un nom à ta liste !');
                    loading = true;
                    const res = await fetch('api/create_list.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({ name: newListName, profile_id: <?= $profile['id'] ?> })
                    });
                    if((await res.json()).success) window.location.reload();
                    else { alert('Erreur'); loading = false; }
                " class="w-full py-4 bg-<?= $color ?>-600 text-white rounded-2xl font-bold text-lg hover:brightness-110 shadow-lg shadow-<?= $color ?>-100 transition-all disabled:opacity-50">
                    Créer la liste
                </button>
            </div>
        </div>
    </div>
</div>
