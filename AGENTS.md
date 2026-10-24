# AGENTS.md — Diretrizes e Contexto para Agentes de IA

Bem-vindo ao repositório do **ERP Alaris** (anteriormente Biosp / Sistema Alaris). Este arquivo fornece todas as diretrizes de engenharia, arquitetura, regras de negócio e convenções necessárias para que agentes de IA e desenvolvedores operem no projeto com máxima precisão e conformidade.

---

## 1. Visão Geral do Projeto

- **Nome do Sistema**: ERP Alaris
- **Cliente**: Alaris Service
- **Domínio de Negócio**: Gestão operacional e financeira de empresas de **Controle de Pragas Urbanas, Dedetização e Serviços de Higienização/Limpeza**.
- **Módulos Principais**:
  - **Clientes & Funil de Prospecção**: Origem do cliente, periodicidade de serviço, status de prospecção com data de follow-up integrada à agenda geral, geocadastro com cidade e estado.
  - **Técnicos & Perfis de Usuário**: Usuários vinculados a técnicos com permissões restritas (apenas visitas próprias, sem acesso a dados financeiros globais).
  - **Equipamentos em Comodato**: Controle de porta-iscas e armadilhas no cliente, periodicidade de reposição em dias (editável), vínculo com produto do estoque e geração de OS.
  - **Estoque & Conversão Fracionada**: Naturezas líquida, sólida e unidade, com cálculo de custo proporcional por dose fracionada (mL, g) baseado no custo base.
  - **Visitas, OS & Agenda Unificada**: Categorias (comercial, técnica, demonstração, execução), serviços (controle de pragas, limpeza), agenda unificada (visitas + comodato + follow-ups).
  - **Proposta Comercial (Sigilo de Custos)**: Visão do cliente restrita ao serviço e valor final, ocultando estritamente mLs, custos unitários, diárias e margens.
  - **Financeiro & Formação de Preço**: Composição de custo da OS (produtos + diária + deslocamento), seletor de Nota Fiscal com acréscimo tributário percentual configurável, fluxo de caixa e relatório de Lucro Bruto.
  - **Dashboard Operacional & Notificações**: Alertas em tempo real (atrasadas, próximas, estoque crítico, reposição de comodato e follow-ups).

---

## 2. Stack Tecnológica

| Componente | Tecnologia | Detalhes |
| :--- | :--- | :--- |
| **Linguagem** | PHP 8.3+ | Tipos estritos, match expressions, Attributes do PHP 8.3/8.4 |
| **Framework Backend** | Laravel ^13.8 (Laravel 12/13 skeleton) | Arquitetura modular, Eloquent ORM, Route Model Binding, Policies |
| **Banco de Dados** | SQLite (dev) / MySQL (prod) | Migrations versionadas, transações atômicas `DB::transaction()` |
| **Frontend & Template** | Blade + Vanilla JS + Tailwind CSS v4 | Estilos via `@tailwindcss/vite` v4 com `@theme` e suporte Dark/Light |
| **Bundler** | Vite 8 + `laravel-vite-plugin` | Compilação em `resources/css/app.css` e `resources/js/app.js` |
| **Testes** | Pest PHP v5 + PHPUnit | Testes de unidade e features com `pestphp/pest-plugin-laravel` |
| **Code Style** | Laravel Pint | Padronização de código PSR-12 |

---

## 3. Regras de Negócio Críticas (Invariantes)

### 3.1. Sigilo Comercial Absoluto na Visão do Cliente
> [!IMPORTANT]
> **Regra de Ouro da Proposta Comercial**:
> - Em propostas comerciais, orçamentos e visualizações geradas para o cliente (impressão ou tela), **devem constar apenas o Serviço Prestado e o Valor Final**.
> - **NUNCA exibir para o cliente externo**:
>   - Quantidades exatas fracionadas de defensivos químicos (ex.: mL ou gramas).
>   - Preço de custo de compra dos insumos.
>   - Valor da diária do técnico ou custos de transporte/deslocamento.
>   - Margem de lucro ou taxas de impostos.
> - Essas informações de custo compõem a inteligência interna do ERP e são visíveis **exclusivamente no painel administrativo** por usuários com perfil `admin`.

### 3.2. Ciclo de Vida da Visita, Conversão Fracionada e Baixa de Estoque
- Os produtos possuem unidade de armazenamento (`L`, `Kg`, `Un`) e unidade de aplicação (`mL`, `g`, `Un`) com um `fator_conversao` (ex.: 1000).
- Ao concluir uma visita com status `concluida`:
  1. A quantidade aplicada em dose fracionada deve ser convertida para a unidade de estoque: $\text{baixa} = \text{dose} / \text{fator\_conversao}$.
  2. O custo proporcional é apurado: $\text{custo} = \text{baixa} \times \text{preco\_custo\_unitario}$.
  3. A baixa de saldo no estoque e o registro do custo na visita devem ser executados sob transação atômica `DB::transaction()`.

