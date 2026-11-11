# AGENTS.md — Diretrizes e Contexto para Agentes de IA

Bem-vindo ao repositório do **ERP Alaris** (anteriormente Biosp / Sistema Alaris). Este arquivo fornece todas as diretrizes de engenharia, arquitetura, regras de negócio e convenções necessárias para que agentes de IA e desenvolvedores operem no projeto com máxima precisão e conformidade.

---

## 1. Visão Geral do Projeto

- **Nome do Sistema**: ERP Alaris
- **Cliente**: Alaris Service
- **Domínio de Negócio**: Gestão operacional e financeira de empresas de **Controle de Pragas Urbanas, Dedetização e Serviços de Higienização/Limpeza**.
- **Módulos Principais**:
  - **Clientes & Funil de Prospecção**: Origem do cliente, status de prospecção com data de follow-up integrada à agenda geral, geocadastro com cidade e estado (desacoplado de periodicidade direta).
  - **Contratos & Gestão Recorrente**: Contratos vinculados a clientes com prazo determinado ou indeterminado, itens de serviço contratados, periodicidades flexíveis (semanal, quinzenal, mensal, bimestral, trimestral, semestral, anual, personalizado com ajuste de dias inexistentes), projeção dinâmica de agendamentos na Agenda Geral (Opção A) sem geração de O.S. vazias antecipadamente no banco e ciclo de vida completo.
  - **Técnicos & Perfis de Usuário**: Usuários vinculados a técnicos com permissões restritas (apenas visitas próprias, sem acesso a dados financeiros globais).
  - **Equipamentos em Comodato**: Controle de porta-iscas e armadilhas no cliente, periodicidade de reposição em dias (editável), vínculo opcional com contratos (`contrato_id`), produtos do estoque, sincronização de reposição via conclusão de OS e rotina de recolhimento na rescisão.
  - **Estoque & Conversão Fracionada**: Naturezas líquida, sólida e unidade, com cálculo de custo proporcional por dose fracionada (mL, g) baseado no custo base.
  - **Visitas, OS & Agenda Unificada**: Categorias (comercial, técnica, demonstração, execução), serviços (controle de pragas, limpeza), agenda unificada (visitas reais + projeções contratuais + comodato + follow-ups).
  - **Proposta Comercial (Sigilo de Custos)**: Visão do cliente restrita ao serviço e valor final, ocultando estritamente mLs, custos unitários, diárias e margens. Emissão de PDF oficial em folha única A4 com dados empresariais e website no rodapé.
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
> - Em propostas comerciais, orçamentos e visualizações geradas para o cliente (impressão, PDF ou tela), **devem constar apenas o Serviço Prestado e o Valor Final**.
> - As Propostas Comerciais são centralizadas no módulo de **Contratos** (`status = 'proposta'`), com emissão em PDF formatado para caber estritamente em **1 folha única A4**.
> - O botão de proposta comercial foi totalmente descontinuado da O.S. (Visita), mantendo o fluxo comercial exclusivo no módulo de Contratos.
> - **NUNCA exibir para o cliente externo ou para técnicos de campo**:
>   - Quantidades exatas fracionadas de defensivos químicos (ex.: mL ou gramas).
>   - Preço de custo de compra dos insumos.
>   - Valor da diária do técnico ou custos de transporte/deslocamento (`custo_diaria_padrao`, `custo_deslocamento_padrao`).
>   - Margem de lucro ou taxas de impostos (`percentual_imposto`, `exige_nota_fiscal`).
>   - Aviso de faturamento de contas a receber no modal de finalização de OS (restrito a `admin`).
> - Essas informações de custo compõem a inteligência interna do ERP e são visíveis **exclusivamente no painel administrativo** por usuários com perfil `admin`.

### 3.2. Ciclo de Vida da Visita, Conversão Fracionada e Controle Imediato de Estoque
- Os produtos possuem unidade de armazenamento (`L`, `Kg`, `Un`) e unidade de aplicação (`mL`, `g`, `Un`) com um `fator_conversao` (ex.: 1000).
- **Validação de Saldo e Reserva Imediata na Abertura da OS**:
  1. A dose aplicada requerida é convertida para a unidade de estoque: $\text{baixa} = \text{dose} / \text{fator\_conversao}$.
  2. Ao criar ou editar uma OS ativa (`agendada`, `em_andamento`, `concluida`), o sistema valida se há saldo suficiente em estoque (`quantidade_estoque >= baixa`). Se insuficiente, rejeita a operação com erro de validação descritivo.
  3. Havendo saldo, a baixa é debitada imediatamente do estoque no momento da adição/criação da OS e registrada na pivot `visita_produto` em `fracao_baixada`, impedindo que múltiplas OSs concorram por saldo indisponível.
  4. Na conclusão da OS (`concluida` ou `finalizar`), o sistema debita apenas eventuais frações pendentes (evitando qualquer dupla baixa).
