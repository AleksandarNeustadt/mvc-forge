{{-- Create User --}}

<div class="p-8">
    {{-- Page Header --}}
    <div class="mb-8">
    </div>

    {{-- Create Form --}}
    <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-xl p-6">
        @php
        global $router;
        $lang = $router->lang ?? 'sr';
        $form = new FormBuilder("/{$lang}/dashboard/users", 'POST');
        $form->class('space-y-6');
        $form->files(true); // Enable file uploads (adds enctype="multipart/form-data")
        $form->attribute('autocomplete', 'new-password'); // Fix browser warning

        // Display form errors if any
        if (class_exists('Form') && method_exists('Form', 'hasErrors') && Form::hasErrors()) {
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

        // Password (required for new users)
        $form->password('password', 'Password')
            ->required()
            ->minLength(8)
            ->placeholder('Minimum 8 characters')
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary')
            ->autocomplete('new-password');
        @endphp

        {{-- Avatar Upload --}}
        <div class="space-y-4">
            <label class="block text-sm font-medium text-slate-300">Profile Picture (Optional)</label>
            
            {{-- No current avatar for new users --}}
            <div class="mb-4">
                <div class="w-24 h-24 rounded-full bg-slate-700/50 border-2 border-slate-600 flex items-center justify-center">
                    <ion-icon name="person-outline" class="text-4xl text-slate-500"></ion-icon>
                </div>
                <p class="text-sm text-slate-400 mt-2">No profile picture</p>
            </div>
            
            {{-- Upload New Avatar --}}
            <div class="space-y-2">
                <input type="file" 
                       name="avatar" 
                       id="avatar-input" 
                       accept="image/jpeg,image/png,image/gif,image/webp"
                       class="hidden"
                       data-avatar-upload>
                <label for="avatar-input" 
                       class="inline-flex items-center gap-2 px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-lg cursor-pointer transition-colors">
                    <ion-icon name="image-outline"></ion-icon>
                    <span>Upload Profile Picture</span>
                </label>
                <p class="text-xs text-slate-400">Max size: 2MB. Allowed: JPEG, PNG, GIF, WebP</p>
                
                {{-- Preview of new upload --}}
                <div id="new-avatar-preview" class="hidden mt-4">
                    <p class="text-sm text-slate-300 mb-2">Preview:</p>
                    <img id="avatar-preview-img" 
                         src="" 
                         alt="Preview" 
                         class="w-24 h-24 rounded-full object-cover border-2 border-theme-primary">
                </div>
            </div>
        </div>

        @php
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

        // Submit button
        $form->submit('Create User')
            ->attribute('class', 'w-full px-6 py-3 bg-theme-primary hover:bg-theme-primary/90 text-white font-medium rounded-lg transition-colors')
            ->attribute('id', 'submit-btn');

        echo $form->render();
        @endphp
        
        {{-- Loading Spinner (hidden by default) --}}
        <div id="loading-spinner" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center">
            <div class="bg-slate-800 rounded-lg p-6 flex flex-col items-center gap-4">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-theme-primary"></div>
                <p class="text-white">Creating user...</p>
            </div>
        </div>
    </div>

    {{-- Back Link --}}
    <div class="mt-6">
        <a href="/{{ $lang }}/dashboard/users" 
           class="inline-flex items-center gap-2 text-slate-400 hover:text-white transition-colors">
            <ion-icon name="arrow-back-outline"></ion-icon>
            Back to Users
        </a>
    </div>
</div>

<script nonce="{{ csp_nonce() }}">
// Form submission with loading state and validation
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form[action*="/dashboard/users"]');
    const submitBtn = document.getElementById('submit-btn');
    const loadingSpinner = document.getElementById('loading-spinner');
    
    if (form) {
        // Client-side validation
        const usernameInput = form.querySelector('[name="username"]');
        const emailInput = form.querySelector('[name="email"]');
        const passwordInput = form.querySelector('[name="password"]');
        
        // Real-time username validation
        if (usernameInput) {
            let usernameTimeout;
            usernameInput.addEventListener('input', function() {
                clearTimeout(usernameTimeout);
                const username = this.value.trim();
                
                if (username.length < 3) {
                    this.setCustomValidity('Username must be at least 3 characters');
                    return;
                }
                
                if (username.length > 30) {
                    this.setCustomValidity('Username must be less than 30 characters');
                    return;
                }
                
                if (!/^[a-zA-Z0-9]+$/.test(username)) {
                    this.setCustomValidity('Username can only contain letters and numbers');
                    return;
                }
                
                this.setCustomValidity('');
            });
        }
        
        // Real-time email validation
        if (emailInput) {
            emailInput.addEventListener('input', function() {
                const email = this.value.trim();
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                
                if (email && !emailRegex.test(email)) {
                    this.setCustomValidity('Please enter a valid email address');
                } else {
                    this.setCustomValidity('');
                }
            });
        }
        
        // Real-time password strength validation
        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let errors = [];
                
                if (password.length < 8) {
                    errors.push('at least 8 characters');
                }
                if (!/[A-Z]/.test(password)) {
                    errors.push('one uppercase letter');
                }
                if (!/[a-z]/.test(password)) {
                    errors.push('one lowercase letter');
                }
                if (!/[0-9]/.test(password)) {
                    errors.push('one number');
                }
                if (!/[^A-Za-z0-9]/.test(password)) {
                    errors.push('one special character');
                }
                
                if (errors.length > 0) {
                    this.setCustomValidity('Password must contain: ' + errors.join(', '));
                } else {
                    this.setCustomValidity('');
                }
            });
        }
        
        // Optimistic updates - AJAX form submission
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            
            const originalButtonText = submitBtn ? submitBtn.innerHTML : '';
            const originalButtonDisabled = submitBtn ? submitBtn.disabled : false;
            
            if (loadingSpinner) {
                loadingSpinner.classList.remove('hidden');
            }
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="animate-spin inline-block mr-2">⏳</span> Creating...';
            }
            
            const successMessage = document.createElement('div');
            successMessage.id = 'optimistic-success';
            successMessage.className = 'fixed top-4 right-4 bg-green-600 text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center gap-2';
            successMessage.innerHTML = '<ion-icon name="checkmark-circle"></ion-icon> <span>User created successfully!</span>';
            document.body.appendChild(successMessage);
            
            const formData = new FormData(form);
            const csrfToken = formData.get('_csrf_token') || formData.get('_token');
            
            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': csrfToken
                }
            })
            .then(response => {
                return response.json().then(data => {
                    if (!response.ok) {
                        if (response.status === 422 && data.errors) {
                            const error = new Error(data.message || 'Validation failed');
                            error.errors = data.errors;
                            throw error;
                        }
                        throw new Error(data.message || 'Failed to create user');
                    }
                    return data;
                }).catch(err => {
                    if (err.errors) {
                        throw err;
                    }
                    if (response.ok) {
                        return { success: true };
                    }
                    throw new Error('Failed to create user');
                });
            })
            .then(data => {
                const optimisticMsg = document.getElementById('optimistic-success');
                if (optimisticMsg) {
                    optimisticMsg.remove();
                }
                
                const successMsg = document.createElement('div');
                successMsg.className = 'fixed top-4 right-4 bg-green-600 text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center gap-2';
                successMsg.innerHTML = '<ion-icon name="checkmark-circle"></ion-icon> <span>User created successfully! Redirecting...</span>';
                document.body.appendChild(successMsg);
                
                if (loadingSpinner) {
                    loadingSpinner.classList.add('hidden');
                }
                
                setTimeout(() => {
                    if (data.data && data.data.id) {
                        window.location.href = form.action.replace('/dashboard/users', `/dashboard/users/${data.data.id}/edit`);
                    } else {
                        window.location.href = form.action.replace('/dashboard/users', '/dashboard/users');
                    }
                }, 1000);
            })
            .catch(error => {
                const optimisticMsg = document.getElementById('optimistic-success');
                if (optimisticMsg) {
                    optimisticMsg.remove();
                }
                
                if (submitBtn) {
                    submitBtn.disabled = originalButtonDisabled;
                    submitBtn.innerHTML = originalButtonText;
                }
                
                if (loadingSpinner) {
                    loadingSpinner.classList.add('hidden');
                }
                
                if (error.errors) {
                    Object.keys(error.errors).forEach(field => {
                        const input = form.querySelector(`[name="${field}"]`);
                        if (input) {
                            input.setCustomValidity(error.errors[field][0] || 'Invalid value');
                            input.reportValidity();
                        }
                    });
                }
                
                const errorMsg = document.createElement('div');
                errorMsg.className = 'fixed top-4 right-4 bg-red-600 text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center gap-2';
                errorMsg.innerHTML = `<ion-icon name="close-circle"></ion-icon> <span>${error.message || 'Failed to create user. Please try again.'}</span>`;
                document.body.appendChild(errorMsg);
                
                setTimeout(() => {
                    errorMsg.remove();
                }, 5000);
            });
        });
    }
    
    // Avatar preview (CSP-compliant with event listener)
    const avatarInput = document.getElementById('avatar-input');
    if (avatarInput) {
        avatarInput.addEventListener('change', function() {
            previewAvatar(this);
        });
    }
});

function previewAvatar(input) {
    requestAnimationFrame(() => {
        if (input.files && input.files[0]) {
            if (input.files[0].size > 2 * 1024 * 1024) {
                alert('File size exceeds 2MB limit. Please choose a smaller file.');
                input.value = '';
                return;
            }
            
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!allowedTypes.includes(input.files[0].type)) {
                alert('Invalid file type. Please choose a JPEG, PNG, GIF, or WebP image.');
                input.value = '';
                return;
            }
            
            const reader = new FileReader();
            
            reader.addEventListener('load', function(e) {
                requestAnimationFrame(() => {
                    const previewImg = document.getElementById('avatar-preview-img');
                    const previewDiv = document.getElementById('new-avatar-preview');
                    
                    if (previewImg && previewDiv) {
                        previewImg.src = e.target.result;
                        previewDiv.classList.remove('hidden');
                    }
                });
            });
            
            reader.addEventListener('error', function() {
                console.error('Error reading file');
            });
            
            reader.readAsDataURL(input.files[0]);
        }
    });
}
</script>
