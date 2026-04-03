<?php
/**
 * Blog List View
 * 
 * Displays all published blog posts with optional list/grid view
 */
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
?>

<div class="w-full pt-3 pb-4">
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl md:text-4xl font-bold text-white mb-4">
                <?= __('blog.all_posts', 'All Blog Posts') ?>
            </h1>

            <?php if (!empty($posts) && is_array($posts)): ?>
                <p class="text-slate-500">
                    <?= count($posts) ?> <?= __('blog.posts', 'posts') ?>
                </p>
            <?php endif; ?>
        </div>

        <!-- Posts -->
        <?php if (!empty($posts) && is_array($posts) && count($posts) > 0): ?>
            <?php if ($viewStyle === 'grid'): ?>
                <!-- Grid View -->
                <?php
                $gridColsClass = match($gridColumns) {
                    1 => 'grid-cols-1',
                    2 => 'grid-cols-1 md:grid-cols-2',
                    3 => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3',
                    4 => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-4',
                    5 => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5',
                    6 => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6',
                    default => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3'
                };
                ?>
                <div class="grid <?= $gridColsClass ?> gap-6">
                    <?php foreach ($posts as $post): ?>
                        <article class="bg-slate-900/50 rounded-lg border border-slate-700 overflow-hidden hover:border-theme-primary/50 transition-colors">
                            <?php if ($showFeaturedImage && !empty($post['featured_image'])): ?>
                                <div class="aspect-video w-full overflow-hidden">
                                    <img src="<?= htmlspecialchars($post['featured_image']) ?>" 
                                         alt="<?= htmlspecialchars($post['title'] ?? '') ?>" 
                                         class="w-full h-full object-cover">
                                </div>
                            <?php endif; ?>
                            
                            <div class="p-6">
                                <h2 class="text-xl font-bold text-white mb-2 hover:text-theme-primary transition-colors">
                                    <a href="<?= htmlspecialchars($post['url'] ?? '#') ?>">
                                        <?= htmlspecialchars($post['title'] ?? 'Untitled') ?>
                                    </a>
                                </h2>

                                <?php if ($showExcerpt && !empty($post['excerpt'])): ?>
                                    <p class="text-slate-400 text-sm mb-4 line-clamp-3">
                                        <?= htmlspecialchars($post['excerpt']) ?>
                                    </p>
                                <?php endif; ?>

                                <!-- Categories -->
                                <?php if (!empty($post['categories'])): ?>
                                    <div class="flex flex-wrap gap-2 mb-4">
                                        <?php foreach (array_slice($post['categories'], 0, 2) as $category): ?>
                                            <span class="px-2 py-1 bg-theme-primary/20 text-theme-primary rounded text-xs">
                                                <?= htmlspecialchars($category['name'] ?? $category['title'] ?? '') ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="flex items-center justify-between text-sm text-slate-500">
                                    <?php if (!empty($post['published_at'])): ?>
                                        <span><?= date('d.m.Y', $post['published_at']) ?></span>
                                    <?php endif; ?>
                                    <a href="<?= htmlspecialchars($post['url'] ?? '#') ?>" 
                                       class="text-theme-primary hover:text-theme-primary/80">
                                        <?= __('blog.read_more', 'Read more') ?> →
                                    </a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php elseif ($viewStyle === 'masonry'): ?>
                <!-- Masonry View -->
                <?php
                $masonryColsClass = match($gridColumns) {
                    1 => 'columns-1',
                    2 => 'columns-1 md:columns-2',
                    3 => 'columns-1 md:columns-2 lg:columns-3',
                    4 => 'columns-1 md:columns-2 lg:columns-4',
                    5 => 'columns-1 md:columns-2 lg:columns-3 xl:columns-5',
                    6 => 'columns-1 md:columns-2 lg:columns-3 xl:columns-6',
                    default => 'columns-1 md:columns-2 lg:columns-3'
                };
                ?>
                <div class="<?= $masonryColsClass ?> gap-6">
                    <?php foreach ($posts as $post): ?>
                        <article class="break-inside-avoid bg-slate-900/50 rounded-lg border border-slate-700 overflow-hidden hover:border-theme-primary/50 transition-colors mb-6">
                            <?php if ($showFeaturedImage && !empty($post['featured_image'])): ?>
                                <div class="w-full overflow-hidden">
                                    <img src="<?= htmlspecialchars($post['featured_image']) ?>" 
                                         alt="<?= htmlspecialchars($post['title'] ?? '') ?>" 
                                         class="w-full h-auto object-cover">
                                </div>
                            <?php endif; ?>
                            
                            <div class="p-6">
                                <h2 class="text-xl font-bold text-white mb-2 hover:text-theme-primary transition-colors">
                                    <a href="<?= htmlspecialchars($post['url'] ?? '#') ?>">
                                        <?= htmlspecialchars($post['title'] ?? 'Untitled') ?>
                                    </a>
                                </h2>

                                <?php if ($showExcerpt && !empty($post['excerpt'])): ?>
                                    <p class="text-slate-400 text-sm mb-4">
                                        <?= htmlspecialchars($post['excerpt']) ?>
                                    </p>
                                <?php endif; ?>

                                <!-- Categories -->
                                <?php if (!empty($post['categories'])): ?>
                                    <div class="flex flex-wrap gap-2 mb-4">
                                        <?php foreach (array_slice($post['categories'], 0, 2) as $category): ?>
                                            <span class="px-2 py-1 bg-theme-primary/20 text-theme-primary rounded text-xs">
                                                <?= htmlspecialchars($category['name'] ?? $category['title'] ?? '') ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="flex items-center justify-between text-sm text-slate-500">
                                    <?php if (!empty($post['published_at'])): ?>
                                        <span><?= date('d.m.Y', $post['published_at']) ?></span>
                                    <?php endif; ?>
                                    <a href="<?= htmlspecialchars($post['url'] ?? '#') ?>" 
                                       class="text-theme-primary hover:text-theme-primary/80">
                                        <?= __('blog.read_more', 'Read more') ?> →
                                    </a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <!-- List View -->
                <div class="space-y-6">
                    <?php foreach ($posts as $post): ?>
                        <article class="bg-slate-900/50 rounded-lg border border-slate-700 p-6 hover:border-theme-primary/50 transition-colors">
                            <div class="flex flex-col md:flex-row gap-6">
                                <?php if ($showFeaturedImage && !empty($post['featured_image'])): ?>
                                    <div class="w-full md:w-64 flex-shrink-0">
                                        <img src="<?= htmlspecialchars($post['featured_image']) ?>" 
                                             alt="<?= htmlspecialchars($post['title'] ?? '') ?>" 
                                             class="w-full h-48 object-cover rounded-lg">
                                    </div>
                                <?php endif; ?>
                                
                                <div class="flex-1">
                                    <h2 class="text-2xl font-bold text-white mb-2 hover:text-theme-primary transition-colors">
                                        <a href="<?= htmlspecialchars($post['url'] ?? '#') ?>">
                                            <?= htmlspecialchars($post['title'] ?? 'Untitled') ?>
                                        </a>
                                    </h2>

                                    <?php if ($showExcerpt && !empty($post['excerpt'])): ?>
                                        <p class="text-slate-400 mb-4">
                                            <?= htmlspecialchars($post['excerpt']) ?>
                                        </p>
                                    <?php endif; ?>

                                    <!-- Categories -->
                                    <?php if (!empty($post['categories'])): ?>
                                        <div class="flex flex-wrap gap-2 mb-4">
                                            <?php foreach ($post['categories'] as $category): ?>
                                                <span class="px-3 py-1 bg-theme-primary/20 text-theme-primary rounded-full text-sm">
                                                    <?= htmlspecialchars($category['name'] ?? $category['title'] ?? '') ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>

                                    <div class="flex items-center justify-between text-sm text-slate-500">
                                        <div class="flex items-center gap-4">
                                            <?php if (!empty($post['published_at'])): ?>
                                                <span><?= date('d.m.Y', $post['published_at']) ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($post['views'])): ?>
                                                <span><?= number_format($post['views']) ?> <?= __('blog.views', 'views') ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <a href="<?= htmlspecialchars($post['url'] ?? '#') ?>" 
                                           class="text-theme-primary hover:text-theme-primary/80">
                                            <?= __('blog.read_more', 'Read more') ?> →
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="text-center py-12 bg-slate-900/50 rounded-lg border border-slate-700">
                <p class="text-slate-400 text-lg"><?= __('blog.no_posts', 'No blog posts available yet.') ?></p>
            </div>
        <?php endif; ?>
</div>

