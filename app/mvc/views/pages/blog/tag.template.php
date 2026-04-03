{{-- Blog Tag View --}}
{{-- Displays posts with a specific tag with optional list/grid/masonry view --}}

@php
// Initialize display options
$displayOptions = $displayOptions ?? [
    'style' => 'list',
    'posts_per_page' => 10,
    'show_excerpt' => true,
    'show_featured_image' => true,
    'grid_columns' => 3
];
$viewStyle = $displayOptions['style'] ?? 'list';
$postsPerPage = (int) ($displayOptions['posts_per_page'] ?? 10);
$showExcerpt = $displayOptions['show_excerpt'] ?? true;
$showFeaturedImage = $displayOptions['show_featured_image'] ?? true;
$gridColumns = (int) ($displayOptions['grid_columns'] ?? 3);
// Ensure grid columns is between 1 and 6
$gridColumns = max(1, min(6, $gridColumns));
@endphp

<div class="w-full pt-3 pb-4">
    @if (isset($tag) && !empty($tag))
        {{-- Tag Header --}}
        <div class="mb-8">
            <h1 class="text-3xl md:text-4xl font-bold text-white mb-4">
                <span class="text-theme-primary">#</span>{{ $tag['name'] ?? $tag['title'] ?? 'Tag' }}
            </h1>

            @if (!empty($posts) && is_array($posts))
                <p class="text-slate-500">
                    {{ count($posts) }} {{ __('blog.posts', 'posts') }}
                </p>
            @endif
        </div>

        {{-- Posts --}}
        @if (!empty($posts) && is_array($posts))
            @if ($viewStyle === 'grid')
                {{-- Grid View --}}
                @php
                $gridColsClass = match($gridColumns) {
                    1 => 'grid-cols-1',
                    2 => 'grid-cols-1 md:grid-cols-2',
                    3 => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3',
                    4 => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-4',
                    5 => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5',
                    6 => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6',
                    default => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3'
                };
                @endphp
                <div class="grid {{ $gridColsClass }} gap-6">
                    @foreach ($posts as $post)
                        <article class="bg-slate-900/50 rounded-lg border border-slate-700 overflow-hidden hover:border-theme-primary/50 transition-colors">
                            @if ($showFeaturedImage && !empty($post['featured_image']))
                                <div class="aspect-video w-full overflow-hidden">
                                    <img src="{{ $post['featured_image'] }}" 
                                         alt="{{ $post['title'] ?? '' }}" 
                                         class="w-full h-full object-cover">
                                </div>
                            @endif
                            
                            <div class="p-6">
                                <h2 class="text-xl font-bold text-white mb-2 hover:text-theme-primary transition-colors">
                                    <a href="{{ $post['url'] ?? '#' }}">
                                        {{ $post['title'] ?? 'Untitled' }}
                                    </a>
                                </h2>

                                @if ($showExcerpt && !empty($post['excerpt']))
                                    <p class="text-slate-400 text-sm mb-4 line-clamp-3">
                                        {{ $post['excerpt'] }}
                                    </p>
                                @endif

                                <div class="flex items-center justify-between text-sm text-slate-500">
                                    @if (!empty($post['published_at']))
                                        <span>{{ date('d.m.Y', $post['published_at']) }}</span>
                                    @endif
                                    <a href="{{ $post['url'] ?? '#' }}" 
                                       class="text-theme-primary hover:text-theme-primary/80">
                                        {{ __('blog.read_more', 'Read more') }} →
                                    </a>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @elseif ($viewStyle === 'masonry')
                {{-- Masonry View --}}
                @php
                $masonryColsClass = match($gridColumns) {
                    1 => 'columns-1',
                    2 => 'columns-1 md:columns-2',
                    3 => 'columns-1 md:columns-2 lg:columns-3',
                    4 => 'columns-1 md:columns-2 lg:columns-4',
                    5 => 'columns-1 md:columns-2 lg:columns-3 xl:columns-5',
                    6 => 'columns-1 md:columns-2 lg:columns-3 xl:columns-6',
                    default => 'columns-1 md:columns-2 lg:columns-3'
                };
                @endphp
                <div class="{{ $masonryColsClass }} gap-6">
                    @foreach ($posts as $post)
                        <article class="break-inside-avoid bg-slate-900/50 rounded-lg border border-slate-700 overflow-hidden hover:border-theme-primary/50 transition-colors mb-6">
                            @if ($showFeaturedImage && !empty($post['featured_image']))
                                <div class="w-full overflow-hidden">
                                    <img src="{{ $post['featured_image'] }}" 
                                         alt="{{ $post['title'] ?? '' }}" 
                                         class="w-full h-auto object-cover">
                                </div>
                            @endif
                            
                            <div class="p-6">
                                <h2 class="text-xl font-bold text-white mb-2 hover:text-theme-primary transition-colors">
                                    <a href="{{ $post['url'] ?? '#' }}">
                                        {{ $post['title'] ?? 'Untitled' }}
                                    </a>
                                </h2>

                                @if ($showExcerpt && !empty($post['excerpt']))
                                    <p class="text-slate-400 text-sm mb-4">
                                        {{ $post['excerpt'] }}
                                    </p>
                                @endif

                                <div class="flex items-center justify-between text-sm text-slate-500">
                                    @if (!empty($post['published_at']))
                                        <span>{{ date('d.m.Y', $post['published_at']) }}</span>
                                    @endif
                                    <a href="{{ $post['url'] ?? '#' }}" 
                                       class="text-theme-primary hover:text-theme-primary/80">
                                        {{ __('blog.read_more', 'Read more') }} →
                                    </a>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                {{-- List View (default) --}}
                <div class="space-y-6">
                    @foreach ($posts as $post)
                        <article class="bg-slate-900/50 rounded-lg border border-slate-700 p-6 hover:border-theme-primary/50 transition-colors">
                            <div class="flex flex-col md:flex-row gap-6">
                                @if ($showFeaturedImage && !empty($post['featured_image']))
                                    <div class="w-full md:w-64 flex-shrink-0">
                                        <img src="{{ $post['featured_image'] }}" 
                                             alt="{{ $post['title'] ?? '' }}" 
                                             class="w-full h-48 object-cover rounded-lg">
                                    </div>
                                @endif
                                
                                <div class="flex-1">
                                    <h2 class="text-2xl font-bold text-white mb-2 hover:text-theme-primary transition-colors">
                                        <a href="{{ $post['url'] ?? '#' }}">
                                            {{ $post['title'] ?? 'Untitled' }}
                                        </a>
                                    </h2>

                                    @if ($showExcerpt && !empty($post['excerpt']))
                                        <p class="text-slate-400 mb-4">
                                            {{ $post['excerpt'] }}
                                        </p>
                                    @endif

                                    <div class="flex items-center justify-between text-sm text-slate-500">
                                        <div class="flex items-center gap-4">
                                            @if (!empty($post['published_at']))
                                                <span>{{ date('d.m.Y', $post['published_at']) }}</span>
                                            @endif
                                            @if (!empty($post['views']))
                                                <span>{{ number_format($post['views']) }} {{ __('blog.views', 'views') }}</span>
                                            @endif
                                        </div>
                                        <a href="{{ $post['url'] ?? '#' }}" 
                                           class="text-theme-primary hover:text-theme-primary/80">
                                            {{ __('blog.read_more', 'Read more') }} →
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        @else
            <div class="text-center py-12 bg-slate-900/50 rounded-lg border border-slate-700">
                <p class="text-slate-400 text-lg">{{ __('blog.no_posts', 'No posts with this tag yet.') }}</p>
            </div>
        @endif
    @else
        <div class="text-center py-12">
            <p class="text-slate-400 text-lg">{{ __('blog.tag_not_found', 'Tag not found.') }}</p>
        </div>
    @endif
</div>
