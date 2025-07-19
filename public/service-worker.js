// Import Pusher Beams service worker
importScripts("https://js.pusher.com/beams/service-worker.js");

// Service Worker for PIX Manager PWA
const CACHE_NAME = 'pix-manager-v1';
const urlsToCache = [
  '/',
  '/dashboard',
  '/pix/list',
  '/pix/generate',
  '/manifest.json'
];

// Listen for push events and forward to clients
self.addEventListener('push', (event) => {
  console.log('🔔 Service worker received push event');

  if (event.data) {
    try {
      const data = event.data.json();
      console.log('📨 Push data:', data);

      // Send message to all clients about the push notification
      event.waitUntil(
        clients.matchAll({ type: 'window' }).then((clientList) => {
          clientList.forEach((client) => {
            client.postMessage({
              type: 'PUSH_NOTIFICATION',
              payload: data
            });
          });
        })
      );
    } catch (error) {
      console.error('Error processing push data:', error);
    }
  }
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();

  if (event.action === 'view' || !event.action) {
    const urlToOpen = event.notification.data.url || '/dashboard';
    
    event.waitUntil(
      clients.matchAll({ type: 'window' }).then((clientList) => {
        // Check if there's already a window/tab open with the target URL
        for (const client of clientList) {
          if (client.url === urlToOpen && 'focus' in client) {
            return client.focus();
          }
        }
        
        if (clients.openWindow) {
          return clients.openWindow(urlToOpen);
        }
      })
    );
  }
});

self.addEventListener('sync', (event) => {
  if (event.tag === 'background-sync') {
    event.waitUntil(
      console.log('Background sync triggered')
    );
  }
});
