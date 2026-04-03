<div class="p-8">
    
    <!-- Page Header -->
    <div class="mb-8">
    </div>

    <!-- Edit Form -->
    <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-xl p-6">
        <?php
        // Use 'editUser' variable to avoid conflict with header's $user variable
        $editUser = $editUser ?? null;
        
        // Ensure $editUser is an array
        if (is_object($editUser)) {
            $editUser = (array) $editUser;
        }
        if (!is_array($editUser)) {
            throw new Exception('User data must be an array');
        }
        
        // Ensure user ID exists
        if (empty($editUser['id'])) {
            throw new Exception('User ID is required for editing');
        }
        $userId = (int)$editUser['id'];
        
        // Debug: Log user data
        error_log("Edit view - User ID: {$userId}");
        error_log("Edit view - User data: " . json_encode($editUser));
        
        // Clear old input from session before creating form (to prevent using stale data)
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        unset($_SESSION['_old_input']);
        
        global $router;
        $lang = $router->lang ?? 'sr';
        // Use POST method directly (route exists for POST)
        $form = new FormBuilder("/{$lang}/dashboard/users/{$userId}", 'POST');
        $form->class('space-y-6');
        $form->files(true); // Enable file uploads (adds enctype="multipart/form-data")
        $form->attribute('novalidate', ''); // Disable browser validation (we use backend validation)
        $form->withOld($editUser);

        // Display form errors if any
        if (Form::hasErrors()) {
            $form->errors(Form::getErrors());
        }

        // Username
        $form->text('username', 'Username')
            ->required()
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary')
            ->autocomplete('username');

        // Email
        $form->email('email', 'Email')
            ->required()
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary')
            ->autocomplete('email');

        // First Name
        $form->text('first_name', 'First Name')
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary');

        // Last Name
        $form->text('last_name', 'Last Name')
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary');

        // Avatar Upload
        ?>
        <div class="space-y-4">
            <label class="block text-sm font-medium text-slate-300">Profile Picture</label>
            
            <!-- Current Avatar Preview -->
            <?php if (!empty($editUser['avatar'])): ?>
                <div class="flex items-center gap-4 mb-4">
                    <div class="relative">
                        <img src="<?= e($editUser['avatar']) ?>" 
                             alt="Current avatar" 
                             class="w-24 h-24 rounded-full object-cover border-2 border-slate-700"
                             id="current-avatar-preview">
                        <button type="button" 
                                onclick="deleteAvatar()" 
                                class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center transition-colors"
                                title="Delete avatar">
                            <ion-icon name="close-outline" class="text-sm"></ion-icon>
                        </button>
                    </div>
                    <div class="text-sm text-slate-400">
                        <p>Current profile picture</p>
                        <p class="text-xs mt-1">Click X to remove</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="mb-4">
                    <div class="w-24 h-24 rounded-full bg-slate-700/50 border-2 border-slate-600 flex items-center justify-center">
                        <ion-icon name="person-outline" class="text-4xl text-slate-500"></ion-icon>
                    </div>
                    <p class="text-sm text-slate-400 mt-2">No profile picture</p>
                </div>
            <?php endif; ?>
            
            <!-- Upload New Avatar -->
            <div class="space-y-2">
                <input type="file" 
                       name="avatar" 
                       id="avatar-input" 
                       accept="image/jpeg,image/png,image/gif,image/webp"
                       class="hidden"
                       onchange="previewAvatar(this)">
                <label for="avatar-input" 
                       class="inline-flex items-center gap-2 px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-lg cursor-pointer transition-colors">
                    <ion-icon name="image-outline"></ion-icon>
                    <span><?= !empty($editUser['avatar']) ? 'Change Profile Picture' : 'Upload Profile Picture' ?></span>
                </label>
                <p class="text-xs text-slate-400">Max size: 2MB. Allowed: JPEG, PNG, GIF, WebP</p>
                
                <!-- Preview of new upload -->
                <div id="new-avatar-preview" class="hidden mt-4">
                    <p class="text-sm text-slate-300 mb-2">New preview:</p>
                    <img id="avatar-preview-img" 
                         src="" 
                         alt="Preview" 
                         class="w-24 h-24 rounded-full object-cover border-2 border-theme-primary">
                </div>
            </div>
            
            <!-- Hidden field to track avatar deletion -->
            <input type="hidden" name="delete_avatar" id="delete-avatar-flag" value="0">
        </div>
        <?php

        // Password (optional)
        $form->password('password', 'New Password')
            ->placeholder('Leave blank to keep current password')
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary')
            ->autocomplete('new-password');

        // Newsletter
        $form->checkbox('newsletter', 'Subscribe to newsletter')
            ->attribute('class', 'w-4 h-4 text-theme-primary bg-slate-900 border-slate-700 rounded focus:ring-theme-primary');

        // Status
        $form->select('status', 'Status', [
            'active' => 'Active',
            'pending' => 'Pending Approval',
            'banned' => 'Banned'
        ])
            ->required()
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary');

        // Roles (only if user has permission to manage roles)
        // Ensure User and Role classes are loaded (already loaded in public/index.php, but check just in case)
        $currentUser = isset($_SESSION['user_id']) && class_exists('User') ? User::find($_SESSION['user_id']) : null;
        if ($currentUser && ($currentUser->hasPermission('users.manage-roles') || $currentUser->isSuperAdmin())):
        ?>
            <div class="space-y-4">
                <label class="block text-sm font-medium text-slate-300">Roles</label>
                <div class="bg-slate-900/30 rounded-lg p-4 space-y-2 max-h-48 overflow-y-auto">
                    <?php if (empty($allRoles ?? [])): ?>
                        <p class="text-sm text-slate-400">No roles available. <a href="/<?= $lang ?>/dashboard/users/roles/create" class="text-theme-primary hover:underline">Create one</a></p>
                    <?php else: ?>
                        <?php foreach (($allRoles ?? []) as $role): ?>
                            <?php
                            $roleArray = is_object($role) ? $role->toArray() : $role;
                            $roleId = $roleArray['id'] ?? 0;
                            $roleName = $roleArray['name'] ?? '';
                            $roleSlug = $roleArray['slug'] ?? '';
                            $isSystem = !empty($roleArray['is_system']);
                            $isChecked = in_array($roleId, ($userRoleIds ?? []));
                            ?>
                            <label class="flex items-center gap-3 p-2 rounded hover:bg-slate-800/50 cursor-pointer">
                                <input type="checkbox" 
                                       name="roles[]" 
                                       value="<?= $roleId ?>" 
                                       <?= $isChecked ? 'checked' : '' ?>
                                       class="w-4 h-4 text-theme-primary bg-slate-700 border-slate-600 rounded focus:ring-theme-primary">
                                <div class="flex-1">
                                    <div class="text-sm font-medium text-white">
                                        <?= e($roleName) ?>
                                        <?php if ($isSystem): ?>
                                            <span class="ml-2 px-2 py-0.5 bg-blue-500/20 text-blue-400 text-xs rounded">System</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-xs text-slate-400 font-mono"><?= e($roleSlug) ?></div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <p class="text-xs text-slate-400">Select roles to assign to this user. Users inherit all permissions from their assigned roles.</p>
            </div>
        <?php endif; ?>

        <?php
        // Submit button
        $form->submit('Update User')
            ->attr('class', 'w-full py-3 px-6 bg-theme-primary hover:bg-theme-primary/80 text-white font-semibold rounded-lg transition-all duration-300 transform hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-theme-primary focus:ring-offset-2 focus:ring-offset-slate-900')
            ->attr('id', 'submit-btn');

        echo $form->render();
        ?>
        
        <!-- Loading Spinner (hidden by default) -->
        <div id="loading-spinner" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center">
            <div class="bg-slate-800 rounded-lg p-6 flex flex-col items-center gap-4">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-theme-primary"></div>
                <p class="text-white">Updating user...</p>
            </div>
        </div>
    </div>

    <!-- Back Link -->
    <div class="mt-6">
        <?php global $router; $lang = $router->lang ?? 'sr'; ?>
        <a href="/<?= $lang ?>/dashboard/users" 
           class="inline-flex items-center gap-2 text-slate-400 hover:text-white transition-colors">
            <ion-icon name="arrow-back-outline"></ion-icon>
            Back to Users
        </a>
    </div>

