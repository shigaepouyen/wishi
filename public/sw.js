const CACHE_NAME = 'wishi-v1';

// Installation du service worker
self.addEventListener('install', (e) => {
  console.log('Wishi Service Worker: Installed');
});

// Activation
self.addEventListener('activate', (e) => {
  console.log('Wishi Service Worker: Activated');
});

// Gestion des requêtes (basique)
self.addEventListener('fetch', (e) => {
  // On laisse passer les requêtes normalement
  e.respondWith(fetch(e.request));
});