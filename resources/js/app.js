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

// ── Polling de notificações ──
function pollNotificacoes() {
    const badge = document.getElementById('notif-badge');
    const dropdown = document.getElementById('notif-dropdown-content');

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

            if (dropdown) {
                let html = '';
                if (data.atrasadas > 0) {
                    html += `<div class="flex items-center gap-2 px-4 py-2 text-red-400 text-xs"><svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 3a9 9 0 110 18 9 9 0 010-18z"/></svg><span>${data.atrasadas} visita(s) atrasada(s)</span></div>`;
                }
                if (data.proximas > 0) {
                    html += `<div class="flex items-center gap-2 px-4 py-2 text-amber-400 text-xs"><svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg><span>${data.proximas} visita(s) próxima(s)</span></div>`;
                }
                if (data.comodatos > 0) {
                    html += `<div class="flex items-center gap-2 px-4 py-2 text-blue-400 text-xs"><svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg><span>${data.comodatos} reposição(ões) de comodato</span></div>`;
                }
                if (data.followups > 0) {
                    html += `<div class="flex items-center gap-2 px-4 py-2 text-purple-400 text-xs"><svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg><span>${data.followups} follow-up(s) de clientes</span></div>`;
                }
                if (data.estoque_baixo > 0) {
                    html += `<div class="flex items-center gap-2 px-4 py-2 text-orange-400 text-xs"><svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg><span>${data.estoque_baixo} insumo(s) em nível crítico</span></div>`;
                }
                if (data.total === 0) {
                    html = '<div class="px-4 py-3 text-center text-xs" style="color: var(--color-surface-400);">Tudo em dia! Nenhuma notificação.</div>';
                }
                dropdown.innerHTML = html;
            }
        })
        .catch(() => {});
}

setInterval(pollNotificacoes, 30000);
document.addEventListener('DOMContentLoaded', pollNotificacoes);

// ── Toggle dropdown de notificações ──
document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('notif-btn');
    const dropdown = document.getElementById('notif-dropdown');
    if (btn && dropdown) {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            dropdown.classList.toggle('hidden');
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

