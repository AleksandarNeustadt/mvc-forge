{{-- Login Page --}}

@php
global $router;
$lang = $router->lang ?? 'sr';
@endphp

<div class="w-full max-w-md mx-auto">
    {{-- Header --}}
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-theme-primary/20 mb-4">
            <ion-icon name="log-in-outline" class="text-3xl text-theme-primary"></ion-icon>
        </div>
        <h1 class="text-3xl font-bold text-white mb-2">{{ __('auth.login.title', 'Prijava') }}</h1>
        <p class="text-slate-400">{{ __('auth.login.subtitle', 'Dobrodošli nazad! Prijavite se na svoj nalog.') }}</p>
    </div>

    {{-- Login Form --}}
    <div class="bg-slate-900/50 backdrop-blur-sm border border-slate-800 rounded-2xl p-8">
        @php
        echo Form::open("/{$lang}/login", 'POST')
            ->id('login-form')
            ->email('email', __('auth.email', 'Email adresa'))
                ->required()
                ->placeholder('vas@email.com')
                ->icon('mail-outline')
                ->autocomplete('email')
            ->password('password', __('auth.password', 'Lozinka'))
                ->required()
                ->minLength(6)
                ->placeholder('••••••••')
                ->icon('lock-closed-outline')
                ->autocomplete('current-password')
            ->checkbox('remember', __('auth.remember', 'Zapamti me'))
            ->submit(__('auth.login.button', 'Prijavi se'))
            ->close();
        @endphp
    </div>

    {{-- Forgot password link --}}
    <div class="mt-4 text-center">
        <a href="/{{ $lang }}/forgot-password" class="text-sm text-slate-400 hover:text-theme-primary transition-colors">
            {{ __('auth.forgot_password', 'Zaboravili ste lozinku?') }}
        </a>
    </div>

    {{-- Register link --}}
    <div class="mt-6 text-center">
        <p class="text-slate-400">
            {{ __('auth.no_account', 'Nemate nalog?') }}
            <a href="/{{ $lang }}/register" class="text-theme-primary hover:underline font-medium">
                {{ __('auth.register.link', 'Registrujte se') }}
            </a>
        </p>
    </div>

    {{-- Social login divider --}}
    <div class="relative my-8">
        <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-slate-800"></div>
        </div>
        <div class="relative flex justify-center text-sm">
            <span class="px-4 bg-slate-950 text-slate-500">{{ __('auth.or', 'ili') }}</span>
        </div>
    </div>

    {{-- Social login buttons --}}
    <div class="grid grid-cols-2 gap-4">
        <button type="button" class="flex items-center justify-center gap-2 py-3 px-4 bg-slate-800/50 hover:bg-slate-800 border border-slate-700 rounded-lg transition-colors">
            <svg class="w-5 h-5" viewBox="0 0 24 24">
                <path fill="currentColor" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="currentColor" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="currentColor" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                <path fill="currentColor" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
            </svg>
            <span class="text-slate-300">Google</span>
        </button>
        <button type="button" class="flex items-center justify-center gap-2 py-3 px-4 bg-slate-800/50 hover:bg-slate-800 border border-slate-700 rounded-lg transition-colors">
            <ion-icon name="logo-github" class="text-xl"></ion-icon>
            <span class="text-slate-300">GitHub</span>
        </button>
    </div>
</div>
