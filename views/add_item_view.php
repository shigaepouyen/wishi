<div class="max-w-5xl mx-auto py-12 px-4" x-data="addGiftForm()">

    <div class="flex justify-between items-center mb-10">
        <a href="list.php?slug=<?= $list['slug_admin'] ?>" class="text-<?= $color ?>-600 font-bold flex items-center gap-2 hover:-translate-x-1 transition-all uppercase text-[10px] tracking-widest">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="3" d="M15 19l-7-7 7-7"/></svg>
            Retour
        </a>
        <div class="flex items-center gap-3 bg-white px-4 py-2 rounded-xl shadow-sm border border-slate-100">
            <span class="text-lg"><?= $list['owner_emoji'] ?></span>
            <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Pour <span class="text-<?= $color ?>-600"><?= htmlspecialchars($list['owner_name']) ?></span></span>
        </div>
    </div>

    <header class="mb-12 text-center">
        <h1 class="text-5xl font-black text-slate-900 tracking-tight">Nouveau souhait ✨</h1>
    </header>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

        <!-- Colonne de Gauche : Outil de Scraping & Image -->
        <div class="lg:col-span-4 space-y-6">

            <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100">
                <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-4 text-center">Remplissage automatique 🪄</label>
                <div class="relative">
                    <input type="url" x-model="form.url" @input.debounce.500ms="scrapeUrl()" placeholder="Collez un lien..." class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-3 outline-none focus:border-<?= $color ?>-500 focus:bg-white transition-all font-medium text-sm">

                    <div x-show="loading" class="absolute right-3 top-3">
                        <svg class="animate-spin h-5 w-5 text-<?= $color ?>-500" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    </div>
                </div>
                <p class="text-[9px] text-slate-400 mt-3 text-center px-4 leading-relaxed">Collez le lien d'un article pour récupérer automatiquement ses infos.</p>
            </div>

            <div class="bg-white rounded-3xl p-4 shadow-sm border border-slate-100 overflow-hidden group">
                <div class="aspect-square bg-slate-50 rounded-2xl overflow-hidden mb-4 flex items-center justify-center text-5xl relative border border-slate-100">
                    <template x-if="form.image_url">
                        <img :src="form.image_url" class="w-full h-full object-cover">
                    </template>
                    <template x-if="!form.image_url">
                        <span class="opacity-20 text-4xl">🎁</span>
                    </template>

                    <!-- Image Selector Overlay -->
                    <template x-if="images.length > 1">
                        <div class="absolute inset-x-0 bottom-3 flex justify-center gap-2 px-3">
                            <button @click.prevent="prevImage()" class="bg-white shadow-md text-slate-900 w-8 h-8 rounded-lg flex items-center justify-center hover:bg-slate-50 transition-all active:scale-90">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="3" d="M15 19l-7-7 7-7"/></svg>
                            </button>
                            <div class="bg-white shadow-md px-3 py-1.5 rounded-lg text-[10px] font-bold text-slate-900 flex items-center">
                                <span x-text="currentImageIndex + 1"></span> / <span x-text="images.length"></span>
                            </div>
                            <button @click.prevent="nextImage()" class="bg-white shadow-md text-slate-900 w-8 h-8 rounded-lg flex items-center justify-center hover:bg-slate-50 transition-all active:scale-90">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="3" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                    </template>
                </div>
                <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-2">Lien de l'image</label>
                <input type="text" x-model="form.image_url" placeholder="http://image-url.jpg" class="w-full bg-slate-50 border border-slate-100 rounded-lg px-3 py-2 text-[10px] text-slate-500 outline-none focus:border-<?= $color ?>-400">
            </div>
        </div>

        <!-- Colonne de Droite : Formulaire Détails -->
        <div class="lg:col-span-8">
            <div class="bg-white rounded-3xl p-8 md:p-10 shadow-sm border border-slate-100">
                <div class="space-y-8">
                    <div>
                        <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-2">Quel est ce cadeau ? *</label>
                        <input type="text" x-model="form.title" placeholder="Ex: Une magnifique montre..." class="w-full border-b-2 border-slate-100 py-2 outline-none focus:border-<?= $color ?>-500 font-bold text-3xl bg-transparent transition-all placeholder:opacity-20">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-2">Prix Estimé (€)</label>
                            <div class="flex items-center gap-2">
                                <input type="number" step="0.01" x-model="form.price" placeholder="0.00" class="w-full border-b-2 border-slate-100 py-2 outline-none focus:border-<?= $color ?>-500 font-bold text-2xl bg-transparent transition-all placeholder:opacity-20">
                                <span class="text-xl font-bold text-slate-300">€</span>
                            </div>
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-2">Catégorie</label>
                            <input type="text" x-model="form.category" list="category-suggestions" placeholder="Ex: Mode, Maison..." class="w-full border-b-2 border-slate-100 py-2 outline-none focus:border-<?= $color ?>-500 font-bold text-xl bg-transparent transition-all placeholder:opacity-20">
                            <datalist id="category-suggestions">
                                <?php foreach ($existingCategories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat) ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                    </div>

                    <div>
                        <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-2">Description / Notes</label>
                        <textarea x-model="form.description" rows="4" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-4 outline-none focus:border-<?= $color ?>-500 focus:bg-white transition-all font-medium text-sm resize-none" placeholder="Ajoutez des détails, la taille, la couleur ou pourquoi vous voulez ce cadeau..."></textarea>
                    </div>

                    <div class="pt-6">
                        <button @click="submitForm()" :disabled="!form.title || submitting" class="w-full py-5 bg-<?= $color ?>-600 text-white rounded-2xl font-bold text-xl shadow-lg shadow-<?= $color ?>-100 hover:brightness-110 active:scale-95 transition-all disabled:opacity-50">
                            <span x-show="!submitting">Enregistrer le vœu</span>
                            <span x-show="submitting" class="flex items-center justify-center gap-3">
                                <svg class="animate-spin h-5 w-5 text-white" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                Enregistrement...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
