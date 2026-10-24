<?php

namespace Database\Seeders;

use App\Models\Configuracao;
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
            ['email' => 'admin@alaris.com'],
            [
                'name' => 'Administrador',
                'password' => bcrypt('12345678'),
                'role' => User::ROLE_ADMIN,
            ]
        );

        Configuracao::set('aliquota_imposto_padrao', '14.0', 'Alíquota padrão de acréscimo para Nota Fiscal (%)');
        Configuracao::set('diaria_tecnico_padrao', '50.00', 'Valor padrão de mão de obra/diária por visita (R$)');

        $this->call([
            NotificationsTestSeeder::class,
        ]);
    }
}
