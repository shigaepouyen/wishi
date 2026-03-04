<?php
require_once __DIR__ . '/../vendor/autoload.php';

try {
    $db = \App\Utils\Database::getConnection();
    // On récupère les profils triés par nom
    $profiles = $db->query("SELECT * FROM profiles ORDER BY name ASC")->fetchAll();
} catch (Exception $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wishi - Le Hub Familial</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <link rel="manifest" href="manifest.json">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Wishi">
    <link rel="apple-touch-icon" href="assets/img/icon-192.png">

    <style>
        [x-cloak] { display: none !important; }
        .glass { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); }
        .profile-card { transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
    </style>
</head>
<body class="bg-slate-50 min-h-screen font-sans text-slate-900" x-data="{ createModal: false }">

    <div class="max-w-5xl mx-auto py-20 px-4">
        
        <header class="text-center mb-20">
            <h1 class="text-7xl font-black text-indigo-600 tracking-tighter mb-4 italic">Wishi.</h1>
            <p class="text-slate-400 font-bold uppercase tracking-[0.4em] text-[10px]">Espace Famille</p>
        </header>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-10 justify-items-center">
            
            <?php foreach($profiles as $p): ?>
                <?php $c = $p['color'] ?: 'indigo'; ?>
                
                <a href="universe.php?slug=<?= $p['slug'] ?>" class="group w-full max-w-sm">
                    <div class="profile-card bg-white rounded-[4rem] p-12 text-center shadow-xl border-4 border-transparent hover:border-<?= $c ?>-400 hover:-translate-y-3 hover:shadow-2xl relative overflow-hidden h-full">
                        
                        <div class="absolute -right-10 -top-10 w-40 h-40 bg-<?= $c ?>-50 rounded-full blur-3xl opacity-60 transition-all group-hover:scale-150"></div>
                        
                        <div class="relative z-10">
                            <div class="text-8xl mb-6 transform group-hover:rotate-12 transition-transform">
                                <?= $p['emoji'] ?: '👤' ?>
                            </div>
                            <h2 class="text-4xl font-black text-slate-800 tracking-tight">
                                <?= htmlspecialchars($p['name']) ?>
                            </h2>
                            <div class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-<?= $c ?>-50 text-<?= $c ?>-600 rounded-full font-black text-[10px] uppercase tracking-widest opacity-0 group-hover:opacity-100 transition-all">
                                Entrer
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="3" d="M9 5l7 7-7 7"/></svg>
                            </div>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>

            <button @click="createModal = true" class="group border-4 border-dashed border-slate-200 rounded-[4rem] p-12 flex flex-col items-center justify-center transition-all hover:border-indigo-300 hover:bg-white w-full max-w-sm min-h-[350px]">
                <div class="w-20 h-20 bg-slate-100 text-slate-300 rounded-[2rem] flex items-center justify-center mb-6 group-hover:bg-indigo-50 group-hover:text-indigo-600 transition-all duration-500">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4" stroke-width="3" stroke-linecap="round"/></svg>
                </div>
                <span class="font-black text-slate-400 group-hover:text-indigo-600 uppercase text-xs tracking-widest">Nouveau Membre</span>
            </button>
        </div>
    </div>

    <div x-show="createModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 glass" x-transition.opacity>
        <div class="bg-white rounded-[3.5rem] p-12 max-w-md w-full shadow-2xl relative border border-slate-100" @click.away="createModal = false">
            
            <h2 class="text-3xl font-black text-slate-900 mb-8 tracking-tight">Ajouter un profil</h2>
            
            <div class="space-y-8" x-data="{ name: '', emoji: '👤', color: 'indigo', loading: false }">
                <div>
                    <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-2 font-bold">Prénom</label>
                    <input type="text" x-model="name" placeholder="Ex: Maman..." class="w-full border-b-2 border-slate-100 py-3 outline-none focus:border-indigo-500 font-bold text-xl bg-transparent">
                </div>

                <div class="grid grid-cols-2 gap-8">
                    <div>
                        <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-2 font-bold">Emoji</label>
                        <input type="text" x-model="emoji" class="w-full border-b-2 border-slate-100 py-3 outline-none focus:border-indigo-500 text-3xl bg-transparent">
                    </div>
                    <div>
                        <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-2 font-bold">Couleur</label>
                        <select x-model="color" class="w-full border-b-2 border-slate-100 py-3 outline-none focus:border-indigo-500 font-bold bg-transparent">
                            <option value="indigo">Indigo</option>
                            <option value="rose">Rose</option>
                            <option value="sky">Bleu</option>
                            <option value="emerald">Vert</option>
                            <option value="amber">Orange</option>
                        </select>
                    </div>
                </div>

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
                    class="w-full py-6 bg-indigo-600 text-white rounded-[2rem] font-black text-lg shadow-xl shadow-indigo-100 hover:scale-[1.02] active:scale-95 transition-all"
                >
                    <span x-show="!loading">Créer l'univers ✨</span>
                    <span x-show="loading">Chargement...</span>
                </button>
                
                <button @click="createModal = false" class="w-full text-slate-300 font-bold uppercase text-[10px] tracking-widest">Annuler</button>
            </div>
        </div>
    </div>

    <script>
      if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
          navigator.serviceWorker.register('sw.js')
            .then(reg => console.log('Wishi PWA Ready !'))
            .catch(err => console.log('PWA Error', err));
        });
      }
    </script>
</body>
</html>