// ── Theme toggle ──
function setTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('theme', theme);
    const sunIcon = document.getElementById('theme-icon-sun');
    const moonIcon = document.getElementById('theme-icon-moon');
    if (sunIcon && moonIcon) {
        if (theme === 'dark') {
            sunIcon.classList.remove('hidden');
            moonIcon.classList.add('hidden');
        } else {
            sunIcon.classList.add('hidden');
            moonIcon.classList.remove('hidden');
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const savedTheme = localStorage.getItem('theme') || 'light';
    setTheme(savedTheme);

    const toggle = document.getElementById('theme-toggle');
    if (toggle) {
        toggle.addEventListener('click', () => {
            const current = document.documentElement.getAttribute('data-theme') || 'light';
            setTheme(current === 'dark' ? 'light' : 'dark');
        });
    }
});

// ── Service Worker & Notificações Nativas no Sistema Operacional (PC & Celular) ──
let swRegistration = null;

function registrarServiceWorker() {
    if (!('serviceWorker' in navigator)) return Promise.resolve(null);
    return navigator.serviceWorker.register('/sw.js', { scope: '/' })
        .then(reg => {
            swRegistration = reg;
            reg.update().catch(() => { });
            atualizarInterfaceNotificacoesSO();
            inscreverWebPush();
            return reg;
        })
        .catch(err => {
            console.warn('Service Worker não registrado:', err);
            return null;
        });
}

// Inicia registro imediatamente sem depender do window.load
if ('serviceWorker' in navigator) {
    registrarServiceWorker();

    // Push recebido pelo Service Worker enquanto o app está aberto
    navigator.serviceWorker.addEventListener('message', (event) => {
        if (!event.data || event.data.type !== 'PUSH_RECEIVED') return;
        const notif = event.data.payload || {};

        if (document.visibilityState === 'visible') {
            tocarSomNotificacao();
            exibirToastNotificacao({
                titulo: notif.titulo || 'ERP Alaris — Agendamento',
                mensagem: notif.mensagem || '',
                url: notif.url || '/agendamentos'
            });
        }

        if (typeof pollNotificacoes === 'function') {
            pollNotificacoes();
        }

        if (notif.tipo_evento === 'visita' && estaNaPaginaVisitas()) {
            recarregarPaginaVisitas();
        }
    });
}

async function obterServiceWorkerRegistration() {
    if (!('serviceWorker' in navigator)) return null;
    if (swRegistration && swRegistration.active) return swRegistration;
    try {
        const reg = await navigator.serviceWorker.ready;
        swRegistration = reg;
        return reg;
    } catch (e) {
        return null;
    }
}

// ── Web Push (VAPID): entrega com o PWA fechado, em segundo plano ou celular bloqueado ──
function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const raw = window.atob(base64);
    const output = new Uint8Array(raw.length);
    for (let i = 0; i < raw.length; i++) {
        output[i] = raw.charCodeAt(i);
    }
    return output;
}

function chavesIguais(bufferA, chaveB) {
    if (!bufferA) return false;
    const a = new Uint8Array(bufferA);
    if (a.length !== chaveB.length) return false;
    for (let i = 0; i < a.length; i++) {
        if (a[i] !== chaveB[i]) return false;
    }
    return true;
}

let inscrevendoPush = null;
async function inscreverWebPush() {
    if (inscrevendoPush) return inscrevendoPush;

    inscrevendoPush = (async () => {
        try {
            const vapid = document.querySelector('meta[name="vapid-public-key"]')?.getAttribute('content');
            if (!vapid || !('PushManager' in window) || !('Notification' in window)) return false;
            if (Notification.permission !== 'granted') return false;

            const reg = await obterServiceWorkerRegistration();
            if (!reg || !reg.pushManager) return false;

            const chave = urlBase64ToUint8Array(vapid);
            let sub = await reg.pushManager.getSubscription();

            // Chave VAPID mudou no servidor: refaz a inscrição
            if (sub && sub.options && sub.options.applicationServerKey && !chavesIguais(sub.options.applicationServerKey, chave)) {
                await sub.unsubscribe().catch(() => { });
                sub = null;
            }

            if (!sub) {
                sub = await reg.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: chave
                });
            }

            const encodings = (window.PushManager && PushManager.supportedContentEncodings) || ['aesgcm'];
            const contentEncoding = encodings.includes('aes128gcm') ? 'aes128gcm' : 'aesgcm';
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            const resp = await fetch('/notificacoes/push/inscrever', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ ...sub.toJSON(), contentEncoding })
            });
            return resp.ok;
        } catch (e) {
            console.warn('Falha ao inscrever Web Push:', e);
            return false;
        } finally {
            setTimeout(() => { inscrevendoPush = null; }, 0);
        }
    })();

    return inscrevendoPush;
}
window.inscreverWebPush = inscreverWebPush;

