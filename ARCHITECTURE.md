# ARCHITECTURE.md — Arquitetura de Software e Fluxos do ERP Alaris

Este documento detalha o desenho arquitetural, relacionamentos de entidades e fluxos operacionais críticos do **ERP Alaris** (anteriormente Biosp).

---

## 1. Visão Arquitetural Geral

O ERP Alaris adota o padrão arquitetural clássico do ecossistema Laravel (MVC moderno com Blade Server-Side Rendering e enriquecimento progressivo via JavaScript Vanilla e Tailwind CSS v4).

```
                      ┌────────────────────────────────────────┐
                      │          Navegador Web / Mobile         │
                      │  (Blade SSR + Vanilla JS + Tailwind)    │
                      └──────────────────┬─────────────────────┘
                                         │  HTTP / HTTPS / JSON Polling
                                         ▼
                      ┌────────────────────────────────────────┐
                      │          Rotas & Middlewares           │
                      │   (web.php, auth, role:admin/tecnico)  │
                      └──────────────────┬─────────────────────┘
                                         │
                                         ▼
                      ┌────────────────────────────────────────┐
                      │         Controllers (HTTP Layer)       │
                      │   Visita, Cliente, Comodato, Produto,  │
                      │   Financeiro, Agendamento, Tecnico...  │
                      └───────────┬────────────────┬───────────┘
                                  │                │
                        (Reads)   │                │ (Writes com DB::transaction)
                                  ▼                ▼
                      ┌────────────────────────────────────────┐
                      │             Eloquent ORM               │
                      │  Modelos com Casts, Scopes, Relaciona- │
                      │  mentos e Políticas de Autorização     │
                      └──────────────────┬─────────────────────┘
                                         │
                                         ▼
                      ┌────────────────────────────────────────┐
                      │         Base de Dados Relacional       │
                      │             (SQLite / MySQL)           │
                      └────────────────────────────────────────┘
```

---

## 2. Diagrama Entidade-Relacionamento (ERD)

```mermaid
erDiagram
    USERS {
        bigint id PK
        string name
        string email
        string password
        string role "admin | tecnico"
        bigint tecnico_id FK "nullable"
        string remember_token
        timestamp created_at
        timestamp updated_at
    }

    CLIENTES {
        bigint id PK
        string nome
        string documento
        string email
        string telefone
        string endereco
        string cidade
        string estado
        string cep
        string origem "Trafego, Indicacao, Prospeccao..."
        string periodicidade "Mensal, Trimestral, Avulso..."
        string status_prospeccao "ativo, em_negociacao, retornar_3_meses..."
        date data_followup "Gera lembrete na agenda"
        text observacoes
        timestamp created_at
        timestamp updated_at
    }

    TECNICOS {
        bigint id PK
        string nome
        string telefone
        string email
        string especialidade
        decimal valor_diaria_padrao "50.00"
        boolean ativo
        timestamp created_at
        timestamp updated_at
    }

    PRODUTOS {
        bigint id PK
        string nome
        string natureza "liquido | solido | unidade"
        string unidade_armazenamento "L | Kg | Un"
        string unidade_aplicacao "mL | g | Un"
        decimal fator_conversao "1000.00"
        decimal quantidade_estoque
        decimal estoque_minimo
        decimal preco_custo_unitario
        text descricao
        timestamp created_at
        timestamp updated_at
    }

    EQUIPAMENTOS_COMODATO {
        bigint id PK
        bigint cliente_id FK
        bigint produto_id FK "Insumo atrelado à reposição"
        string nome_equipamento "Ex: Porta-iscas"
        int quantidade
        int periodicidade_dias "Ex: a cada 15 dias"
        date data_instalacao
        date data_ultima_reposicao
        date data_proxima_reposicao "Gera evento na agenda"
        text observacoes
        timestamp created_at
        timestamp updated_at
    }

    VISITAS {
        bigint id PK
        bigint cliente_id FK
        bigint tecnico_id FK
        datetime data_hora
        string status "agendada | em_andamento | concluida | cancelada"
        string tipo_visita "comercial | tecnica | demonstracao | execucao"
        string tipo_servico "controle_pragas | limpeza"
        string tipo_praga
        string area_tratada
        datetime proxima_visita
        decimal valor_cobrado "Preço final ao cliente"
        decimal custo_produtos "Soma das frações aplicadas"
        decimal custo_mao_de_obra "Diária / Mão de obra"
        decimal custo_deslocamento "Transporte / Combustível"
        boolean exige_nota_fiscal "Aplica acréscimo de imposto"
        decimal percentual_imposto "Alíquota configurada"
        decimal valor_imposto "Calculado sobre valor bruto"
        decimal lucro_bruto "Cobrado - Custos - Impostos"
        text observacoes
        timestamp created_at
        timestamp updated_at
    }

    VISITA_PRODUTO {
        bigint id PK
        bigint visita_id FK
        bigint produto_id FK
        decimal quantidade_aplicada "Em mL, g ou Un"
        decimal custo_proporcional "Calculado automaticamente"
    }

    MOVIMENTACOES_FINANCEIRAS {
        bigint id PK
        bigint visita_id FK "nullable"
        string tipo "receita | despesa"
        string categoria "faturamento, insumos, diaria, impostos, fixo"
        string descricao
        decimal valor
        date data_competencia
        date data_pagamento
        string status "pendente | pago | cancelado"
        timestamp created_at
        timestamp updated_at
    }

    CONFIGURACOES {
        bigint id PK
        string chave
        text valor
        string descricao
        timestamp created_at
        timestamp updated_at
    }

    CLIENTES ||--o{ VISITAS : "possui (1:N)"
    CLIENTES ||--o{ EQUIPAMENTOS_COMODATO : "aloca (1:N)"
    TECNICOS ||--o{ VISITAS : "executa (1:N)"
    TECNICOS ||--o| USERS : "login_vinculado (1:1)"
    PRODUTOS ||--o{ EQUIPAMENTOS_COMODATO : "reabastece (1:N)"
    PRODUTOS ||--|{ VISITA_PRODUTO : "utilizado_em (1:N)"
    VISITAS ||--|{ VISITA_PRODUTO : "consome (1:N)"
    VISITAS ||--o{ MOVIMENTACOES_FINANCEIRAS : "gera (1:N)"
```

