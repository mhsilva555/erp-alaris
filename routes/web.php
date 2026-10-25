<?php

use App\Http\Controllers\AgendamentoController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoriaEquipamentoController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ComodatoController;
use App\Http\Controllers\ConfiguracaoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EquipamentoController;
use App\Http\Controllers\FinanceiroController;
use App\Http\Controllers\NotificacaoAgendamentoController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\ProdutoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TecnicoController;
use App\Http\Controllers\VisitaController;
use Illuminate\Support\Facades\Route;

// Rotas para visitantes (não autenticados)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Rotas protegidas (apenas usuários autenticados)
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Perfil
    Route::get('/perfil', [ProfileController::class, 'edit'])->name('perfil.edit');
    Route::put('/perfil', [ProfileController::class, 'update'])->name('perfil.update');

    // Dashboard e Notificações Polling
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/notificacoes', [DashboardController::class, 'notificacoes'])->name('notificacoes');

    // Notificações de Agendamento no Sistema Operacional (Desktop & Celular)
    Route::get('/notificacoes/agendamentos/pendentes', [NotificacaoAgendamentoController::class, 'pendentes'])->name('notificacoes.agendamentos.pendentes');
    Route::post('/notificacoes/agendamentos/{notificacao}/marcar-exibida', [NotificacaoAgendamentoController::class, 'marcarExibida'])->name('notificacoes.agendamentos.marcar-exibida');
    Route::post('/notificacoes/agendamentos/{notificacao}/marcar-lida', [NotificacaoAgendamentoController::class, 'marcarLida'])->name('notificacoes.agendamentos.marcar-lida');
    Route::post('/notificacoes/agendamentos/marcar-todas-lidas', [NotificacaoAgendamentoController::class, 'marcarTodasLidas'])->name('notificacoes.agendamentos.marcar-todas-lidas');
    Route::post('/notificacoes/agendamentos/testar', [NotificacaoAgendamentoController::class, 'testar'])->name('notificacoes.agendamentos.testar');

    // Clientes
    Route::resource('clientes', ClienteController::class);

    // Categorias de Equipamentos
    Route::get('/equipamentos/categorias', [CategoriaEquipamentoController::class, 'index'])->name('equipamentos.categorias.index');
    Route::post('/equipamentos/categorias', [CategoriaEquipamentoController::class, 'store'])->name('equipamentos.categorias.store');
    Route::delete('/equipamentos/categorias/{categoria}', [CategoriaEquipamentoController::class, 'destroy'])->name('equipamentos.categorias.destroy');

    // Gestão de Equipamentos do Inventário
    Route::resource('equipamentos', EquipamentoController::class);

    // Equipamentos em Comodato
    Route::resource('comodatos', ComodatoController::class);
    Route::post('/comodatos/{comodato}/reposicao', [ComodatoController::class, 'registrarReposicao'])->name('comodatos.reposicao');

    // Produtos e Estoque Fracionado
    Route::resource('produtos', ProdutoController::class)->except(['show']);

    // Visitas e Ordens de Serviço
    Route::resource('visitas', VisitaController::class);
    Route::get('/visitas/{visita}/os', [VisitaController::class, 'ordemServico'])->name('visitas.os');
    Route::get('/visitas/{visita}/proposta', [VisitaController::class, 'propostaCliente'])->name('visitas.proposta');
    Route::post('/visitas/{visita}/finalizar', [VisitaController::class, 'finalizar'])->name('visitas.finalizar');

    // Agenda Geral Unificada
    Route::get('/agendamentos', [AgendamentoController::class, 'index'])->name('agendamentos.index');
    Route::delete('/agendamentos/{tipo}/{id}', [AgendamentoController::class, 'destroy'])->name('agendamentos.destroy');

    // Rotas Restritas a Administradores (Financeiro, Usuários, Configurações)
    Route::middleware('admin')->group(function () {
        // Técnicos
        Route::resource('tecnicos', TecnicoController::class)->except(['show']);

        // Gestão de Logins de Técnicos / Usuários
        Route::post('/perfil/usuarios', [ProfileController::class, 'storeUsuario'])->name('perfil.usuarios.store');
        Route::delete('/perfil/usuarios/{user}', [ProfileController::class, 'destroyUsuario'])->name('perfil.usuarios.destroy');

        // Financeiro & Fluxo de Caixa
        Route::get('/financeiro/fluxo-caixa', [FinanceiroController::class, 'index'])->name('financeiro.fluxo');
        Route::post('/financeiro/movimentacoes', [FinanceiroController::class, 'store'])->name('financeiro.store');
        Route::post('/financeiro/movimentacoes/acoes-em-massa', [FinanceiroController::class, 'acoesEmMassa'])->name('financeiro.acoes-massa');
        Route::patch('/financeiro/movimentacoes/{movimentacao}/status', [FinanceiroController::class, 'updateStatus'])->name('financeiro.status');
        Route::delete('/financeiro/movimentacoes/{movimentacao}', [FinanceiroController::class, 'destroy'])->name('financeiro.destroy');

        // Relatórios Gerenciais (Lucro Bruto e Demonstrativo)
        Route::get('/financeiro/relatorios', [FinanceiroController::class, 'relatorio'])->name('financeiro.relatorio');
        Route::get('/financeiro/relatorios/pdf', [FinanceiroController::class, 'relatorioPdf'])->name('financeiro.relatorio.pdf');

        // Configurações Fiscais e Operacionais
        Route::get('/configuracoes', [ConfiguracaoController::class, 'index'])->name('configuracoes.index');
        Route::put('/configuracoes', [ConfiguracaoController::class, 'update'])->name('configuracoes.update');

        // Perfis de Acesso & Permissões Granulares
        Route::resource('perfis', PerfilController::class)->parameters(['perfis' => 'perfil']);
    });
});