// Síntese de áudio cristalino de alerta (sem arquivos externos ou latência)
function tocarSomNotificacao() {
    try {
        const AudioCtx = window.AudioContext || window.webkitAudioContext;
        if (!AudioCtx) return;
        const ctx = new AudioCtx();
        const now = ctx.currentTime;

        // Tom 1: 587 Hz (D5)
        const osc1 = ctx.createOscillator();
        const gain1 = ctx.createGain();
        osc1.type = 'sine';
        osc1.frequency.setValueAtTime(587.33, now);
        gain1.gain.setValueAtTime(0.12, now);
        gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.15);
        osc1.connect(gain1);
        gain1.connect(ctx.destination);
        osc1.start(now);
        osc1.stop(now + 0.15);

        // Tom 2: 880 Hz (A5)
        const osc2 = ctx.createOscillator();
        const gain2 = ctx.createGain();
        osc2.type = 'sine';
        osc2.frequency.setValueAtTime(880.00, now + 0.12);
        gain2.gain.setValueAtTime(0.18, now + 0.12);
        gain2.gain.exponentialRampToValueAtTime(0.001, now + 0.35);
        osc2.connect(gain2);
        gain2.connect(ctx.destination);
        osc2.start(now + 0.12);
        osc2.stop(now + 0.35);
    } catch (e) { }
}

// Banner In-App Heads-up (Toast nativo flutuante para quando o app está aberto no celular)
function exibirToastNotificacao(notif) {
    const containerId = 'alaris-toast-container';
    let container = document.getElementById(containerId);
    if (!container) {
        container = document.createElement('div');
        container.id = containerId;
        container.className = 'fixed top-3 left-3 right-3 sm:left-auto sm:right-4 sm:w-96 z-50 flex flex-col gap-2 pointer-events-none';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = 'pointer-events-auto p-3.5 rounded-xl border shadow-xl flex items-start gap-3 animate-fadeIn cursor-pointer transition transform hover:-translate-y-0.5';
    toast.style.background = 'var(--color-surface)';
    toast.style.borderColor = 'var(--color-border-strong)';
    toast.style.boxShadow = '0 12px 32px -4px rgba(0, 0, 0, 0.4), 0 0 0 1px var(--color-border)';

    toast.innerHTML = `
        <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0" style="background: rgba(35, 180, 235, 0.15); color: #23b4eb;">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
        </div>
        <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between gap-1 mb-0.5">
                <span class="text-xs font-bold truncate" style="color: var(--color-surface-100);">${notif.titulo}</span>
                <span class="text-[10px] shrink-0" style="color: var(--color-surface-400);">Agora</span>
            </div>
            <p class="text-xs line-clamp-2" style="color: var(--color-surface-300);">${notif.mensagem}</p>
        </div>
        <button type="button" class="text-surface-400 hover:text-surface-200 p-1 -mr-1" onclick="this.parentElement.remove();">&times;</button>
    `;

    toast.addEventListener('click', (e) => {
        if (e.target.tagName !== 'BUTTON') {
            if (notif.url) window.location.href = notif.url;
            toast.remove();
        }
    });

    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 6000);
}

function atualizarInterfaceNotificacoesSO() {
    const isGranted = ('Notification' in window) && Notification.permission === 'granted';

    // Banner da Agenda Geral Unificada
    const bannerAgenda = document.getElementById('banner-notif-agenda');
    if (bannerAgenda) {
        if (isGranted) {
            bannerAgenda.classList.add('hidden');
        } else {
            bannerAgenda.classList.remove('hidden');
        }
    }

    // Boxes do Dropdown no Header
    const requestBox = document.getElementById('notif-os-request-box');
    const activeBox = document.getElementById('notif-os-active-box');
    if (requestBox && activeBox) {
        if (!('Notification' in window)) {
            requestBox.classList.add('hidden');
            activeBox.classList.add('hidden');
        } else if (isGranted) {
            requestBox.classList.add('hidden');
            activeBox.classList.remove('hidden');
        } else {
            requestBox.classList.remove('hidden');
            activeBox.classList.add('hidden');
        }
    }
}

