<?php
require_once __DIR__ . '/../vendor/autoload.php';

// 1. Récupération du slug du profil (ex: universe.php?slug=zoe)
$slug = $_GET['slug'] ?? null;

if (!$slug) {
    header('Location: hub.php');
    exit;
}

$db = \App\Utils\Database::getConnection();

// 2. Récupérer le profil correspondant au slug
$stmt = $db->prepare("SELECT * FROM profiles WHERE slug = ?");
$stmt->execute([$slug]);
$profile = $stmt->fetch();

if (!$profile) {
    header('Location: hub.php');
    exit;
}

// 3. Récupérer les listes (avec le compte d'items et le slug_admin)
$stmtLists = $db->prepare("
    SELECT l.*, 
    (SELECT COUNT(*) FROM items WHERE list_id = l.id) as count 
    FROM lists l 
    WHERE l.profile_id = ?
    ORDER BY l.created_at DESC
");
$stmtLists->execute([$profile['id']]);
$lists = $stmtLists->fetchAll();

$color = $profile['color'] ?: 'indigo';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>L'univers de <?= htmlspecialchars($profile['name']) ?> - Wishi</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <link rel="manifest" href="manifest.json">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="apple-touch-icon" href="assets/img/icon-192.png">

    <style>
        [x-cloak] { display: none !important; }
        .glass { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); }
    </style>
