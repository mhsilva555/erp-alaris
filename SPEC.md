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
   - **Bloqueio total de visibilidade**: Não visualiza relatórios financeiros da empresa, fluxo de caixa, custos de compra de insumos, margens de lucro ou taxas tributárias.

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

### 2.3. Módulo Técnico: Equipamentos em Comodato e O.S.
- **Gestão de Comodato (`equipamentos_comodato`)**:
  - Controle de ativos alocados no endereço do cliente (ex.: *Porta-Iscas modelo túnel*, *Armadilha Luminosa com placa adesiva*, *Gaiola de Captura*).
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
  - Visão consolidada em lista/calendário integrando três fontes de eventos:
    1. **Visitas Marcadas**: Ordens de serviço e visitas comerciais cadastradas pela assessoria.
    2. **Manutenção de Comodato**: Datas programadas para reposição de iscas e manutenção preventiva de equipamentos.
    3. **Follow-ups de Prospecção**: Clientes com retorno agendado (ex.: "Retornar em 3 meses", "Vencimento contrato concorrente").
- **Filtros da Agenda**: Por período (hoje, próximos 7 dias, mês), por técnico responsável, por tipo de evento e por status.

### 2.6. Módulo de Orçamentos e Propostas (Visão do Cliente)
- **Regra de Ouro — Sigilo de Custos Internos**:
  - Ao gerar a proposta impressa, PDF ou tela de visualização do cliente:
    - **Exibição Permitida**: Descrição do Serviço, Área/Metragem tratada, Garantia técnica, Condições de pagamento e **Valor Final**.
    - **Ocultação Mandatória**: Quantidades exatas de mL ou gramas aplicados, nomes técnicos de defensivos se configurado em sigilo, custos proporcionais de insumos, valor da diária do técnico, custos de transporte e margem de lucro.
  - Os custos internos ficam disponíveis exclusivamente no painel administrativo para os usuários com papel `admin`.

### 2.7. Módulo Financeiro, Formação de Preço e Fluxo de Caixa
- **Formação de Preço da Visita / OS**:
  - A visita possui uma estrutura detalhada de custos diretos:
    - **Custo de Produtos**: Somatório automático do custo proporcional de todos os insumos aplicados.
    - **Mão de Obra / Diária do Técnico**: Custo da diária ou valor fixo por serviço (ex.: R$ 50,00).
    - **Custos de Deslocamento**: Combustível, pedágio ou taxa de transporte.
    - **Subtotal de Custos Operacionais**: $\text{Custo Operacional} = \text{Produtos} + \text{Mão de Obra} + \text{Deslocamento}$.
- **Impostos e Alíquota de Nota Fiscal**:
  - Campo booleano: `exige_nota_fiscal` (`true`/`false`).
  - Se marcado como `true`, o sistema aplica o acréscimo percentual configurável de impostos (ex.: 12% a 14%):
    $$\text{Valor do Imposto} = \text{Valor Bruto} \times \frac{\text{Acréscimo Imposto \%}}{100}$$
  - Parâmetros tributários gerenciáveis na tela de **Configurações Financeiras**.
  - *Nota*: Nesta fase, o sistema não realiza emissão de NFS-e junto à prefeitura/SEFAZ; a rotina é estritamente de controle gerencial e cálculo de margens.
- **Margem e Lucro Bruto por Visita**:
  $$\text{Lucro Bruto da OS} = \text{Valor Cobrado} - (\text{Custo Operacional} + \text{Impostos})$$
- **Fluxo de Caixa (Contas a Pagar e Receber)**:
  - Registro de **Entradas** (faturamento das visitas/contratos) e **Saídas** (compra de estoque, pagamentos de técnicos, custos fixos, manutenção).
  - Status das movimentações: `pendente`, `pago`, `cancelado`.
- **Relatórios Gerenciais**:
  - Relatório semanal e mensal consolidado:
    $$\text{Lucro Líquido Operacional} = \sum \text{Faturamento Recebido} - \sum (\text{Produtos} + \text{Mão de Obra} + \text{Deslocamento} + \text{Impostos} + \text{Despesas Gerais})$$

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