window.solicitarNotificacoesSO = function () {
    if (!('Notification' in window)) {
        alert('Seu navegador ou dispositivo não possui suporte a notificações nativas.');
        return;
    }

    const processarPermissao = async (permission) => {
        atualizarInterfaceNotificacoesSO();
        if (permission === 'granted') {
            tocarSomNotificacao();
            await inscreverWebPush();
            window.testarNotificacaoSO();
        }
    };

    try {
        const p = Notification.requestPermission();
        if (p && typeof p.then === 'function') {
            p.then(processarPermissao);
        }
    } catch (e) {
        // Navegadores antigos (Safari < 15) usam a API baseada em callback
        Notification.requestPermission(processarPermissao);
    }
};

window.testarNotificacaoSO = async function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        || (document.querySelector('input[name="_token"]')?.value)
        || '';

    // Garante que este aparelho está inscrito antes de testar o push real
    await inscreverWebPush();

    fetch('/notificacoes/agendamentos/testar', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(r => r.json())
        .then(data => {
            if (!data.notificacao) return;
            if (data.push_entregues > 0) {
                // A notificação do SO chega pelo Web Push (evento "push" no sw.js)
                return;
            }
            dispararNotificacaoNativaSO(data.notificacao);
        })
        .catch(err => console.error('Erro ao testar notificação:', err));
};

async function dispararNotificacaoNativaSO(notif) {
    // 1. Sempre vibra e toca o áudio no aparelho
    try {
        if ('vibrate' in navigator) {
            navigator.vibrate([200, 100, 200]);
        }
    } catch (e) { }

    tocarSomNotificacao();

    // 2. Sempre renderiza o banner Heads-up na tela
    exibirToastNotificacao(notif);

    if (!('Notification' in window) || Notification.permission !== 'granted') {
        return true;
    }

    const payload = {
        id: notif.id,
        titulo: notif.titulo || 'ERP Alaris — Agendamento',
        mensagem: notif.mensagem || 'Você tem um evento próximo na agenda.',
        url: notif.url || '/agendamentos'
    };

    const options = {
        body: payload.mensagem,
        icon: '/icons/icon-192.png',
        badge: '/favicon-32x32.png',
        tag: 'alaris-event-' + (payload.id || Date.now()),
        renotify: true,
        vibrate: [200, 100, 200],
        requireInteraction: false,
        data: {
            url: payload.url,
            id: payload.id
        }
    };

    // 3. Emissão via ServiceWorker (mandatório no Android Chrome e iOS PWA)
    const reg = await obterServiceWorkerRegistration();
    if (reg && typeof reg.showNotification === 'function') {
        try {
            await reg.showNotification(payload.titulo, options);
            return true;
        } catch (swErr) {
            console.warn('showNotification via Service Worker falhou, tentando fallback:', swErr);
        }
    }

    // 4. Fallback direto via construtor Notification (Desktop)
    try {
        new Notification(payload.titulo, options);
        return true;
    } catch (notifErr) {
        console.warn('Erro ao disparar Notification direta:', notifErr);
    }

    return true;
}

// ── Helpers para atualização automática da listagem de Visitas & O.S ──
function estaNaPaginaVisitas() {
    const naListagem = !!document.getElementById('pagina-visitas-index');
    const path = window.location.pathname.replace(/\/$/, '');
    return naListagem || path === '/visitas';
}

let recarregandoVisitas = false;
function recarregarPaginaVisitas() {
    if (recarregandoVisitas) return;
    recarregandoVisitas = true;

    // Aguarda brevemente para exibição da notificação nativa no SO antes do reload
    setTimeout(() => {
        window.location.reload();
    }, 800);
}