- **Estorno Automático no Cancelamento ou Exclusão da OS**:
  - Se uma OS for cancelada (via endpoint dedicado `visitas.cancelar`, alteração de status para `cancelada` na edição, ou remoção na agenda), todas as frações consumidas (`fracao_baixada`) são devolvidas atomicamente ao saldo do produto (`quantidade_estoque`) e `fracao_baixada` é resetada para `0`.
  - Se a OS for excluída (`destroy`), qualquer saldo baixado é estornado antes da exclusão.
  - Se uma OS cancelada for reaberta (`agendada`), o sistema valida novamente a disponibilidade atual do estoque antes de permitir a reativação.

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
- Todos os manifestos PWA (`manifest.json`, `manifest.webmanifest`), `<meta name="theme-color">`, `background_color`, favicons e tokens CSS do `@theme` operam sob essa referência visual.
- **Invariante de Ícones PWA no iOS (iPhone/iPad) e Android**:
  - Os ícones de instalação na Tela de Início (`public/apple-touch-icon.png`, `apple-touch-icon-precomposed.png`, `apple-touch-icon-180x180.png`, `167x167`, `152x152`, `120x120`, bem como `public/icons/icon-180.png`, `icon-192.png`, `icon-512.png` e *maskables*) devem ser **PNGs RGB 24-bit 100% opacos (Color Type 2, sem canal alfa/transparência)** preenchendo todo o quadrado (*full-bleed*) com o fundo oficial Alaris e a gota branca centralizada, permitindo que o iOS aplique sua máscara *squircle* nativa e o Android aplique os *adaptive icons* sem bordas pretas ou fallback de monograma ("A").
  - Os favicons de aba de navegador (`favicon-32x32.png`, `favicon-16x16.png`, `favicon.png`, `favicon.ico`) mantêm o formato circular com fundo transparente.
  - As tags `<link rel="apple-touch-icon*">`, `<link rel="icon">` e `<link rel="manifest">` no `<head>` devem utilizar **caminhos relativos à raiz** (ex.: `/apple-touch-icon.png?v=2`, `/manifest.json?v=2`) em vez de `asset()`, combinados com `$middleware->trustProxies(at: '*')` em `bootstrap/app.php`, evitando bloqueio de *Mixed Content* (`http://` em páginas `https://`) no Safari/iOS quando servido atrás de proxy reverso ou túnel HTTPS.

### 3.12. Experiência Mobile Nativa (PWA & Layout de App)
- **Header Mobile Anti-Overlap**: O título do módulo no header deve manter sempre `min-w-0 flex-1 truncate whitespace-nowrap` em linha única, impedindo quebras que sobreponham o header.
- **Barra Inferior Nativa (`#mobile-bottom-nav`)**: Aplicação móvel fixa na base com 5 destinos (Início, Agenda, Visitas, Clientes/Comodatos, Mais), suporte a safe area (`env(safe-area-inset-bottom)`) e compensação no container principal (`<main class="... pb-24 lg:pb-8">`).
- **Central de Notificações Mobile**: A classe `.notif-dropdown` deve manter no mobile posicionamento fixo ancorado nas bordas da viewport (`left: 0.75rem; right: 0.75rem; width: auto; max-width: calc(100vw - 1.5rem)`), prevenindo cortes fora da tela.

### 3.14. Gestão de Contratos, Periodicidade e Ciclo de Vida de Agendamentos
- **Fluxo Estrutural**:
  $$\text{Cliente} \longrightarrow \text{Proposta Comercial} \xrightarrow{\text{Aprovação}} \text{Contrato Ativo} \longrightarrow \text{Agenda Geral} \longrightarrow \text{Ordem de Serviço (OS)}$$
