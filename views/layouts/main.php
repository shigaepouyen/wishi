<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= $title ?? 'Wishi' ?></title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <link rel="manifest" href="manifest.json">

    <!-- iOS support -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Wishi">
    <link rel="apple-touch-icon" href="assets/img/icon-192.png">

    <!-- Theme color -->
    <meta name="theme-color" content="<?= $theme_color ?? '#4f46e5' ?>">

    <style>
        [x-cloak] { display: none !important; }
        <?= $extra_css ?? '' ?>
    </style>
</head>
<body class="<?= $body_class ?? 'bg-slate-50' ?> min-h-screen font-sans text-slate-900 overflow-x-hidden" <?= $body_attrs ?? '' ?> x-data="{
    notifications: [],
    notify(message, type = 'info') {
        const id = Date.now();
        this.notifications.push({ id, message, type });
        setTimeout(() => {
            this.notifications = this.notifications.filter(n => n.id !== id);
        }, 5000);
    }
}" @notify.window="notify($event.detail.message, $event.detail.type)">

    <?= $content ?>

    <!-- Global Notification System -->
    <div class="fixed bottom-6 right-6 z-[200] space-y-3 max-w-sm w-full pointer-events-none">
        <template x-for="n in notifications" :key="n.id">
            <div x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 translate-y-4"
                 class="pointer-events-auto p-4 rounded-2xl shadow-xl flex items-center gap-3 border"
                 :class="{
                    'bg-white border-slate-100 text-slate-900': n.type === 'info',
                    'bg-red-50 border-red-100 text-red-800': n.type === 'error',
                    'bg-green-50 border-green-100 text-green-800': n.type === 'success'
                 }">
                <div x-show="n.type === 'info'" class="w-2 h-2 rounded-full bg-blue-500"></div>
                <div x-show="n.type === 'error'" class="w-2 h-2 rounded-full bg-red-500"></div>
                <div x-show="n.type === 'success'" class="w-2 h-2 rounded-full bg-green-500"></div>
                <p class="text-sm font-bold" x-text="n.message"></p>
                <button @click="notifications = notifications.filter(notif => notif.id !== n.id)" class="ml-auto text-slate-300 hover:text-slate-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </template>
    </div>

    <script>
      window.WISHI_CSRF = <?= json_encode($csrf_token ?? '') ?>;

      if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
          navigator.serviceWorker.register('sw.js')
            .then(reg => console.log('Wishi PWA Ready !'))
            .catch(err => console.log('PWA Error', err));
        });
      }
    </script>
    <?= $extra_js ?? '' ?>
</body>
</html>