// ── Polling de notificações de Agendamento para o Sistema Operacional ──
async function verificarNotificacoesSO() {
    try {
        const r = await fetch('/notificacoes/agendamentos/pendentes', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        const data = await r.json();

        if (data.sucesso && data.notificacoes && data.notificacoes.length > 0) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                || (document.querySelector('input[name="_token"]')?.value)
                || '';

            let temNotificacaoVisita = false;

            for (const notif of data.notificacoes) {
                await dispararNotificacaoNativaSO(notif);
                if (notif.tipo_evento === 'visita') {
                    temNotificacaoVisita = true;
                }

                // Marca como entregue no SO
                fetch(`/notificacoes/agendamentos/${notif.id}/marcar-exibida`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }).catch(() => { });
            }

            // Atualiza o badge e dropdown do header imediatamente
            if (typeof pollNotificacoes === 'function') {
                pollNotificacoes();
            }

            // Se o técnico estiver na página de Visitas e O.S e recebeu notificação de OS, recarrega a página
            if (temNotificacaoVisita && estaNaPaginaVisitas()) {
                recarregarPaginaVisitas();
            }
        }
    } catch (e) { }
}

// ── Marcar Notificações como Lidas ──
window.marcarNotificacaoLida = function (id, event) {
    if (event) {
        event.stopPropagation();
    }
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        || (document.querySelector('input[name="_token"]')?.value)
        || '';

    fetch(`/notificacoes/agendamentos/${id}/marcar-lida`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(r => r.json())
        .then(() => {
            pollNotificacoes();
        })
        .catch(() => { });
};

window.marcarTodasNotificacoesLidas = function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        || (document.querySelector('input[name="_token"]')?.value)
        || '';

    fetch('/notificacoes/agendamentos/marcar-todas-lidas', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(r => r.json())
        .then(() => {
            pollNotificacoes();
        })
        .catch(() => { });
};

let ultimosNotifIdsConhecidos = null;

// ── Polling de notificações no Header ──
function pollNotificacoes() {
    const badge = document.getElementById('notif-badge');
    const dropdown = document.getElementById('notif-dropdown-content');
    const unreadCountBadge = document.getElementById('notif-unread-count');
    const btnMarcarTodas = document.getElementById('btn-marcar-todas-lidas');

    if (!badge && !dropdown) return;

    fetch('/notificacoes', {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(r => {
            if (!r.ok) throw new Error('Não autorizado');
            return r.json();
        })
        .then(data => {
            const itensAtuais = data.itens || [];
            const idsAtuais = itensAtuais.map(i => i.id);

            // Se novas notificações de visita chegaram para o técnico
            if (ultimosNotifIdsConhecidos !== null) {
                const temNovaVisita = itensAtuais.some(item =>
                    !ultimosNotifIdsConhecidos.includes(item.id) && item.tipo_evento === 'visita'
                );

                if (temNovaVisita && estaNaPaginaVisitas()) {
                    ultimosNotifIdsConhecidos = idsAtuais;
                    recarregarPaginaVisitas();
                    return;
                }
            }
            ultimosNotifIdsConhecidos = idsAtuais;

            if (badge) {
                if (data.total > 0) {
                    badge.textContent = data.total;
                    badge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                }
            }

            if (unreadCountBadge) {
                if (data.total_nao_lidas > 0) {
                    unreadCountBadge.textContent = data.total_nao_lidas;
                    unreadCountBadge.classList.remove('hidden');
                } else {
                    unreadCountBadge.classList.add('hidden');
                }
            }

            if (btnMarcarTodas) {
                if (data.total_nao_lidas > 0) {
                    btnMarcarTodas.classList.remove('hidden');
                } else {
                    btnMarcarTodas.classList.add('hidden');
                }
            }

            if (dropdown) {
                let html = '';

                // 1. Alertas de Resumo do Sistema (Atrasadas / Estoque Crítico)
                if (data.atrasadas > 0) {
                    html += `
                        <a href="/visitas?status=agendada" class="alert alert-danger mx-2 my-1 text-xs py-2 px-3 flex items-center justify-between no-underline">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 3a9 9 0 110 18 9 9 0 010-18z"/></svg>
                                <span class="font-semibold">${data.atrasadas} visita(s) atrasada(s)</span>
                            </div>
                            <span class="text-[10px] uppercase font-bold tracking-wider">Ver &rarr;</span>
                        </a>
                    `;
                }
                if (data.estoque_baixo > 0) {
                    html += `
                        <a href="/produtos" class="alert alert-warning mx-2 my-1 text-xs py-2 px-3 flex items-center justify-between no-underline">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                <span class="font-semibold">${data.estoque_baixo} insumo(s) em nível crítico</span>
                            </div>
                            <span class="text-[10px] uppercase font-bold tracking-wider">Ver &rarr;</span>
                        </a>
                    `;
                }

                // 2. Lista de Notificações do Agendamento (NotificacaoAgendamento)
                if (data.itens && data.itens.length > 0) {
                    data.itens.forEach(item => {
                        let iconSvg = '';
                        let iconBoxStyle = 'background: rgba(35, 180, 235, 0.12); color: var(--color-primary-500);';
                        let badgeHtml = '<span class="badge badge-info text-[9px] py-0 px-1.5">Visita</span>';

                        if (item.tipo_evento === 'visita') {
                            iconBoxStyle = 'background: rgba(35, 180, 235, 0.12); color: var(--color-primary-500);';
                            badgeHtml = '<span class="badge badge-info text-[9px] py-0 px-1.5">OS Agendada</span>';
                            iconSvg = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>';
                        } else if (item.tipo_evento === 'comodato') {
                            iconBoxStyle = 'background: rgba(35, 180, 235, 0.12); color: #23b4eb;';
                            badgeHtml = '<span class="badge badge-cyan text-[9px] py-0 px-1.5">Comodato</span>';
                            iconSvg = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>';
                        } else {
                            iconBoxStyle = 'background: rgba(168, 85, 247, 0.12); color: #9333ea;';
                            badgeHtml = '<span class="badge badge-purple text-[9px] py-0 px-1.5">Follow-up</span>';
                            iconSvg = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>';
                        }

                        const unreadBar = !item.lida
                            ? '<div class="notif-unread-bar"></div>'
                            : '';

                        const unreadDot = !item.lida
                            ? '<span class="w-1.5 h-1.5 rounded-full bg-primary-500 shrink-0"></span>'
                            : '';

                        html += `
                            <a href="${item.url}" onclick="window.marcarNotificacaoLida(${item.id})"
                               class="notif-item ${!item.lida ? 'is-unread' : ''}">
                                ${unreadBar}
                                <div class="notif-icon-box" style="${iconBoxStyle}">
                                    ${iconSvg}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-1 mb-1">
                                        <div class="flex items-center gap-1.5 truncate">
                                            ${unreadDot}
                                            ${badgeHtml}
                                            <p class="text-xs font-semibold truncate" style="color: var(--color-surface-100);">${item.titulo}</p>
                                        </div>
                                        <span class="text-[10px] shrink-0 font-medium" style="color: var(--color-surface-400);">${item.tempo_relativo}</span>
                                    </div>
                                    <p class="text-[11px] leading-relaxed line-clamp-2" style="color: var(--color-surface-300);">${item.mensagem}</p>
                                </div>
                                ${!item.lida ? `
                                    <button type="button" onclick="window.marcarNotificacaoLida(${item.id}, event)"
                                            class="notif-action-btn" title="Marcar como lida">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    </button>
                                ` : ''}
                            </a>
                        `;
                    });
                }

                // 3. Estado vazio elegante com Empty State do Design System
                if (html === '') {
                    html = `
                        <div class="empty-state py-8 px-4 text-center">
                            <div class="empty-state-icon w-10 h-10 mb-2">
                                <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <p class="empty-state-title text-xs font-semibold">Tudo tranquilo por aqui!</p>
                            <p class="empty-state-desc text-[11px]">Nenhuma notificação pendente no momento.</p>
                        </div>
                    `;
                }

                dropdown.innerHTML = html;
            }
        })
        .catch(() => { });
}

const intervaloPollingNotificacoes = estaNaPaginaVisitas() ? 8000 : 15000;
setInterval(pollNotificacoes, intervaloPollingNotificacoes);
setInterval(verificarNotificacoesSO, intervaloPollingNotificacoes);

// Despertar instantâneo ao reabrir a aba, desbloquear o smartphone ou voltar ao aplicativo
document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
        pollNotificacoes();
        verificarNotificacoesSO();
    }
});

