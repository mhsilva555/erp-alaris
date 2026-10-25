# SPEC.md — Especificação Funcional e Técnica: ERP Alaris

Documento de especificação técnica e de requisitos do **ERP Alaris** (anteriormente Biosp) — plataforma integrada de gestão operacional e financeira para empresas de controle de pragas urbanas, saúde ambiental, dedetização e serviços de higienização.

---

## 1. Visão Geral do Produto

### 1.1. Objetivo
Centralizar e automatizar a operação de ponta a ponta da empresa **Alaris Service**:
- **Ciclo Comercial e Funil de Clientes**: Captação, canais de aquisição, prospecção ativa e follow-up automatizado integrado à agenda.
- **Gestão Operacional de Campo**: Emissão de Ordens de Serviço (OS), rastreamento de equipamentos em comodato (porta-iscas), rotinas de reposição preventiva e atendimento técnico.
- **Engenharia de Estoque e Conversão Fracionada**: Suporte a produtos líquidos, pós e sólidos, com cálculo proporcional exato de custo por dose/fração aplicada.
- **Agenda Geral Unificada**: Ponto focal para o setor de atendimento e comercial, sincronizando visitas técnicas, follow-ups de prospecção e rotinas de manutenção de comodato.
- **Orçamentos e Propostas com Proteção de Margem**: Emissão de propostas limpas para o cliente (apenas serviço e valor final, sem exposição de dosagens, custos de produtos ou diárias).
- **Controladoria Financeira e Formação de Preço**: Composição de custo por visita (produtos + diária/mão de obra + deslocamento + impostos), opção de Nota Fiscal com alíquota parametrizável, fluxo de caixa e relatório gerencial de Lucro Bruto.
- **Perfis de Acesso Segregados**: Separação clara entre Gestor/Administrador e Técnicos de Campo.

### 1.2. Perfis de Usuário (ACL)
1. **Administrador / Gestor Operacional (`admin`)**:
   - Acesso irrestrito a configurações fiscais, margens de lucro, relatórios gerenciais, fluxo de caixa, catálogo de produtos e custos unitários.
   - Gerenciamento de acessos e criação de credenciais de usuários e técnicos.
2. **Técnico de Campo (`tecnico`)**:
   - Acesso estritamente restrito via login individual.
   - Visualização focada na sua própria escala de agendamentos e ordens de serviço designadas.
   - Lançamento de dados técnicos de execução (área tratada, produtos aplicados, doses, equipamentos instalados).
   - **Bloqueio total de visibilidade e sigilo comercial**:
     - Não visualiza relatórios financeiros da empresa, fluxo de caixa, custos de compra de insumos, margens de lucro ou taxas tributárias.
     - Botão e rota de *"Proposta Comercial (Sigilosa)"* estritamente ocultos e bloqueados (`abort(403)`).
     - Alerta de faturamento a receber no modal de finalização de OS rápida exibido exclusivamente para administradores.

---

## 2. Arquitetura de Informação e Módulos

### 2.1. Módulo de Autenticação, Perfis e Segurança
- **Controle de Acesso Baseado em Papéis (RBAC)**: Usuários possuem a coluna `role` (`admin` ou `tecnico`) e opcionalmente uma chave estrangeira `tecnico_id` vinculando ao registro operacional.
- **Auto-Provisionamento de Emergência**:
  - Se a tabela `users` estiver vazia na tela de login, provisiona o administrador padrão: `admin@alaris.com` / `12345678`.
- **Gestão de Usuários**: Aba de Configurações/Perfil permite a criação e manutenção de logins para cada técnico com definição de status ativo/inativo.
- **Políticas de Acesso (Policies/Gates)**:
  - Rotas financeiras (`/financeiro/*`, `/relatorios/*`, `/configuracoes/*`) são protegidas por middleware restrito ao papel `admin`.
  - Técnicos enxergam apenas visitas onde `visitas.tecnico_id == auth()->user()->tecnico_id`.

### 2.2. Módulo de Clientes e Gestão de Prospecção
- **Dados Cadastrais Principais**: Nome/Razão Social, Documento (CPF/CNPJ), E-mail, Telefone/WhatsApp, Endereço completo, Cidade, Estado (UF obrigatória) e Observações de acesso.
- **Canal de Origem / Aquisição (`origem`)**:
  - Identificação de onde o lead veio: `Trafego Pago`, `Indicacao`, `Prospeccao Ativa`, `Redes Sociais`, `Site/Organico`, `Parceria`, `Outros`.
- **Periodicidade de Atendimento (`periodicidade`)**:
  - Frequência contratual ou recomendada para aquele cliente: `Avulso`, `Quinzenal`, `Mensal`, `Bimestral`, `Trimestral`, `Semestral`, `Anual`.
- **Funil de Prospecção e Status (`status_prospeccao`)**:
  - `ativo`: Cliente com serviços vigentes ou contratos recorrentes.
  - `em_negociacao`: Proposta enviada aguardando aceite.
  - `prospeccao`: Em fase de qualificação ou sondagem inicial.
  - `retornar_3_meses`: Cliente não fechou de imediato; agendar contato em 90 dias.
  - `contrato_concorrente`: Cliente com contrato ativo em outra empresa; data prevista de encerramento registrada para abordagem estratégica.
  - `inativo`: Cliente cancelado ou sem movimentação.
- **Data de Follow-up (`data_followup`)**:
  - Quando preenchida (ex.: nos casos de retorno futuro ou vencimento do concorrente), injeta automaticamente uma entrada de lembrete na **Agenda Geral Unificada**.
- **Dossiê do Cliente (`clientes.show`)**:
  - Painel com histórico completo: visitas realizadas, equipamentos em comodato ativos, histórico de orçamentos e linha do tempo de interações.