### 3.3. Gestão de Comodato e Reposição Preventiva
- Equipamentos alocados em clientes (ex.: Porta-iscas) possuem campo de **periodicidade de reposição em dias** (editável pelo operador).
- A cada reposição registrada, a `data_proxima_reposicao` é recalculada: $\text{data\_proxima} = \text{data\_reposicao} + \text{periodicidade\_dias}$.
- Equipamentos cuja data de próxima reposição seja $\le \text{now}() + 48\text{h}$ geram alertas e aparecem destacados na **Agenda Geral Unificada**.

### 3.4. Agenda Geral Unificada
- O módulo de agendamentos (`/agendamentos`) consolida três pilares:
  1. **Ordens de Serviço e Visitas Técnicas/Comerciais**.
  2. **Vencimentos de Reposição de Comodato**.
  3. **Follow-ups de Clientes em Prospecção** (clientes com status "Retornar em 3 meses" ou "Vencimento contrato concorrente").

### 3.5. Formação de Preço, Impostos e Nota Fiscal
- Cada Ordem de Serviço possui uma aba de apuração financeira:
  - $\text{Custo Operacional} = \text{Custo Insumos} + \text{Mão de Obra/Diária} + \text{Deslocamento}$.
  - Se `exige_nota_fiscal === true`, calcula acréscimo percentual configurável (ex.: 12% a 14%):
    $$\text{Imposto} = \text{Valor Cobrado} \times \frac{\text{Percentual Imposto}}{100}$$
  - $\text{Lucro Bruto} = \text{Valor Cobrado} - (\text{Custo Operacional} + \text{Imposto})$.
- *Nota*: O sistema não emite NFS-e direta no SEFAZ; o recurso é de controle financeiro gerencial e cálculo de margem líquida.

### 3.6. Segregação de Perfis (ACL)
- Papéis definidos: `admin` (Gestor) e `tecnico` (Técnico de Campo).
- **Restrição do Técnico**:
  - Acessa unicamente a sua própria escala de visitas e ordens de serviço.
  - Não possui acesso a rotas de faturamento global, fluxo de caixa, relatórios de Lucro Bruto ou custos de aquisição de insumos.

---

## 4. Diretrizes de Codificação e Estilo

### 4.1. PHP e Laravel
- **Tipagem Estrita**: Métodos, controllers e actions devem conter `declare(strict_types=1);` ou assinaturas tipadas.
- **Transações de Banco**: Operações que alteram status de visita, baixam estoque ou geram lançamentos no fluxo de caixa devem usar `DB::transaction(fn() => ...)`.
- **Policies e Gates**: Utilize Laravel Policies para checagem de autorização (`VisitaPolicy`, `ClientePolicy`, `FinanceiroPolicy`).
- **Formatação de Código**: Execute sempre `vendor/bin/pint` antes de finalizar modificações.

### 4.2. Frontend (Blade, CSS e JS)
- **Tema Light/Dark**: Uso exclusivo das variáveis do design system (`var(--color-surface-*)`, `var(--color-primary-*)`, `var(--form-text)`).
- **Propostas e Documentos**: Ao formatar a proposta comercial do cliente, oculte explicitamente linhas de insumos e custos internos.
- **JavaScript Centralizado**: Funções de cálculo em tempo real e injeção dinâmica devem residir em `resources/js/app.js`.

---

## 5. Comandos de Operação

| Ação | Comando |
| :--- | :--- |
| **Instalação Completa** | `composer run setup` |
| **Ambiente de Desenvolvimento** | `composer run dev` |
| **Execução de Testes** | `composer run test` ou `php artisan test` |
| **Lint e Formatação** | `vendor/bin/pint` |
| **Executar Migrações** | `php artisan migrate` |
| **Compilar Assets** | `npm run build` |

---

## 6. Instruções Específicas para Agentes de IA

1. **Nunca quebrar a Regra de Sigilo Comercial**: Jamais insira detalhamento de doses, custos internos ou diárias técnicas em views ou impressos de proposta destinados ao cliente final.
2. **Preservar Baixa Atômica Fracionada**: Sempre utilize o fator de conversão ao abater produtos do estoque.
3. **Links Clicáveis em Respostas**: Sempre cite arquivos com sintaxe markdown clicável `file:///` (ex: [VisitaController.php](file:///d:/projetos/sistema-alaris/app/Http/Controllers/VisitaController.php)).
4. **Segurança de Rotas**: Todas as rotas financeiras e de configurações devem exigir validação do papel `admin`.