window.addEventListener('focus', () => {
    pollNotificacoes();
    verificarNotificacoesSO();
});

document.addEventListener('DOMContentLoaded', () => {
    pollNotificacoes();
    verificarNotificacoesSO();
    atualizarInterfaceNotificacoesSO();

    // Toggle dropdown de notificações
    const btn = document.getElementById('notif-btn');
    const dropdown = document.getElementById('notif-dropdown');
    if (btn && dropdown) {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            dropdown.classList.toggle('hidden');
            atualizarInterfaceNotificacoesSO();
        });
        dropdown.addEventListener('click', (e) => {
            e.stopPropagation();
        });
        document.addEventListener('click', () => dropdown.classList.add('hidden'));
    }
});

// ── Produtos dinâmicos na visita ──
window.addProdutoRow = function () {
    const container = document.getElementById('produtos-container');
    if (!container) return;

    const index = container.children.length;
    const optionsData = window.__produtosOptions || '';

    const row = document.createElement('div');
    row.className = 'produto-row p-3 rounded-lg border space-y-2 animate-fadeIn';
    row.style.borderColor = 'var(--color-border)';
    row.style.backgroundColor = 'var(--color-surface-subtle)';
    row.innerHTML = `
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
            <div class="flex-1 min-w-0">
                <select name="produtos[${index}][id]" class="form-select produto-select w-full" required onchange="window.atualizarProdutoRow && window.atualizarProdutoRow(this); window.recalcularFinanceiroVisita && window.recalcularFinanceiroVisita();">
                    <option value="">Selecione o insumo / defensivo...</option>
                    ${optionsData}
                </select>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <div class="relative flex items-center">
                    <input type="number" name="produtos[${index}][quantidade]" step="any" min="0.0001"
                           placeholder="Qtd / Dose" class="form-input produto-qtd text-left sm:text-right !pr-10" style="width: 10.5rem;" required
                           oninput="window.atualizarProdutoRow && window.atualizarProdutoRow(this); window.recalcularFinanceiroVisita && window.recalcularFinanceiroVisita();" />
                    <span class="produto-unidade-badge absolute right-2.5 text-xs font-semibold pointer-events-none text-slate-500">
                        mL
                    </span>
                </div>
                <button type="button" onclick="this.closest('.produto-row').remove(); window.recalcularFinanceiroVisita && window.recalcularFinanceiroVisita();"
                        class="btn btn-danger-soft btn-sm p-2 shrink-0" title="Remover produto">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
            </div>
        </div>
        <div class="produto-feedback text-[11px] flex items-center justify-between px-1" style="color: var(--color-slate-500);">
            <span class="produto-baixa-preview">Informe a quantidade usada na OS. Aceita frações (ex: 50 mL ou 0,5 pacote).</span>
            <span class="produto-custo-preview font-medium" style="color: var(--color-ink-900);"></span>
        </div>
    `;
    container.appendChild(row);
    if (window.recalcularFinanceiroVisita) {
        window.recalcularFinanceiroVisita();
    }
};