- **Filtros da Listagem (`clientes.index`)**:
  - Busca textual (nome, CPF/CNPJ, telefone, cidade), filtro por `status_prospeccao`, filtro por `periodicidade` (avulso, quinzenal, mensal, bimestral, trimestral, semestral, anual) e filtro por `origem`, com botão de limpeza rápida de filtros.

### 2.3. Módulo Técnico: Gestão de Equipamentos, Comodato e O.S.
- **Catálogo & Gestão de Equipamentos (`equipamentos`)**:
  - Cadastro de ativos físicos do patrimônio da empresa (ex.: *Porta-Iscas Túnel*, *Armadilha Luminosa 30W*, *Pulverizador Manual/Motorizado*, *Atomizador Costal*).
  - **Categorias Dinâmicas (`categoria_equipamentos`)**:
    - Gerenciamento direto através do botão "Categorias" no cabeçalho do módulo, abrindo modal interativo.
    - Listagem das categorias cadastradas com contador de equipamentos vinculados.
    - Adição instantânea de novas categorias sem necessidade de recarregar a página.
    - Exclusão permitida apenas para categorias sem equipamentos vinculados.
  - Controle de estoque duplo:
    - **Quantidade Total (`quantidade_total`)**: Estoque patrimonial total adquirido.
    - **Quantidade Disponível (`quantidade_disponivel`)**: Saldo pronto no almoxarifado/depósito para novas alocações.
  - Indicadores no topo do módulo: Total de Modelos, Estoque Físico Total, Quantidade em Comodato (instalados em clientes) e Saldo Disponível para novas alocações.
  - Bloqueio de exclusão quando o equipamento possui contratos de comodato ativos vinculados.
- **Gestão de Comodato (`equipamentos_comodato`)**:
  - Alocação de ativos no endereço do cliente com vínculo obrigatório ou opcional ao catálogo de `equipamentos`.
  - **Validação de Saldo Disponível**: Na criação de um novo comodato, o sistema lista os equipamentos ativos disponíveis e valida tanto no frontend (UX em tempo real com `max` e aviso visual) quanto no backend (`ValidationException`) se a quantidade solicitada não excede a `quantidade_disponivel`.
  - **Baixa e Restituição Atômica no Estoque (`DB::transaction`)**:
    - Ao cadastrar um comodato, a quantidade é deduzida de `quantidade_disponivel`.
    - Ao atualizar a quantidade ou trocar o equipamento do comodato, os saldos são recalculados e ajustados.
    - Ao excluir um comodato, o saldo é restituído integralmente para `quantidade_disponivel`.
  - Vínculo direto ao insumo químico do estoque: Cada equipamento possui um `produto_id` associado (ex.: *Raticida Bloco Parafinado 20g* associado ao *Porta-Iscas*).
  - **Periodicidade de Reposição (`periodicidade_dias`)**: Campo editável que define a frequência de inspeção e substituição das iscas (ex.: a cada 15 dias, a cada 30 dias).
  - Cálculo automático da **Próxima Reposição**: Data da última troca + periodicidade em dias, gerando alerta de reabastecimento na agenda.
- **Ordem de Serviço (OS Operacional)**:
  - Documento de execução para a equipe de campo contendo:
    - Identificação do Cliente, endereço e contatos.
    - Tipo de Serviço: Implantação de Comodato, Manutenção/Reposição de Equipamentos ou Tratamento Químico Geral.
    - Relação de equipamentos a revisar/instalar.
    - **Carga de Produtos**: Quantidade exata de insumos que o técnico deve retirar do estoque e levar para o serviço.

### 2.4. Módulo de Produtos, Estoque e Conversão Fracionada
- **Naturezas e Unidades de Medida**:
  - **Líquidos**: Compra/Armazenamento em Litros (`L`), aplicação dosada em Mililitros (`mL`).
  - **Pós / Sólidos**: Compra/Armazenamento em Quilos (`Kg`), aplicação dosada em Gramas (`g`).
  - **Unidades / Peças**: Peças (`Un`), Frascos, Blocos, Sachês.
- **Seletores Padronizados com `<optgroup>`s**:
  - Os campos de "Unidade Estoque" (`unidade_armazenamento`) e "Unidade Dose" (`unidade_aplicacao`) utilizam elementos `<select>` com agrupamentos por natureza para evitar digitação inconsistente.
  - O sistema calcula e preenche automaticamente o **Fator de Conversão** (`fator_conversao`) com base na combinação selecionada (ex.: Litro -> mL = 1000; Kg -> g = 1000; Un -> Un = 1).
- **Cálculo de Custo Proporcional**:
  - Cada produto armazena seu custo de aquisição por unidade base (`preco_custo_unitario`).
  - A aplicação em campo registra a quantidade na unidade de dose (`mL` ou `g`).
  - O sistema converte automaticamente a dose para a unidade fracionária e calcula o custo consumido:
    $$\text{Custo da Aplicação} = \frac{\text{Dose Utilizada em mL}}{\text{Volume Base em mL}} \times \text{Preço de Custo Base}$$
    *(Exemplo: Frasco de 1.000 mL com custo de R$ 150,00 -> dose de 20 mL gera custo proporcional de R$ 3,00).*
- **Controle de Ponto de Pedido**:
  - Alerta automático quando `quantidade_estoque <= estoque_minimo`.

### 2.5. Módulo de Visitas, Categorização e Agenda Geral Unificada
- **Categorização da Visita (`tipo_visita`)**:
  - `comercial`: Visita de avaliação, vistoria técnica inicial ou apresentação comercial.
  - `tecnica`: Inspeção diagnóstica ou suporte especializado.
  - `demonstracao`: Aplicação teste em cliente estratégico.
  - `execucao`: Execução padrão do serviço contratado.
