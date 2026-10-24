<!DOCTYPE html>
<html lang="pt-BR" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="ERP Alaris — Gestão de Controle de Pragas">
    <title>@yield('title', 'Dashboard') — ERP Alaris</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#0284c7">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Alaris">
    <script>
        (function() {
            var t = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', t);
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="h-full"
    style="background: var(--color-surface-950); color: var(--color-surface-200); font-family: var(--font-sans);">

    {{-- Overlay mobile --}}
    <div id="sidebar-overlay" class="hidden fixed inset-0 bg-black/60 z-30 lg:hidden"></div>

    {{-- Sidebar --}}
    <aside id="sidebar"
        class="fixed top-0 left-0 z-40 w-64 h-full transition-transform -translate-x-full lg:translate-x-0"
        style="background: var(--color-surface-900); border-right: 1px solid var(--color-surface-800);">
        <div class="flex flex-col h-full">
            {{-- Logo --}}
            <div class="flex items-center px-5 h-16" style="border-bottom: 1px solid var(--color-surface-800);">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                    <img src="{{ asset('images/logo.png') }}" alt="Alaris Service" class="h-8 w-auto object-contain">
                </a>
            </div>

            {{-- Nav --}}
            <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
                <a href="{{ route('dashboard') }}"
                    class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                    </svg>
                    Dashboard
                </a>
                <a href="{{ route('agendamentos.index') }}"
                    class="sidebar-link {{ request()->routeIs('agendamentos.*') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Agenda Unificada
                </a>
                <a href="{{ route('clientes.index') }}"
                    class="sidebar-link {{ request()->routeIs('clientes.*') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Clientes & Funil
                </a>
                <a href="{{ route('equipamentos.index') }}"
                    class="sidebar-link {{ request()->routeIs('equipamentos.*') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    Equipamentos
                </a>
                <a href="{{ route('comodatos.index') }}"
                    class="sidebar-link {{ request()->routeIs('comodatos.*') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    Comodatos
                </a>
                <a href="{{ route('visitas.index') }}"
                    class="sidebar-link {{ request()->routeIs('visitas.*') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                    Visitas & O.S.
                </a>
                <a href="{{ route('produtos.index') }}"
                    class="sidebar-link {{ request()->routeIs('produtos.*') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    Produtos & Insumos
                </a>

                @if (auth()->user() && auth()->user()->isAdmin())
                    <div class="pt-3 pb-1 px-3 text-[10px] font-bold uppercase tracking-wider text-surface-400"
                        style="color: var(--color-surface-400);">Gestão & Controladoria</div>
                    <a href="{{ route('tecnicos.index') }}"
                        class="sidebar-link {{ request()->routeIs('tecnicos.*') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zm-4 7a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        Técnicos
                    </a>
                    <a href="{{ route('financeiro.fluxo') }}"
                        class="sidebar-link {{ request()->routeIs('financeiro.fluxo') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Fluxo de Caixa
                    </a>
                    <a href="{{ route('financeiro.relatorio') }}"
                        class="sidebar-link {{ request()->routeIs('financeiro.relatorio') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        Lucro Bruto & DRE
                    </a>
                    <a href="{{ route('configuracoes.index') }}"
                        class="sidebar-link {{ request()->routeIs('configuracoes.*') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Configurações
                    </a>
                @endif

                <div class="pt-3 pb-1 px-3 text-[10px] font-bold uppercase tracking-wider text-surface-400"
                    style="color: var(--color-surface-400);">Conta</div>
                <a href="{{ route('perfil.edit') }}"
                    class="sidebar-link {{ request()->routeIs('perfil.*') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zm-4 7a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    Perfil & Acessos
                </a>
            </nav>

            {{-- Footer --}}
            <div class="p-4 flex items-center justify-between gap-2"
                style="border-top: 1px solid var(--color-surface-800);">
                <div class="truncate">
                    <div class="flex items-center gap-1.5">
                        <p class="text-xs font-semibold text-white truncate">
                            {{ auth()->user()->name ?? 'Administrador' }}</p>
                        <span
                            class="text-[9px] px-1.5 py-0.5 rounded font-bold uppercase {{ auth()->user() && auth()->user()->isTecnico() ? 'bg-amber-500/20 text-amber-300' : 'bg-primary-500/20 text-blue-300' }}">
                            {{ auth()->user() && auth()->user()->isTecnico() ? 'Técnico' : 'Admin' }}
                        </span>
                    </div>
                    <p class="text-[10px] truncate" style="color: var(--color-surface-400);">
                        {{ auth()->user()->email ?? 'admin@alaris.com' }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="shrink-0">
                    @csrf
                    <button type="submit"
                        class="p-1.5 rounded-lg text-red-400 hover:text-red-300 hover:bg-red-500/10 transition cursor-pointer"
                        title="Sair do sistema">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- Main --}}
    <div class="lg:ml-64 min-h-full flex flex-col">
        {{-- Topbar --}}
        <header class="sticky top-0 z-20 h-16 flex items-center justify-between px-4 lg:px-8"
            style="background: var(--topbar-bg); backdrop-filter: blur(12px); border-bottom: 1px solid var(--color-surface-800);">
            <div class="flex items-center gap-3">
                <button id="sidebar-toggle" class="lg:hidden p-2 rounded-lg"
                    style="color: var(--color-surface-400);">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <img src="{{ asset('images/logo.png') }}" alt="Alaris Service"
                    class="h-6 w-auto object-contain lg:hidden">
                <h1 class="text-lg font-semibold text-white">@yield('title', 'Dashboard')</h1>
            </div>

            <div class="flex items-center gap-2">
                {{-- Theme toggle --}}
                <button id="theme-toggle" class="p-2 rounded-lg transition cursor-pointer"
                    style="color: var(--color-surface-400);" title="Alternar tema">
                    <svg id="theme-icon-moon" class="w-5 h-5" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                    <svg id="theme-icon-sun" class="w-5 h-5 hidden" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </button>

                {{-- Notificações --}}
                <div class="relative">
                    <button id="notif-btn" class="relative p-2 rounded-lg transition-colors cursor-pointer hover:bg-surface-800"
                        style="color: var(--color-surface-400);" title="Central de Notificações">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <span id="notif-badge"
                            class="hidden absolute -top-0.5 -right-0.5 min-w-4 h-4 px-1 rounded-full text-[10px] font-bold flex items-center justify-center text-white preserve-white"
                            style="background: var(--color-danger); box-shadow: 0 0 0 2px var(--color-surface-900);">0</span>
                    </button>
                    <div id="notif-dropdown"
                        class="hidden absolute right-0 mt-2 notif-dropdown">
                        {{-- Header --}}
                        <div class="px-4 py-3 flex items-center justify-between border-b"
                            style="border-color: var(--color-surface-800); background: var(--color-surface-900);">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-primary-500"></span>
                                <span class="font-bold text-sm tracking-tight" style="color: var(--color-surface-100);">Notificações</span>
                                <span id="notif-unread-count" class="hidden badge badge-info text-[10px] font-bold py-0.5 px-2">0 novas</span>
                            </div>
                            <button type="button" id="btn-marcar-todas-lidas" onclick="window.marcarTodasNotificacoesLidas && window.marcarTodasNotificacoesLidas()"
                                class="text-xs font-semibold hover:underline flex items-center gap-1 cursor-pointer transition"
                                style="color: var(--color-primary-500);">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Marcar lidas</span>
                            </button>
                        </div>

                        {{-- Lista de Notificações com scroll suave --}}
                        <div id="notif-dropdown-content" class="py-2 max-h-88 overflow-y-auto space-y-1">
                            <div class="px-4 py-6 text-center text-xs" style="color: var(--color-surface-400);">Carregando notificações...</div>
                        </div>

                        {{-- Footer: Status das Notificações do Navegador / SO --}}
                        <div id="notif-os-footer" class="p-3 border-t" style="border-color: var(--color-surface-800); background: var(--color-surface-950);">
                            <div id="notif-os-request-box" class="flex flex-col gap-2">
                                <div class="flex items-center gap-2 text-xs" style="color: var(--color-surface-300);">
                                    <svg class="w-4 h-4 text-primary-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                    </svg>
                                    <span class="font-semibold" style="color: var(--color-surface-100);">Alertas no Navegador (PC & Celular)</span>
                                </div>
                                <button type="button" id="btn-ativar-notif-so" onclick="window.solicitarNotificacoesSO && window.solicitarNotificacoesSO()"
                                    class="w-full btn btn-primary btn-sm text-xs justify-center py-2 shadow-sm">
                                    Ativar Notificações no Dispositivo
                                </button>
                            </div>
                            <div id="notif-os-active-box" class="hidden flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <span class="relative flex h-2 w-2">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                    </span>
                                    <span class="text-xs font-semibold text-emerald-500">Notificações no Navegador Ativas</span>
                                </div>
                                <button type="button" onclick="window.testarNotificacaoSO && window.testarNotificacaoSO()"
                                    class="btn btn-secondary btn-sm text-xs py-1 px-3">
                                    Testar
                                </button>
                            </div>
                        </div>

                        {{-- Link para agenda --}}
                        <a href="{{ route('agendamentos.index') }}"
                           class="block text-center py-2.5 text-xs font-semibold border-t transition"
                           style="border-color: var(--color-surface-800); background: var(--color-surface-900); color: var(--color-primary-500);"
                           onmouseover="this.style.background='var(--color-surface-800)'"
                           onmouseout="this.style.background='var(--color-surface-900)'">
                            Acessar Agenda Geral Completa &rarr;
                        </a>
                    </div>
                </div>

                {{-- User Profile & Logout --}}
                <div class="flex items-center gap-2 pl-2 border-l" style="border-color: var(--color-surface-800);">
                    <a href="{{ route('perfil.edit') }}" class="flex items-center gap-2 p-1 rounded-lg transition"
                        title="Perfil & Acessos">
                        <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold text-white preserve-white"
                            style="background: linear-gradient(135deg, var(--color-primary-700), var(--color-primary-500));">
                            {{ mb_substr(auth()->user()->name ?? 'A', 0, 1) }}
                        </div>
                        <span
                            class="text-xs font-medium text-white hidden sm:inline">{{ auth()->user()->name ?? 'Admin' }}</span>
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit"
                            class="p-2 rounded-lg transition text-red-400 hover:text-red-300 hover:bg-red-500/10 cursor-pointer"
                            title="Sair do sistema">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </header>

        {{-- Flash --}}
        @if (session('sucesso'))
            <div class="mx-4 lg:mx-8 mt-4 alert alert-success animate-fadeIn">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ session('sucesso') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="mx-4 lg:mx-8 mt-4 alert alert-danger animate-fadeIn">
                <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Content --}}
        <main class="flex-1 p-4 lg:p-8">
            @yield('content')
        </main>
    </div>

</body>

</html>