---

## 3. Fluxos Operacionais e de Negócio Críticos

### 3.1. Ciclo de Conclusão de Visita, Conversão Fracionada e Formação de Custo

```mermaid
sequenceDiagram
    autonumber
    actor Operador as Técnico / Admin
    participant Controller as VisitaController
    participant Service as CalculadoraCustos
    participant DB as Banco de Dados
    participant Visita as Model Visita
    participant Produto as Model Produto

    Operador->>Controller: POST/PUT /visitas (status: concluida, produtos[])
    Controller->>Service: Calcular custos proporcionais das frações aplicadas
    loop Para cada produto informado (ex.: 20 mL de Frasco de 1000 mL)
        Service->>Service: Fração = dose / fator_conversao
        Service->>Service: Custo Item = Fração * preco_custo_unitario
    end
    Controller->>Service: Calcular Impostos (se exige_nota_fiscal == true)
    Service-->>Controller: Retorna custo_produtos, valor_imposto, lucro_bruto

    Controller->>DB: Iniciar DB::transaction()
    Controller->>Visita: Persistir visita com custos, impostos e lucro_bruto
    Controller->>Visita: Sincronizar visita_produto com quantidade_aplicada e custo_proporcional

    opt Se transição de status para CONCLUIDA
        loop Para cada produto aplicado
            Controller->>Produto: Subtrair saldo proporcional em estoque
            Note over Controller,Produto: decrement('quantidade_estoque', dose / fator_conversao)
        end
    end

    Controller->>DB: Gerar movimentacao_financeira (Contas a Receber)
    Controller->>DB: Commit DB::transaction()
    Controller-->>Operador: Sucesso com atualização de margens e estoque
```

---

### 3.2. Fluxo da Agenda Geral Unificada

O módulo de agendamentos (`/agendamentos`) consolida três origens de dados operacionais e comerciais:

```mermaid
graph TD
    A[Agenda Geral Unificada] --> B[1. Visitas Técnicas & Comerciais]
    A --> C[2. Manutenção de Comodato]
    A --> D[3. Follow-up de Prospecção]

    B --> B1[Visitas Comerciais da Assessoria]
    B --> B2[Ordens de Serviço Técnicas]
    B --> B3[Demonstrações & Retornos Preventivos]

    C --> C1[Porta-iscas com Reposição Vencendo]
    C --> C2[Cálculo: data_ultima_reposicao + periodicidade_dias]

    D --> D1[Clientes com status 'retornar_3_meses']
    D --> D2[Clientes com 'contrato_concorrente' a vencer]
```

