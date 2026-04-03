{{-- Contact Page --}}

@php
global $router;
$lang = $router->lang ?? 'sr';

// Check if user is authenticated
$isAuthenticated = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$formDisabled = !$isAuthenticated;

$formHasSuccess = class_exists('Form') && method_exists('Form', 'hasSuccess') ? Form::hasSuccess() : false;
$formSuccess = $formHasSuccess && method_exists('Form', 'getSuccess') ? Form::getSuccess() : '';
@endphp

<div class="w-full max-w-2xl mx-auto">
    {{-- Header --}}
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-theme-primary/20 mb-4">
            <ion-icon name="mail-outline" class="text-3xl text-theme-primary"></ion-icon>
        </div>
        <h1 class="text-3xl font-bold text-white mb-2">Kontakt</h1>
        <p class="text-slate-400">Pošaljite nam poruku i odgovorićemo vam u najkraćem roku.</p>
    </div>

    {{-- Success Message --}}
    @if ($formHasSuccess && !empty($formSuccess))
        <div class="mb-6 bg-green-900/20 border border-green-800/50 rounded-lg p-4">
            <div class="flex items-start gap-3">
                <ion-icon name="checkmark-circle-outline" class="text-green-400 text-xl flex-shrink-0 mt-0.5"></ion-icon>
                <div class="text-sm text-green-300">
                    <p class="font-medium">{{ e($formSuccess) }}</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Guest Notice (only show if not authenticated) --}}
    @if (!$isAuthenticated)
        <div class="mb-6 bg-yellow-900/20 border border-yellow-800/50 rounded-lg p-4">
            <div class="flex items-start gap-3">
                <ion-icon name="information-circle-outline" class="text-yellow-400 text-xl flex-shrink-0 mt-0.5"></ion-icon>
                <div class="flex-1">
                    <p class="text-sm text-yellow-300 font-medium mb-2">Za slanje poruke potrebno je da budete prijavljeni.</p>
                    <div class="flex items-center gap-4 mt-3">
                        <a href="/{{ $lang }}/login" 
                           class="inline-flex items-center gap-2 px-4 py-2 bg-theme-primary hover:bg-theme-primary/90 text-white font-medium rounded-lg transition-colors">
                            <ion-icon name="log-in-outline"></ion-icon>
                            Prijavite se
                        </a>
                        <a href="/{{ $lang }}/register" 
                           class="inline-flex items-center gap-2 px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white font-medium rounded-lg transition-colors">
                            <ion-icon name="person-add-outline"></ion-icon>
                            Registrujte se
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Contact Form --}}
    <div class="bg-slate-900/50 backdrop-blur-sm border border-slate-800 rounded-2xl p-8 {{ $formDisabled ? 'opacity-60' : '' }}">
        @php
        // Set form start time for anti-spam timing validation (only if authenticated)
        if ($isAuthenticated && class_exists('Security') && method_exists('Security', 'setFormStartTime')) {
            Security::setFormStartTime('contact');
        }
        
        $formAction = $isAuthenticated ? "/{$lang}/contact" : "#";
        $formMethod = $isAuthenticated ? 'POST' : 'GET';
        
        echo Form::open($formAction, $formMethod)
            ->id('contact-form')
            ->text('subject', 'Predmet')
                ->required()
                ->minLength(3)
                ->maxLength(255)
                ->placeholder('O čemu želite da razgovaramo?')
                ->icon('chatbubble-outline')
                ->autocomplete('off')
                ->disabled($formDisabled)
                ->readonly($formDisabled)
            ->textarea('message', 'Poruka')
                ->required()
                ->minLength(10)
                ->maxLength(5000)
                ->rows(8)
                ->placeholder('Vaša poruka...')
                ->help('Minimum 10 karaktera, maksimum 5000 karaktera.')
                ->disabled($formDisabled)
                ->readonly($formDisabled)
            ->raw('<div style="position: absolute; left: -9999px; opacity: 0; pointer-events: none;" aria-hidden="true">')
            ->text('website_url', 'Website URL')
                ->attr('tabindex', '-1')
                ->attr('autocomplete', 'off')
            ->raw('</div>')
            ->submit('Pošalji poruku')
                ->class('w-full')
                ->disabled($formDisabled)
            ->close();
        @endphp
    </div>

    {{-- Info --}}
    <div class="mt-6 text-center text-sm text-slate-400">
        <p>Vaša poruka će biti pročitana i odgovorena u najkraćem mogućem roku.</p>
    </div>
</div>
