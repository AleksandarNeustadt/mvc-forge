<?php
/**
 * Default Custom Page Template
 * 
 * This template is used for custom pages created in Page Manager
 */
?>
<div class="w-full pt-3 pb-4">
    <!-- Page Title -->
    <h1 class="text-3xl md:text-4xl font-bold text-white mb-6">
        <?= htmlspecialchars($page['title'] ?? 'Page') ?>
    </h1>

    <!-- Page Content -->
    <div class="prose prose-invert prose-lg max-w-none">
        <?php if (!empty($page['content'])): ?>
            <div class="text-slate-300 leading-relaxed whitespace-pre-wrap">
                <?= nl2br(htmlspecialchars($page['content'])) ?>
            </div>
        <?php else: ?>
            <p class="text-slate-400 italic">No content available for this page.</p>
        <?php endif; ?>
    </div>
</div>

