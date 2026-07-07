// ============================================================
// SGFEP — Service Worker v5.0
// Stratégies: Cache-First (assets), Network-First (API/pages)
// Offline: page dédiée + Background Sync complet
// ============================================================

const CACHE_VERSION  = '5.0.0';
const STATIC_CACHE   = `sgfep-static-${CACHE_VERSION}`;
const DYNAMIC_CACHE  = `sgfep-dynamic-${CACHE_VERSION}`;
const API_CACHE      = `sgfep-api-${CACHE_VERSION}`;
const MAX_DYNAMIC    = 80;    // max entries in dynamic cache

// Static assets to pre-cache on install
const STATIC_ASSETS = [
  '/offline.html',
  '/assets/css/app.css',
  '/assets/css/design-system.css',
  '/assets/css/rtl.css',
  '/assets/js/app.js',
  '/assets/js/pwa-prompt.js',
  '/manifest.json',
];

// API routes to cache with Network-First strategy
const API_ROUTES = [
  '/api/v1/',
  '/dashboard/api/',
];

// ── Install: pre-cache static assets ────────────────────────
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(STATIC_CACHE)
      .then(cache => cache.addAll(STATIC_ASSETS.filter(url => {
        // Skip missing assets (prevents install failure)
        return fetch(url, {method: 'HEAD'}).then(r => r.ok).catch(() => false);
      })))
      .then(() => self.skipWaiting())
  );
});

// ── Activate: remove old caches ─────────────────────────────
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys =>
      Promise.all(
        keys.filter(key => key !== STATIC_CACHE && key !== DYNAMIC_CACHE && key !== API_CACHE)
            .map(key => caches.delete(key))
      )
    ).then(() => self.clients.claim())
  );
});

// ── Fetch: Smart routing strategy ───────────────────────────
self.addEventListener('fetch', event => {
  const url = new URL(event.request.url);
  const isAPI      = API_ROUTES.some(r => url.pathname.startsWith(r));
  const isNav      = event.request.mode === 'navigate';
  const isStatic   = STATIC_ASSETS.some(a => url.pathname === a);
  const isImage    = event.request.destination === 'image';
  const isSameOrigin = url.origin === self.location.origin;

  if (!isSameOrigin) return;  // Ignore cross-origin

  if (isStatic) {
    // Cache-First for static assets
    event.respondWith(cacheFirst(event.request, STATIC_CACHE));
  } else if (isAPI) {
    // Network-First for API (with cache fallback)
    event.respondWith(networkFirst(event.request, API_CACHE));
  } else if (isImage) {
    // Cache-First for images with dynamic cache
    event.respondWith(cacheFirst(event.request, DYNAMIC_CACHE, true));
  } else if (isNav) {
    // Network-First for navigation with offline fallback
    event.respondWith(
      networkFirst(event.request, DYNAMIC_CACHE, true).catch(() =>
        caches.match('/offline.html')
      )
    );
  } else {
    // Dynamic strategy for everything else
    event.respondWith(networkFirst(event.request, DYNAMIC_CACHE));
  }
});

// ── Push Notifications ───────────────────────────────────────
self.addEventListener('push', event => {
  const data = event.data ? event.data.json() : {};
  const title   = data.title   || 'منصة تسيير — إشعار جديد';
  const options = {
    body:    data.body    || '',
    icon:    data.icon    || '/assets/icons/icon-192x192.png',
    badge:   data.badge   || '/assets/icons/icon-72x72.png',
    tag:     data.tag     || 'sgfep-notification',
    data:    { url: data.url || '/dashboard' },
    dir:     'rtl',
    lang:    'ar',
    vibrate: [200, 100, 200],
    actions: [
      { action: 'open',    title: 'فتح' },
      { action: 'dismiss', title: 'تجاهل' },
    ],
  };
  event.waitUntil(self.registration.showNotification(title, options));
});