- **Desacoplamento do Cliente**: O cadastro de clientes não dita mais a periodicidade de atendimento. Um cliente pode existir sem contratos ou gerenciar múltiplos contratos simultâneos com vigências, serviços e frequências distintas.
- **Proposta Comercial Centralizada em Contratos (`status = 'proposta'`)**:
  - Propostas comerciais aguardando aprovação do cliente **não geram eventos na Agenda Geral**.
  - O gestor pode emitir a qualquer momento o **PDF da Proposta ou Contrato em folha única (A4)** via rota `contratos.pdf`.
  - O PDF expõe exclusivamente o escopo dos serviços, periodicidade, vigência, investimento final e condições de pagamento, omitindo estritamente custos internos de insumos, diárias técnicas e alíquotas de imposto.
  - Ao aprovar a proposta (`contratos.ativar`), o status é alterado para `ativo` e os atendimentos passam a ser projetados na Agenda Geral imediatamente.
- **Projeção Dinâmica na Agenda & Emissão de O.S. sob Demanda (Opção A)**:
  - **Invariante de O.S. Manual**: Contratos ativos **nunca gravam antecipadamente linhas de O.S. (`visitas`) no banco de dados**. Rotinas diárias e comandos de background não criam visitas vazias automaticamente.
  - A Agenda Geral (`/agendamentos`) projeta dinamicamente os agendamentos virtuais de atendimentos previstos para o mês visualizado via `ContratoAgendamentoService::calcularDatasNoPeriodo()`, exibidos como `Atendimento Contratual` com status `Previsto (Contrato)`.
  - O gestor despacha a equipe emitindo a Ordem de Serviço real **manualmente sob demanda** através do botão **`Agendar OS`** no evento da Agenda ou no botão **`Emitir Ordem de Serviço`** na página do contrato, selecionando o técnico responsável e horário.
  - Ao salvar a O.S., a visita física real substitui a projeção no calendário sem duplicidade (`$jaExisteOs`).
  - Tratamento seguro de datas inexistentes (ex.: dia 31 em meses de 28, 29 ou 30 dias ajustado via `min($dia, $mes->daysInMonth)`).
- **Invariante de Faturamento da O.S. & Preço Fechado no Contrato**:
  - Ordens de Serviço geradas para contratos sob **Mensalidade Fixa / Preço Fechado** operam com `valor_cobrado = 0.00` ("Incluso no Contrato") para evitar faturamento em duplicidade no Fluxo de Caixa e DRE.
  - **Unicidade de Verba Contratual**: A receita do contrato é única. Custos tributários de NFS-e (`exige_nota_fiscal` e `percentual_imposto`), custos de diária técnica (`custo_diaria_padrao`), deslocamento (`custo_deslocamento_padrao`), defensivos químicos e gastos adicionais de campo são absorvidos/deduzidos da verba contratual.
  - **Experiência na O.S. (Centro de Custo)**:
    - O formulário de O.S. vinculada a contrato de preço fechado omite o seletor avulso de NF (tributação já retida no contrato) e exibe o resumo financeiro com indicador visual de *"Atendimento Coberto / Deduzido do Contrato"*, eliminando falsos lucros negativos em vermelho.
    - No contas a pagar, a diária do técnico e os imprevistos adicionais são gerados normalmente para assegurar o pagamento ao profissional de campo.
    - O DRE (`/financeiro/relatorios` e PDF) confronta a receita consolidada dos contratos ativos com os custos operacionais de todas as OSs vinculadas no período, expondo o saldo líquido real de cada contrato.
- **Invariante de Preservação de Histórico Operacional**:
  - Ao alterar, suspender ou cancelar um contrato, o sistema expurga/cancela exclusivamente visitas futuras pendentes (`agendada`).
  - Visitas em andamento ou concluídas (`em_andamento`, `concluida`) e movimentações financeiras geradas são **estritamente preservadas**, mantendo a integridade e rastreabilidade contábil.

### 3.15. Integração entre Módulos de Contratos e Comodatos
- **Vínculo Estrutural**: A tabela `equipamentos_comodato` possui a chave estrangeira `contrato_id` (opcional), permitindo associar armadilhas e porta-iscas cedidos a um contrato ativo do cliente.
- **Transparência Comercial no PDF**: Os equipamentos em comodato vinculados ao contrato são listados na Proposta Comercial e no Contrato formal (tipo de equipamento e quantidade cedida), omitindo defensivos e custos internos conforme a Regra de Ouro de Sigilo.
- **Sincronização Operacional de Campo**:
  - Formulários de O.S. vinculadas a contratos com comodato exibem a relação dos dispositivos instalados e seus respectivos insumos.
  - Ao concluir uma O.S. associada ao contrato, o ciclo de reposição dos comodatos vinculados é automaticamente atualizado (`data_ultima_reposicao = now()` e `data_proxima_reposicao = now() + periodicidade_dias`).
