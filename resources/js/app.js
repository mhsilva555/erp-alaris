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

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then(reg => {
                swRegistration = reg;
                atualizarInterfaceNotificacoesSO();
            })
            .catch(err => {
                console.warn('Service Worker não registrado:', err);
            });
    });
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

    Notification.requestPermission().then(permission => {
        atualizarInterfaceNotificacoesSO();
        if (permission === 'granted') {
            window.testarNotificacaoSO();
        }
    });
};

window.testarNotificacaoSO = function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        || (document.querySelector('input[name="_token"]')?.value)
        || '';

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
        if (data.notificacao) {
            dispararNotificacaoNativaSO(data.notificacao);
        }
    })
    .catch(err => console.error('Erro ao testar notificação:', err));
};

function dispararNotificacaoNativaSO(notif) {
    if (!('Notification' in window) || Notification.permission !== 'granted') return;

    const payload = {
        id: notif.id,
        titulo: notif.titulo || 'ERP Alaris — Agendamento',
        mensagem: notif.mensagem || 'Você tem um evento próximo na agenda.',
        url: notif.url || '/agendamentos'
    };

    if (navigator.serviceWorker && navigator.serviceWorker.controller) {
        navigator.serviceWorker.controller.postMessage({
            type: 'SHOW_NOTIFICATION',
            payload: payload
        });
    } else if (swRegistration && swRegistration.showNotification) {
        swRegistration.showNotification(payload.titulo, {
            body: payload.mensagem,
            icon: '/icons/icon-192.png',
            badge: '/favicon-32x32.png',
            vibrate: [200, 100, 200, 100, 200],
            data: { url: payload.url, id: payload.id }
        });
    } else {
        try {
            new Notification(payload.titulo, {
                body: payload.mensagem,
                icon: '/icons/icon-192.png'
            });
        } catch (e) {
            console.warn('Erro ao disparar Notification direta:', e);
        }
    }
}

// ── Polling de notificações de Agendamento para o Sistema Operacional ──
function verificarNotificacoesSO() {
    if (!('Notification' in window) || Notification.permission !== 'granted') return;

    fetch('/notificacoes/agendamentos/pendentes', {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.sucesso && data.notificacoes && data.notificacoes.length > 0) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                || (document.querySelector('input[name="_token"]')?.value)
                || '';

            data.notificacoes.forEach(notif => {
                dispararNotificacaoNativaSO(notif);

                // Marca como entregue no SO
                fetch(`/notificacoes/agendamentos/${notif.id}/marcar-exibida`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }).catch(() => {});
            });
        }
    })
    .catch(() => {});
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
    .catch(() => {});
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
    .catch(() => {});
};

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
                        let iconBoxStyle = 'background: rgba(43, 143, 255, 0.12); color: var(--color-primary-500);';
                        let badgeHtml = '<span class="badge badge-info text-[9px] py-0 px-1.5">Visita</span>';

                        if (item.tipo_evento === 'visita') {
                            iconBoxStyle = 'background: rgba(43, 143, 255, 0.12); color: var(--color-primary-500);';
                            badgeHtml = '<span class="badge badge-info text-[9px] py-0 px-1.5">OS Agendada</span>';
                            iconSvg = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>';
                        } else if (item.tipo_evento === 'comodato') {
                            iconBoxStyle = 'background: rgba(14, 165, 233, 0.12); color: #0284c7;';
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
        .catch(() => {});
}

setInterval(pollNotificacoes, 30000);
setInterval(verificarNotificacoesSO, 30000);

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
    row.className = 'produto-row flex items-center gap-3 animate-fadeIn';
    row.innerHTML = `
        <select name="produtos[${index}][id]" class="form-select produto-select" style="flex:1;min-width:0;width:auto;" required onchange="window.recalcularFinanceiroVisita && window.recalcularFinanceiroVisita()">
            <option value="">Selecione o insumo...</option>
            ${optionsData}
        </select>
        <input type="number" name="produtos[${index}][quantidade]" step="0.01" min="0.01"
               placeholder="Dose" class="form-input produto-qtd" style="width:7rem;flex:none;" required oninput="window.recalcularFinanceiroVisita && window.recalcularFinanceiroVisita()" />
        <button type="button" onclick="this.closest('.produto-row').remove(); window.recalcularFinanceiroVisita && window.recalcularFinanceiroVisita();"
                class="text-red-400 hover:text-red-300 transition p-1 shrink-0" title="Remover produto">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        </button>
    `;
    container.appendChild(row);
    if (window.recalcularFinanceiroVisita) {
        window.recalcularFinanceiroVisita();
    }
};

// ── Cálculo financeiro em tempo real no formulário da visita ──
window.recalcularFinanceiroVisita = function () {
    const valorCobradoEl = document.getElementById('valor_cobrado');
    const custoMaoDeObraEl = document.getElementById('custo_mao_de_obra');
    const custoDeslocamentoEl = document.getElementById('custo_deslocamento');
    const exigeNfEl = document.getElementById('exige_nota_fiscal');

    if (!valorCobradoEl) return;

    const valorCobrado = parseFloat(valorCobradoEl.value) || 0;
    const custoMaoDeObra = parseFloat(custoMaoDeObraEl ? custoMaoDeObraEl.value : 0) || 0;
    const custoDeslocamento = parseFloat(custoDeslocamentoEl ? custoDeslocamentoEl.value : 0) || 0;
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
        }
    });

    const aliquotaPadrao = window.__aliquotaImpostoPadrao || 14.0;
    const aliquota = exigeNf ? aliquotaPadrao : 0.0;
    const valorImposto = (valorCobrado * aliquota) / 100;
    const custoOperacionalTotal = custoProdutosTotal + custoMaoDeObra + custoDeslocamento;
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
    const toggle = document.getElementById('sidebar-toggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');

    if (toggle && sidebar) {
        toggle.addEventListener('click', () => {
            sidebar.classList.toggle('-translate-x-full');
            overlay?.classList.toggle('hidden');
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


