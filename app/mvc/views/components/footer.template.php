{{-- Footer Component - Global Footer --}}

@php
$currentLang = $lang ?? 'sr';

// Get current language ID for filtering
$currentLanguageId = null;
if (class_exists('Language')) {
    $currentLanguage = Language::findByCode($currentLang);
    if ($currentLanguage) {
        $currentLanguageId = $currentLanguage->id;
    }
}

$footerMenus = [];
if (class_exists('NavigationMenu')) {
    $footerMenus = NavigationMenu::getByPosition('footer', $currentLanguageId);
    $footerMenus = is_array($footerMenus) ? $footerMenus : [];
}
@endphp

<footer class="relative border-t border-slate-800/50 bg-slate-950/80 backdrop-blur-md z-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        {{-- Footer Navigation Menus --}}
        @if (!empty($footerMenus))
            <div class="mb-4 pb-4 border-b border-slate-800/50">
                <div class="flex flex-wrap gap-6 justify-center">
                    @foreach ($footerMenus as $menu)
                        @php
                        $menuItems = $menu->getMenuItems();
                        $menuItems = is_array($menuItems) ? $menuItems : [];
                        @endphp
                        @if (!empty($menuItems))
                            <div class="flex flex-col gap-2">
                                <h3 class="text-sm font-semibold text-slate-300 mb-2">{{ e($menu->name) }}</h3>
                                <div class="flex flex-col gap-1">
                                    @foreach ($menuItems as $menuPage)
                                        <a href="/{{ $currentLang }}{{ $menuPage->route }}" 
                                           class="text-xs sm:text-sm text-slate-400 hover:text-theme-primary transition-colors">
                                            {{ e($menuPage->title ?? '') }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif
        
        <div class="flex items-center justify-between text-xs sm:text-sm text-slate-400">
            {{-- Tech Stack (Left) --}}
            <span class="inline-flex items-center gap-1 sm:gap-2">
                <span class="whitespace-nowrap">Betrieben von</span>
                <a href="https://vitejs.dev" target="_blank" rel="noopener noreferrer" class="text-theme-primary hover:underline">Vite</a>
                <span class="text-slate-600">+</span>
                <span class="text-theme-primary">PHP MVC</span>
                <span class="text-slate-600">+</span>
                <a href="https://tailwindcss.com" target="_blank" rel="noopener noreferrer" class="text-theme-primary hover:underline">Tailwind</a>
            </span>

            {{-- Copyright (Center) --}}
            <span class="text-slate-500 whitespace-nowrap">&copy; {{ date('Y') }} {{ Env::get('BRAND_NAME', 'aleksandar.pro') }}</span>

            {{-- Privacy Policy Link (Right) --}}
            <a
                href="{{ Env::get('PRIVACY_POLICY_URL', 'https://policies.google.com/privacy') }}"
                target="_blank"
                rel="noopener noreferrer"
                class="hover:text-theme-primary transition-colors inline-flex items-center gap-1 whitespace-nowrap"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
                Privacy Policy
            </a>
        </div>
    </div>
</footer>