---

### 3.3. Fluxo de Geração de Proposta Comercial (Regra de Sigilo)

Para garantir proteção comercial e conformidade com as diretrizes da Alaris Service, dados sensíveis de custos internos nunca são expostos ao cliente final:

```
┌────────────────────────────────────────────────────────────────────────┐
│                        Dados da Visita / OS                            │
│  - Metragem: 250 m²             - Praga: Baratas Germânicas            │
│  - Insumo: 30 mL Fipronil       - Custo Produto: R$ 4,50               │
│  - Diária Técnico: R$ 50,00     - Deslocamento: R$ 20,00               │
│  - Imposto (14%): R$ 42,00      - Preço Final Cobrado: R$ 300,00       │
└───────────────────────────────────┬────────────────────────────────────┘
                                    │
               ┌────────────────────┴────────────────────┐
               ▼                                         ▼
┌──────────────────────────────┐        ┌──────────────────────────────┐
│  Painel Interno (Admin)      │        │  Proposta / Visão do Cliente │
│  (Visibilidade Completa)     │        │  (Sigilo Comercial Absoluto) │
├──────────────────────────────┤        ├──────────────────────────────┤
│ • Preço Cobrado: R$ 300,00   │        │ • Serviço: Controle de Pragas│
│ • Custo Insumos: R$ 4,50     │        │ • Metragem: 250 m²           │
│ • Mão de Obra: R$ 50,00      │        │ • Garantia: 90 dias          │
│ • Deslocamento: R$ 20,00     │        │                              │
│ • Impostos (NF): R$ 42,00    │        │                              │
│ ──────────────────────────── │        │ ──────────────────────────── │
│ • Lucro Bruto: R$ 183,50     │        │ • VALOR FINAL: R$ 300,00     │
│ • Margem: 61.1%              │        │                              │
└──────────────────────────────┘        └──────────────────────────────┘
```

---

## 4. Arquitetura Frontend: Temas, Polling e Reatividade

```
┌──────────────────────────────────────────────────────────────┐
│                    resources/js/app.js                       │
│                                                              │
│  ┌────────────────────────────────────────────────────────┐  │
│  │ 1. Theme Engine                                        │  │
│  │    • Lê 'theme' do localStorage (padrão: light)        │  │
│  │    • Aplica [data-theme="dark"] ou padrão light no html│  │
│  │    • Alterna ícones de Sol e Lua                       │  │
│  └────────────────────────────────────────────────────────┘  │
│  ┌────────────────────────────────────────────────────────┐  │
│  │ 2. Polling de Notificações (30s)                       │  │
│  │    • GET /notificacoes                                 │  │
│  │    • Recebe contagens { atrasadas, proximas, estoque,  │  │
│  │      comodato_reposicao, followups_pendentes }         │  │
│  │    • Alimenta badge no header e dropdown contextual    │  │
│  └────────────────────────────────────────────────────────┘  │
│  ┌────────────────────────────────────────────────────────┐  │
│  │ 3. Gerenciador Dinâmico de Produtos e Custos           │  │
│  │    • addProdutoRow() injeta linha de produto e dose    │  │
│  │    • Atualiza subtotal estimado de custos em tela      │  │
│  │    • Calcula acréscimo imediato ao marcar [x] Exige NF │  │
│  └────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────┘
```

---

## 5. Estratégia de Segurança e Isolamento de Perfis (ACL)

1. **Separação de Papéis**:
   - `admin`: Visualiza faturamento global, relatórios de DRE, fluxo de caixa, custos de insumos, margens e cadastro de usuários.
   - `tecnico`: Possui acesso estrito ao dashboard operacional reduzido, à sua própria agenda e às ordens de serviço atribuídas a si.
2. **Proteção em Camadas**:
   - **Camada de Rotas**: Middleware `EnsureUserHasRole('admin')` bloqueia acesso a rotas `/financeiro/*`, `/relatorios/*`, `/configuracoes/*`.
   - **Camada de Model/Query Scope**: Métodos de busca de visitas aplicam automaticamente `where('tecnico_id', auth()->user()->tecnico_id)` quando o usuário logado for técnico.
   - **Camada de View**: Blade directives `@can('admin')` e checagem de papel omitem colunas de custo, diárias e lucros em tabelas e modais.