</div>

<script nonce="<?= csp_nonce() ?>">
// Simple form submission handler - prevent multiple submissions
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form[action*="/dashboard/users"]');
    const submitBtn = document.getElementById('submit-btn');
    const loadingSpinner = document.getElementById('loading-spinner');
    
    if (form && submitBtn) {
        let isSubmitting = false;
        
        form.addEventListener('submit', function(e) {
            // Prevent multiple submissions
            if (isSubmitting) {
                e.preventDefault();
                return false;
            }
            
            // Set submitting flag immediately
            isSubmitting = true;
            
            // Disable button and show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="animate-spin inline-block mr-2">⏳</span> Updating...';
            
            // Show loading spinner
            if (loadingSpinner) {
                loadingSpinner.classList.remove('hidden');
            }
            
            // Allow form to submit normally - backend will handle response and redirect
        });
    }
});

function previewAvatar(input) {
    // Use requestAnimationFrame to prevent blocking UI
    requestAnimationFrame(() => {
        if (input.files && input.files[0]) {
            // Validate file size (2MB)
            if (input.files[0].size > 2 * 1024 * 1024) {
                alert('File size exceeds 2MB limit. Please choose a smaller file.');
                input.value = '';
                return;
            }
            
            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!allowedTypes.includes(input.files[0].type)) {
                alert('Invalid file type. Please choose a JPEG, PNG, GIF, or WebP image.');
                input.value = '';
                return;
            }
            
            const reader = new FileReader();
            
            reader.onload = function(e) {
                requestAnimationFrame(() => {
                    const previewImg = document.getElementById('avatar-preview-img');
                    const previewDiv = document.getElementById('new-avatar-preview');
                    
                    if (previewImg && previewDiv) {
                        previewImg.src = e.target.result;
                        previewDiv.classList.remove('hidden');
                    }
                });
            };
            
            reader.onerror = function() {
                console.error('Error reading file');
            };
            
            reader.readAsDataURL(input.files[0]);
        }
    });
}

function deleteAvatar() {
    if (confirm('Are you sure you want to delete the current profile picture?')) {
        // Hide current avatar preview
        const currentPreview = document.getElementById('current-avatar-preview');
        if (currentPreview) {
            currentPreview.parentElement.parentElement.style.display = 'none';
        }
        
        // Set delete flag
        const deleteFlag = document.getElementById('delete-avatar-flag');
        if (deleteFlag) {
            deleteFlag.value = '1';
        }
        
        // Clear file input if any
        const fileInput = document.getElementById('avatar-input');
        if (fileInput) {
            fileInput.value = '';
        }
        
        // Hide new preview if shown
        const newPreview = document.getElementById('new-avatar-preview');
        if (newPreview) {
            newPreview.classList.add('hidden');
        }
    }
}
</script>

