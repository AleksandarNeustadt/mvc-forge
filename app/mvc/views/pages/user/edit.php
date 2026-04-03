<?php
// Ensure $profileUser is an array
if (is_object($profileUser)) {
    $profileUser = (array) $profileUser;
}
if (!is_array($profileUser)) {
    $profileUser = [];
}

// Ensure user ID exists
if (empty($profileUser['id'])) {
    throw new Exception('User ID is required for editing');
}
$userId = (int)$profileUser['id'];

global $router;
$lang = $router->lang ?? 'sr';
?>

<section class="relative w-full px-4 py-10 max-w-3xl mx-auto">
    
    <!-- Page Header -->
    <div class="mb-8 flex items-center justify-between flex-wrap gap-4">
        <div>
            <h1 class="text-4xl font-bold text-white mb-2">Edit Profile</h1>
            <p class="text-slate-400">Update your account information</p>
        </div>
        <div>
            <a href="/<?= $lang ?>/profile" 
               class="inline-flex items-center gap-2 px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white font-medium rounded-lg transition-colors">
                <ion-icon name="arrow-back-outline"></ion-icon>
                Back to Profile
            </a>
        </div>
    </div>

    <!-- Edit Form -->
    <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-xl p-6">
        <?php
        // Clear old input from session before creating form (to prevent using stale data)
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        unset($_SESSION['_old_input']);
        
        $form = new FormBuilder("/{$lang}/profile/update", 'PUT');
        $form->class('space-y-6');
        $form->files(true); // Enable file uploads (adds enctype="multipart/form-data")
        $form->withOld($profileUser);

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
            <?php if (!empty($profileUser['avatar'])): ?>
                <div class="flex items-center gap-4 mb-4">
                    <div class="relative">
                        <img src="<?= e($profileUser['avatar']) ?>" 
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
                    <span><?= !empty($profileUser['avatar']) ? 'Change Profile Picture' : 'Upload Profile Picture' ?></span>
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

        // Submit button
        $form->submit('Update Profile')
            ->attribute('class', 'w-full px-6 py-3 bg-theme-primary hover:bg-theme-primary/90 text-white font-medium rounded-lg transition-colors')
            ->attribute('id', 'submit-btn');

        echo $form->render();
        ?>
        
        <!-- Loading Spinner (hidden by default) -->
        <div id="loading-spinner" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center">
            <div class="bg-slate-800 rounded-xl p-8 flex flex-col items-center gap-4">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-theme-primary"></div>
                <p class="text-white font-medium">Updating profile...</p>
            </div>
        </div>
    </div>

</section>

<script nonce="<?= function_exists('csp_nonce') ? csp_nonce() : '' ?>">
// Avatar preview and deletion handling
function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const previewDiv = document.getElementById('new-avatar-preview');
            const previewImg = document.getElementById('avatar-preview-img');
            
            if (previewDiv && previewImg) {
                previewImg.src = e.target.result;
                previewDiv.classList.remove('hidden');
            }
        };
        
        reader.readAsDataURL(input.files[0]);
    }
}

function deleteAvatar() {
    if (confirm('Are you sure you want to delete your profile picture?')) {
        const deleteFlag = document.getElementById('delete-avatar-flag');
        const currentPreview = document.getElementById('current-avatar-preview');
        const newPreview = document.getElementById('new-avatar-preview');
        const avatarInput = document.getElementById('avatar-input');
        
        if (deleteFlag) {
            deleteFlag.value = '1';
        }
        
        if (currentPreview) {
            currentPreview.style.opacity = '0.5';
        }
        
        if (newPreview) {
            newPreview.classList.add('hidden');
        }
        
        if (avatarInput) {
            avatarInput.value = '';
        }
    }
}

// Form submission with loading state
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const submitBtn = document.getElementById('submit-btn');
    const loadingSpinner = document.getElementById('loading-spinner');
    
    if (form && submitBtn && loadingSpinner) {
        form.addEventListener('submit', function(e) {
            // Show loading spinner
            loadingSpinner.classList.remove('hidden');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Updating profile...';
        });
    }
});
</script>

