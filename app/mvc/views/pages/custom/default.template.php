{{-- Default Custom Page Template using Template Engine --}}

<div class="w-full pt-3 pb-4">
    {{-- Page Title --}}
    <h1 class="text-3xl md:text-4xl font-bold text-white mb-6">
        {{ $page['title'] ?? 'Page' }}
    </h1>

    {{-- Page Content --}}
    <div class="prose prose-invert prose-lg max-w-none">
        @if (!empty($page['content']))
            <div class="text-slate-300 leading-relaxed whitespace-pre-wrap">
                {!! nl2br(htmlspecialchars($page['content'])) !!}
            </div>
        @else
            <p class="text-slate-400 italic">No content available for this page.</p>
        @endif
    </div>
</div>
