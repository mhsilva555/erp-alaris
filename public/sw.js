// ERP Alaris — Service Worker para Notificações Nativas no Sistema Operacional (Desktop & Celular)
// v3: suporte a Web Push (evento "push") — entrega mesmo com o PWA fechado ou o celular bloqueado.
const CACHE_NAME = 'alaris-sw-v3';

self.addEventListener('install', () => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

function montarNotificacao(payload) {
    payload = payload || {};
    const title = payload.titulo || 'ERP Alaris — Agendamento';
    // Obs.: sem "actions" — o Safari/iOS rejeita showNotification quando presente
    const options = {
        body: payload.mensagem || 'Você tem um evento próximo na agenda.',
        icon: '/icons/icon-192.png',
        badge: '/favicon-32x32.png',
        tag: 'alaris-event-' + (payload.id || Date.now()),
        renotify: true,
        vibrate: [200, 100, 200, 100, 200],
        requireInteraction: false,
        timestamp: Date.now(),
        data: {
            url: payload.url || '/agendamentos',
            id: payload.id,
            tipo_evento: payload.tipo_evento || null,
        },
    };
    return { title, options };
}

async function avisarJanelasAbertas(payload) {
    const janelas = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
    janelas.forEach((janela) => janela.postMessage({ type: 'PUSH_RECEIVED', payload }));
}

// Web Push vindo do servidor (desperta o SW mesmo com o app fechado)
self.addEventListener('push', (event) => {
    let payload = {};
    if (event.data) {
        try {
            payload = event.data.json();
        } catch (e) {
            payload = { mensagem: event.data.text() };
        }
    }

    const { title, options } = montarNotificacao(payload);

    // O push SEMPRE precisa exibir uma notificação (userVisibleOnly), senão o navegador revoga a inscrição
    event.waitUntil(
        self.registration.showNotification(title, options)
            .then(() => avisarJanelasAbertas(payload))
            .catch(() => avisarJanelasAbertas(payload))
    );
});

// Listener para receber pedidos de notificação vindos da página (fallback por polling)
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SHOW_NOTIFICATION') {
        const { title, options } = montarNotificacao(event.data.payload);
        event.waitUntil(self.registration.showNotification(title, options));
    }
});

// Clique na notificação nativa do SO
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const targetUrl = (event.notification.data && event.notification.data.url) ? event.notification.data.url : '/agendamentos';

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
            for (const client of windowClients) {
                if (client.url.includes(self.location.origin) && 'focus' in client) {
                    return client.focus().then((focado) => {
                        const alvo = focado || client;
                        return ('navigate' in alvo) ? alvo.navigate(targetUrl).catch(() => null) : null;
                    });
                }
            }
            if (self.clients.openWindow) {
                return self.clients.openWindow(targetUrl);
            }
            return null;
        })
    );
});
