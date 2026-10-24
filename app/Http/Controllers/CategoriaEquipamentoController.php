<?php

namespace App\Http\Controllers;

use App\Models\CategoriaEquipamento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoriaEquipamentoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $categorias = CategoriaEquipamento::withCount('equipamentos')
            ->orderBy('nome')
            ->get();

        return response()->json([
            'sucesso' => true,
            'categorias' => $categorias,
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'nome' => 'required|string|max:100|unique:categoria_equipamentos,nome',
        ], [
            'nome.required' => 'O nome da categoria é obrigatório.',
            'nome.unique' => 'Já existe uma categoria cadastrada com este nome.',
            'nome.max' => 'O nome não pode ter mais de 100 caracteres.',
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'sucesso' => false,
                    'errors' => $validator->errors(),
                    'mensagem' => $validator->errors()->first('nome'),
                ], 422);
            }

            return redirect()->back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();

        $categoria = CategoriaEquipamento::create([
            'nome' => trim($validated['nome']),
            'ativo' => true,
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'sucesso' => true,
                'mensagem' => "Categoria '{$categoria->nome}' cadastrada com sucesso!",
                'categoria' => $categoria->loadCount('equipamentos'),
            ], 201);
        }

        return redirect()->back()->with('sucesso', "Categoria '{$categoria->nome}' cadastrada com sucesso!");
    }

    public function destroy(Request $request, CategoriaEquipamento $categoria): JsonResponse|RedirectResponse
    {
        if ($categoria->equipamentos()->exists()) {
            $msg = "Não é possível excluir a categoria '{$categoria->nome}' porque existem equipamentos cadastrados nela.";
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => $msg,
                ], 422);
            }

            return redirect()->back()->with('erro', $msg);
        }

        $nome = $categoria->nome;
        $categoria->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'sucesso' => true,
                'mensagem' => "Categoria '{$nome}' excluída com sucesso!",
            ]);
        }

        return redirect()->back()->with('sucesso', "Categoria '{$nome}' excluída com sucesso!");
    }
}
