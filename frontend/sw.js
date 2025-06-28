// Service Worker pour LivraisonP2P
// Version: 1.0.0

const CACHE_NAME = 'livraisonp2p-v1.0.0';
const STATIC_CACHE = 'static-v1.0.0';
const DYNAMIC_CACHE = 'dynamic-v1.0.0';

// Ressources à mettre en cache immédiatement
const STATIC_ASSETS = [
  '/',
  '/index.html',
  '/css/app-styles.css',
  '/assets/icons/Service_De_Livraison_Avec_Concept_De_Masque___Vecteur_Gratuite-removebg-preview.png',
  '/assets/images/favicon.ico',
  'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap',
  'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css'
];

// Pages critiques
const CRITICAL_PAGES = [
  '/auth/login.html',
  '/auth/register.html',
  '/client/dashboard.html',
  '/courier/dashboard.html',
  '/admin/dashboard.html'
];

// Installation du Service Worker
self.addEventListener('install', (event) => {
  console.log('Service Worker: Installation');
  
  event.waitUntil(
    caches.open(STATIC_CACHE)
      .then((cache) => {
        console.log('Service Worker: Mise en cache des ressources statiques');
        return cache.addAll(STATIC_ASSETS);
      })
      .then(() => {
        console.log('Service Worker: Installation terminée');
        return self.skipWaiting();
      })
      .catch((error) => {
        console.error('Service Worker: Erreur lors de l\'installation', error);
      })
  );
});

// Activation du Service Worker
self.addEventListener('activate', (event) => {
  console.log('Service Worker: Activation');
  
  event.waitUntil(
    caches.keys()
      .then((cacheNames) => {
        return Promise.all(
          cacheNames.map((cacheName) => {
            if (cacheName !== STATIC_CACHE && cacheName !== DYNAMIC_CACHE) {
              console.log('Service Worker: Suppression de l\'ancien cache', cacheName);
              return caches.delete(cacheName);
            }
          })
        );
      })
      .then(() => {
        console.log('Service Worker: Activation terminée');
        return self.clients.claim();
      })
  );
});

// Interception des requêtes
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // Stratégie de cache selon le type de ressource
  if (request.method === 'GET') {
    // Ressources statiques (CSS, JS, images)
    if (isStaticAsset(url.pathname)) {
      event.respondWith(cacheFirst(request, STATIC_CACHE));
    }
    // Pages HTML
    else if (isHTMLPage(url.pathname)) {
      event.respondWith(networkFirst(request, DYNAMIC_CACHE));
    }
    // API calls
    else if (isAPIRequest(url.pathname)) {
      event.respondWith(networkOnly(request));
    }
    // Autres ressources
    else {
      event.respondWith(cacheFirst(request, DYNAMIC_CACHE));
    }
  } else {
    // Requêtes non-GET (POST, PUT, DELETE)
    event.respondWith(networkOnly(request));
  }
});

// Stratégie Cache First
async function cacheFirst(request, cacheName) {
  try {
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
      return cachedResponse;
    }
    
    const networkResponse = await fetch(request);
    if (networkResponse.ok) {
      const cache = await caches.open(cacheName);
      cache.put(request, networkResponse.clone());
    }
    return networkResponse;
  } catch (error) {
    console.error('Cache First Error:', error);
    return new Response('Ressource non disponible hors ligne', { status: 503 });
  }
}

// Stratégie Network First
async function networkFirst(request, cacheName) {
  try {
    const networkResponse = await fetch(request);
    if (networkResponse.ok) {
      const cache = await caches.open(cacheName);
      cache.put(request, networkResponse.clone());
    }
    return networkResponse;
  } catch (error) {
    console.log('Network First: Fallback vers cache', error);
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
      return cachedResponse;
    }
    
    // Page offline personnalisée
    if (request.destination === 'document') {
      return caches.match('/offline.html');
    }
    
    return new Response('Contenu non disponible hors ligne', { status: 503 });
  }
}

// Stratégie Network Only
async function networkOnly(request) {
  try {
    return await fetch(request);
  } catch (error) {
    console.error('Network Only Error:', error);
    return new Response('Service non disponible', { status: 503 });
  }
}

// Fonctions utilitaires
function isStaticAsset(pathname) {
  return /\.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$/.test(pathname);
}

function isHTMLPage(pathname) {
  return pathname.endsWith('.html') || pathname === '/' || pathname === '';
}

function isAPIRequest(pathname) {
  return pathname.startsWith('/api/') || pathname.includes('supabase.co');
}

// Gestion des notifications push
self.addEventListener('push', (event) => {
  console.log('Service Worker: Notification push reçue');
  
  const options = {
    body: event.data ? event.data.text() : 'Nouvelle notification LivraisonP2P',
    icon: '/assets/icons/Service_De_Livraison_Avec_Concept_De_Masque___Vecteur_Gratuite-removebg-preview.png',
    badge: '/assets/icons/Service_De_Livraison_Avec_Concept_De_Masque___Vecteur_Gratuite-removebg-preview.png',
    vibrate: [200, 100, 200],
    data: {
      dateOfArrival: Date.now(),
      primaryKey: 1
    },
    actions: [
      {
        action: 'explore',
        title: 'Voir',
        icon: '/assets/icons/Service_De_Livraison_Avec_Concept_De_Masque___Vecteur_Gratuite-removebg-preview.png'
      },
      {
        action: 'close',
        title: 'Fermer',
        icon: '/assets/icons/Service_De_Livraison_Avec_Concept_De_Masque___Vecteur_Gratuite-removebg-preview.png'
      }
    ]
  };

  event.waitUntil(
    self.registration.showNotification('LivraisonP2P', options)
  );
});

// Gestion des clics sur les notifications
self.addEventListener('notificationclick', (event) => {
  console.log('Service Worker: Clic sur notification');
  
  event.notification.close();

  if (event.action === 'explore') {
    event.waitUntil(
      clients.openWindow('/')
    );
  } else if (event.action === 'close') {
    // Fermer la notification
    return;
  } else {
    // Action par défaut
    event.waitUntil(
      clients.openWindow('/')
    );
  }
});

// Gestion des messages du client
self.addEventListener('message', (event) => {
  console.log('Service Worker: Message reçu', event.data);
  
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
  
  if (event.data && event.data.type === 'GET_VERSION') {
    event.ports[0].postMessage({ version: CACHE_NAME });
  }
}); 