- **Tipos de Serviço (`tipo_servico`)**:
  - `controle_pragas`: Foco central (Desinsetização, Desratização, Descupinização, Manejo de Pragas).
  - `limpeza`: Higienização, sanitização de ambientes, limpeza de caixas d'água e reservatórios (estruturado para expansão de linha de serviços).
- **Agenda Geral Unificada (`/agendamentos`)**:
  - Visão consolidada em lista/calendário mensal integrando três fontes de eventos:
    1. **Visitas e Ordens de Serviço**: Ordens de serviço e visitas cadastradas com status real (Agendada, Em andamento, Concluída).
       - **Regra de Visibilidade para OS de Comodato**: Se o usuário logado for **Administrador**, ele não visualiza OSs vinculadas a Comodatos (`comodato_id != null`) separadamente na agenda, pois o próprio card do Evento de Comodato agrega e exibe as ordens de serviço geradas. Usuários com perfil **Técnico** visualizam normalmente as OSs atribuídas a si.
    2. **Manutenção e Reposição de Comodato com Projeção Recorrente**: Projeção dinâmica de todos os ciclos de reposição dentro do mês selecionado com base em `data_proxima_reposicao` e `periodicidade_dias` (ex.: comodato com ciclo de 15 dias exibe tanto a âncora original quanto as recorrências subsequentes do mês com o badge `Previsto (Recorrente)`).
    3. **Visitas Contratuais Recorrentes de Clientes Ativos**: Projeção automática de visitas para clientes com periodicidade contratual (`quinzenal`, `mensal`, `bimestral`, `trimestral`, `semestral`, `anual`) quando ainda não há OS física criada para aquele ciclo, com botão direto `Agendar OS` que pré-preenche o cliente e a data estimada. Ao criar a OS real, o item projetado é substituído imediatamente pela OS física correspondente.
    4. **Follow-ups de Prospecção**: Clientes com retorno agendado (ex.: "Retornar em 3 meses", "Vencimento contrato concorrente").
- **Precedência Operacional na Listagem de Visitas & OS (`/visitas`)**:
  - Ordens de serviço e atendimentos com status `agendada` e `em_andamento` possuem ordenação prioritária no topo da listagem de visitas, independentemente da data agendada, garantindo visibilidade imediata das pendências operacionais críticas da equipe.
- **Filtros e Ações da Agenda**:
  - Filtro por mês/ano, categoria de evento (Todos, Visitas, Comodatos, Follow-ups) e técnico responsável.
  - Botão de remoção individual de eventos e agendamento rápido de recorrências.

### 2.6. Módulo de Orçamentos e Propostas (Visão do Cliente)
- **Regra de Ouro — Sigilo de Custos Internos**:
  - Ao gerar a proposta impressa, PDF ou tela de visualização do cliente:
    - **Exibição Permitida**: Descrição do Serviço, Área/Metragem tratada, Garantia técnica, Condições de pagamento e **Valor Final**.
    - **Ocultação Mandatória**: Quantidades exatas de mL ou gramas aplicados, nomes técnicos de defensivos se configurado em sigilo, custos proporcionais de insumos, valor da diária do técnico, custos de transporte e margem de lucro.
  - Os custos internos ficam disponíveis exclusivamente no painel administrativo para os usuários com papel `admin`.

### 2.7. Módulo Financeiro, Formação de Preço, DRE e Fluxo de Caixa
- **Formação de Preço da Visita / OS**:
  - A visita possui uma estrutura detalhada de custos diretos operacionais:
    - **Custo de Produtos**: Somatório automático do custo proporcional de todos os defensivos aplicados.
    - **Mão de Obra / Diária do Técnico**: Remuneração da diária técnica padrão ou personalizada por visita (garantida inclusive quando a OS é aberta a partir de um comodato).
    - **Deslocamento / Transporte (`custo_deslocamento`)**: Custo com combustível, pedágio ou taxa de transporte da equipe.
    - **Gastos Adicionais (`gastos_adicionais`)**: Despesas imprevistas ocorridas na execução de campo (ex.: compra de conectores extras, estacionamento) com justificativa (`motivo_gastos_adicionais`).
    - **Subtotal de Custos Operacionais**:
      $$\text{Custo Operacional} = \text{Produtos} + \text{Mão de Obra} + \text{Deslocamento} + \text{Gastos Adicionais}$$
- **Impostos e Alíquota de Nota Fiscal**:
  - Campo booleano: `exige_nota_fiscal` (`true`/`false`).
  - Se marcado como `true`, o sistema aplica o acréscimo percentual configurável de impostos (ex.: 12% a 14%):
    $$\text{Valor do Imposto} = \text{Valor Bruto} \times \frac{\text{Acréscimo Imposto \%}}{100}$$
  - Parâmetros tributários gerenciáveis na tela de **Configurações Financeiras**.
  - *Nota*: Nesta fase, o sistema não realiza emissão de NFS-e junto à prefeitura/SEFAZ; a rotina é estritamente de controle gerencial e cálculo de margens.
- **Margem e Lucro Bruto por Visita**:
  $$\text{Lucro Bruto da OS} = \text{Valor Cobrado} - (\text{Custo Operacional} + \text{Impostos})$$
- **Ações em Massa no Fluxo de Caixa (`financeiro.acoes-massa`)**:
  - Tabela financeira com checkboxes de seleção individual e seleção geral.
  - Barra de ações em lote permitindo que administradores executem em um único clique:
    - **Liquidar Lançamentos**: Marca todas as movimentações selecionadas como pagas (`status = 'paga'`) com data de pagamento atual.
    - **Reabrir Lançamentos**: Retorna movimentações para o status pendente (`status = 'pendente'`).
    - **Excluir Lançamentos**: Remove os registros selecionados em lote sob transação de banco.
