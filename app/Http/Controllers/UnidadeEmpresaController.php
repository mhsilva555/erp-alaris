<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Configuracao;
use App\Models\UnidadeEmpresa;
use App\Support\MascaraHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UnidadeEmpresaController extends Controller
{
    public function index(Request $request)
    {
        $this->autorizar('unidades.ver');

        $query = UnidadeEmpresa::withCount(['contratos', 'visitas']);

        if ($busca = $request->get('busca')) {
            $buscaLimpa = MascaraHelper::apenasNumeros($busca);
            $query->where(function ($q) use ($busca, $buscaLimpa) {
                $q->where('nome', 'like', "%{$busca}%")
                    ->orWhere('razao_social', 'like', "%{$busca}%")
                    ->orWhere('nome_fantasia', 'like', "%{$busca}%")
                    ->orWhere('cidade', 'like', "%{$busca}%")
                    ->orWhere('cnpj', 'like', "%{$busca}%");

                if ($buscaLimpa) {
                    $q->orWhere('cnpj', 'like', "%{$buscaLimpa}%");
                }
            });
        }

        if ($tipo = $request->get('tipo')) {
            $query->where('tipo', $tipo);
        }

        $totais = [
            'total' => UnidadeEmpresa::count(),
            'matrizes' => UnidadeEmpresa::where('tipo', UnidadeEmpresa::TIPO_MATRIZ)->count(),
            'filiais' => UnidadeEmpresa::where('tipo', UnidadeEmpresa::TIPO_FILIAL)->count(),
            'ativas' => UnidadeEmpresa::where('ativo', true)->count(),
        ];

        $unidades = $query
            ->orderByDesc('padrao')
            ->orderByRaw("CASE WHEN tipo = 'matriz' THEN 0 ELSE 1 END ASC")
            ->orderBy('nome')
            ->paginate(15)
            ->withQueryString();

        return view('unidades.index', compact('unidades', 'totais'));
    }

    public function create()
    {
        $this->autorizar('unidades.criar');

        $empresaPadrao = Configuracao::dadosEmpresa();
        $jaExisteMatriz = UnidadeEmpresa::where('tipo', UnidadeEmpresa::TIPO_MATRIZ)->exists();

        return view('unidades.create', compact('empresaPadrao', 'jaExisteMatriz'));
    }

    public function store(Request $request)
    {
        $this->autorizar('unidades.criar');

        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'tipo' => 'required|in:matriz,filial',
            'razao_social' => 'required|string|max:255',
            'nome_fantasia' => 'required|string|max:255',
            'cnpj' => 'required|string|max:30',
            'licenca_sanitaria' => 'nullable|string|max:255',
            'telefone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|string|max:255',
            'slogan' => 'nullable|string|max:255',
            'cep' => 'nullable|string|max:20',
            'endereco' => 'nullable|string|max:255',
            'bairro' => 'nullable|string|max:100',
            'cidade' => 'nullable|string|max:100',
            'estado' => 'nullable|string|max:2',
            'padrao' => 'boolean',
            'ativo' => 'boolean',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $primeiraUnidade = UnidadeEmpresa::count() === 0;
            $ehPadrao = $request->has('padrao') || $primeiraUnidade;
            $ehAtivo = $request->has('ativo') || ! $request->has('ativo_submitted');

            if ($ehPadrao) {
                UnidadeEmpresa::query()->update(['padrao' => false]);
            }

            $validated['padrao'] = $ehPadrao;
            $validated['ativo'] = $ehAtivo;

            UnidadeEmpresa::create($validated);
        });

        return redirect()->route('unidades.index')
            ->with('sucesso', 'Unidade da empresa cadastrada com sucesso.');
    }

    public function edit(UnidadeEmpresa $unidade)
    {
        $this->autorizar('unidades.editar');

        return view('unidades.edit', compact('unidade'));
    }

    public function update(Request $request, UnidadeEmpresa $unidade)
    {
        $this->autorizar('unidades.editar');

        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'tipo' => 'required|in:matriz,filial',
            'razao_social' => 'required|string|max:255',
            'nome_fantasia' => 'required|string|max:255',
            'cnpj' => 'required|string|max:30',
            'licenca_sanitaria' => 'nullable|string|max:255',
            'telefone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|string|max:255',
            'slogan' => 'nullable|string|max:255',
            'cep' => 'nullable|string|max:20',
            'endereco' => 'nullable|string|max:255',
            'bairro' => 'nullable|string|max:100',
            'cidade' => 'nullable|string|max:100',
            'estado' => 'nullable|string|max:2',
            'padrao' => 'boolean',
            'ativo' => 'boolean',
        ]);

        DB::transaction(function () use ($validated, $request, $unidade) {
            $ehPadrao = $request->has('padrao');
            $ehAtivo = $request->has('ativo_submitted') ? $request->has('ativo') : true;

            if ($ehPadrao) {
                UnidadeEmpresa::where('id', '!=', $unidade->id)->update(['padrao' => false]);
            }

            $validated['padrao'] = $ehPadrao;
            $validated['ativo'] = $ehAtivo;

            $unidade->update($validated);
        });

        return redirect()->route('unidades.index')
            ->with('sucesso', 'Unidade da empresa atualizada com sucesso.');
    }

    public function destroy(UnidadeEmpresa $unidade)
    {
        $this->autorizar('unidades.deletar');

        DB::transaction(function () use ($unidade) {
            $eraPadrao = $unidade->padrao;
            $unidade->delete();

            if ($eraPadrao) {
                $proxima = UnidadeEmpresa::where('ativo', true)
                    ->orderByRaw("CASE WHEN tipo = 'matriz' THEN 0 ELSE 1 END ASC")
                    ->first();
                $proxima?->update(['padrao' => true]);
            }
        });

        return redirect()->route('unidades.index')
            ->with('sucesso', 'Unidade da empresa removida com sucesso.');
    }

    private function autorizar(string $permissao): void
    {
        $user = auth()->user();
        if (! $user || ! $user->temPermissao($permissao)) {
            abort(403, "Acesso não autorizado. Seu perfil não possui a permissão: {$permissao}.");
        }
    }
}