// Handle notification click
self.addEventListener('notificationclick', event => {
  event.notification.close();
  if (event.action === 'dismiss') return;
  const url = event.notification.data?.url || '/dashboard';
  event.waitUntil(
    clients.matchAll({type: 'window'}).then(windowClients => {
      const existing = windowClients.find(c => c.url === url && 'focus' in c);
      if (existing) return existing.focus();
      return clients.openWindow(url);
    })
  );
});

// ── Background Sync ──────────────────────────────────────────
self.addEventListener('sync', event => {
  if (event.tag === 'sync-messages') {
    event.waitUntil(syncOfflineMessages());
  }
  if (event.tag === 'sync-workflow') {
    event.waitUntil(syncOfflineWorkflow());
  }
});

async function syncOfflineMessages() {
  const db = await openDB();
  const tx = db.transaction('offline-messages', 'readwrite');
  const messages = await tx.objectStore('offline-messages').getAll();
  for (const msg of messages) {
    try {
      const resp = await fetch('/dashboard/messages/send', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
        body: JSON.stringify(msg),
      });
      if (resp.ok) {
        await tx.objectStore('offline-messages').delete(msg.id);
      }
    } catch {}
  }
}

async function syncOfflineWorkflow() {
  const db = await openDB();
  const tx = db.transaction('offline-workflow', 'readwrite');
  const store = tx.objectStore('offline-workflow');
  const requests = await store.getAll();

  let synced = 0;
  for (const wfReq of requests) {
    try {
      const resp = await fetch('/dashboard/workflow/store', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': wfReq._token || '',
        },
        body: JSON.stringify(wfReq),
      });
      if (resp.ok) {
        await store.delete(wfReq.id);
        synced++;
      }
    } catch (e) {
      // Will retry next time
    }
  }
  if (synced > 0) {
    // Notify all clients about successful sync
    const clients = await self.clients.matchAll({ type: 'window' });
    clients.forEach(client => client.postMessage({
      type: 'WORKFLOW_SYNCED',
      count: synced,
      message: `تم مزامنة ${synced} طلب(ات) محفوظة أثناء الانقطاع.`,
    }));
  }
}

// ── Queue an offline request for sync later ───────────────────────────────
async function queueOfflineWorkflow(data) {
  const db = await openDB();
  const tx = db.transaction('offline-workflow', 'readwrite');
  await tx.objectStore('offline-workflow').add({ ...data, _queued_at: Date.now() });
}

async function queueOfflineMessage(data) {
  const db = await openDB();
  const tx = db.transaction('offline-messages', 'readwrite');
  await tx.objectStore('offline-messages').add({ ...data, _queued_at: Date.now() });
}

// ── Helper functions ─────────────────────────────────────────
async function cacheFirst(request, cacheName, dynamic = false) {
  const cached = await caches.match(request);
  if (cached) return cached;
  try {
    const response = await fetch(request);
    if (dynamic && response.ok) {
      await addToCache(cacheName, request, response.clone(), MAX_DYNAMIC);
    }
    return response;
  } catch {
    return new Response('Not available offline', {status: 503});
  }
}

async function networkFirst(request, cacheName, dynamic = false) {
  try {
    const response = await fetch(request);
    if (response.ok && dynamic) {
      await addToCache(cacheName, request, response.clone(), MAX_DYNAMIC);
    }
    return response;
  } catch {
    const cached = await caches.match(request);
    return cached || caches.match('/offline.html');
  }
}

async function addToCache(cacheName, request, response, maxItems) {
  const cache = await caches.open(cacheName);
  const keys  = await cache.keys();
  if (keys.length >= maxItems) {
    await cache.delete(keys[0]);
  }
  await cache.put(request, response);
}

function openDB() {
  return new Promise((resolve, reject) => {
    const req = indexedDB.open('sgfep-offline', 1);
    req.onupgradeneeded = e => {
      e.target.result.createObjectStore('offline-messages', {keyPath: 'id', autoIncrement: true});
      e.target.result.createObjectStore('offline-workflow', {keyPath: 'id', autoIncrement: true});
    };
    req.onsuccess = e => resolve(e.target.result);
    req.onerror   = e => reject(e.target.error);
  });
}