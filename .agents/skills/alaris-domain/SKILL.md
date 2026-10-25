---
name: alaris-domain
description: >-
  Provides domain knowledge, business rules, and step-by-step procedures for the ERP Alaris
  pest control and sanitization management system. Use this skill when modifying or creating models,
  controllers, visits, comodato, stock deductions, fractional conversions, pricing, cash flow,
  unified agenda, or customer workflows.
---

# Alaris Domain Skill

This skill documents the core domain logic, data models, and business operations of **ERP Alaris** (Alaris Service) — an integrated operational and financial platform for urban pest control, vector management, and sanitization services.

---

## 1. Core Domain Models & Roles

1. **Cliente (`App\Models\Cliente`)**:
   - Fields: `nome`, `documento` (CPF/CNPJ), `email`, `telefone`, `endereco`, `cidade`, `estado`, `cep`, `origem` (lead source), `periodicidade`, `status_prospeccao`, `data_followup`, `observacoes`.
   - Relationships: `hasMany(Visita::class)`, `hasMany(EquipamentoComodato::class)`.
   - **Rule**: When `data_followup` is set (e.g. for `retornar_3_meses` or `contrato_concorrente`), an automatic reminder event is injected into the Unified Agenda.

2. **Tecnico (`App\Models\Tecnico`)**:
   - Fields: `nome`, `telefone`, `email`, `especialidade`, `valor_diaria_padrao`, `ativo`.
   - Relationships: `hasMany(Visita::class)`, `hasOne(User::class)`.
   - **Rule**: Only technicians with `ativo == true` appear in service scheduling dropdowns.

3. **EquipamentoComodato (`App\Models\EquipamentoComodato`)**:
   - Assets installed at the customer's property (bait stations/porta-iscas, light traps, cages).
   - Fields: `cliente_id`, `produto_id` (chemical bait linked to this station), `nome_equipamento`, `quantidade`, `periodicidade_dias`, `data_instalacao`, `data_ultima_reposicao`, `data_proxima_reposicao`, `observacoes`.
   - **Rule**: `data_proxima_reposicao = data_ultima_reposicao + periodicidade_dias`. When approaching, it generates maintenance events in the agenda.

4. **Produto (`App\Models\Produto`)**:
   - Fields: `nome`, `natureza` (`liquido`, `solido`, `unidade`), `unidade_armazenamento` (`L`, `Kg`, `Un`), `unidade_aplicacao` (`mL`, `g`, `Un`), `fator_conversao`, `quantidade_estoque`, `estoque_minimo`, `preco_custo_unitario`, `descricao`.
   - Method: `estoqueBaixo(): bool` -> `$this->quantidade_estoque <= $this->estoque_minimo`.
   - Method: `calcularCustoDose(float $dose): float` -> `($dose / $this->fator_conversao) * $this->preco_custo_unitario`.

5. **Visita (`App\Models\Visita`)**:
   - Fields: `cliente_id`, `comodato_id`, `tecnico_id`, `data_hora`, `status` (`agendada`, `em_andamento`, `concluida`, `cancelada`), `tipo_visita` (`comercial`, `tecnica`, `demonstracao`, `execucao`), `tipo_servico` (`controle_pragas`, `limpeza`), `tipo_praga`, `area_tratada`, `proxima_visita`, `valor_cobrado`, `custo_produtos`, `custo_mao_de_obra`, `custo_deslocamento`, `gastos_adicionais`, `motivo_gastos_adicionais`, `exige_nota_fiscal`, `percentual_imposto`, `valor_imposto`, `lucro_bruto`, `observacoes`.
   - Relationships: `belongsTo(Cliente::class)`, `belongsTo(EquipamentoComodato::class, 'comodato_id')`, `belongsTo(Tecnico::class)`, `belongsToMany(Produto::class, 'visita_produto')->withPivot('quantidade', 'custo_proporcional')`, `hasMany(MovimentacaoFinanceira::class)`.
   - Methods:
     - `statusRecebimento(): string` -> returns `'pago'`, `'pendente'`, `'isento'`, `'cancelado'`.
     - `movimentacaoReceita(): ?MovimentacaoFinanceira` -> returns revenue ledger row.
     - `movimentacaoDiaria(): ?MovimentacaoFinanceira` -> returns technician daily fee ledger row.
     - `custoOperacionalTotal(): float` -> sum of products, labor, transit and additional expenses.
     - `margemPercentual(): float` -> `($lucro_bruto / $valor_cobrado) * 100`.

