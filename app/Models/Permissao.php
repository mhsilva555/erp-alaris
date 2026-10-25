<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['modulo', 'nome', 'chave', 'descricao'])]
class Permissao extends Model
{
    use HasFactory;

    protected $table = 'permissoes';

    public function perfis(): BelongsToMany
    {
        return $this->belongsToMany(Perfil::class, 'perfil_permissao')->withTimestamps();
    }

    /**
     * Dicionário canônico de todos os módulos e suas respectivas permissões granulares.
     * Inclui as 5 permissões comuns obrigatórias (Ver, Criar, Editar, Deletar, Acessar Dados)
     * e as permissões específicas de cada funcionalidade.
     */
    public static function catalogoModulos(): array
    {
        return [
            'clientes' => [
                'nome' => 'Clientes & Funil de Prospecção',
                'descricao' => 'Gestão de clientes, geocadastro e follow-ups comerciais.',
                'permissoes' => [
                    'clientes.ver' => ['nome' => 'Ver Clientes', 'descricao' => 'Visualizar lista e dados básicos de clientes.'],
                    'clientes.criar' => ['nome' => 'Criar Clientes', 'descricao' => 'Cadastrar novos clientes ou prospects no sistema.'],
                    'clientes.editar' => ['nome' => 'Editar Clientes', 'descricao' => 'Alterar dados cadastrais e status de funil.'],
                    'clientes.deletar' => ['nome' => 'Deletar Clientes', 'descricao' => 'Excluir clientes do sistema.'],
                    'clientes.acessar_dados' => ['nome' => 'Acessar Dados Completos', 'descricao' => 'Visualizar documentos, históricos e dados sensíveis.'],
                ],
            ],

            'equipamentos' => [
                'nome' => 'Equipamentos do Inventário',
                'descricao' => 'Controle de armadilhas, porta-iscas e patrimônio em estoque.',
                'permissoes' => [
                    'equipamentos.ver' => ['nome' => 'Ver Equipamentos', 'descricao' => 'Visualizar inventário de equipamentos.'],
                    'equipamentos.criar' => ['nome' => 'Criar Equipamentos', 'descricao' => 'Cadastrar novos equipamentos no inventário.'],
                    'equipamentos.editar' => ['nome' => 'Editar Equipamentos', 'descricao' => 'Editar saldo, preço e especificações de equipamentos.'],
                    'equipamentos.deletar' => ['nome' => 'Deletar Equipamentos', 'descricao' => 'Excluir equipamentos do inventário.'],
                    'equipamentos.acessar_dados' => ['nome' => 'Acessar Dados do Inventário', 'descricao' => 'Visualizar custos de aquisição e alocação.'],
                    'equipamentos.gerenciar_categorias' => ['nome' => 'Gerenciar Categorias', 'descricao' => 'Criar e excluir categorias de equipamentos.'],
                ],
            ],

            'comodatos' => [
                'nome' => 'Equipamentos em Comodato',
                'descricao' => 'Instalação e controle de pontos de isca alocados em clientes.',
                'permissoes' => [
                    'comodatos.ver' => ['nome' => 'Ver Comodatos', 'descricao' => 'Visualizar equipamentos alocados em clientes.'],
                    'comodatos.criar' => ['nome' => 'Criar Comodatos', 'descricao' => 'Alocar novos equipamentos em clientes.'],
                    'comodatos.editar' => ['nome' => 'Editar Comodatos', 'descricao' => 'Alterar periodicidade de reposição e detalhes do comodato.'],
                    'comodatos.deletar' => ['nome' => 'Deletar Comodatos', 'descricao' => 'Desinstalar / remover comodatos.'],
                    'comodatos.acessar_dados' => ['nome' => 'Acessar Histórico de Comodato', 'descricao' => 'Acessar histórico de reposições e auditoria.'],
                    'comodatos.registrar_reposicao' => ['nome' => 'Registrar Reposição', 'descricao' => 'Registrar troca preventiva de iscas e manutenções.'],
                ],
            ],

            'visitas' => [
                'nome' => 'Visitas Técnicas & Ordens de Serviço',
                'descricao' => 'Agendamento de OS, execução em campo e propostas.',
                'permissoes' => [
                    'visitas.ver' => ['nome' => 'Ver Visitas & OS', 'descricao' => 'Visualizar lista e escala de ordens de serviço.'],
                    'visitas.criar' => ['nome' => 'Criar Visitas & OS', 'descricao' => 'Agendar novas visitas e emitir ordens de serviço.'],
                    'visitas.editar' => ['nome' => 'Editar Visitas & OS', 'descricao' => 'Modificar horários, técnicos, produtos e valores da OS.'],
                    'visitas.deletar' => ['nome' => 'Deletar Visitas & OS', 'descricao' => 'Excluir ordens de serviço e visitas.'],
                    'visitas.acessar_dados' => ['nome' => 'Acessar Dados Financeiros da OS', 'descricao' => 'Visualizar custos internos, margens e valores cobrados.'],
                    'visitas.finalizar' => ['nome' => 'Finalizar OS & Baixar Estoque', 'descricao' => 'Concluir visita com registro de insumos aplicados.'],
                    'visitas.gerar_os' => ['nome' => 'Gerar Ordem de Serviço (PDF/Tela)', 'descricao' => 'Imprimir folha de execução para técnico de campo.'],
                    'visitas.proposta_comercial' => ['nome' => 'Gerar Proposta Comercial', 'descricao' => 'Visualizar e imprimir proposta sigilosa do cliente.'],
                ],
            ],

            'produtos' => [
                'nome' => 'Produtos & Estoque Fracionado',
                'descricao' => 'Defensivos químicos, conversão em doses (mL/g) e saldo.',
                'permissoes' => [
                    'produtos.ver' => ['nome' => 'Ver Produtos & Estoque', 'descricao' => 'Visualizar catálogo de produtos e saldo em estoque.'],
                    'produtos.criar' => ['nome' => 'Criar Produtos', 'descricao' => 'Cadastrar novos defensivos e insumos.'],
                    'produtos.editar' => ['nome' => 'Editar Produtos', 'descricao' => 'Alterar fatores de conversão, doses e preços.'],
                    'produtos.deletar' => ['nome' => 'Deletar Produtos', 'descricao' => 'Remover produtos do catálogo.'],
                    'produtos.acessar_dados' => ['nome' => 'Acessar Dados de Custos', 'descricao' => 'Visualizar preço de compra unitário e fornecedores.'],
                    'produtos.ajustar_estoque' => ['nome' => 'Ajustar Estoque Manualmente', 'descricao' => 'Dar entrada ou baixa avulsa de saldo no estoque.'],
                ],
            ],

            'agendamentos' => [
                'nome' => 'Agenda Geral Unificada',
                'descricao' => 'Visão integrada de visitas, reposições de comodato e follow-ups.',
                'permissoes' => [
                    'agendamentos.ver' => ['nome' => 'Ver Agenda Unificada', 'descricao' => 'Acessar a agenda no formato de calendário e lista.'],
                    'agendamentos.criar' => ['nome' => 'Criar Agendamentos', 'descricao' => 'Adicionar compromissos na agenda.'],
                    'agendamentos.editar' => ['nome' => 'Editar Agendamentos', 'descricao' => 'Remarcar datas e horários de agendamentos.'],
                    'agendamentos.deletar' => ['nome' => 'Deletar Agendamentos', 'descricao' => 'Excluir compromissos e tarefas da agenda.'],
                    'agendamentos.acessar_dados' => ['nome' => 'Acessar Dados Consolidados', 'descricao' => 'Visualizar dados completos de clientes na agenda.'],
                    'agendamentos.excluir_evento' => ['nome' => 'Remover Eventos da Agenda', 'descricao' => 'Descartar notificações e eventos unificados.'],
                ],
            ],

            'tecnicos' => [
                'nome' => 'Técnicos de Campo',
                'descricao' => 'Equipe técnica operacional, contatos e valor de diária padrão.',
                'permissoes' => [
                    'tecnicos.ver' => ['nome' => 'Ver Técnicos', 'descricao' => 'Visualizar equipe de técnicos cadastrados.'],
                    'tecnicos.criar' => ['nome' => 'Criar Técnicos', 'descricao' => 'Cadastrar novos colaboradores técnicos.'],
                    'tecnicos.editar' => ['nome' => 'Editar Técnicos', 'descricao' => 'Editar dados cadastrais e status ativo/inativo.'],
                    'tecnicos.deletar' => ['nome' => 'Deletar Técnicos', 'descricao' => 'Remover técnicos da equipe.'],
                    'tecnicos.acessar_dados' => ['nome' => 'Acessar Dados de Diária', 'descricao' => 'Visualizar e editar o valor da diária padrão do técnico.'],
                ],
            ],

            'financeiro' => [
                'nome' => 'Financeiro, Fluxo de Caixa & DRE',
                'descricao' => 'Gestão de receitas, despesas, apuração de lucro bruto e DRE.',
                'permissoes' => [
                    'financeiro.ver' => ['nome' => 'Ver Fluxo de Caixa', 'descricao' => 'Visualizar entradas e saídas no fluxo de caixa.'],
                    'financeiro.criar' => ['nome' => 'Criar Movimentações', 'descricao' => 'Lançar novas receitas e despesas manuais.'],
                    'financeiro.editar' => ['nome' => 'Editar Movimentações', 'descricao' => 'Alterar valores, descrições e datas de competência.'],
                    'financeiro.deletar' => ['nome' => 'Deletar Movimentações', 'descricao' => 'Excluir lançamentos do fluxo de caixa.'],
                    'financeiro.acessar_dados' => ['nome' => 'Acessar Dados Financeiros Globais', 'descricao' => 'Visualizar saldos totais, conciliação e DRE.'],
                    'financeiro.alterar_status' => ['nome' => 'Alterar Status de Pagamento', 'descricao' => 'Baixar títulos (marcar como pago ou pendente).'],
                    'financeiro.relatorio_dre' => ['nome' => 'Acessar Relatório de Lucro Bruto & DRE', 'descricao' => 'Acessar tela de conciliação DRE vs Caixa.'],
                    'financeiro.exportar_pdf' => ['nome' => 'Exportar Relatórios em PDF', 'descricao' => 'Gerar e baixar o relatório financeiro consolidado em PDF.'],
                ],
            ],

            'configuracoes' => [
                'nome' => 'Configurações do Sistema',
                'descricao' => 'Parâmetros fiscais (alíquota de NF), diária técnica e cron.',
                'permissoes' => [
                    'configuracoes.ver' => ['nome' => 'Ver Configurações', 'descricao' => 'Visualizar os parâmetros de configuração geral.'],
                    'configuracoes.criar' => ['nome' => 'Criar Parâmetros', 'descricao' => 'Definir novas chaves de configuração.'],
                    'configuracoes.editar' => ['nome' => 'Editar Configurações', 'descricao' => 'Alterar alíquotas de imposto e valores padrão.'],
                    'configuracoes.deletar' => ['nome' => 'Deletar Parâmetros', 'descricao' => 'Remover chaves customizadas de configuração.'],
                    'configuracoes.acessar_dados' => ['nome' => 'Acessar Logs & Diagnóstico', 'descricao' => 'Visualizar status da cron e auditoria.'],
                ],
            ],

            'perfis' => [
                'nome' => 'Perfis de Acesso & Permissões',
                'descricao' => 'Criação e parametrização de papéis de usuários e matriz de permissões.',
                'permissoes' => [
                    'perfis.ver' => ['nome' => 'Ver Perfis de Acesso', 'descricao' => 'Visualizar lista de perfis e permissões configuradas.'],
                    'perfis.criar' => ['nome' => 'Criar Perfis', 'descricao' => 'Cadastrar novos perfis de acesso.'],
                    'perfis.editar' => ['nome' => 'Editar Perfis & Permissões', 'descricao' => 'Modificar permissões atribuídas a um perfil.'],
                    'perfis.deletar' => ['nome' => 'Deletar Perfis', 'descricao' => 'Excluir perfis de acesso personalizados.'],
                    'perfis.acessar_dados' => ['nome' => 'Acessar Matriz Completa', 'descricao' => 'Visualizar auditoria de atribuições de acessos.'],
                ],
            ],

            'usuarios' => [
                'nome' => 'Gestão de Usuários & Logins',
                'descricao' => 'Contas de acesso, logins de técnicos e vinculação de perfis.',
                'permissoes' => [
                    'usuarios.ver' => ['nome' => 'Ver Usuários', 'descricao' => 'Visualizar lista de usuários e contas ativas.'],
                    'usuarios.criar' => ['nome' => 'Criar Usuários', 'descricao' => 'Criar novos logins de técnicos ou gestores.'],
                    'usuarios.editar' => ['nome' => 'Editar Usuários', 'descricao' => 'Alterar senhas, dados de contato e perfil de acesso.'],
                    'usuarios.deletar' => ['nome' => 'Deletar Usuários', 'descricao' => 'Revogar / excluir contas de usuário.'],
                    'usuarios.acessar_dados' => ['nome' => 'Acessar Dados de Login', 'descricao' => 'Visualizar vínculos técnicos e status de autenticação.'],
                ],
            ],
        ];
    }
}