### 3.5. Tabela `equipamentos_comodato`
| Coluna | Tipo | Nulo | Default | Descrição |
| :--- | :--- | :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED | Não | Auto Increment | Identificador do comodato |
| `cliente_id` | BIGINT UNSIGNED | Não | - | FK -> `clientes.id` (cascade) |
| `produto_id` | BIGINT UNSIGNED | Sim | NULL | FK -> `produtos.id` (insumo químico atrelado à reposição) |
| `nome_equipamento` | VARCHAR(255) | Não | - | Ex.: Porta-Isca Externo Modelo Túnel |
| `quantidade` | INT UNSIGNED | Não | `1` | Número de unidades instaladas |
| `periodicidade_dias`| INT UNSIGNED | Não | `15` | Frequência de reposição (editável) |
| `data_instalacao` | DATE | Não | - | Data de início do comodato |
| `data_ultima_reposicao`| DATE | Sim | NULL | Data em que as iscas foram trocadas |
| `data_proxima_reposicao`| DATE | Não | - | Data programada para retorno e troca |
| `observacoes` | TEXT | Sim | NULL | Localização dos pontos na planta do cliente |
| `created_at` / `updated_at` | TIMESTAMP | Sim | NULL | Timestamps de auditoria |

### 3.6. Tabela `visitas`
| Coluna | Tipo | Nulo | Default | Descrição |
| :--- | :--- | :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED | Não | Auto Increment | Identificador único |
| `cliente_id` | BIGINT UNSIGNED | Não | - | FK -> `clientes.id` (cascade) |
| `tecnico_id` | BIGINT UNSIGNED | Sim | NULL | FK -> `tecnicos.id` |
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
| `exige_nota_fiscal`| BOOLEAN | Não | `false` | Se cliente exige NF |
| `percentual_imposto`| DECIMAL(5,2) | Não | `0.00` | Alíquota de imposto aplicada (ex.: 12.00%) |
| `valor_imposto` | DECIMAL(10,2) | Não | `0.00` | Valor calculado de imposto |
| `lucro_bruto` | DECIMAL(10,2) | Não | `0.00` | Valor cobrado - custos totais - imposto |
| `observacoes` | TEXT | Sim | NULL | Observações de campo |
| `created_at` / `updated_at` | TIMESTAMP | Sim | NULL | Timestamps de auditoria |

### 3.7. Tabela `visita_produto` (Pivot com Custo Fracionado)
| Coluna | Tipo | Nulo | Default | Descrição |
| :--- | :--- | :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED | Não | Auto Increment | Identificador único |
| `visita_id` | BIGINT UNSIGNED | Não | - | FK -> `visitas.id` (cascade) |
| `produto_id` | BIGINT UNSIGNED | Não | - | FK -> `produtos.id` (cascade) |
| `quantidade_aplicada`| DECIMAL(10,2)| Não | - | Dose utilizada na unidade de aplicação (ex.: 25 mL) |
| `custo_proporcional` | DECIMAL(10,2)| Não | `0.00` | Custo calculado da dose aplicada |

### 3.8. Tabela `movimentacoes_financeiras` (Fluxo de Caixa)
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

### 3.9. Tabela `configuracoes`
| Coluna | Tipo | Nulo | Default | Descrição |
| :--- | :--- | :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED | Não | Auto Increment | Identificador |
| `chave` | VARCHAR(100) | Não | - | Identificador único (ex.: `aliquota_imposto_padrao`) |
| `valor` | TEXT | Sim | NULL | Valor armazenado (ex.: `12.5`) |
| `descricao` | VARCHAR(255) | Sim | NULL | Explicação do parâmetro |
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
| `GET` | `/financeiro/relatorios` | `financeiro.relatorio`| `FinanceiroController@relatorio` | Admin (DRE e Lucro Bruto) |
| `GET` | `/configuracoes` | `configuracoes.index` | `ConfiguracaoController@index` | Admin |
| `PUT` | `/configuracoes` | `configuracoes.update`| `ConfiguracaoController@update` | Admin |
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
4. **Desempenho e Responsividade**:
   - Layout 100% responsivo para operação mobile por técnicos no campo via smartphone.
