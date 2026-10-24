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
   - Fields: `cliente_id`, `tecnico_id`, `data_hora`, `status` (`agendada`, `em_andamento`, `concluida`, `cancelada`), `tipo_visita` (`comercial`, `tecnica`, `demonstracao`, `execucao`), `tipo_servico` (`controle_pragas`, `limpeza`), `tipo_praga`, `area_tratada`, `proxima_visita`, `valor_cobrado`, `custo_produtos`, `custo_mao_de_obra`, `custo_deslocamento`, `exige_nota_fiscal`, `percentual_imposto`, `valor_imposto`, `lucro_bruto`, `observacoes`.
   - Relationships: `belongsTo(Cliente::class)`, `belongsTo(Tecnico::class)`, `belongsToMany(Produto::class, 'visita_produto')->withPivot('quantidade_aplicada', 'custo_proporcional')`.

6. **MovimentacaoFinanceira (`App\Models\MovimentacaoFinanceira`)**:
   - Cash flow ledger entries: revenues and operational expenses.
   - Fields: `visita_id` (nullable), `tipo` (`receita`, `despesa`), `categoria`, `descricao`, `valor`, `data_competencia`, `data_pagamento`, `status`.

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
    $valorCobrado = (float) ($validated['valor_cobrado'] ?? 0.00);
    $exigeNf = (bool) ($validated['exige_nota_fiscal'] ?? false);
    
    $percentualImposto = $exigeNf ? (float) config('alaris.aliquota_imposto', 14.00) : 0.00;
    $valorImposto = $valorCobrado * ($percentualImposto / 100);
    $custoTotal = $custoProdutos + $custoMaoDeObra + $custoDeslocamento;
    $lucroBruto = $valorCobrado - ($custoTotal + $valorImposto);

    $visita->update(array_merge($validated, [
        'custo_produtos'     => $custoProdutos,
        'custo_mao_de_obra'  => $custoMaoDeObra,
        'custo_deslocamento' => $custoDeslocamento,
        'percentual_imposto' => $percentualImposto,
        'valor_imposto'      => $valorImposto,
        'lucro_bruto'        => $lucroBruto,
    ]));

    // Atomic stock deduction when transitioning to concluida
    if ($statusAnterior !== Visita::STATUS_CONCLUIDA && $visita->status === Visita::STATUS_CONCLUIDA) {
        $visita->load('produtos');
        foreach ($visita->produtos as $prod) {
            $fracaoBaixa = $prod->pivot->quantidade_aplicada / $prod->fator_conversao;
            $prod->decrement('quantidade_estoque', $fracaoBaixa);
        }

        // Register accounts receivable
        MovimentacaoFinanceira::create([
            'visita_id'        => $visita->id,
            'tipo'             => 'receita',
            'categoria'        => 'faturamento_servico',
            'descricao'        => "Serviço OS #{$visita->id} - {$visita->cliente->nome}",
            'valor'            => $visita->valor_cobrado,
            'data_competencia' => now()->toDateString(),
            'status'           => 'pendente',
        ]);
    }
});
```

### 2.2. Commercial Proposal View (Absolute Secrecy Rule)
- When generating proposals (`/visitas/{id}/proposta`):
  - **Visible to Client**: Service type, customer details, area in m², warranty term, payment terms, and **Final Total Price**.
  - **Strictly Hidden**: Never include chemical amounts (mL/g), chemical wholesale costs, technician per diem, transit cost, tax breakdown, or gross margin.

### 2.3. Unified Agenda Aggregation
The agenda query consolidates:
1. **Visitas**: `Visita::with(['cliente', 'tecnico'])->whereBetween('data_hora', [$start, $end])`
2. **Comodato Refills**: `EquipamentoComodato::with(['cliente', 'produto'])->whereBetween('data_proxima_reposicao', [$start, $end])`
3. **Prospecting Follow-ups**: `Cliente::whereNotNull('data_followup')->whereBetween('data_followup', [$start, $end])`

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
