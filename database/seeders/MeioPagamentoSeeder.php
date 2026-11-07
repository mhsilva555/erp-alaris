<?php

namespace Database\Seeders;

use App\Models\MeioPagamento;
use Illuminate\Database\Seeder;

class MeioPagamentoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $meios = [
            [
                'nome' => 'Boleto Bancário',
                'codigo' => 'boleto',
                'descricao' => 'Boleto bancário emitido com prazo de vencimento',
                'ativo' => true,
            ],
            [
                'nome' => 'PIX',
                'codigo' => 'pix',
                'descricao' => 'Pagamento instantâneo via chave PIX / QR Code',
                'ativo' => true,
            ],
            [
                'nome' => 'Cartão de Crédito',
                'codigo' => 'cartao_credito',
                'descricao' => 'Cartão de crédito presencial ou link de pagamento',
                'ativo' => true,
            ],
            [
                'nome' => 'Cartão de Débito',
                'codigo' => 'cartao_debito',
                'descricao' => 'Cartão de débito na maquininha',
                'ativo' => true,
            ],
            [
                'nome' => 'Transferência Bancária / TED',
                'codigo' => 'transferencia',
                'descricao' => 'Transferência eletrônica direta / TED / DOC',
                'ativo' => true,
            ],
            [
                'nome' => 'Dinheiro / Espécie',
                'codigo' => 'dinheiro',
                'descricao' => 'Pagamento em moeda corrente no ato do atendimento',
                'ativo' => true,
            ],
            [
                'nome' => 'Faturado / Boleto a Prazo',
                'codigo' => 'faturado',
                'descricao' => 'Faturamento mensal direto para pessoa jurídica',
                'ativo' => true,
            ],
        ];

        foreach ($meios as $meio) {
            MeioPagamento::updateOrCreate(
                ['codigo' => $meio['codigo']],
                [
                    'nome' => $meio['nome'],
                    'descricao' => $meio['descricao'],
                    'ativo' => $meio['ativo'],
                ]
            );
        }
    }
}