- **Sincronização Atômica entre OS e Fluxo de Caixa (`sincronizarMovimentacoesFinanceiras`)**:
  - Ao criar, editar ou finalizar uma OS concluída:
    1. A movimentação de faturamento (receita) é criada ou atualizada com `data_competencia` idêntica à data de execução da OS (`$visita->data_hora->toDateString()`), prevenindo descasamento entre o mês da OS e o fluxo financeiro.
    2. A movimentação de diária técnica e de gastos adicionais é criada ou atualizada sem duplicar registros a cada edição.
    3. Ao cancelar uma OS, lançamentos pendentes são expurgados do fluxo para manter previsões limpas.
    4. Ao excluir uma OS, as movimentações financeiras atreladas são deletadas em transação atômica (`DB::transaction`), evitando registros órfãos que poluiriam despesas fixas gerais da empresa.
- **Painel de Auditoria & Conciliação Cruzada: DRE vs Fluxo de Caixa**:
  - No Relatório de Lucro Bruto (`/financeiro/relatorios`), painel executivo confronta:
    - **Faturamento OS (Competência DRE)** vs **Recebido Efetivo no Caixa** vs **Pendente no Contas a Receber**.
    - **Taxa de Liquidação da Receita (%)**.
    - **Diárias Técnicas Apuradas vs Pagas vs Pendentes**.
    - **Status de Recebimento por OS**: Coluna na tabela de OS indicando se cada serviço foi `Recebido`, está `A Receber`, `Isento` ou `Cancelado`.
  - No Fluxo de Caixa (`/financeiro`), banner superior exibe o resumo de alinhamento de OSs concluídas do mês e botão de atalho direto para auditar o DRE do mesmo período.
- **Exportação do Relatório Gerencial em PDF no Backend**:
  - Rota `GET /financeiro/relatorios/pdf` (`financeiro.relatorio.pdf`) gera documento corporativo em formato A4 Paisagem via DomPDF, contendo cabeçalho Alaris Service, KPIs, painel de conciliação e tabela detalhada de OSs.
- **Seletor de Data Amigável (`<x-month-picker>`)**:
  - Componente Blade reaproveitável com navegação facilitada mês anterior/próximo e trigger automático para atualização do relatório e fluxo de caixa.

### 2.8. Módulo de Notificações, Monitoramento em Tempo Real & Cron
- **Monitoramento Automático Minuto a Minuto**:
  - Comando `php artisan agendamentos:verificar-notificacoes` agendado no Laravel Scheduler (`routes/console.php`) para execução contínua `everyMinute()->withoutOverlapping()->runInBackground()`.
  - Instalador automatizado multiplataforma: `php artisan cron:install` instala a entrada correspondente no crontab (Linux/WSL) ou no Agendador de Tarefas do Windows (`schtasks`).
  - Suporte às opções `--status` para diagnóstico e `--uninstall` para desativação limpa.
- **Tríplice Pilar de Monitoramento do Agendamento**:
  1. **Visitas Iminentes**: Visitas agendadas para os próximos 60 minutos (atribuição direta ao técnico ou aviso global aos gestores).
  2. **Reposições de Comodato**: Equipamentos com vencimento de reposição de iscas programado para hoje ou já em atraso.
  3. **Follow-ups Comerciais**: Leads e clientes com data de retorno programada para a data corrente.
  - **Deduplicação Inteligente**: Registros gerados possuem chave de unicidade diária por evento para evitar notificações duplicadas.
- **Emissão Exclusiva via Navegador Web (Desktop & Mobile)**:
  - Notificações nativas no Sistema Operacional (Windows, macOS, Linux, Android e iOS PWA) emitidas estritamente através da **Web Notifications API** e **Service Worker** (`public/sw.js`).
  - O sistema **não utiliza** popups locais do servidor (PowerShell / Windows Forms); a emissão respeita a arquitetura web padrão através de navegadores modernos (Google Chrome, Firefox, Safari e Edge).
- **Central de Notificações no Header**:
  - Dropdown interativo totalmente integrado ao **Design System ERP Alaris** (suporte aos modos Light e Dark com variáveis `var(--color-surface-*)`).
  - Indicador numérico em tempo real de não-lidas, marcação individual de leitura (`POST /notificacoes/agendamentos/{id}/marcar-lida`) e marcação em lote (`POST /notificacoes/agendamentos/marcar-todas-lidas`).
  - **Ocultação Inteligente de Permissão**: Banners e avisos de solicitação de permissão de notificação são ocultados automaticamente no frontend assim que o usuário concede acesso (`Notification.permission === 'granted'`).

### 2.9. Usabilidade de Entrada: Máscaras de Dados e Persistência Limpa
- **Máscaras Dinâmicas no Frontend**:
  - Enriquecimento progressivo sem dependências pesadas, acionado por atributos `data-mask`:
    - `data-mask="documento"`: Alterna automaticamente entre CPF (`000.000.000-00`) e CNPJ (`00.000.000/0000-00`) de acordo com o comprimento inserido.
    - `data-mask="telefone"`: Alterna entre telefone fixo (`(00) 0000-0000`) e celular (`(00) 00000-0000`) com 9 dígitos.
    - `data-mask="cep"`: Formata automaticamente para `00000-000`.
- **Persistência Backend Limpa (Regra Invariante)**:
  - Todos os campos mascaráveis (`documento`, `telefone`, `cep`) são **estritamente limpos** antes de gravar no banco de dados, mantendo apenas dígitos numéricos (`MascaraHelper::apenasNumeros()`).
  - Garantia dupla através de Mutators nos modelos (`Cliente`, `Tecnico`) e sanitização explícita nos formulários de criação/edição.
  - **Accessors Formatados**: Disponibilização de propriedades calculadas (`$cliente->documento_formatado`, `$cliente->telefone_formatado`, `$cliente->cep_formatado`, `$tecnico->telefone_formatado`) para renderização formatada nas views e relatórios.
  - **Busca Híbrida**: O mecanismo de pesquisa permite localizar registros pesquisando tanto pela string formatada quanto apenas pelos dígitos numéricos.

