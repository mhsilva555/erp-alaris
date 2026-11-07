<?php

namespace App\Http\Controllers;

use App\Models\Configuracao;
use Illuminate\Http\Request;

class ConfiguracaoController extends Controller
{
    public function index()
    {
        $aliquotaImposto = Configuracao::get('aliquota_imposto_padrao', '14.0');
        $diariaTecnico = Configuracao::get('diaria_tecnico_padrao', '50.00');

        $aliquotaImpostoPadrao = $aliquotaImposto;
        $diariaTecnicoPadrao = $diariaTecnico;

        $empresa = Configuracao::dadosEmpresa();

        return view('configuracoes.index', compact(
            'aliquotaImposto',
            'diariaTecnico',
            'aliquotaImpostoPadrao',
            'diariaTecnicoPadrao',
            'empresa'
        ));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'aliquota_imposto_padrao' => 'required|numeric|min:0|max:100',
            'diaria_tecnico_padrao' => 'required|numeric|min:0',

            // Informações Empresariais
            'empresa_razao_social' => 'nullable|string|max:255',
            'empresa_nome_fantasia' => 'nullable|string|max:255',
            'empresa_cnpj' => 'nullable|string|max:30',
            'empresa_licenca_sanitaria' => 'nullable|string|max:255',
            'empresa_telefone' => 'nullable|string|max:30',
            'empresa_email' => 'nullable|email|max:255',
            'empresa_endereco' => 'nullable|string|max:255',
            'empresa_bairro' => 'nullable|string|max:100',
            'empresa_cidade' => 'nullable|string|max:100',
            'empresa_estado' => 'nullable|string|max:2',
            'empresa_cep' => 'nullable|string|max:20',
            'empresa_slogan' => 'nullable|string|max:255',
        ]);

        Configuracao::set('aliquota_imposto_padrao', (string) $validated['aliquota_imposto_padrao'], 'Alíquota padrão de acréscimo para Nota Fiscal (%)');
        Configuracao::set('diaria_tecnico_padrao', (string) $validated['diaria_tecnico_padrao'], 'Valor padrão de mão de obra/diária por visita (R$)');

        // Grava dados empresariais da Alaris
        $camposEmpresa = [
            'empresa_razao_social' => 'Razão Social oficial da empresa',
            'empresa_nome_fantasia' => 'Nome Fantasia da marca',
            'empresa_cnpj' => 'CNPJ oficial da empresa',
            'empresa_licenca_sanitaria' => 'Alvará Sanitário / Licença Ambiental',
            'empresa_telefone' => 'Telefone / WhatsApp comercial',
            'empresa_email' => 'E-mail de contato comercial',
            'empresa_endereco' => 'Endereço da sede (Logradouro e número)',
            'empresa_bairro' => 'Bairro da sede',
            'empresa_cidade' => 'Cidade da sede',
            'empresa_estado' => 'Estado / UF da sede',
            'empresa_cep' => 'CEP da sede',
            'empresa_slogan' => 'Slogan / Ramo de atuação',
        ];

        foreach ($camposEmpresa as $campo => $desc) {
            Configuracao::set($campo, $validated[$campo] ?? null, $desc);
        }

        return redirect()->route('configuracoes.index')
            ->with('sucesso', 'Configurações operacionais e empresariais atualizadas com sucesso.');
    }
}
