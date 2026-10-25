<?php

namespace Database\Seeders;

use App\Models\Perfil;
use App\Models\Permissao;
use App\Models\User;
use Illuminate\Database\Seeder;

class PerfilPermissaoSeeder extends Seeder
{
    public function run(): void
    {
        $catalogo = Permissao::catalogoModulos();
        $todasPermissoesIds = [];
        $permissoesMap = [];

        // 1. Cadastra todas as permissões do catálogo
        foreach ($catalogo as $moduloChave => $moduloInfo) {
            foreach ($moduloInfo['permissoes'] as $chave => $dados) {
                $permissao = Permissao::firstOrCreate(
                    ['chave' => $chave],
                    [
                        'modulo' => $moduloChave,
                        'nome' => $dados['nome'],
                        'descricao' => $dados['descricao'] ?? null,
                    ]
                );

                $todasPermissoesIds[] = $permissao->id;
                $permissoesMap[$chave] = $permissao->id;
            }
        }

        // 2. Perfil 1: Administrador (Super-usuário com todas as permissões)
        $perfilAdmin = Perfil::firstOrCreate(
            ['slug' => 'admin'],
            [
                'nome' => 'Administrador',
                'descricao' => 'Controle total e irrestrito sobre todas as funcionalidades e configurações do ERP.',
                'is_sistema' => true,
            ]
        );
        $perfilAdmin->permissoes()->sync($todasPermissoesIds);

        // 3. Perfil 2: Técnico de Campo (Apenas visitas próprias, conclusão e comodatos)
        $perfilTecnico = Perfil::firstOrCreate(
            ['slug' => 'tecnico'],
            [
                'nome' => 'Técnico de Campo',
                'descricao' => 'Acesso restrito às suas ordens de serviço, execução técnica e reposições de comodato.',
                'is_sistema' => true,
            ]
        );
        $chavesTecnico = [
            'dashboard.ver',
            'dashboard.card_visitas_hoje',
            'dashboard.card_visitas_mes',
            'dashboard.proximas_visitas',
            'dashboard.historico_visitas',
            'notificacoes.ver',
            'notificacoes.editar',
            'notificacoes.alertas_navegador',
            'visitas.ver',
            'visitas.finalizar',
            'visitas.gerar_os',
            'comodatos.ver',
            'comodatos.registrar_reposicao',
            'agendamentos.ver',
            'produtos.ver',
        ];
        $idsTecnico = array_intersect_key($permissoesMap, array_flip($chavesTecnico));
        $perfilTecnico->permissoes()->sync(array_values($idsTecnico));

        // 4. Perfil 3: Gerente Operacional (Gestão de clientes, visitas, insumos, equipamentos e agenda)
        $perfilGerente = Perfil::firstOrCreate(
            ['slug' => 'gerente_operacional'],
            [
                'nome' => 'Gerente Operacional',
                'descricao' => 'Coordenação de equipe, abertura e controle de ordens de serviço, clientes, comodatos e estoque.',
                'is_sistema' => false,
            ]
        );
        $chavesGerente = [
            // Dashboard & Notificações
            'dashboard.ver', 'dashboard.criar', 'dashboard.editar', 'dashboard.deletar', 'dashboard.acessar_dados',
            'dashboard.card_clientes', 'dashboard.card_comodatos', 'dashboard.card_visitas_hoje', 'dashboard.card_visitas_mes',
            'dashboard.alertas', 'dashboard.proximas_visitas', 'dashboard.historico_visitas',
            'notificacoes.ver', 'notificacoes.criar', 'notificacoes.editar', 'notificacoes.deletar', 'notificacoes.acessar_dados', 'notificacoes.alertas_navegador',
            // Clientes
            'clientes.ver', 'clientes.criar', 'clientes.editar', 'clientes.deletar', 'clientes.acessar_dados',
            // Equipamentos
            'equipamentos.ver', 'equipamentos.criar', 'equipamentos.editar', 'equipamentos.deletar', 'equipamentos.acessar_dados', 'equipamentos.gerenciar_categorias',
            // Comodatos
            'comodatos.ver', 'comodatos.criar', 'comodatos.editar', 'comodatos.deletar', 'comodatos.acessar_dados', 'comodatos.registrar_reposicao',
            // Visitas & OS
            'visitas.ver', 'visitas.criar', 'visitas.editar', 'visitas.deletar', 'visitas.acessar_dados', 'visitas.finalizar', 'visitas.gerar_os', 'visitas.proposta_comercial',
            // Produtos & Estoque
            'produtos.ver', 'produtos.criar', 'produtos.editar', 'produtos.deletar', 'produtos.acessar_dados', 'produtos.ajustar_estoque',
            // Agenda
            'agendamentos.ver', 'agendamentos.criar', 'agendamentos.editar', 'agendamentos.deletar', 'agendamentos.acessar_dados', 'agendamentos.excluir_evento',
            // Técnicos
            'tecnicos.ver', 'tecnicos.criar', 'tecnicos.editar', 'tecnicos.acessar_dados',
        ];
        $idsGerente = array_intersect_key($permissoesMap, array_flip($chavesGerente));
        $perfilGerente->permissoes()->sync(array_values($idsGerente));

        // 5. Perfil 4: Financeiro (Fluxo de caixa, conciliação, DRE, faturamento e propostas)
        $perfilFinanceiro = Perfil::firstOrCreate(
            ['slug' => 'financeiro'],
            [
                'nome' => 'Financeiro',
                'descricao' => 'Controle de contas a pagar/receber, conciliação de fluxo de caixa, relatórios DRE e custos.',
                'is_sistema' => false,
            ]
        );
        $chavesFinanceiro = [
            // Dashboard & Notificações
            'dashboard.ver', 'dashboard.card_clientes', 'dashboard.card_visitas_mes', 'dashboard.historico_visitas',
            'notificacoes.ver', 'notificacoes.editar',
            // Financeiro completo
            'financeiro.ver', 'financeiro.criar', 'financeiro.editar', 'financeiro.deletar', 'financeiro.acessar_dados', 'financeiro.alterar_status', 'financeiro.relatorio_dre', 'financeiro.exportar_pdf',
            // Clientes e Visitas para faturamento
            'clientes.ver', 'clientes.acessar_dados',
            'visitas.ver', 'visitas.acessar_dados', 'visitas.proposta_comercial',
            'comodatos.ver', 'comodatos.acessar_dados',
            'produtos.ver', 'produtos.acessar_dados',
            'tecnicos.ver', 'tecnicos.acessar_dados',
            'agendamentos.ver',
        ];
        $idsFinanceiro = array_intersect_key($permissoesMap, array_flip($chavesFinanceiro));
        $perfilFinanceiro->permissoes()->sync(array_values($idsFinanceiro));

        // 6. Vincula usuários existentes no banco aos novos perfis
        User::whereNull('perfil_id')->orWhere('perfil_id', 0)->chunk(50, function ($users) use ($perfilAdmin, $perfilTecnico) {
            foreach ($users as $user) {
                if ($user->role === User::ROLE_TECNICO) {
                    $user->perfil_id = $perfilTecnico->id;
                } else {
                    $user->perfil_id = $perfilAdmin->id;
                }
                $user->save();
            }
        });
    }
}
