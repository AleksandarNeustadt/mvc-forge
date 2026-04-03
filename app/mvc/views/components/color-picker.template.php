{{-- Color Picker Component - Slides left, colors follow --}}

<div
    id="colorPickerContainer"
    class="fixed right-0 top-1/2 -translate-y-1/2 z-30 flex items-center transition-transform duration-300 translate-x-[calc(100%-48px)]"
>
    {{-- Toggle Button (visible initially) --}}
    <button
        id="colorPickerToggle"
        class="w-12 h-12 flex items-center justify-center bg-slate-800/90 backdrop-blur-sm rounded-l-xl border border-slate-700/50 border-r-0 hover:border-theme-primary/50 transition-all shadow-lg flex-shrink-0"
        aria-label="Theme Color Picker"
    >
        <ion-icon name="color-palette" class="text-theme-primary text-2xl"></ion-icon>
    </button>

    {{-- Colors Panel (hidden initially, slides in with container) --}}
    <div
        id="colorPickerColors"
        class="flex flex-col gap-2 bg-slate-800/95 backdrop-blur-md border border-slate-700/50 border-l-0 p-3 shadow-2xl transition-opacity duration-300 opacity-0"
    >
        <button data-theme="cyan" class="theme-btn w-10 h-10 rounded-lg bg-gradient-to-br from-cyan-500 to-cyan-600 hover:scale-110 transition-transform shadow-lg ring-2 ring-transparent hover:ring-cyan-400" title="Cyan"></button>
        <button data-theme="purple" class="theme-btn w-10 h-10 rounded-lg bg-gradient-to-br from-purple-500 to-purple-600 hover:scale-110 transition-transform shadow-lg ring-2 ring-transparent hover:ring-purple-400" title="Purple"></button>
        <button data-theme="pink" class="theme-btn w-10 h-10 rounded-lg bg-gradient-to-br from-pink-500 to-pink-600 hover:scale-110 transition-transform shadow-lg ring-2 ring-transparent hover:ring-pink-400" title="Pink"></button>
        <button data-theme="emerald" class="theme-btn w-10 h-10 rounded-lg bg-gradient-to-br from-emerald-500 to-emerald-600 hover:scale-110 transition-transform shadow-lg ring-2 ring-transparent hover:ring-emerald-400" title="Emerald"></button>
        <button data-theme="orange" class="theme-btn w-10 h-10 rounded-lg bg-gradient-to-br from-orange-500 to-orange-600 hover:scale-110 transition-transform shadow-lg ring-2 ring-transparent hover:ring-orange-400" title="Orange"></button>
        <button data-theme="red" class="theme-btn w-10 h-10 rounded-lg bg-gradient-to-br from-red-500 to-red-600 hover:scale-110 transition-transform shadow-lg ring-2 ring-transparent hover:ring-red-400" title="Red"></button>
    </div>
</div>
