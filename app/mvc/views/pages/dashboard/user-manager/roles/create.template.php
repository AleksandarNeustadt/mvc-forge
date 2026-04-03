{{-- Create Role --}}

<div class="p-8">
    {{-- Page Header --}}
    <div class="mb-8">
    </div>

    {{-- Create Form --}}
    <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-xl p-6">
        @php
        global $router;
        $lang = $router->lang ?? 'sr';
        $form = new FormBuilder("/{$lang}/dashboard/users/roles", 'POST');
        $form->class('space-y-6');

        // Display form errors if any
        if (class_exists('Form') && method_exists('Form', 'hasErrors') && Form::hasErrors()) {
            $form->errors(Form::getErrors());
        }

        // Name
        $form->text('name', 'Role Name')
            ->required()
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary')
            ->placeholder('e.g., Editor, Moderator');

        // Slug
        $form->text('slug', 'Slug')
            ->required()
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary')
            ->placeholder('e.g., editor, moderator');

        // Description
        $form->textarea('description', 'Description')
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary')
            ->attribute('rows', '3')
            ->placeholder('Brief description of this role...');

        // Priority
        $form->number('priority', 'Priority')
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary')
            ->attribute('min', '0')
            ->attribute('max', '1000')
            ->attribute('value', '100')
            ->help('Lower number = higher priority');

        // Permissions
        echo '<div class="space-y-4">';
        echo '<label class="block text-sm font-medium text-slate-300 mb-2">Permissions</label>';
        echo '<div class="bg-slate-900/30 rounded-lg p-4 space-y-4">';
        
        foreach ($permissions as $category => $categoryPermissions):
            echo '<div class="space-y-2">';
            echo '<h4 class="text-sm font-semibold text-theme-primary uppercase tracking-wide">' . htmlspecialchars($category) . '</h4>';
            echo '<div class="space-y-2 pl-4">';
            
            foreach ($categoryPermissions as $permission):
                $permissionArray = is_object($permission) ? $permission->toArray() : $permission;
                $permissionId = $permissionArray['id'] ?? 0;
                $permissionName = $permissionArray['name'] ?? '';
                $permissionSlug = $permissionArray['slug'] ?? '';
                $permissionDesc = $permissionArray['description'] ?? '';
                
                echo '<label class="flex items-start gap-3 p-2 rounded hover:bg-slate-800/50 cursor-pointer">';
                echo '<input type="checkbox" name="permissions[]" value="' . htmlspecialchars($permissionId) . '" class="mt-1 w-4 h-4 text-theme-primary bg-slate-700 border-slate-600 rounded focus:ring-theme-primary">';
                echo '<div class="flex-1">';
                echo '<div class="text-sm font-medium text-white">' . htmlspecialchars($permissionName) . '</div>';
                echo '<div class="text-xs text-slate-400 font-mono">' . htmlspecialchars($permissionSlug) . '</div>';
                if (!empty($permissionDesc)) {
                    echo '<div class="text-xs text-slate-500 mt-1">' . htmlspecialchars($permissionDesc) . '</div>';
                }
                echo '</div>';
                echo '</label>';
            endforeach;
            
            echo '</div>';
            echo '</div>';
        endforeach;
        
        echo '</div>';
        echo '</div>';

        // Submit button
        $form->submit('Create Role')
            ->attribute('class', 'w-full px-6 py-3 bg-theme-primary hover:bg-theme-primary/90 text-white font-medium rounded-lg transition-colors');
        
        echo $form->render();
        @endphp
    </div>

    {{-- Back Link --}}
    <div class="mt-6">
        <a href="/{{ $lang }}/dashboard/users/roles" 
           class="inline-flex items-center gap-2 text-slate-400 hover:text-white transition-colors">
            <ion-icon name="arrow-back-outline"></ion-icon>
            Back to Roles
        </a>
    </div>
</div>
