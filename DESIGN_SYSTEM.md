# Design System — ERP Alaris

O **Design System do ERP Alaris** documenta os padrões visuais, tokens, componentes de interface e regras de ergonomia utilizados em todo o sistema. Seu objetivo é garantir consistência estética, suporte nativo aos temas Claro e Escuro, alto padrão de usabilidade operacional e conformidade com as regras de negócio de controle de pragas e higienização.

---

## 1. Princípios de Design & Identidade

1. **Clareza e Agilidade Operacional**: A interface prioriza legibilidade imediata de status de ordens de serviço, datas de reposição de comodato, níveis de estoque e indicadores de lucratividade.
2. **Suporte Dual-Theme Nativo (Light & Dark)**: A alternância entre tema claro e escuro é gerida via atributo `data-theme="dark"` no elemento `<html>`, consumindo variáveis CSS semânticas (`--color-surface-*`). Jamais hardcode cores fixas de fundo ou texto (como `bg-stone-900` ou `text-stone-400`).
3. **Sigilo Comercial por Design**: Vistas e documentos gerados para o cliente (ex.: Propostas Comerciais) exibem apenas o serviço e o valor final cobrado, ocultando dados confidenciais (doses em mL, custos de compra, diárias e margem).
4. **Acabamento Premium**: Uso de tipografia moderna (*Inter*), cantos arredondados consistentes (`0.5rem` a `0.75rem`), sombras suaves, microinterações em botões e cards com elevação ao passar o mouse (`:hover`).

---

## 2. Tokens de Design (Design Tokens)

### 2.1. Cores Primárias (Brand Blue & PWA — #23b4eb)

| Token | Hex | Aplicação |
| :--- | :--- | :--- |
| `--color-primary-50` | `rgba(35, 180, 235, 0.12)` | Fundo suave / destaque sutil |
| `--color-primary-100` | `#b8ebfb` | Fundo de hover claro |
| `--color-primary-300` | `#67d1f5` | Destaque e bordas ativas |
| `--color-primary-500` | `#23b4eb` | **Cor Principal da Marca** (botões primários, itens ativos, PWA e badges) |
| `--color-primary-600` | `#1499cc` | Hover no Light Theme / ênfase de botões |
| `--color-primary-700` | `#0e7ca8` | Hover aprofundado e gradientes |
| `--color-primary-900` | `#0b506b` | Textos e acentos em fundos claros |

### 2.2. Superfícies & Temas (Surface Inverted Scale)

O ERP Alaris utiliza uma escala invertida onde os tokens `--color-surface-*` alteram seu papel dinamicamente:

| Token Semântico | Papel no Light Theme (`:root`) | Papel no Dark Theme (`[data-theme="dark"]`) |
| :--- | :--- | :--- |
| `--color-surface-950` | Fundo geral da página (`#f1f5f9` - slate suave) | Fundo geral da página (`#131720` - dark navy profundo) |
| `--color-surface-900` | Superfície de cartões/cards (`#ffffff` - branco puro) | Superfície de cartões/cards (`#1e2433` - surface dark) |
| `--color-surface-800` | Bordas e divisores (`#e2e8f0` - cinza claro) | Bordas e divisores (`#3b4253` - cinza escuro sutil) |
| `--color-surface-700` | Bordas de input e botões secundários (`#cbd5e1`) | Bordas de input e botões secundários (`#444d62`) |
| `--color-surface-600` | Elementos de apoio / hover (`#94a3b8`) | Elementos de apoio / hover (`#535f78`) |
| `--color-surface-500` | Placeholders e ícones neutros (`#64748b`) | Placeholders e ícones neutros (`#677591`) |
| `--color-surface-400` | Texto secundário e legendas (`#475569`) | Texto secundário e legendas (`#8793ab`) |
| `--color-surface-300` | Labels de formulário e cabeçalhos secundários (`#334155`) | Labels de formulário e cabeçalhos secundários (`#b1b9c9`) |
| `--color-surface-200` | Texto principal de leitura (`#1e293b`) | Texto principal de leitura (`#d5d9e2`) |
| `--color-surface-100` | Títulos e ênfase máxima (`#0f172a`) | Títulos e ênfase máxima (`#eceef2`) |

> [!NOTE]
> Para garantir que textos dentro de botões primários ou badges coloridos permaneçam brancos em ambos os temas, aplique a classe utilitária `.preserve-white` ou utilize `text-white` com `.preserve-white`.