---

## 3. Modelo de Dados (Data Dictionary Expandido)

### 3.1. Tabela `users`
| Coluna | Tipo | Nulo | Default | Descrição |
| :--- | :--- | :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED | Não | Auto Increment | Identificador único |
| `name` | VARCHAR(255) | Não | - | Nome completo |
| `email` | VARCHAR(255) | Não | - | E-mail corporativo (único) |
| `password` | VARCHAR(255) | Não | - | Hash bcrypt |
| `role` | VARCHAR(20) | Não | `'admin'` | `'admin'` ou `'tecnico'` |
| `tecnico_id` | BIGINT UNSIGNED | Sim | NULL | FK -> `tecnicos.id` se role for técnico |
| `remember_token` | VARCHAR(100) | Sim | NULL | Token de sessão |
| `created_at` / `updated_at` | TIMESTAMP | Sim | NULL | Timestamps de auditoria |

### 3.2. Tabela `clientes`
| Coluna | Tipo | Nulo | Default | Descrição |
| :--- | :--- | :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED | Não | Auto Increment | Identificador único |
| `nome` | VARCHAR(255) | Não | - | Nome ou Razão Social |
| `documento` | VARCHAR(20) | Sim | NULL | CPF ou CNPJ |
| `email` | VARCHAR(255) | Sim | NULL | E-mail de contato |
| `telefone` | VARCHAR(20) | Sim | NULL | Telefone / WhatsApp |
| `endereco` | VARCHAR(255) | Sim | NULL | Endereço completo |
| `cidade` | VARCHAR(100) | Não | - | Cidade |
| `estado` | VARCHAR(2) | Não | - | UF (2 dígitos) |
| `cep` | VARCHAR(10) | Sim | NULL | CEP |
| `origem` | VARCHAR(50) | Sim | `'outros'` | Canal de captação (Trafego Pago, Indicacao, etc.) |
| `periodicidade` | VARCHAR(50) | Sim | `'avulso'` | Frequência de atendimento |
| `status_prospeccao`| VARCHAR(50) | Não | `'ativo'` | `ativo`, `em_negociacao`, `retornar_3_meses`, etc. |
| `data_followup` | DATE | Sim | NULL | Data de retorno futuro na agenda |
| `observacoes` | TEXT | Sim | NULL | Restrições ou detalhes do local |
| `created_at` / `updated_at` | TIMESTAMP | Sim | NULL | Timestamps de auditoria |

### 3.3. Tabela `tecnicos`
| Coluna | Tipo | Nulo | Default | Descrição |
| :--- | :--- | :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED | Não | Auto Increment | Identificador único |
| `nome` | VARCHAR(255) | Não | - | Nome do profissional |
| `telefone` | VARCHAR(20) | Sim | NULL | Contato |
| `email` | VARCHAR(255) | Sim | NULL | E-mail profissional |
| `especialidade` | VARCHAR(255) | Sim | NULL | Ex.: Desinsetização, Roedores, Cupins |
| `valor_diaria_padrao` | DECIMAL(10,2) | Não | `50.00` | Valor padrão de mão de obra por serviço |
| `ativo` | BOOLEAN | Não | `true` | Disponível na escala |
| `created_at` / `updated_at` | TIMESTAMP | Sim | NULL | Timestamps de auditoria |

### 3.4. Tabela `produtos`
| Coluna | Tipo | Nulo | Default | Descrição |
| :--- | :--- | :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED | Não | Auto Increment | Identificador único |
| `nome` | VARCHAR(255) | Não | - | Nome comercial do produto/insumo |
| `natureza` | VARCHAR(20) | Não | `'liquido'` | `'liquido'`, `'solido'`, `'unidade'` |
| `unidade_armazenamento`| VARCHAR(10)| Não | `'L'` | Unidade do estoque principal (`L`, `Kg`, `Un`) |
| `unidade_aplicacao` | VARCHAR(10) | Não | `'mL'` | Unidade fracionada de uso (`mL`, `g`, `Un`) |
| `fator_conversao` | DECIMAL(10,4)| Não | `1000.0000` | Ex.: 1 L = 1000 mL, 1 Kg = 1000 g |
| `quantidade_estoque` | DECIMAL(12,4)| Não | `0.0000` | Saldo físico em estoque |
| `estoque_minimo` | DECIMAL(12,4)| Não | `0.0000` | Limite de alerta de reposição |
| `preco_custo_unitario`| DECIMAL(10,2)| Não | `0.00` | Preço de custo por unidade de armazenamento |
| `descricao` | TEXT | Sim | NULL | Princípio ativo / registro ANVISA |
| `created_at` / `updated_at` | TIMESTAMP | Sim | NULL | Timestamps de auditoria |

### 3.5. Tabela `categoria_equipamentos`
| Coluna | Tipo | Nulo | Default | Descrição |
| :--- | :--- | :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED | Não | Auto Increment | Identificador único da categoria |
| `nome` | VARCHAR(100) | Não | - | Nome exibido (ex.: Porta-Iscas / Túnel, Gaiolas) |
| `slug` | VARCHAR(100) | Não | - | Slug único para identificação técnica |
| `ativo` | BOOLEAN | Não | `true` | Categoria ativa para seleção |
| `created_at` / `updated_at` | TIMESTAMP | Sim | NULL | Timestamps de auditoria |