- **Ciclo de Vida & Rescisão com Comodatos**:
  - Ao suspender, encerrar ou cancelar um contrato que possua comodatos ativos, o sistema exibe alerta operacional destacando a quantidade de itens pendentes de recolhimento.
  - Disponibiliza atalhos para emissão de O.S. de recolhimento técnico ou devolução direta ao estoque patrimonial disponível (`contratos.recolher-comodatos`).

### 3.16. Informações Empresariais e Customização de Documentos (PDF A4)
- **Centralização Institucional (`/configuracoes`)**:
  - Dados da Alaris Service cadastrados e parametrizáveis: Razão Social, Nome Fantasia, CNPJ, Alvará Sanitário / Licença Ambiental, Telefone/WhatsApp, E-mail, Website Oficial (`website_empresa`), Slogan e Endereço Completo da Sede (Logradouro, Bairro, Cidade, Estado, CEP).
- **Emissão Oficial de Propostas e Contratos (Folha Única A4)**:
  - Cabeçalho institucional com marca Alaris Cyan (`#23b4eb`), dados do cliente e escopo detalhado.
  - Exibição integral das **Cláusulas Adicionais / Descrição Completa** pactuadas no contrato.
  - Campo de Data e Assinaturas (Contratante e Contratada) com espaçamento superior harmonizado.
  - Rodapé institucional contendo Razão Social, CNPJ, Contatos, Website da empresa, Endereço completo, Alvará Sanitário e autenticação eletrônica.

### 3.17. Insumos Químicos e Defensivos Planejados em Propostas e Contratos
- **Modelagem Pivot (`contrato_produto`)**:
  - Tabela pivot contendo `contrato_id`, `produto_id`, `quantidade` (dose planejada por atendimento) e `custo_proporcional` (custo proporcional calculado por visita).
  - Relacionamentos Eloquent recíprocos: `$contrato->produtos()` e `$produto->contratos()`.
- **Formação de Preço e Previsão de Custos Operacionais**:
  - Cálculo automático por atendimento: $\text{custo} = (\text{dose} / \text{fator\_conversao}) \times \text{preco\_custo\_unitario}$.
  - Métodos no modelo: `custoInsumosPrevistoPorVisita()` e integração direta em `custoOperacionalPrevisto()`, compondo a margem prevista do contrato juntamente com a diária técnica, deslocamento e gastos adicionais.
- **Formulários Dinâmicos (Criação & Edição)**:
  - Seções dinâmicas idênticas às de Ordens de Serviço, com seleção de produto, exibição automática da unidade de aplicação (`mL`, `g`, `Un`), saldo atual em estoque e cálculo em tempo real de custos proporcionais.
- **Despacho Automático na Emissão de O.S. (`visitas/create`)**:
  - Ao abrir o formulário de emissão de Ordem de Serviço vinculado a um contrato (`?contrato_id=...` ou selecionando o contrato no dropdown), o sistema **pré-carrega automaticamente** os insumos químicos e doses planejados na tabela de produtos da O.S., otimizando o despacho operacional.
- **Regra de Ouro de Sigilo no PDF Oficial**:
  - No PDF da proposta ou contrato (folha única A4), são exibidos **apenas os nomes comerciais/técnicos dos saneantes autorizados no escopo de metodologia técnica**.
  - Doses aplicadas (mL/g), custos unitários de compra, diárias técnicas e margens internas permanecem **estritamente confidenciais e ocultos do cliente**.

### 3.18. Ciclo de Vida, Lixeira e Soft Deletes em Propostas e Contratos
- **Arquitetura de Lixeira (Soft Deletes)**:
  - O modelo `Contrato` adota `SoftDeletes` (`deleted_at`), permitindo descartar propostas recusadas ou contratos descontinuados sem perda imediata de dados.
  - Rotas dedicadas: `contratos.lixeira` (`GET /contratos/lixeira`), `contratos.restaurar` (`POST /contratos/{id}/restaurar`), `contratos.force-delete` (`DELETE /contratos/{id}/force-delete`) e `contratos.esvaziar-lixeira` (`POST /contratos/esvaziar-lixeira`).
- **Invariante de Proteção Contábil**:
  - **Bloqueio de Descarte com Atendimentos Executados**: É expressamente proibido mover para a lixeira ou excluir fisicamente qualquer contrato que possua ordens de serviço executadas ou em andamento (`status` em `concluida` ou `em_andamento`). O sistema orienta o gestor a utilizar as rotas de Encerramento (`encerrar`) ou Cancelamento (`cancelar`), preservando o histórico fiscal e contábil.
  - Para contratos sem atendimentos executados ou propostas comerciais, o envio à lixeira estorna/cancela visitas agendadas pendentes e preserva integralmente os itens, comodatos e insumos cadastrados para eventual restauração.