window.atualizarProdutoRow = function (element) {
    const row = element.closest('.produto-row');
    if (!row) return;

    const sel = row.querySelector('.produto-select');
    const qtdInput = row.querySelector('.produto-qtd');
    const badge = row.querySelector('.produto-unidade-badge');
    const baixaPreview = row.querySelector('.produto-baixa-preview');
    const custoPreview = row.querySelector('.produto-custo-preview');

    if (!sel || !sel.value) {
        if (badge) badge.textContent = '';
        if (baixaPreview) baixaPreview.textContent = 'Selecione um insumo para ver a unidade e cálculo de baixa.';
        if (custoPreview) custoPreview.textContent = '';
        return;
    }

    const prodId = sel.value;
    const meta = (window.__produtosMetadata && window.__produtosMetadata[prodId]) || {};
    const unApl = meta.unidade_aplicacao || 'mL';
    const unArm = meta.unidade_armazenamento || 'L';
    const fator = meta.fator || 1;
    const preco = meta.preco || 0;

    if (badge) badge.textContent = unApl;

    const qtd = parseFloat(qtdInput ? qtdInput.value : 0) || 0;
    if (qtd > 0) {
        const fracao = fator > 0 ? (qtd / fator) : qtd;
        const custo = fracao * preco;

        let fracaoStr = fracao.toLocaleString('pt-BR', { minimumFractionDigits: 0, maximumFractionDigits: 4 });
        let custoStr = custo.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        if (baixaPreview) {
            baixaPreview.innerHTML = `📉 <strong>Baixa no estoque:</strong> ${fracaoStr} ${unArm} (utilizado: ${qtd} ${unApl})`;
        }
        if (custoPreview) {
            custoPreview.innerHTML = `Custo proporcional: <strong class="text-emerald-400">R$ ${custoStr}</strong>`;
        }
    } else {
        if (baixaPreview) {
            baixaPreview.innerHTML = `Aplicação informada em <strong>${unApl}</strong>. Estoque em <strong>${unArm}</strong>. Aceita valores fracionados (ex: 50 ou 0,5).`;
        }
        if (custoPreview) custoPreview.textContent = '';
    }
};

