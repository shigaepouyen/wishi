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
<body class="<?= $body_class ?? 'bg-slate-50' ?> min-h-screen font-sans text-slate-900 overflow-x-hidden" <?= $body_attrs ?? '' ?>>

    <?= $content ?>

    <script>
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
