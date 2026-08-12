const CACHE_NAME = 'serviapp-cache-v1';
// Agrega aquí los archivos estáticos que quieres que carguen instantáneamente o funcionen offline
const ASSETS_TO_CACHE = [
  '/',
  '/manifest.json',
  '/image/eqx.jpg'
];

// Instalar el Service Worker y almacenar archivos en caché
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      console.log('Cacheando archivos esenciales...');
      return cache.addAll(ASSETS_TO_CACHE);
    })
  );
});

// Activar y limpiar cachés antiguas
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys => {
      return Promise.all(
        keys.map(key => {
          if (key !== CACHE_NAME) {
            return caches.delete(key);
          }
        })
      );
    })
  );
});

// Interceptador de peticiones (Estrategia: Intentar Red, si falla, usar Caché)
self.addEventListener('fetch', event => {
  event.respondWith(
    fetch(event.request).catch(() => {
      return caches.match(event.request);
    })
  );
});