<div class="p-8 max-w-2xl">
    <!-- Page Header -->
    <div class="mb-8">
    </div>

    <!-- Edit Form -->
    <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-xl p-6">
        <?php
        // Use $navigationMenu instead of $menu to avoid conflict with header's $menu variable
        $menu = $navigationMenu ?? null;
        
        // Get menu ID - try from array first, then from object
        $menuId = null;
        if (is_array($menu) && isset($menu['id'])) {
            $menuId = (int)$menu['id'];
            $menuData = $menu;
        } elseif (is_object($menu)) {
            // If it's an object, get ID first, then convert to array
            $menuId = isset($menu->id) ? (int)$menu->id : null;
            if (method_exists($menu, 'toArray')) {
                $menuData = $menu->toArray();
                // Ensure ID is in array
                if (!isset($menuData['id']) && $menuId) {
                    $menuData['id'] = $menuId;
                }
            } else {
                $menuData = (array) $menu;
            }
        } else {
            $menuData = is_array($menu) ? $menu : [];
        }
        
        // Final check - if we still don't have ID, try to get it from menuData
        if (!$menuId && isset($menuData['id'])) {
            $menuId = (int)$menuData['id'];
        }
        
        if (!$menuId) {
            error_log("Edit navigation menu - Failed to get menu ID. Menu type: " . gettype($menu) . ", Menu data keys: " . json_encode(is_array($menuData) ? array_keys($menuData) : 'not array'));
            throw new Exception('Menu ID is required for editing');
        }
        
        global $router;
        $lang = $router->lang ?? 'sr';
        
        $form = new FormBuilder("/{$lang}/dashboard/navigation-menus/{$menuId}", 'PUT');
        $form->class('space-y-6');
        $form->withOld($menuData);
        
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
            ->fieldClass('w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary');

        // Language (custom select with flags)
        if (isset($languagesData) && !empty($languagesData)) {
            require_once __DIR__ . '/../../../helpers/language-select.php';
            $selectedLangId = Form::old('language_id', $menuData['language_id'] ?? '');
            renderLanguageSelect($form, $languagesData, $selectedLangId);
        } elseif (isset($languages) && !empty($languages)) {
            $form->select('language_id', 'Language', $languages)
                ->fieldClass('w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary')
                ->attribute('placeholder', 'Select language');
        }

        // Order
        $form->number('menu_order', 'Order')
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary')
            ->attribute('min', '0')
            ->attribute('placeholder', '0');

        // Is Active
        $form->checkbox('is_active', 'Active')
            ->attribute('class', 'w-4 h-4 text-theme-primary bg-slate-900 border-slate-700 rounded focus:ring-theme-primary');

        // Submit button
        $form->submit('Update Menu')
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