### 2.3. Cores Semânticas de Estado

- **Success (`--color-success: #10b981`)**: Visitas concluídas, lucros positivos, clientes ativos, estoque normal.
- **Warning (`--color-warning: #f59e0b`)**: Visitas em andamento, follow-ups de 3 meses, reposições de comodato vencendo em até 48h, estoque crítico.
- **Danger (`--color-danger: #ef4444`)**: Visitas atrasadas ou canceladas, prejuízo, contrato com concorrente, botões de exclusão.
- **Info (`--color-info: #3b82f6`)**: Visitas agendadas, clientes em negociação, links de navegação.
- **Purple (`#a855f7` / `#c084fc`)**: Follow-ups de prospecção comercial e propostas.
- **Cyan (`#0ea5e9` / `#38bdf8`)**: Módulo de comodato e manutenções preventivas.

### 2.4. Tipografia & Elevações

- **Fonte**: `'Inter', system-ui, sans-serif`
- **Tamanhos e Pesos**:
  - Títulos Principais: `text-xl` a `text-2xl` (`font-bold`, cor `var(--color-surface-100)`)
  - Subtítulos e Seções: `text-sm` a `text-base` (`font-semibold`)
  - Corpo / Tabelas: `text-sm` (`font-normal` ou `font-medium`)
  - Rótulos / Legendas: `text-xs` (`font-medium` ou `font-semibold`, uppercase tracking)
  - Moeda & Números: `font-mono` (`font-bold`)
- **Sombras**:
  - Cards: `var(--shadow-card)`
  - Hover / Modais: `var(--shadow-elevated)`

### 2.5. Identidade Visual, Logotipo, Favicon & PWA

- **Logotipo Principal**: `public/images/logo.png` (PNG transparente de alta resolução, 699x272 px).
- **Ícone de Marca (Badge Circular)**:
  - **Favicons Web**: `public/favicon.ico`, `public/favicon.png`, `public/favicon-32x32.png`, `public/favicon-16x16.png`.
  - **Apple Touch Icon**: `public/apple-touch-icon.png` (180x180 px).
  - **PWA Manifest & Ícones**: `public/manifest.json`, `public/manifest.webmanifest`.
    - `public/icons/icon-192.png` & `public/icons/icon-512.png` (any).
    - `public/icons/icon-maskable-192.png` & `public/icons/icon-maskable-512.png` (maskable para Android/Chrome).
- **Aplicações do Logotipo**:
  - **Sidebar Desktop**: `resources/views/layouts/app.blade.php` (altura `h-8`).
  - **Topbar Mobile**: `resources/views/layouts/app.blade.php` (altura `h-6`).
  - **Tela de Autenticação / Login**: `resources/views/auth/login.blade.php` (altura `h-14`).
- **Aplicações de Marca d'Água (Watermark)**:
  - **Documentos & Propostas**: `resources/views/visitas/proposta.blade.php` com classe `.watermark-container` centralizada e opacidade suave (`0.055`).
  - **Ordens de Serviço (OS)**: `resources/views/visitas/os.blade.php` com marca d'água ao centro e cabeçalho oficial.
  - **Relatórios Gerenciais**: `resources/views/financeiro/relatorios.blade.php` com marca d'água automática em `@media print` e cabeçalho de emissão.

---

## 3. Catálogo de Componentes Padronizados

### 3.1. Estrutura de Cartão (`.card`)

O cartão é o elemento base de agrupamento de conteúdo:

```html
<div class="card">
    <div class="card-header">
        <h3 class="text-base font-semibold text-white">Título do Cartão</h3>
        <span class="badge badge-info">Status</span>
    </div>
    <div class="card-body">
        <p class="text-sm" style="color: var(--color-surface-300);">Conteúdo principal do cartão.</p>
    </div>
    <div class="card-footer">
        <button class="btn btn-secondary btn-sm">Cancelar</button>
        <button class="btn btn-primary btn-sm">Salvar</button>
    </div>
</div>
```

### 3.2. Cartão de Estatística / KPI (`.stat-card`)

Utilizado em Dashboards, Fluxo de Caixa, Agenda e Relatórios:

```html
<div class="card stat-card">
    <div class="flex items-center justify-between">
        <div>
            <p class="stat-label">Clientes Cadastrados</p>
            <p class="stat-value">128</p>
            <p class="stat-helper">+12 este mês</p>
        </div>
        <div class="stat-icon" style="background: rgba(43,143,255,.15); color: var(--color-primary-400);">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">...</svg>
        </div>
    </div>
</div>
```

### 3.3. Botões (`.btn`)

| Classe | Finalidade | Exemplo de Uso |
| :--- | :--- | :--- |
| `.btn-primary` | Ação primária (Salvar, Criar, Confirmar) | `<button class="btn btn-primary">Salvar</button>` |
| `.btn-secondary` | Ação secundária (Cancelar, Voltar, Filtrar) | `<a class="btn btn-secondary">Voltar</a>` |
| `.btn-success` | Ação de aprovação ou conclusão direta | `<button class="btn btn-success">Concluir OS</button>` |
| `.btn-danger` | Ação destrutiva definitiva | `<button class="btn btn-danger">Excluir</button>` |
| `.btn-danger-soft` | Ação destrutiva em tabelas (fundo suave) | `<button class="btn btn-danger-soft btn-sm">Excluir</button>` |
| `.btn-outline` | Botões neutros em barras de ferramentas | `<button class="btn btn-outline btn-sm">Exportar</button>` |
| `.btn-sm` | Modificadores de tamanho compacto para tabelas | `<button class="btn btn-primary btn-sm">Editar</button>` |

### 3.4. Badges & Pílulas de Status (`.badge`)

Substitui qualquer declaração inline de cores rgba:

- `.badge-success`: Verde (Ativo, Concluída, Lucro Alto).
- `.badge-warning`: Amarelo/Âmbar (Em andamento, Vencendo, Alerta).
- `.badge-danger`: Vermelho (Atrasada, Cancelada, Inativo, Concorrente).
- `.badge-info`: Azul (Agendada, Em Negociação).
- `.badge-purple`: Roxo (Prospecção, Follow-up).
- `.badge-cyan`: Ciano (Comodato, Instalação).
- `.badge-neutral`: Cinza/Superfície neutra.

Exemplo de sintaxe:
```html
<span class="badge badge-success">
    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
    Concluída
</span>
```

### 3.5. Filtros & Barra de Pesquisa (`.filter-card`)

Padrão para cabeçalhos de listagens com formulário de filtros responsivos:

```html
<div class="card p-4">
    <form method="GET" action="..." class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 items-center">
        <div class="relative sm:col-span-2">
            <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-surface-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" name="busca" placeholder="Buscar por termo..." class="form-input pl-10">
        </div>
        <div>
            <select name="status" class="form-select" onchange="this.form.submit()">
                <option value="">Todos os status</option>
            </select>
        </div>
        <div class="flex items-center gap-2">
            <button type="submit" class="btn btn-secondary shrink-0">Filtrar</button>
            <a href="..." class="btn btn-primary shrink-0">Novo</a>
        </div>
    </form>
</div>
```

### 3.6. Tabelas de Dados (`.table-container` & `.data-table`)

Container com rolagem horizontal automática e estilo uniforme:

```html
<div class="card table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th>Identificação</th>
                <th>Status</th>
                <th class="hidden md:table-cell">Detalhe</th>
                <th class="text-right">Ações</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>...</td>
                <td><span class="badge badge-success">Ativo</span></td>
                <td class="hidden md:table-cell">...</td>
                <td class="text-right">
                    <div class="flex items-center justify-end gap-1">
                        <a href="..." class="btn btn-secondary btn-sm">Editar</a>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</div>
```

### 3.7. Formulários e Controles

- **Label Obrigatório**: `<label class="form-label" for="campo">Nome do Campo *</label>`
- **Inputs & Selects**: `<input class="form-input" />`, `<select class="form-select"></select>`
- **Texto de Ajuda**: `<span class="form-hint">Orientações auxiliares</span>`
- **Mensagem de Erro**: `<span class="form-error">Mensagem de validação</span>`

### 3.8. Estados Vazios (`.empty-state`)

Apresenta feedback visual elegante quando não há registros:

```html
<div class="empty-state">
    <div class="empty-state-icon">
        <svg class="w-8 h-8 text-surface-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">...</svg>
    </div>
    <h4 class="empty-state-title">Nenhum registro encontrado</h4>
    <p class="empty-state-desc">Tente ajustar seus filtros de busca ou cadastre um novo item.</p>
    <a href="..." class="btn btn-primary btn-sm mt-3">Novo Registro</a>
</div>
```

