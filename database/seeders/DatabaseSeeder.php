<?php

namespace Database\Seeders;

use App\Models\Configuracao;
use App\Models\UnidadeEmpresa;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'alaris@alarisservice.com.br'],
            [
                'name' => 'Administrador',
                'password' => bcrypt('alaris4817'),
                'role' => User::ROLE_ADMIN,
            ]
        );

        Configuracao::set('aliquota_imposto_padrao', '14.0', 'Alíquota padrão de acréscimo para Nota Fiscal (%)');
        Configuracao::set('diaria_tecnico_padrao', '50.00', 'Valor padrão de mão de obra/diária por visita (R$)');

        if (UnidadeEmpresa::count() === 0) {
            $empresa = Configuracao::dadosEmpresa();
            UnidadeEmpresa::create([
                'nome' => 'Matriz - '.($empresa['cidade'] ?: 'Imperatriz'),
                'tipo' => UnidadeEmpresa::TIPO_MATRIZ,
                'razao_social' => $empresa['razaoSocial'],
                'nome_fantasia' => $empresa['nomeFantasia'],
                'cnpj' => $empresa['cnpj'],
                'licenca_sanitaria' => $empresa['licenca'],
                'telefone' => $empresa['telefone'],
                'email' => $empresa['email'],
                'website' => $empresa['website'],
                'slogan' => $empresa['slogan'],
                'cep' => $empresa['cep'],
                'endereco' => $empresa['endereco'],
                'bairro' => $empresa['bairro'],
                'cidade' => $empresa['cidade'],
                'estado' => $empresa['estado'],
                'padrao' => true,
                'ativo' => true,
            ]);
        }

        $this->call([
            PerfilPermissaoSeeder::class,
            MeioPagamentoSeeder::class,
            NotificationsTestSeeder::class,
        ]);
    }
}