// ── Preenchimento automático da diária do técnico selecionado ──
window.atualizarDiariaTecnico = function () {
    const sel = document.getElementById('tecnico_id');
    const inputMaoDeObra = document.getElementById('custo_mao_de_obra');
    const labelDiaria = document.getElementById('label-diaria-tecnico');
    if (!sel || !inputMaoDeObra) return;

    const opt = sel.options[sel.selectedIndex];
    if (opt && opt.dataset.diaria !== undefined && opt.value !== '') {
        inputMaoDeObra.value = parseFloat(opt.dataset.diaria).toFixed(2);
        if (labelDiaria) {
            // labelDiaria.textContent = 'Diária padrão do técnico: R$ ' + parseFloat(opt.dataset.diaria).toFixed(2).replace('.', ',');
        }
    } else {
        if (labelDiaria) labelDiaria.textContent = '';
    }

    if (typeof window.recalcularFinanceiroVisita === 'function') {
        window.recalcularFinanceiroVisita();
    }
};

// ── Cálculo financeiro em tempo real no formulário da visita ──
window.recalcularFinanceiroVisita = function () {
    const valorCobradoEl = document.getElementById('valor_cobrado');
    const custoMaoDeObraEl = document.getElementById('custo_mao_de_obra');
    const custoDeslocamentoEl = document.getElementById('custo_deslocamento');
    const gastosAdicionaisEl = document.getElementById('gastos_adicionais');
    const exigeNfEl = document.getElementById('exige_nota_fiscal');

    if (!valorCobradoEl) return;

    const valorCobrado = parseFloat(valorCobradoEl.value) || 0;
    const custoMaoDeObra = parseFloat(custoMaoDeObraEl ? custoMaoDeObraEl.value : 0) || 0;
    const custoDeslocamento = parseFloat(custoDeslocamentoEl ? custoDeslocamentoEl.value : 0) || 0;
    const gastosAdicionais = parseFloat(gastosAdicionaisEl ? gastosAdicionaisEl.value : 0) || 0;
    const exigeNf = exigeNfEl ? exigeNfEl.checked : false;

    // Calcular custo de insumos
    let custoProdutosTotal = 0;
    const precosObj = window.__produtosPrecos || {};
    const fatoresObj = window.__produtosFatores || {};

    const rows = document.querySelectorAll('.produto-row');
    rows.forEach(row => {
        const sel = row.querySelector('.produto-select');
        const qtdInput = row.querySelector('.produto-qtd');
        if (sel && qtdInput && sel.value) {
            const prodId = sel.value;
            const dose = parseFloat(qtdInput.value) || 0;
            const precoCusto = precosObj[prodId] || 0;
            const fator = fatoresObj[prodId] || 1;
            const custoItem = (dose / (fator > 0 ? fator : 1)) * precoCusto;
            custoProdutosTotal += custoItem;
            if (window.atualizarProdutoRow) {
                window.atualizarProdutoRow(sel);
            }
        }
    });

    const aliquotaPadrao = window.__aliquotaImpostoPadrao || 14.0;
    const aliquota = exigeNf ? aliquotaPadrao : 0.0;
    const valorImposto = (valorCobrado * aliquota) / 100;
    const custoOperacionalTotal = custoProdutosTotal + custoMaoDeObra + custoDeslocamento + gastosAdicionais;
    const lucroBruto = valorCobrado - (custoOperacionalTotal + valorImposto);
    const margem = valorCobrado > 0 ? (lucroBruto / valorCobrado) * 100 : 0;

    // Atualiza elementos de preview se existirem
    const previewCustoInsumos = document.getElementById('preview-custo-insumos');
    const previewCustoOperacional = document.getElementById('preview-custo-operacional');
    const previewImposto = document.getElementById('preview-imposto');
    const previewLucroBruto = document.getElementById('preview-lucro-bruto');
    const previewMargem = document.getElementById('preview-margem');

    if (previewCustoInsumos) previewCustoInsumos.textContent = 'R$ ' + custoProdutosTotal.toFixed(2).replace('.', ',');
    if (previewCustoOperacional) previewCustoOperacional.textContent = 'R$ ' + custoOperacionalTotal.toFixed(2).replace('.', ',');
    if (previewImposto) previewImposto.textContent = 'R$ ' + valorImposto.toFixed(2).replace('.', ',') + (exigeNf ? ` (${aliquota}%)` : ' (Isento)');
    if (previewLucroBruto) {
        previewLucroBruto.textContent = 'R$ ' + lucroBruto.toFixed(2).replace('.', ',');
        previewLucroBruto.className = lucroBruto >= 0 ? 'text-emerald-400 font-bold' : 'text-red-400 font-bold';
    }
    if (previewMargem) {
        previewMargem.textContent = margem.toFixed(1) + '%';
        previewMargem.className = margem >= 0 ? 'text-emerald-400 font-semibold' : 'text-red-400 font-semibold';
    }
};

