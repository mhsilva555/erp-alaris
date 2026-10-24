// ERP Alaris — Service Worker para Notificações Nativas no Sistema Operacional (Desktop & Celular)
const CACHE_NAME = 'alaris-sw-v1';

self.addEventListener('install', (event) => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

// Listener para receber pedidos de notificação vindos da página
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SHOW_NOTIFICATION') {
        const payload = event.data.payload || {};
        const title = payload.titulo || 'ERP Alaris — Agendamento';
        const options = {
            body: payload.mensagem || 'Você tem um evento próximo na agenda.',
            icon: '/icons/icon-192.png',
            badge: '/favicon-32x32.png',
            tag: 'alaris-event-' + (payload.id || Date.now()),
            renotify: true,
            vibrate: [200, 100, 200, 100, 200], // Vibração no celular
            requireInteraction: false,
            data: {
                url: payload.url || '/agendamentos',
                id: payload.id
            },
            actions: [
                { action: 'open', title: 'Visualizar' },
                { action: 'close', title: 'Fechar' }
            ]
        };

        event.waitUntil(
            self.registration.showNotification(title, options)
        );
    }
});

// Clique na notificação nativa do SO
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    if (event.action === 'close') {
        return;
    }

    const targetUrl = (event.notification.data && event.notification.data.url) ? event.notification.data.url : '/agendamentos';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
            for (let client of windowClients) {
                if (client.url.includes(self.location.origin) && 'focus' in client) {
                    client.navigate(targetUrl);
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});
