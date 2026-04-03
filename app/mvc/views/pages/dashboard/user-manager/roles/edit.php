<div class="p-8">
    <!-- Page Header -->
    <div class="mb-8">
    </div>

    <!-- Edit Form -->
    <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-xl p-6">
        <?php
        // Ensure $role is an array
        if (is_object($role)) {
            $role = (array) $role;
        }
        if (!is_array($role)) {
            throw new Exception('Role data must be an array');
        }
        
        $roleId = (int)($role['id'] ?? 0);
        global $router;
        $lang = $router->lang ?? 'sr';
        $form = new FormBuilder("/{$lang}/dashboard/users/roles/{$roleId}", 'PUT');
        $form->class('space-y-6');
        $form->withOld($role);

        // Display form errors if any
        if (Form::hasErrors()) {
            $form->errors(Form::getErrors());
        }

        // Name
        $form->text('name', 'Role Name')
            ->required()
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary')
            ->disabled(!empty($role['is_system']));

        // Slug
        $form->text('slug', 'Slug')
            ->required()
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary')
            ->disabled(!empty($role['is_system']));

        // Description
        $form->textarea('description', 'Description')
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary')
            ->attribute('rows', '3');

        // Priority
        $form->number('priority', 'Priority')
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary')
            ->attribute('min', '0')
            ->attribute('max', '1000')
            ->help('Lower number = higher priority');

        // System role notice
        if (!empty($role['is_system'])) {
            echo '<div class="bg-blue-500/20 border border-blue-500/50 rounded-lg p-4">';
            echo '<div class="flex items-center gap-2 text-blue-400">';
            echo '<ion-icon name="information-circle-outline"></ion-icon>';
            echo '<span class="text-sm font-medium">This is a system role. Name and slug cannot be modified.</span>';
            echo '</div>';
            echo '</div>';
        }

        // Permissions
        echo '<div class="space-y-4">';
        echo '<label class="block text-sm font-medium text-slate-300 mb-2">Permissions</label>';
        echo '<div class="bg-slate-900/30 rounded-lg p-4 space-y-4">';
        
        foreach ($permissions as $category => $categoryPermissions):
            echo '<div class="space-y-2">';
            echo '<h4 class="text-sm font-semibold text-theme-primary uppercase tracking-wide">' . e($category) . '</h4>';
            echo '<div class="space-y-2 pl-4">';
            
            foreach ($categoryPermissions as $permission):
                $permissionArray = is_object($permission) ? $permission->toArray() : $permission;
                $permissionId = $permissionArray['id'] ?? 0;
                $permissionName = $permissionArray['name'] ?? '';
                $permissionSlug = $permissionArray['slug'] ?? '';
                $permissionDesc = $permissionArray['description'] ?? '';
                $isChecked = in_array($permissionId, $rolePermissionIds ?? []);
                
                echo '<label class="flex items-start gap-3 p-2 rounded hover:bg-slate-800/50 cursor-pointer">';
                echo '<input type="checkbox" name="permissions[]" value="' . $permissionId . '" ' . ($isChecked ? 'checked' : '') . ' class="mt-1 w-4 h-4 text-theme-primary bg-slate-700 border-slate-600 rounded focus:ring-theme-primary">';
                echo '<div class="flex-1">';
                echo '<div class="text-sm font-medium text-white">' . e($permissionName) . '</div>';
                echo '<div class="text-xs text-slate-400 font-mono">' . e($permissionSlug) . '</div>';
                if (!empty($permissionDesc)) {
                    echo '<div class="text-xs text-slate-500 mt-1">' . e($permissionDesc) . '</div>';
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
        $form->submit('Update Role');
        $form->close();
        ?>
    </div>
</div>