</head>
<body class="bg-<?= $color ?>-50 min-h-screen p-4 md:p-10 font-sans text-slate-900" 
      x-data="{ 
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

    <div class="max-w-3xl mx-auto">
        
        <div class="flex justify-between items-center mb-12">
            <a href="hub.php" class="text-<?= $color ?>-600 font-black flex items-center gap-2 hover:-translate-x-2 transition-all uppercase text-xs tracking-widest">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="3" d="M15 19l-7-7 7-7"/></svg>
                Hub Familial
            </a>
            
            <button @click="adminModal = true" class="p-3 bg-white border border-<?= $color ?>-100 text-<?= $color ?>-500 rounded-2xl hover:bg-<?= $color ?>-500 hover:text-white transition-all shadow-sm group">
                <svg class="w-6 h-6 group-hover:rotate-90 transition-transform duration-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </button>
        </div>

        <header class="flex items-center gap-8 mb-16">
            <div class="text-8xl drop-shadow-2xl transform -rotate-12 select-none">
                <?= $profile['emoji'] ?: '👤' ?>
            </div>
            <div>
                <h1 class="text-5xl font-black tracking-tighter">
                    L'univers de <span class="text-<?= $color ?>-600"><?= htmlspecialchars($profile['name']) ?></span>
                </h1>
                <p class="text-slate-400 font-bold uppercase tracking-widest text-[10px] mt-2 italic">
                    Gère tes envies et tes cadeaux
                </p>
            </div>
        </header>

        <div class="grid gap-6">
            <?php if (empty($lists)): ?>
                <div class="bg-white/60 border-4 border-dashed border-slate-200 rounded-[3rem] p-16 text-center">
                    <p class="text-slate-400 font-black uppercase text-xs tracking-widest">Aucune liste pour le moment ✨</p>
                </div>
            <?php else: ?>
                <?php foreach($lists as $l): ?>
                <a href="list.php?slug=<?= $l['slug_admin'] ?>" class="group bg-white p-8 rounded-[2.5rem] flex justify-between items-center shadow-xl shadow-slate-200/50 hover:shadow-2xl hover:-translate-y-1 transition-all border-4 border-transparent hover:border-<?= $color ?>-200">
                    <div class="flex items-center gap-6">
                        <div class="w-16 h-16 bg-<?= $color ?>-50 text-<?= $color ?>-500 rounded-3xl flex items-center justify-center text-3xl group-hover:scale-110 transition-transform">
                            🎁
                        </div>
                        <div>
                            <h3 class="font-black text-slate-800 text-2xl tracking-tight"><?= htmlspecialchars($l['name']) ?></h3>
                            <p class="text-slate-400 text-xs font-bold uppercase tracking-widest">
                                <?= $l['count'] ?> souhait<?= $l['count'] > 1 ? 's' : '' ?>
                            </p>
                        </div>
                    </div>
                    <div class="text-<?= $color ?>-400 opacity-0 group-hover:opacity-100 transition-all transform group-hover:translate-x-2">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="3" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </a>
                <?php endforeach; ?>
            <?php endif; ?>

            <button @click="createModal = true" class="w-full mt-4 p-10 border-4 border-dashed border-slate-200 rounded-[3rem] text-slate-400 font-black uppercase text-xs tracking-widest hover:bg-white hover:border-<?= $color ?>-300 hover:text-<?= $color ?>-500 transition-all flex flex-col items-center gap-4">
                <div class="w-12 h-12 bg-slate-50 rounded-full flex items-center justify-center text-2xl font-light">+</div>
                Créer une nouvelle liste
            </button>
        </div>
    </div>

    <div x-show="adminModal" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4 glass" x-transition>
        <div class="bg-white rounded-[3.5rem] p-12 max-w-md w-full shadow-2xl relative border border-<?= $color ?>-100" @click.away="adminModal = false">
            <h2 class="text-3xl font-black text-slate-900 mb-8 tracking-tighter italic">Réglages Profil</h2>
            
            <div class="space-y-8">
                <div>
                    <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-2">Ton Prénom</label>
                    <input type="text" x-model="profileForm.name" class="w-full border-b-4 border-slate-50 py-3 outline-none focus:border-<?= $color ?>-500 font-black text-2xl bg-transparent transition-all">
                </div>

                <div class="grid grid-cols-2 gap-8">
                    <div>
                        <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-2">Emoji</label>
                        <input type="text" x-model="profileForm.emoji" class="w-full border-b-4 border-slate-50 py-3 outline-none focus:border-<?= $color ?>-500 text-3xl bg-transparent">
                    </div>
                    <div>
                        <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-2">Couleur thème</label>
                        <select x-model="profileForm.color" class="w-full border-b-4 border-slate-50 py-3 outline-none focus:border-<?= $color ?>-500 font-bold bg-transparent">
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
                    " class="w-full py-6 bg-slate-900 text-white rounded-[2rem] font-black text-xl shadow-xl hover:scale-[1.02] active:scale-95 transition-all">
                        Sauvegarder ✨
                    </button>
                    
                    <button @click="
                        if(confirm('🚨 Supprimer définitivement cet univers et TOUTES ses listes ?')) {
                            fetch('api/delete_profile.php', {
                                method: 'POST',
                                headers: {'Content-Type': 'application/json'},
                                body: JSON.stringify({ id: profileForm.id })
                            }).then(() => window.location.href = 'hub.php');
                        }
                    " class="w-full py-3 text-red-400 font-bold uppercase text-[10px] tracking-widest hover:text-red-600 transition-colors">
                        Supprimer le profil
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div x-show="createModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 glass" x-transition.opacity>
        <div class="bg-white rounded-[3.5rem] p-12 max-w-md w-full shadow-2xl border border-<?= $color ?>-100 relative" @click.away="createModal = false">
            <h2 class="text-3xl font-black text-slate-900 mb-8 tracking-tight text-center italic">Nouvelle Liste</h2>
            <div class="space-y-8">
                <div>
                    <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-2">Nom de l'événement</label>
                    <input type="text" x-model="newListName" placeholder="Ex: Liste de Noël 🎄" class="w-full border-b-4 border-slate-50 py-4 outline-none focus:border-<?= $color ?>-500 font-black text-2xl bg-transparent transition-all">
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
                " class="w-full py-6 bg-<?= $color ?>-600 text-white rounded-[2rem] font-black text-xl shadow-xl shadow-<?= $color ?>-100 hover:scale-[1.02] active:scale-95 transition-all disabled:opacity-50">
                    Créer la liste ✨
                </button>
            </div>
        </div>
    </div>

    <script>
        // PWA Service Worker
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js').catch(err => console.log('PWA Error', err));
            });
        }
    </script>
</body>
</html>