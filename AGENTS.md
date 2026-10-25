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

### 3.1. Sigilo Comercial Absoluto na Visão do Cliente e Técnicos
> [!IMPORTANT]
> **Regra de Ouro da Proposta Comercial & Sigilo Interno**:
> - Em propostas comerciais, orçamentos e visualizações geradas para o cliente (impressão ou tela), **devem constar apenas o Serviço Prestado e o Valor Final**.
> - **NUNCA exibir para o cliente externo ou para técnicos de campo**:
>   - Quantidades exatas fracionadas de defensivos químicos (ex.: mL ou gramas).
>   - Preço de custo de compra dos insumos.
>   - Valor da diária do técnico ou custos de transporte/deslocamento.
>   - Margem de lucro ou taxas de impostos.
>   - Aviso de faturamento de contas a receber no modal de finalização de OS (restrito a `admin`).
> - O botão e a rota de *"Proposta Comercial (Sigilosa)"* são estritamente bloqueados para usuários com papel `tecnico` (`abort(403)`).
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

### 3.4. Agenda Geral Unificada e Regra de Visibilidade de Comodato
- O módulo de agendamentos (`/agendamentos`) consolida três pilares:
  1. **Ordens de Serviço e Visitas Técnicas/Comerciais**.
  2. **Vencimentos de Reposição de Comodato**.
  3. **Follow-ups de Clientes em Prospecção** (clientes com status "Retornar em 3 meses" ou "Vencimento contrato concorrente").
- **Regra de Visibilidade de OS de Comodato**:
  - Se o usuário for **Administrador (`admin`)**, ele não visualiza Ordens de Serviço vinculadas a Comodatos separadamente na agenda, pois o próprio Evento de Comodato já agrega as ordens geradas.
  - Se o usuário for **Técnico (`tecnico`)**, visualiza normalmente suas OSs na sua agenda de trabalho.

### 3.5. Formação de Preço, DRE e Sincronização com Fluxo de Caixa
- Cada Ordem de Serviço possui apuração de custos diretos:
  $$\text{Custo Operacional} = \text{Insumos} + \text{Diária Técnica} + \text{Deslocamento} + \text{Gastos Adicionais}$$
  - $\text{Deslocamento / Transporte}$ (`custo_deslocamento`): Combustível e logística prevista.
  - $\text{Gastos Adicionais}$ (`gastos_adicionais` + `motivo_gastos_adicionais`): Imprevistos de campo.
  - Se `exige_nota_fiscal === true`, calcula acréscimo percentual configurável:
    $$\text{Imposto} = \text{Valor Cobrado} \times \frac{\text{Percentual Imposto}}{100}$$
  - $\text{Lucro Bruto} = \text{Valor Cobrado} - (\text{Custo Operacional} + \text{Imposto})$.
- **Invariante de Sincronização Atômica (`sincronizarMovimentacoesFinanceiras`)**:
  - Toda criação, edição ou conclusão de OS executada com status `concluida` sincroniza seus lançamentos no Caixa:
    1. A `data_competencia` deve espelhar obrigatoriamente a data de execução da OS (`$visita->data_hora->toDateString()`).
    2. Edições subsequentes de valor cobrado, diária ou gastos extras devem atualizar os lançamentos existentes sem gerar duplicatas.
    3. Ao cancelar uma OS, lançamentos pendentes são expurgados do fluxo.
    4. Ao excluir uma OS (`destroy`), suas movimentações vinculadas devem ser excluídas em transação atômica (`DB::transaction`) antes de apagar a visita, impedindo a geração de movimentações órfãs (`visita_id = null`) que distorceriam as despesas fixas gerais da empresa.
- **Conciliação Cruzada DRE vs Fluxo de Caixa**:
  - O Relatório de Lucro Bruto (`/financeiro/relatorios` e PDF) expõe o confronto entre DRE Faturado (Competência), Recebido no Caixa (Pagas), Pendente (A Receber), Diárias Pagas vs Pendentes e status de recebimento por OS (`Recebido`, `A Receber`, `Isento`, `Cancelado`).

