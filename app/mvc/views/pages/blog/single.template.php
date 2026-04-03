{{-- Single Blog Post View --}}
{{-- Displays a single blog post with full content --}}

<div class="w-full max-w-5xl mx-auto pt-3 pb-4">
    @if (isset($blogPost) && !empty($blogPost))
        {{-- Back Link (only in preview mode from dashboard) --}}
        @if (isset($isPreview) && $isPreview)
            @php
            global $router;
            $lang = $router->lang ?? 'sr';
            @endphp
            <a href="/{{ $lang }}/dashboard/blog/posts" class="inline-flex items-center text-theme-primary hover:text-theme-primary/80 mb-4 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Dashboard
            </a>
        @endif

        {{-- Post Header --}}
        <article class="bg-slate-900/50 rounded-lg border border-slate-700 p-6 md:p-8 xl:p-10">
            {{-- Title --}}
            <h1 class="text-3xl md:text-4xl font-bold text-white mb-4">
                {{ $blogPost['title'] ?? 'Untitled Post' }}
            </h1>

            {{-- Meta Information --}}
            <div class="flex flex-wrap items-center gap-4 text-slate-400 text-sm mb-6 pb-6 border-b border-slate-700">
                @if (!empty($blogPost['author']))
                    <div class="flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        {{ $blogPost['author']['name'] ?? $blogPost['author']['username'] ?? 'Unknown' }}
                    </div>
                @endif
                
                @if (!empty($blogPost['published_at']))
                    <div class="flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        {{ date('d.m.Y', $blogPost['published_at']) }}
                    </div>
                @endif

                @if (!empty($blogPost['views']))
                    <div class="flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        {{ number_format($blogPost['views']) }} {{ __('blog.views', 'views') }}
                    </div>
                @endif
            </div>

            {{-- Featured Image --}}
            @if (!empty($blogPost['featured_image']))
                <div class="mb-6">
                    <img src="{{ $blogPost['featured_image'] }}" 
                         alt="{{ $blogPost['title'] ?? '' }}" 
                         class="w-full h-auto rounded-lg">
                </div>
            @endif

            {{-- Categories --}}
            @if (!empty($blogPost['categories']))
                <div class="flex flex-wrap gap-2 mb-6">
                    @foreach ($blogPost['categories'] as $category)
                        <span class="px-3 py-1 bg-theme-primary/20 text-theme-primary rounded-full text-sm">
                            {{ $category['name'] ?? $category['title'] ?? '' }}
                        </span>
                    @endforeach
                </div>
            @endif

            {{-- Content --}}
            <div class="prose prose-invert prose-lg max-w-4xl">
                <div class="text-slate-300 leading-relaxed blog-content">
                    @if (!empty($blogPost['content']))
                        {!! Security::sanitizeHtml($blogPost['content']) !!}
                    @else
                        <p class="text-slate-400 italic">{{ __('blog.no_content', 'No content available.') }}</p>
                    @endif
                </div>
            </div>

            {{-- Keywords --}}
            @if (!empty($blogPost['meta_keywords']))
                <div class="mt-8 pt-6 border-t border-slate-700">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-slate-400 text-sm font-medium">{{ __('blog.keywords', 'Keywords') }}:</span>
                        @php
                        $keywords = explode(',', $blogPost['meta_keywords']);
                        @endphp
                        @foreach ($keywords as $keyword)
                            @php
                            $keyword = trim($keyword);
                            @endphp
                            @if (!empty($keyword))
                                <span class="px-3 py-1 bg-slate-800 text-slate-300 rounded-full text-sm">
                                    {{ $keyword }}
                                </span>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif
        </article>
    @else
        <div class="text-center py-12">
            <p class="text-slate-400 text-lg">{{ __('blog.post_not_found', 'Blog post not found.') }}</p>
        </div>
    @endif
</div>