### 3.9. Modais e Diálogos (`.modal-backdrop` & `.modal-card`)

Janelas modais padronizadas com fundo translúcido e foco central:

```html
<div id="modal-exemplo" class="modal-backdrop hidden">
    <div class="modal-card">
        <div class="card-header">
            <h3 class="text-base font-semibold text-white">Título do Modal</h3>
            <button onclick="..." class="text-surface-400 hover:text-white">&times;</button>
        </div>
        <div class="card-body">
            <!-- Conteúdo -->
        </div>
        <div class="card-footer">
            <button class="btn btn-secondary">Cancelar</button>
            <button class="btn btn-primary">Confirmar</button>
        </div>
    </div>
</div>
```

### 3.10. Experiência Mobile Nativa (PWA & App-Like UX)

O ERP Alaris conta com componentes e comportamentos otimizados para operar como aplicativo móvel nativo em smartphones (iOS e Android):

1. **Header Mobile Compacto & Anti-Overlap**:
   - Título com `truncate min-w-0 whitespace-nowrap` em linha única, impedindo quebra de texto que sobreponha ícones do topo.
   - Ocultação inteligente do logo em larguras ultraestreitas (`hidden min-[440px]:block lg:hidden`) para priorizar o título do módulo.
   - Ocultação de botão de logout redundante no header em telas `< 640px` (`hidden sm:inline`).
2. **Barra de Navegação Inferior Nativa (`#mobile-bottom-nav`)**:
   - Fixada na base da tela (`lg:hidden`) com efeito de vidro fosco (`backdrop-filter: blur(16px)`).
   - 5 destinos táticos: Início, Agenda, Visitas & OS, Clientes/Comodatos e Mais (menu lateral).
   - Indicador ativo com dot iluminado e destaque na cor da marca `#23b4eb`.
   - Compensação de área segura de entalhes gestuais via `padding-bottom: max(env(safe-area-inset-bottom), 0.375rem);`.
   - Margem de rolagem compensada no conteúdo principal (`pb-24 lg:pb-8`).
3. **Central de Notificações Responsiva (`.notif-dropdown`)**:
   - No mobile, abre com margens seguras e simétricas fixadas na viewport (`left: 0.75rem; right: 0.75rem; width: auto; max-width: calc(100vw - 1.5rem)`), prevenindo qualquer corte fora da tela.
   - Scroll suave com `max-height: calc(100dvh - 5rem)`.
4. **Ergonomia e Resposta Tátil**:
   - Desativação de atraso de toque e destaque cinza (`-webkit-tap-highlight-color: transparent; touch-action: manipulation;`).
   - Bloqueio de seleção acidental de texto durante toques rápidos nos itens de navegação (`user-select: none`).

---

## 4. Diretrizes de Ergonomia e Acessibilidade

1. **Responsividade Mobile-First**: Em telas menores (`< 768px`), colunas secundárias de tabelas devem ser ocultadas com `hidden sm:table-cell` ou `hidden md:table-cell`, preservando legibilidade do cliente e ações.
2. **Campos Numéricos e Financeiros**: Sempre usar classe `font-mono` para alinhamento tabular de valores monetários (`R$ 1.250,00`), alinhados à direita quando aplicável.
3. **Feedback Imediato**: Operações de sucesso e validação utilizam banners animados com `.animate-fadeIn` no topo do layout principal.
4. **Links de Arquivos Clicáveis**: Na documentação e relatórios internos, referenciar arquivos com formato `file:///`.

---

## 5. Checklist de Conformidade para Novas Telas

- [ ] Usa `@extends('layouts.app')` e `@section('title', '...')`?
- [ ] Cartões utilizam `.card` e `.card p-*` sem classes fixas `bg-stone-*`?
- [ ] Textos secundários usam `style="color: var(--color-surface-400);"` ou classes de token semântico?
- [ ] Status utilizam as classes unificadas `.badge .badge-*`?
- [ ] Botões utilizam a família `.btn .btn-*`?
- [ ] Tabelas estão encapsuladas em `.card .table-container` com `.data-table`?
- [ ] Inputs e selects utilizam `.form-input` e `.form-select`?
- [ ] Estados vazios utilizam `.empty-state` com ícone e texto instrutivo?
- [ ] Se a tela exibir propostas ao cliente, a regra de sigilo de custos é rigorosamente obedecida?