// ── Confirmação de exclusão ──
window.confirmDelete = function (formId) {
    if (confirm('Tem certeza que deseja excluir? Esta ação não pode ser desfeita.')) {
        document.getElementById(formId).submit();
    }
};

// ── Toggle sidebar mobile ──
document.addEventListener('DOMContentLoaded', () => {
    const toggles = document.querySelectorAll('#sidebar-toggle, .mobile-sidebar-toggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');

    if (sidebar) {
        toggles.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                sidebar.classList.toggle('-translate-x-full');
                overlay?.classList.toggle('hidden');
            });
        });
        overlay?.addEventListener('click', () => {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        });
    }

    if (window.recalcularFinanceiroVisita) {
        window.recalcularFinanceiroVisita();
    }
});

// ── Fechar modais ao pressionar ESC ──
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-backdrop:not(.hidden)').forEach(modal => {
            modal.classList.add('hidden');
        });
    }
});

// ── Máscaras de Entrada (CPF/CNPJ, Telefone, CEP) ──
function formatarCpfCnpj(valor) {
    if (!valor) return '';
    const v = valor.replace(/\D/g, '').slice(0, 14);
    if (v.length <= 11) {
        // CPF: 000.000.000-00
        return v
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    }
    // CNPJ: 00.000.000/0000-00
    return v
        .replace(/^(\d{2})(\d)/, '$1.$2')
        .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
        .replace(/\.(\d{3})(\d)/, '.$1/$2')
        .replace(/(\d{4})(\d{1,2})$/, '$1-$2');
}

function formatarTelefone(valor) {
    if (!valor) return '';
    const v = valor.replace(/\D/g, '').slice(0, 11);
    if (v.length <= 10) {
        // Telefone Fixo: (00) 0000-0000
        return v
            .replace(/^(\d{2})(\d)/g, '($1) $2')
            .replace(/(\d{4})(\d{1,4})$/, '$1-$2');
    }
    // Celular: (00) 00000-0000
    return v
        .replace(/^(\d{2})(\d)/g, '($1) $2')
        .replace(/(\d{5})(\d{1,4})$/, '$1-$2');
}

function formatarCep(valor) {
    if (!valor) return '';
    const v = valor.replace(/\D/g, '').slice(0, 8);
    return v.replace(/(\d{5})(\d{1,3})$/, '$1-$2');
}

window.initMascaras = function () {
    // 1. CPF / CNPJ
    const docs = document.querySelectorAll('input[data-mask="cpf-cnpj"], input[name="documento"], #documento');
    docs.forEach(input => {
        if (input.value) {
            input.value = formatarCpfCnpj(input.value);
        }
        input.addEventListener('input', () => {
            input.value = formatarCpfCnpj(input.value);
        });
    });

    // 2. Telefone / Celular
    const tels = document.querySelectorAll('input[data-mask="telefone"], input[name="telefone"], #telefone');
    tels.forEach(input => {
        if (input.value) {
            input.value = formatarTelefone(input.value);
        }
        input.addEventListener('input', () => {
            input.value = formatarTelefone(input.value);
        });
    });

    // 3. CEP
    const ceps = document.querySelectorAll('input[data-mask="cep"], input[name="cep"], #cep');
    ceps.forEach(input => {
        if (input.value) {
            input.value = formatarCep(input.value);
        }
        input.addEventListener('input', () => {
            input.value = formatarCep(input.value);
        });
    });
};

document.addEventListener('DOMContentLoaded', () => {
    window.initMascaras();
});


