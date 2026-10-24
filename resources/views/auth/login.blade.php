<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login — ERP Alaris</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
<body class="h-full flex items-center justify-center p-4" style="background: var(--color-surface-950); color: var(--color-surface-200); font-family: var(--font-sans);">

    <div class="w-full max-w-md space-y-6">
        <div class="text-center space-y-3">
            <div class="flex items-center justify-center">
                <img src="{{ asset('images/logo.png') }}" alt="Alaris Service" class="h-14 w-auto object-contain">
            </div>
            <p class="text-xs" style="color: var(--color-surface-400);">Gestão Integrada de Controle de Pragas & Higienização</p>
        </div>

        @if(session('sucesso'))
            <div class="alert alert-success animate-fadeIn">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>{{ session('sucesso') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger animate-fadeIn">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <div class="card p-6 sm:p-8">
            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf

                <div>
                    <label class="form-label" for="email">E-mail</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" class="form-input" required autofocus placeholder="seu@email.com">
                </div>

                <div>
                    <label class="form-label" for="password">Senha</label>
                    <input type="password" id="password" name="password" class="form-input" required placeholder="••••••••">
                </div>

                <div class="flex items-center justify-between text-xs pt-1">
                    <label class="flex items-center gap-2 cursor-pointer" style="color: var(--color-surface-300);">
                        <input type="checkbox" name="remember" class="w-4 h-4 rounded" style="accent-color: var(--color-primary-600);">
                        Lembrar-me
                    </label>
                </div>

                <button type="submit" class="btn btn-primary w-full mt-2">
                    Entrar no Sistema
                </button>
            </form>
        </div>
    </div>

</body>
</html>
