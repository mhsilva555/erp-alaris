# ERP Alaris

> **Sistema Integrado de Gestão Operacional e Financeira para Controle de Pragas Urbanas, Dedetização e Sanitização**

O **ERP Alaris** (anteriormente Biosp) é uma plataforma desenvolvida para empresas de controle de pragas, saúde ambiental e saneamento. O sistema centraliza a operação técnica de campo, o ciclo comercial de prospecção, a gestão de ativos em comodato (porta-iscas), a engenharia de estoque com conversão fracionada e a controladoria financeira com formação de preços e fluxo de caixa.

---

## 🚀 Principais Módulos e Funcionalidades

### 1. Módulo de Clientes e Prospecção
- **Canais de Aquisição/Origem**: Registro da origem do cliente (Tráfego Pago, Indicação, Prospecção Ativa, Redes Sociais, Parcerias).
- **Periodicidade de Atendimento**: Frequência de retorno recomendada ou contratada (Mensal, Bimestral, Trimestral, Avulso).
- **Funil de Prospecção & Follow-up**: Categorização de leads que não fecharam de imediato (*"Retornar em 3 meses"*, *"Contrato com concorrente a vencer"*).
- **Integração com Agenda**: Clientes com datas de retorno futuro geram lembretes automáticos na **Agenda Geral Unificada**.
- **Localização Geográfica**: Cadastro estruturado com Cidade e Estado (UF).

### 2. Módulo Técnico: Equipamentos em Comodato e O.S.
- **Gestão de Comodato**: Controle de equipamentos instalados no cliente (ex.: Porta-iscas, armadilhas luminosas).
- **Periodicidade de Reposição**: Frequência de manutenção/troca de iscas editável (ex.: a cada 15 dias).
- **Vínculo com Estoque**: Cada equipamento é atrelado ao insumo químico do estoque utilizado na recarga.
- **Geração de Ordem de Serviço (OS)**: Detalhamento para o técnico em campo (dados do cliente, serviço, implantação ou reposição, e quantidade exata de insumos a retirar do estoque).

### 3. Estoque e Conversão Fracionada
- **Múltiplas Naturezas de Produto**: Suporte a Líquidos (`L` / `mL`), Pós/Sólidos (`Kg` / `g`) e Unidades (`Un`, Frascos, Blocos).
- **Seletores Padronizados com `<optgroup>`s**: Seleção assistida por dropdown impedindo preenchimento inconsistente e calculando automaticamente o fator de conversão fracionário.
- **Cálculo de Custo Proporcional**: Apuração exata do custo por dose aplicada (ex.: frasco de 1.000 mL com custo de R$ 100 -> dose de 20 mL gera custo proporcional de R$ 2,00).
- **Baixa Automática Atômica**: Ao concluir uma OS, o estoque é debitado com conversão precisa da fração utilizada.

### 4. Visitas e Agenda Geral Unificada
- **Categorização de Visitas**: Visita Comercial, Visita Técnica, Demonstração ou Execução de Serviço.
- **Linhas de Serviço**: Controle de Pragas (foco atual) e estrutura preparada para expansão em Limpeza e Sanitização.
- **Agenda Geral Unificada**: Ponto de controle integrando visitas marcadas, reposições de comodato e follow-ups de prospecção.
  - *Regra de Exibição de Comodato*: Administradores visualizam o comodato consolidado (ocultando OSs de comodato avulsas); técnicos visualizam normalmente suas OSs atribuídas.
- **Proposta Comercial com Sigilo de Custos**:
  - Na visualização ou PDF para o cliente, são exibidos **apenas o Serviço Prestado e o Valor Final**.
  - Informações de dosagem (mL), custos de insumos, diárias técnicas e margens de lucro permanecem estritamente restritas ao painel administrativo. Botão bloqueado para técnicos (`abort(403)`).

### 5. Financeiro, Formação de Preço, DRE e Fluxo de Caixa
- **Formação de Preço por OS**: Custo proporcional de produtos + mão de obra/diária do técnico + custos de deslocamento + gastos adicionais imprevistos com justificativa.
- **Sincronização Atômica Contínua**: Espelhamento automático de receitas, diárias e despesas no Caixa com data de competência da OS (`$visita->data_hora`), sem duplicidades em edições e com expurgo de pendências em cancelamentos.
- **Conciliação Cruzada DRE x Caixa**: Painel executivo confrontando Faturamento Apurado (Competência) vs Recebido Efetivo (Caixa) vs Pendente (A Receber), taxas de liquidação e coluna de status de recebimento por OS.
- **Exportação em PDF no Backend**: Geração de relatório gerencial completo em PDF A4 Paisagem via DomPDF.
- **Seletor de Data Amigável**: Componente `<x-month-picker>` com navegação mês a mês e trigger instantâneo.

### 6. Perfis de Acesso e Segurança (ACL)
- **Papel Administrador (`admin`)**: Visão irrestrita de faturamento, margens, DRE e configurações globais.
- **Papel Técnico (`tecnico`)**: Acesso focado exclusivamente à sua própria agenda de trabalho e ordens de serviço, sem visibilidade de dados financeiros da empresa ou avisos de faturamento.

---

## 🛠️ Stack Tecnológica

- **Backend**: PHP 8.3+ / Laravel 13 (`laravel/framework` ^13.8)
- **Banco de Dados**: SQLite (desenvolvimento padrão) / MySQL (produção)
- **Frontend**: Blade Templates, Tailwind CSS v4 (`@tailwindcss/vite`), Vanilla JavaScript
- **Bundler**: Vite 8 + `laravel-vite-plugin`
- **Qualidade & Testes**: Pest PHP v5, Laravel Pint

---

## 📚 Documentação de Referência

| Documento | Descrição |
| :--- | :--- |
| **[SPEC.md](./SPEC.md)** | Especificação técnica e funcional completa, dicionário de dados de 12 tabelas e matriz de rotas |
| **[ARCHITECTURE.md](./ARCHITECTURE.md)** | Diagrama ERD expandido, fluxos de conversão fracionada, comodato e sigilo comercial |
| **[AGENTS.md](./AGENTS.md)** | Diretrizes operacionais, regras críticas invariantes e convenções para agentes de IA |
| **[Skill: alaris-domain](./.agents/skills/alaris-domain/SKILL.md)** | Regras de negócio, modelos Eloquent, transações de estoque e rotinas de comodato |
| **[Skill: laravel-workflow](./.agents/skills/laravel-workflow/SKILL.md)** | Guia de execução de migrações, testes Pest e compilação de assets |

---

## ⚙️ Instalação e Execução

### Pré-requisitos
- PHP 8.3+ com extensões `pdo_sqlite` (ou `pdo_mysql`), `mbstring`, `openssl`, `curl`
- Composer
- Node.js (v18+) e NPM

### 1. Instalação de Dependências
```bash
composer run setup
```

### 2. Iniciar o Ambiente de Desenvolvimento
```bash
composer run dev
```

### 3. Acesso Inicial (Bootstrap)
Ao acessar `http://localhost:8000/login` pela primeira vez, o sistema provisiona o usuário administrador:
- **E-mail**: `admin@alaris.com`
- **Senha**: `12345678`

---

## 🧪 Testes e Padronização

Executar testes automatizados:
```bash
composer run test
```

Formatar código com Laravel Pint:
```bash
vendor/bin/pint
```

---

## 📄 Licença
Software proprietário desenvolvido para o ERP Alaris.
