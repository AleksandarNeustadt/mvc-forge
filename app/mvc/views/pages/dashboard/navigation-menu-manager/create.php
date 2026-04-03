<div class="p-8 max-w-2xl">
    <!-- Page Header -->
    <div class="mb-8">
    </div>

    <!-- Create Form -->
    <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-xl p-6">
        <?php
        global $router;
        $lang = $router->lang ?? 'sr';
        
        $form = new FormBuilder("/{$lang}/dashboard/navigation-menus", 'POST');
        $form->class('space-y-6');
        
        // Display form errors if any
        if (Form::hasErrors()) {
            $form->errors(Form::getErrors());
        }

        // Name
        $form->text('name', 'Menu Name')
            ->required()
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary')
            ->attribute('placeholder', 'e.g., Navbar-header, navbar-footer');

        // Position
        $form->select('position', 'Position', [
            'header' => 'Header',
            'footer' => 'Footer',
        ])
            ->required()
            ->fieldClass('w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary')
            ->attribute('placeholder', 'Select position');

        // Language (custom select with flags)
        if (isset($languagesData) && !empty($languagesData)) {
            require_once __DIR__ . '/../../../helpers/language-select.php';
            $selectedLangId = Form::old('language_id', '');
            renderLanguageSelect($form, $languagesData, $selectedLangId);
        } elseif (isset($languages) && !empty($languages)) {
            $form->select('language_id', 'Language', $languages)
                ->fieldClass('w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary')
                ->attribute('placeholder', 'Select language');
        }

        // Order
        $form->number('menu_order', 'Order')
            ->default(0)
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary')
            ->attribute('min', '0')
            ->attribute('placeholder', '0');

        // Is Active
        $form->checkbox('is_active', 'Active')
            ->attribute('class', 'w-4 h-4 text-theme-primary bg-slate-900 border-slate-700 rounded focus:ring-theme-primary')
            ->checked(true);

        // Submit button
        $form->submit('Create Menu')
            ->attribute('class', 'w-full px-6 py-3 bg-theme-primary hover:bg-theme-primary/90 text-white font-medium rounded-lg transition-colors');

        echo $form->render();
        ?>
    </div>

    <!-- Back Link -->
    <div class="mt-6">
        <a href="/<?= $lang ?>/dashboard/navigation-menus" 
           class="inline-flex items-center gap-2 text-slate-400 hover:text-white transition-colors">
            <ion-icon name="arrow-back-outline"></ion-icon>
            Back to Navigation Menus
        </a>
    </div>
</div>