6. **MovimentacaoFinanceira (`App\Models\MovimentacaoFinanceira`)**:
   - Cash flow ledger entries: revenues and operational expenses.
   - Fields: `visita_id` (nullable), `tipo` (`receita`, `despesa`), `categoria`, `descricao`, `valor`, `data_competencia`, `data_pagamento`, `status`.

7. **NotificacaoAgendamento (`App\Models\NotificacaoAgendamento`)**:
   - Notification records generated for upcoming visits, comodato refills, and prospecting follow-ups.
   - Fields: `tipo_evento` (`visita`, `comodato`, `followup`), `evento_id`, `titulo`, `mensagem`, `url`, `data_evento`, `data_notificacao`, `enviada_so`, `lida`, `user_id`.
   - Scopes: `scopePendentesSo()`, `scopeNaoLidas()`.

---

## 2. Business Workflows & Invariants

### 2.1. Fractional Stock Decrement & Cost Calculation on Completion
```php
DB::transaction(function () use ($visita, $validated, $statusAnterior) {
    $custoProdutos = 0.0;
    $sync = [];

    if (!empty($validated['produtos'])) {
        foreach ($validated['produtos'] as $item) {
            $produto = Produto::findOrFail($item['id']);
            $dose = (float) $item['quantidade'];
            $custoItem = ($dose / $produto->fator_conversao) * $produto->preco_custo_unitario;
            $custoProdutos += $custoItem;

            $sync[$produto->id] = [
                'quantidade_aplicada' => $dose,
                'custo_proporcional'  => $custoItem,
            ];
        }
        $visita->produtos()->sync($sync);
    }

    // Costing calculations
    $custoMaoDeObra = (float) ($validated['custo_mao_de_obra'] ?? 50.00);
    $custoDeslocamento = (float) ($validated['custo_deslocamento'] ?? 0.00);
    $gastosAdicionais = (float) ($validated['gastos_adicionais'] ?? 0.00);
    $valorCobrado = (float) ($validated['valor_cobrado'] ?? 0.00);
    $exigeNf = (bool) ($validated['exige_nota_fiscal'] ?? false);
    
    $percentualImposto = $exigeNf ? (float) config('alaris.aliquota_imposto', 14.00) : 0.00;
    $valorImposto = $valorCobrado * ($percentualImposto / 100);
    $custoTotal = $custoProdutos + $custoMaoDeObra + $custoDeslocamento + $gastosAdicionais;
    $lucroBruto = $valorCobrado - ($custoTotal + $valorImposto);

    $visita->update(array_merge($validated, [
        'custo_produtos'           => $custoProdutos,
        'custo_mao_de_obra'        => $custoMaoDeObra,
        'custo_deslocamento'       => $custoDeslocamento,
        'gastos_adicionais'        => $gastosAdicionais,
        'motivo_gastos_adicionais' => $validated['motivo_gastos_adicionais'] ?? null,
        'percentual_imposto'       => $percentualImposto,
        'valor_imposto'            => $valorImposto,
        'lucro_bruto'              => $lucroBruto,
    ]));

    // Atomic stock deduction when transitioning to concluida
    if ($statusAnterior !== Visita::STATUS_CONCLUIDA && $visita->status === Visita::STATUS_CONCLUIDA) {
        $visita->load('produtos');
        foreach ($visita->produtos as $prod) {
            $fracaoBaixa = $prod->pivot->quantidade / $prod->fator_conversao;
            $prod->decrement('quantidade_estoque', $fracaoBaixa);
        }
    }

    // Atomic cash flow synchronization (store, update, and finalize)
    // Synchronizes Revenue, Daily fee, and Extra expenses with data_competencia = data_hora
    $this->sincronizarMovimentacoesFinanceiras($visita);
});
```