### 3.6. Segregação de Perfis & Permissões Granulares (ACL)
- **Módulo de Permissões Granulares (`/perfis`)**:
  - Matriz completa de permissões por módulo (`clientes`, `equipamentos`, `comodatos`, `visitas`, `produtos`, `agendamentos`, `tecnicos`, `financeiro`, `configuracoes`, `perfis`, `usuarios`).
  - **Permissões Comuns em Cada Módulo**: `ver`, `criar`, `editar`, `deletar` e `acessar_dados`.
  - **Permissões Específicas**: Ações pontuais como `visitas.finalizar`, `visitas.gerar_os`, `visitas.proposta_comercial`, `comodatos.registrar_reposicao`, `equipamentos.gerenciar_categorias`, `produtos.ajustar_estoque`, `financeiro.alterar_status`, `financeiro.relatorio_dre` e `financeiro.exportar_pdf`.
- **Perfis Pré-configurados e Dinâmicos**:
  - `admin` (Administrador): Super-usuário com acesso irrestrito a todas as rotas e permissões.
  - `tecnico` (Técnico de Campo): Restrito unicamente à sua própria escala de visitas, finalização de OS, comodatos e produtos sem acesso a dados financeiros ou sigilosos.
  - `gerente_operacional`: Coordenação de campo, clientes, insumos e equipamentos.
  - `financeiro`: Gestão de fluxo de caixa, títulos a pagar/receber e relatórios de DRE.
  - Novos perfis personalizados podem ser cadastrados e ajustados pelo gestor via matriz de checkboxes.
- **Middleware & Gates**:
  - Middleware de rota: `permissao:modulo.acao` (ex.: `permissao:visitas.proposta_comercial`).
  - Interceptação via Gate e diretivas Blade `@permissao('chave')` / `@endpermissao`.

### 3.7. Notificações Nativas no Navegador e Monitoramento Minuto a Minuto
- O sistema executa monitoramento minuto a minuto via Laravel Scheduler e crontab/Task Scheduler (`php artisan cron:install`).
- A rotina monitora:
  1. Visitas agendadas para os próximos 60 minutos.
  2. Reposições de comodato vencendo hoje ou atrasadas.
  3. Follow-ups de prospecção agendados para a data atual.
- **Emissão Estrita via Navegador Web**:
  - Alertas ao Sistema Operacional (Desktop e Mobile) são gerados **exclusivamente via Web Notifications API e Service Worker** (`public/sw.js`).
  - É expressamente proibido disparar scripts locais do servidor (PowerShell/Windows Forms) para emitir notificações na máquina.
- **Entrega via Web Push (VAPID) — PWA fechado / celular bloqueado**:
  - Toda `NotificacaoAgendamento` criada dispara, após o commit, o envio via `WebPushService` (`minishlink/web-push`) para as inscrições em `push_subscriptions` dos destinatários (mesmas regras de visibilidade de `scopeParaUsuario`).
  - O `public/sw.js` trata o evento `push` e sempre chama `showNotification` (sem `actions`, incompatível com iOS). Inscrições expiradas (404/410) são removidas automaticamente.
  - O polling JavaScript permanece apenas como fallback para dispositivos sem inscrição push; quando o push é aceito, a notificação é marcada `enviada_so` para evitar duplicidade.
  - Chaves: `php artisan webpush:vapid` (grava `VAPID_PUBLIC_KEY`/`VAPID_PRIVATE_KEY` no `.env`). Exige HTTPS em produção; no iOS (16.4+) apenas com o PWA instalado na Tela de Início.

### 3.8. Máscaras no Frontend & Persistência Limpa no Banco
- Os campos `documento` (CPF/CNPJ), `telefone` e `cep` possuem máscaras dinâmicas no frontend via atributos `data-mask`.
- **Regra de Armazenamento**: Os dados **devem ser persistidos limpos** (apenas números, sem pontuação, traços ou barras), utilizando `MascaraHelper::apenasNumeros()` e Mutators nos modelos.
- Para exibição formatada, utilize os accessors (`$cliente->documento_formatado`, etc.).

### 3.9. Invariante de Consultas de Data no SQLite/MySQL
- Em todas as consultas que filtram datas (`data_followup`, `data_proxima_reposicao`, `data_hora`), **deve-se utilizar obrigatoriamente `whereDate()`**:
  ```php
  $query->whereDate('data_followup', '>=', $inicio)
        ->whereDate('data_followup', '<=', $fim);
  ```
