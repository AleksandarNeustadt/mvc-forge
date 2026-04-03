{{-- Forgot Password Page --}}

@php
global $router;
$lang = $router->lang ?? 'sr';
@endphp

<div class="w-full max-w-md mx-auto">
    {{-- Header --}}
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-theme-primary/20 mb-4">
            <ion-icon name="key-outline" class="text-3xl text-theme-primary"></ion-icon>
        </div>
        <h1 class="text-3xl font-bold text-white mb-2">{{ __('auth.forgot.title', 'Zaboravljena lozinka') }}</h1>
        <p class="text-slate-400">{{ __('auth.forgot.subtitle', 'Unesite email adresu i poslaćemo vam link za reset lozinke.') }}</p>
    </div>

    {{-- Forgot Password Form --}}
    <div class="bg-slate-900/50 backdrop-blur-sm border border-slate-800 rounded-2xl p-8">
        @php
        echo Form::open("/{$lang}/forgot-password", 'POST')
            ->id('forgot-password-form')
            ->email('email', __('auth.email', 'Email adresa'))
                ->required()
                ->placeholder('vas@email.com')
                ->icon('mail-outline')
                ->autocomplete('email')
            ->submit(__('auth.forgot.button', 'Pošalji link za reset'))
            ->close();
        @endphp
    </div>

    {{-- Back to login --}}
    <div class="mt-6 text-center">
        <a href="/{{ $lang }}/login" class="text-slate-400 hover:text-theme-primary transition-colors inline-flex items-center gap-2">
            <ion-icon name="arrow-back-outline"></ion-icon>
            {{ __('auth.back_to_login', 'Nazad na prijavu') }}
        </a>
    </div>
</div>