- **Transparência e Acesso aos Registros na Lixeira**:
  - Rota de detalhes configurada com `withTrashed(['show'])`, permitindo inspecionar o contrato na lixeira exibindo badge `Na Lixeira`, aviso com data de descarte e botões de ação para **Restaurar** ou **Excluir Definitivamente**.
  - O contador da lixeira é exposto no card/badge de ações da listagem principal (`totais['lixeira']`).
  - O gerador de numeração `gerarProximoNumero()` consulta `withTrashed()` para impedir colisões ou saltos indevidos de códigos sequenciais.

---

## 4. Diretrizes de Codificação e Estilo

### 4.1. PHP e Laravel
- **Tipagem Estrita**: Métodos, controllers e actions devem conter `declare(strict_types=1);` ou assinaturas tipadas.
- **Transações de Banco**: Operações que alteram status de visita, baixam estoque ou geram lançamentos no fluxo de caixa devem usar `DB::transaction(fn() => ...)`.
- **Policies e Gates**: Utilize Laravel Policies para checagem de autorização (`VisitaPolicy`, `ClientePolicy`, `FinanceiroPolicy`).
- **Formatação de Código**: Execute sempre `vendor/bin/pint` antes de finalizar modificações.

### 4.2. Frontend (Blade, CSS e JS)
- **Tema Light/Dark**: Uso exclusivo das variáveis do design system (`var(--color-surface-*)`, `var(--color-primary-*)`, `var(--form-text)`).
- **Proibição Estrita de `font-mono`**: **Nunca aplicar a classe `font-mono` nos elementos de interface**. Toda a tipografia da aplicação (incluindo inputs numéricos, valores monetários, tabelas, cards de resumo, formulários e relatórios) deve adotar estritamente a tipografia sans-serif padrão do sistema (`Instrument Sans` / `font-sans`), mantendo a estética visual limpa, corporativa e coesa.
- **Padronização Global de Botões de Exclusão**:
  - Em todas as tabelas de listagem (Clientes, Produtos, Técnicos, Equipamentos, Comodatos, Visitas, Contratos, Unidades, Perfis, Financeiro), os botões de exclusão/descarte devem utilizar obrigatoriamente a classe `.btn-danger-soft btn-sm` acompanhada do ícone SVG de lixeira (`w-4 h-4`, `stroke-width="2"`) e tooltip explicativo (`title="Excluir"` ou `"Mover para a Lixeira"`).
  - É proibido utilizar botões vermelhos sólidos (`.btn-danger`) ou botões neutros cinza (`.btn-secondary`) para ações de remoção em linhas de tabelas.
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
| **Gerar Visitas de Contratos (90 dias)** | `php artisan contratos:gerar-visitas` |

---

## 6. Instruções Específicas para Agentes de IA

1. **Nunca quebrar a Regra de Sigilo Comercial**: Jamais insira detalhamento de doses, custos internos ou diárias técnicas em views ou impressos de proposta destinados ao cliente final.
2. **Preservar Baixa Atômica Fracionada**: Sempre utilize o fator de conversão ao abater produtos do estoque.
3. **Persistência Limpa de Documentos**: Ao manipular `documento`, `telefone` ou `cep`, garanta que o salvamento no banco remova toda a pontuação através de `MascaraHelper::apenasNumeros()`.
4. **Uso Mandatório de `whereDate`**: Nunca compare datas usando strings em SQLite/MySQL; utilize sempre `whereDate()`.
5. **Notificações Exclusivas via Navegador**: Alertas de agendamento são emitidos apenas pela API Web (`Notification` e Service Worker), sem chamadas locais de servidor.
6. **Links Clicáveis em Respostas**: Sempre cite arquivos com sintaxe markdown clicável `file:///` (ex: [VisitaController.php](file:///z:/home/mh/projetos/erp-alaris/app/Http/Controllers/VisitaController.php)).
7. **Segurança de Rotas**: Todas as rotas financeiras e de configurações devem exigir validação do papel `admin`.
8. **Proibição de `font-mono` em Classes**: Jamais utilize a classe Tailwind `font-mono` em templates Blade, componentes ou injeções dinâmicas JavaScript. Toda a tipografia de valores monetários, formulários e números segue a fonte padrão (`font-sans`).
