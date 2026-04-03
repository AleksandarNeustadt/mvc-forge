<div class="p-8 max-w-4xl">
    <!-- Page Header -->
    <div class="mb-8">
    </div>

    <!-- Create Form -->
    <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-xl p-6">
        <?php
        global $router;
        $lang = $router->lang ?? 'sr';
        $form = new FormBuilder("/{$lang}/dashboard/blog/categories", 'POST');
        $form->class('space-y-6');
        
        // Display form errors if any
        if (Form::hasErrors()) {
            $form->errors(Form::getErrors());
        }

        // Name
        $form->text('name', 'Category Name')
            ->required()
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary')
            ->attribute('placeholder', 'Category Name')
            ->attribute('id', 'name_input')
            ->attribute('oninput', 'syncSlugFromName()');

        // Slug
        $form->text('slug', 'Slug')
            ->required()
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary')
            ->attribute('placeholder', 'category-slug')
            ->attribute('id', 'slug_input');

        // Language (custom select with flags)
        if (isset($languagesData) && !empty($languagesData)) {
            require_once __DIR__ . '/../../../../helpers/language-select.php';
            $selectedLangId = Form::old('language_id', '');
            renderLanguageSelect($form, $languagesData, $selectedLangId);
        } elseif (isset($languages) && !empty($languages)) {
            $form->select('language_id', 'Language', $languages)
                ->fieldClass('w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary')
                ->attribute('placeholder', 'Select language');
        }

        // Description
        $form->textarea('description', 'Description')
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary')
            ->attribute('rows', '4')
            ->attribute('placeholder', 'Category description...');

        // Parent Category
        $form->select('parent_id', 'Parent Category', $parentCategories)
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary');

        // Image
        $form->text('image', 'Image URL')
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary')
            ->attribute('placeholder', 'https://example.com/image.jpg');

        // Sort Order
        $form->number('sort_order', 'Sort Order')
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary')
            ->attribute('min', '0')
            ->default(0);

        // Meta Title
        $form->text('meta_title', 'Meta Title (SEO)')
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary');

        // Meta Description
        $form->textarea('meta_description', 'Meta Description (SEO)')
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary')
            ->attribute('rows', '3');

        // Submit button
        $form->submit('Create Category')
            ->attribute('class', 'w-full px-6 py-3 bg-theme-primary hover:bg-theme-primary/90 text-white font-medium rounded-lg transition-colors');

        echo $form->render();
        ?>
    </div>

    <!-- Back Link -->
    <div class="mt-6">
        <?php global $router; $lang = $router->lang ?? 'sr'; ?>
        <a href="/<?= $lang ?>/dashboard/blog/categories" 
           class="inline-flex items-center gap-2 text-slate-400 hover:text-white transition-colors">
            <ion-icon name="arrow-back-outline"></ion-icon>
            Back to Categories
        </a>
    </div>

</div>

<script nonce="<?= function_exists('csp_nonce') ? csp_nonce() : '' ?>">
// Sync slug from name
function syncSlugFromName() {
    const nameInput = document.getElementById('name_input');
    const slugInput = document.getElementById('slug_input');
    
    if (!nameInput || !slugInput) return;
    
    // Only auto-fill if slug is empty
    if (!slugInput.value.trim()) {
        const name = nameInput.value.trim();
        if (name) {
            // Simple slug conversion
            slugInput.value = name.toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .trim('-');
        }
    }
}
</script>