### 3.6. Tabela `equipamentos`
| Coluna | Tipo | Nulo | Default | Descrição |
| :--- | :--- | :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED | Não | Auto Increment | Identificador único do equipamento |
| `nome` | VARCHAR(255) | Não | - | Nome comercial (ex.: Porta-Iscas Túnel) |
| `modelo` | VARCHAR(255) | Sim | NULL | Modelo / Fabricante |
| `tipo` | VARCHAR(50) | Não | `'porta_iscas'` | Slug da categoria (`categoria_equipamentos.slug`) |
| `quantidade_total` | INT UNSIGNED | Não | `0` | Quantidade total física do patrimônio |
| `quantidade_disponivel` | INT UNSIGNED | Não | `0` | Saldo disponível no almoxarifado para comodato |
| `valor_unitario` | DECIMAL(10,2) | Sim | `0.00` | Custo patrimonial unitário |
| `descricao` | TEXT | Sim | NULL | Especificações técnicas do equipamento |
| `ativo` | BOOLEAN | Não | `true` | Disponível para novos comodatos |
| `created_at` / `updated_at` | TIMESTAMP | Sim | NULL | Timestamps de auditoria |

### 3.7. Tabela `equipamentos_comodato`
| Coluna | Tipo | Nulo | Default | Descrição |
| :--- | :--- | :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED | Não | Auto Increment | Identificador do comodato |
| `cliente_id` | BIGINT UNSIGNED | Não | - | FK -> `clientes.id` (cascade) |
| `equipamento_id` | BIGINT UNSIGNED | Sim | NULL | FK -> `equipamentos.id` (null on delete / restringe se ativo) |
| `produto_id` | BIGINT UNSIGNED | Sim | NULL | FK -> `produtos.id` (insumo químico atrelado à reposição) |
| `nome_equipamento` | VARCHAR(255) | Não | - | Ex.: Porta-Isca Externo Modelo Túnel |
| `quantidade` | INT UNSIGNED | Não | `1` | Número de unidades instaladas |
| `periodicidade_dias`| INT UNSIGNED | Não | `15` | Frequência de reposição (editável) |
| `data_instalacao` | DATE | Não | - | Data de início do comodato |
| `data_ultima_reposicao`| DATE | Sim | NULL | Data em que as iscas foram trocadas |
| `data_proxima_reposicao`| DATE | Não | - | Data programada para retorno e troca |
| `observacoes` | TEXT | Sim | NULL | Localização dos pontos na planta do cliente |
| `created_at` / `updated_at` | TIMESTAMP | Sim | NULL | Timestamps de auditoria |

### 3.8. Tabela `visitas`
| Coluna | Tipo | Nulo | Default | Descrição |
| :--- | :--- | :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED | Não | Auto Increment | Identificador único |
| `cliente_id` | BIGINT UNSIGNED | Não | - | FK -> `clientes.id` (cascade) |
| `tecnico_id` | BIGINT UNSIGNED | Sim | NULL | FK -> `tecnicos.id` |
| `comodato_id` | BIGINT UNSIGNED | Sim | NULL | FK -> `equipamentos_comodato.id` (null on delete) |
| `data_hora` | DATETIME | Não | - | Horário agendado |
| `status` | VARCHAR(20) | Não | `'agendada'` | `agendada`, `em_andamento`, `concluida`, `cancelada` |
| `tipo_visita` | VARCHAR(30) | Não | `'execucao'` | `comercial`, `tecnica`, `demonstracao`, `execucao` |
| `tipo_servico` | VARCHAR(30) | Não | `'controle_pragas'`| `controle_pragas`, `limpeza` |
| `tipo_praga` | VARCHAR(255) | Sim | NULL | Praga-alvo |
| `area_tratada` | VARCHAR(255) | Sim | NULL | Dimensões/cômodos tratados |
| `proxima_visita` | DATETIME | Sim | NULL | Data de retorno preventivo |
| `valor_cobrado` | DECIMAL(10,2) | Não | `0.00` | Preço final cobrado do cliente |
| `custo_produtos` | DECIMAL(10,2) | Não | `0.00` | Custo somado dos insumos aplicados |
| `custo_mao_de_obra`| DECIMAL(10,2) | Não | `0.00` | Diária/remuneração do técnico |
| `custo_deslocamento`| DECIMAL(10,2)| Não | `0.00` | Combustível e transporte |
| `gastos_adicionais` | DECIMAL(10,2)| Não | `0.00` | Gastos operacionais imprevistos/adicionais da visita |
| `motivo_gastos_adicionais` | VARCHAR(255) | Sim | NULL | Descrição/motivo dos gastos adicionais (pedágio, etc.) |
| `exige_nota_fiscal`| BOOLEAN | Não | `false` | Se cliente exige NF |
| `percentual_imposto`| DECIMAL(5,2) | Não | `0.00` | Alíquota de imposto aplicada (ex.: 12.00%) |
| `valor_imposto` | DECIMAL(10,2) | Não | `0.00` | Valor calculado de imposto |
| `lucro_bruto` | DECIMAL(10,2) | Não | `0.00` | Valor cobrado - custos totais - imposto |
| `observacoes` | TEXT | Sim | NULL | Observações de campo |
| `created_at` / `updated_at` | TIMESTAMP | Sim | NULL | Timestamps de auditoria |

### 3.9. Tabela `visita_produto` (Pivot com Custo Fracionado)
| Coluna | Tipo | Nulo | Default | Descrição |
| :--- | :--- | :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED | Não | Auto Increment | Identificador único |
| `visita_id` | BIGINT UNSIGNED | Não | - | FK -> `visitas.id` (cascade) |
| `produto_id` | BIGINT UNSIGNED | Não | - | FK -> `produtos.id` (cascade) |
| `quantidade_aplicada`| DECIMAL(10,2)| Não | - | Dose utilizada na unidade de aplicação (ex.: 25 mL) |
| `custo_proporcional` | DECIMAL(10,2)| Não | `0.00` | Custo calculado da dose aplicada |

