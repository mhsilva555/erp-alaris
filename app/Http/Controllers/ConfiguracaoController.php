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

        return view('configuracoes.index', compact(
            'aliquotaImposto',
            'diariaTecnico',
            'aliquotaImpostoPadrao',
            'diariaTecnicoPadrao'
        ));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'aliquota_imposto_padrao' => 'required|numeric|min:0|max:100',
            'diaria_tecnico_padrao' => 'required|numeric|min:0',
        ]);

        Configuracao::set('aliquota_imposto_padrao', (string) $validated['aliquota_imposto_padrao'], 'Alíquota padrão de acréscimo para Nota Fiscal (%)');
        Configuracao::set('diaria_tecnico_padrao', (string) $validated['diaria_tecnico_padrao'], 'Valor padrão de mão de obra/diária por visita (R$)');

        return redirect()->route('configuracoes.index')
            ->with('sucesso', 'Configurações atualizadas com sucesso.');
    }
}