- **Proibição**: Nunca utilizar comparações diretas de strings (`where('data', '<=', $dataStr)`) ou `whereBetween()` com strings puras em colunas que armazenam timestamp/datetime, sob risco de omitir registros no último dia do mês (ex.: 31/10).

### 3.10. Padronização de Unidades de Medida em Produtos e Insumos
- Formulários de produtos utilizam `<select>` com `<optgroup>`s pré-definidos (Líquidos: L/mL; Sólidos: Kg/g; Unidades: Un/Un) e cálculo automático do fator de conversão no frontend, impedindo entradas incorretas.

### 3.11. Identidade Visual Oficial, Cor Primária e PWA (#23b4eb)
- A cor primária e de marca é padronizada estritamente em **`#23b4eb`** (Alaris Cyan).
- Todos os manifestos PWA (`manifest.json`, `manifest.webmanifest`), `<meta name="theme-color">`, favicons e tokens CSS do `@theme` operam sob essa referência visual.
- O novo conjunto de ícones PWA e favicons (`/icons/icon-*.png`, `apple-touch-icon.png`, `favicon.ico`) adota o símbolo oficial circular da gota Alaris.

### 3.12. Experiência Mobile Nativa (PWA & Layout de App)
- **Header Mobile Anti-Overlap**: O título do módulo no header deve manter sempre `min-w-0 flex-1 truncate whitespace-nowrap` em linha única, impedindo quebras que sobreponham o header.
- **Barra Inferior Nativa (`#mobile-bottom-nav`)**: Aplicação móvel fixa na base com 5 destinos (Início, Agenda, Visitas, Clientes/Comodatos, Mais), suporte a safe area (`env(safe-area-inset-bottom)`) e compensação no container principal (`<main class="... pb-24 lg:pb-8">`).
- **Central de Notificações Mobile**: A classe `.notif-dropdown` deve manter no mobile posicionamento fixo ancorado nas bordas da viewport (`left: 0.75rem; right: 0.75rem; width: auto; max-width: calc(100vw - 1.5rem)`), prevenindo cortes fora da tela.

### 3.13. Ações em Massa no Fluxo de Caixa e Precedência de Visitas
- **Ações em Lote**: A listagem financeira conta com checkboxes de seleção múltipla permitindo que administradores liquidem, reabram ou excluam múltiplos lançamentos de forma atômica.
- **Precedência Operacional**: Na listagem de visitas e ordens de serviço (`/visitas`), atendimentos com status `agendada` e `em_andamento` são exibidos com precedência prioritária no topo da listagem, independente da data agendada.

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
| **Instalar Cron no SO** | `php artisan cron:install` |
| **Status da Cron** | `php artisan cron:install --status` |
| **Verificar Notificações Manuais** | `php artisan agendamentos:verificar-notificacoes` |

---

## 6. Instruções Específicas para Agentes de IA

1. **Nunca quebrar a Regra de Sigilo Comercial**: Jamais insira detalhamento de doses, custos internos ou diárias técnicas em views ou impressos de proposta destinados ao cliente final.
2. **Preservar Baixa Atômica Fracionada**: Sempre utilize o fator de conversão ao abater produtos do estoque.
3. **Persistência Limpa de Documentos**: Ao manipular `documento`, `telefone` ou `cep`, garanta que o salvamento no banco remova toda a pontuação através de `MascaraHelper::apenasNumeros()`.
4. **Uso Mandatório de `whereDate`**: Nunca compare datas usando strings em SQLite/MySQL; utilize sempre `whereDate()`.
5. **Notificações Exclusivas via Navegador**: Alertas de agendamento são emitidos apenas pela API Web (`Notification` e Service Worker), sem chamadas locais de servidor.
6. **Links Clicáveis em Respostas**: Sempre cite arquivos com sintaxe markdown clicável `file:///` (ex: [VisitaController.php](file:///z:/home/mh/projetos/erp-alaris/app/Http/Controllers/VisitaController.php)).
7. **Segurança de Rotas**: Todas as rotas financeiras e de configurações devem exigir validação do papel `admin`.