### 3.10. Tabela `movimentacoes_financeiras` (Fluxo de Caixa)
| Coluna | Tipo | Nulo | Default | Descrição |
| :--- | :--- | :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED | Não | Auto Increment | Identificador da movimentação |
| `visita_id` | BIGINT UNSIGNED | Sim | NULL | FK -> `visitas.id` (opcional, quando atrelado a OS) |
| `tipo` | VARCHAR(10) | Não | `'receita'` | `'receita'` ou `'despesa'` |
| `categoria` | VARCHAR(50) | Não | - | Ex.: `faturamento_servico`, `compra_estoque`, `diaria_tecnico`, `impostos`, `deslocamento`, `custo_fixo` |
| `descricao` | VARCHAR(255) | Não | - | Detalhamento da operação |
| `valor` | DECIMAL(10,2) | Não | - | Montante monetário |
| `data_competencia` | DATE | Não | - | Data de competência |
| `data_pagamento` | DATE | Sim | NULL | Data de liquidação/recebimento |
| `status` | VARCHAR(20) | Não | `'pendente'` | `'pendente'`, `'pago'`, `'cancelado'` |
| `created_at` / `updated_at` | TIMESTAMP | Sim | NULL | Timestamps de auditoria |

### 3.11. Tabela `configuracoes`
| Coluna | Tipo | Nulo | Default | Descrição |
| :--- | :--- | :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED | Não | Auto Increment | Identificador |
| `chave` | VARCHAR(100) | Não | - | Identificador único (ex.: `aliquota_imposto_padrao`) |
| `valor` | TEXT | Sim | NULL | Valor armazenado (ex.: `12.5`) |
| `descricao` | VARCHAR(255) | Sim | NULL | Explicação do parâmetro |
| `created_at` / `updated_at` | TIMESTAMP | Sim | NULL | Timestamps de auditoria |

### 3.12. Tabela `notificacoes_agendamentos`
| Coluna | Tipo | Nulo | Default | Descrição |
| :--- | :--- | :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED | Não | Auto Increment | Identificador único da notificação |
| `tipo_evento` | VARCHAR(30) | Não | - | Categoria: `visita`, `comodato`, `followup` |
| `evento_id` | BIGINT UNSIGNED | Não | - | ID do registro associado (`visitas.id`, `equipamentos_comodato.id`, etc.) |
| `titulo` | VARCHAR(255) | Não | - | Título curto do alerta (ex.: "Visita Agendada: OS #10") |
| `mensagem` | TEXT | Não | - | Detalhamento da notificação |
| `url` | VARCHAR(255) | Sim | NULL | Link de redirecionamento ao clicar no alerta |
| `data_evento` | DATETIME | Não | - | Momento previsto da ocorrência |
| `data_notificacao` | DATETIME | Não | `now()` | Momento em que o alerta foi processado |
| `enviada_so` | BOOLEAN | Não | `false` | Se já foi emitida ao Sistema Operacional via navegador |
| `lida` | BOOLEAN | Não | `false` | Se foi marcada como lida pelo usuário |
| `user_id` | BIGINT UNSIGNED | Sim | NULL | Usuário destinatário (null = notificação global) |
| `created_at` / `updated_at` | TIMESTAMP | Sim | NULL | Timestamps de auditoria |

### 3.10. Tabela `perfis`
| Coluna | Tipo | Nulo | Default | Descrição |
| :--- | :--- | :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED | Não | Auto Increment | Identificador único |
| `nome` | VARCHAR(100) | Não | - | Nome legível do perfil (ex: Administrador, Gerente Operacional) |
| `slug` | VARCHAR(100) | Não | - | Identificador único padronizado (ex: admin, financeiro) |
| `descricao` | VARCHAR(255) | Sim | NULL | Finalidade e responsabilidade do perfil |
| `is_sistema` | BOOLEAN | Não | `false` | Se é perfil protegido e nativo do sistema (não deletável) |
| `created_at` / `updated_at` | TIMESTAMP | Sim | NULL | Timestamps de auditoria |

### 3.11. Tabela `permissoes`
| Coluna | Tipo | Nulo | Default | Descrição |
| :--- | :--- | :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED | Não | Auto Increment | Identificador único |
| `modulo` | VARCHAR(50) | Não | - | Módulo correspondente (ex: clientes, visitas, financeiro) |
| `nome` | VARCHAR(150) | Não | - | Título descritivo da permissão |
| `chave` | VARCHAR(100) | Não | - | Chave canônica única (ex: clientes.ver, visitas.finalizar) |
| `descricao` | VARCHAR(255) | Sim | NULL | Detalhamento da ação permitida |
| `created_at` / `updated_at` | TIMESTAMP | Sim | NULL | Timestamps de auditoria |

### 3.12. Tabela `perfil_permissao`
| Coluna | Tipo | Nulo | Default | Descrição |
| :--- | :--- | :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED | Não | Auto Increment | Identificador único da relação |
| `perfil_id` | BIGINT UNSIGNED | Não | - | FK -> `perfis.id` (cascadeOnDelete) |
| `permissao_id` | BIGINT UNSIGNED | Não | - | FK -> `permissoes.id` (cascadeOnDelete) |
| `created_at` / `updated_at` | TIMESTAMP | Sim | NULL | Timestamps de auditoria |

---

## 4. Matriz de Rotas e Endpoints