### 2.2. Commercial Proposal View (Absolute Secrecy Rule)
- When generating proposals (`/visitas/{id}/proposta`):
  - **Visible to Client**: Service type, customer details, area in m², warranty term, payment terms, and **Final Total Price**.
  - **Strictly Hidden**: Never include chemical amounts (mL/g), chemical wholesale costs, technician per diem, transit cost, tax breakdown, or gross margin.

### 2.3. Unified Agenda Aggregation & Date Queries
Always use `whereDate()` for date boundaries to avoid string comparison truncation on SQLite/MySQL:
1. **Visitas**: `Visita::with(['cliente', 'tecnico'])->whereBetween('data_hora', [$start, $end])`
2. **Comodato Refills**: `EquipamentoComodato::with(['cliente', 'produto'])->whereDate('data_proxima_reposicao', '>=', $start)->whereDate('data_proxima_reposicao', '<=', $end)`
3. **Prospecting Follow-ups**: `Cliente::whereNotNull('data_followup')->whereDate('data_followup', '>=', $start)->whereDate('data_followup', '<=', $end)`

### 2.4. Clean Data Persistence & Masks
- Dynamic frontend masks via `data-mask="documento"`, `data-mask="telefone"`, `data-mask="cep"`.
- Backend persistence MUST store numbers only (`MascaraHelper::apenasNumeros()`).
- Presentation uses accessors (`documento_formatado`, `telefone_formatado`, `cep_formatado`).

### 2.5. Minute-by-Minute Cron & Native Web Notifications
- `php artisan cron:install` manages crontab / Windows Task Scheduler.
- `php artisan agendamentos:verificar-notificacoes` scans for imminent visits (<= 60 min), comodato refills due today, and follow-ups due today.
- Notifications are emitted to OS exclusively via **Web Notifications API** and Service Worker (`sw.js`). No server-side PowerShell/Windows Forms popups.

### 2.6. Mass Cash Flow Operations (Ações em Massa)
- Endpoint: `POST /financeiro/movimentacoes/acoes-em-massa` (`financeiro.acoes-massa`).
- Supported actions: `liquidar` (marks selected as paid), `reabrir` (marks selected as pending), `excluir` (deletes selected records atomically).
- Restricted to users with financial write permissions (`admin`).

### 2.7. Mobile-First App UX & PWA Color Standard (#23b4eb)
- The brand primary color and PWA theme color is strictly `#23b4eb` (Alaris Cyan).
- Mobile layout operates with native app ergonomics:
  - Responsive topbar with `truncate min-w-0 whitespace-nowrap` on module titles to prevent multi-line overlapping.
  - Native Mobile Bottom Navigation Bar (`#mobile-bottom-nav`) with 5 core tabs, glassmorphic backdrop blur, and safe area inset support (`env(safe-area-inset-bottom)`).
  - Main scroll area has `pb-24 lg:pb-8` offset.
  - Notification dropdown is bound to viewport edges on mobile (`left: 0.75rem; right: 0.75rem; width: auto; max-width: calc(100vw - 1.5rem)`).
- Orders of service with status `agendada` and `em_andamento` have operational precedence at the top of the visits list.

---

## 3. Step-by-Step Runbooks

### Adding a Comodato Inspection
1. Technician visits location with replacement chemical baits specified in OS.
2. Updates `data_ultima_reposicao = now()`.
3. Recalculates `data_proxima_reposicao = now()->addDays($comodato->periodicidade_dias)`.
4. System automatically adjusts the customer's agenda and logs bait stock decrement.

### Creating a Technician User
1. Ensure the `tecnicos` table record exists.
2. Under Admin / Profile / Users, register user with role `'tecnico'` and foreign key `tecnico_id`.
3. Technicians log in and receive an auto-scoped dashboard displaying only their allocated work orders.