| Método | Rota | Nome da Rota | Controlador & Ação | Perfil Autorizado |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/login` | `login` | `AuthController@showLogin` | Guest |
| `POST` | `/login` | - | `AuthController@login` | Guest |
| `POST` | `/logout` | `logout` | `AuthController@logout` | Autenticado |
| `GET` | `/` | `dashboard` | `DashboardController@index` | Autenticado (visão adaptada por perfil) |
| `GET` | `/notificacoes` | `notificacoes` | `DashboardController@notificacoes` | Autenticado |
| `GET` | `/notificacoes/agendamentos/pendentes` | `notificacoes.agendamentos.pendentes` | `NotificacaoAgendamentoController@pendentes` | Autenticado |
| `POST`| `/notificacoes/agendamentos/{id}/marcar-exibida` | `notificacoes.agendamentos.marcar-exibida` | `NotificacaoAgendamentoController@marcarExibida` | Autenticado |
| `POST`| `/notificacoes/agendamentos/{id}/marcar-lida` | `notificacoes.agendamentos.marcar-lida` | `NotificacaoAgendamentoController@marcarLida` | Autenticado |
| `POST`| `/notificacoes/agendamentos/marcar-todas-lidas` | `notificacoes.agendamentos.marcar-todas-lidas` | `NotificacaoAgendamentoController@marcarTodasLidas` | Autenticado |
| `POST`| `/notificacoes/agendamentos/testar` | `notificacoes.agendamentos.testar` | `NotificacaoAgendamentoController@testar` | Autenticado |
| `GET` | `/agendamentos` | `agendamentos.index` | `AgendamentoController@index` | Autenticado (Agenda Unificada) |
| `RESOURCE` | `/clientes` | `clientes.*` | `ClienteController` | Admin / Técnico (somente leitura de clientes) |
| `RESOURCE` | `/comodatos` | `comodatos.*` | `ComodatoController` | Admin / Técnico |
| `RESOURCE` | `/tecnicos` | `tecnicos.*` | `TecnicoController` | Admin |
| `RESOURCE` | `/produtos` | `produtos.*` | `ProdutoController` | Admin / Técnico (visão sem custos para técnico) |
| `RESOURCE` | `/visitas` | `visitas.*` | `VisitaController` | Admin / Técnico (escopado para visitas próprias) |
| `GET` | `/visitas/{visita}/os` | `visitas.os` | `VisitaController@ordemServico` | Admin / Técnico |
| `GET` | `/visitas/{visita}/proposta`| `visitas.proposta`| `VisitaController@propostaCliente` | Admin / Comercial (visão sem custos internos) |
| `GET` | `/financeiro/fluxo-caixa` | `financeiro.fluxo` | `FinanceiroController@index` | Admin |
| `POST` | `/financeiro/movimentacoes`| `financeiro.store` | `FinanceiroController@store` | Admin |
| `POST` | `/financeiro/movimentacoes/acoes-em-massa` | `financeiro.acoes-massa` | `FinanceiroController@acoesEmMassa` | Admin (`financeiro.editar` ou `financeiro.deletar`) |
| `GET` | `/financeiro/relatorios` | `financeiro.relatorio`| `FinanceiroController@relatorio` | Admin (DRE e Lucro Bruto Web) |
| `GET` | `/financeiro/relatorios/pdf` | `financeiro.relatorio.pdf` | `FinanceiroController@relatorioPdf` | Admin (Relatório em PDF gerado no Backend) |
| `GET` | `/configuracoes` | `configuracoes.index` | `ConfiguracaoController@index` | Admin |
| `PUT` | `/configuracoes` | `configuracoes.update`| `ConfiguracaoController@update` | Admin |
| `RESOURCE` | `/perfis` | `perfis.*` | `PerfilController` | Admin (`permissao:perfis.ver`) |
| `GET` | `/perfil` | `perfil.edit` | `ProfileController@edit` | Autenticado |
| `PUT` | `/perfil` | `perfil.update` | `ProfileController@update` | Autenticado |

---

## 5. Requisitos Não Funcionais (RNF)

1. **Segurança e Privacidade Comercial (Mandatório)**:
   - Bloqueio estrito no backend e na camada de visualização para garantir que nenhum dado de custo, margem ou diária vaze na proposta impressa ou em telas acessíveis pelo cliente ou por técnicos.
2. **Precisão de Fracionamento**:
   - Campos de estoque e doses utilizam `DECIMAL(12,4)` ou `DECIMAL(10,2)` para suportar fracionamento exato em mililitros e gramas sem arredondamentos distorcidos.
3. **Consistência em Banco de Dados**:
   - Toda alteração de estoque, reposição de comodato e encerramento de visitas com baixa de produtos deve ser executada obrigatoriamente sob `DB::transaction()`.
4. **Tratamento de Datas e Compatibilidade SQLite / MySQL**:
   - Consultas filtrando colunas de datas (`data_followup`, `data_proxima_reposicao`, `data_hora`) devem empregar **obrigatoriamente** o helper `whereDate('coluna', '>=', $data)` e `whereDate('coluna', '<=', $data)`.
   - **Proibição**: Nunca utilizar `where('coluna', '<=', 'YYYY-MM-DD')` ou `whereBetween()` com strings puras em colunas que armazenam datetime/timestamp, pois a comparação lexicográfica em SQLite omite registros no último dia do mês (ex.: 31/10).
5. **Concorrência e Locks em SQLite**:
   - Configuração de `busy_timeout` de no mínimo 5000ms (`config/database.php`) para evitar erros de `database is locked` em sistemas de arquivos compartilhados ou no ambiente de desenvolvimento WSL 2.
6. **Desempenho e Responsividade**:
   - Layout 100% responsivo para operação mobile por técnicos no campo via smartphone.
7. **PWA e Identidade Visual Padronizada (#23b4eb)**:
   - Sistema opera como Progressive Web App instalável (PWA) com manifestos (`manifest.json` e `manifest.webmanifest`), `<meta name="theme-color">` e tokens primários centrados em `#23b4eb`, além de novos ícones oficiais em formato circular.
8. **Ergonomia e Layout Nativo Mobile (App-Like UX)**:
   - Navegação inferior fixa no smartphone (`#mobile-bottom-nav`) com 5 destinos, compensação de área segura de gestos (`env(safe-area-inset-bottom)`), rolagem principal com espaçamento seguro (`pb-24`), cabeçalho anti-quebra em linha única e central de notificações confinada à viewport do aparelho.
