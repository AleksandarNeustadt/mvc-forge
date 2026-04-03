<div class="p-8 max-w-4xl">
    <!-- Page Header -->
    <div class="mb-8">
    </div>

    <!-- Create Form -->
    <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-xl p-6">
        <?php
        global $router;
        $lang = $router->lang ?? 'sr';
        $form = new FormBuilder("/{$lang}/dashboard/blog/posts", 'POST');
        $form->class('space-y-6');
        
        // Display form errors if any
        if (Form::hasErrors()) {
            $form->errors(Form::getErrors());
        }

        // Title
        $form->text('title', 'Title')
            ->required()
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary')
            ->attribute('placeholder', 'Post Title')
            ->attribute('id', 'title_input');

        // Slug
        $form->text('slug', 'Slug')
            ->required()
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary')
            ->attribute('placeholder', 'post-slug')
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

        // Excerpt
        $form->textarea('excerpt', 'Excerpt')
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary')
            ->attribute('rows', '3')
            ->attribute('placeholder', 'Short description for preview...');

        // Content (Rich Text Editor)
        $form->raw('<div class="space-y-2">');
        $form->raw('<label class="block text-sm font-medium text-slate-300">Content <span class="text-red-400">*</span></label>');
        $form->raw('<textarea id="content" name="content" class="w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary" rows="15" placeholder="Full post content">' . htmlspecialchars(Form::old('content', '')) . '</textarea>');
        if (Form::hasError('content')) {
            $form->raw('<p class="text-red-400 text-sm mt-1">' . htmlspecialchars(Form::getError('content')) . '</p>');
        }
        $form->raw('</div>');

        // Featured Image Upload
        $form->raw('<div class="space-y-2">');
        $form->raw('<label class="block text-sm font-medium text-slate-300">Featured Image</label>');
        $form->raw('<div class="space-y-4">');
        
        // Hidden input for URL (will be filled by JavaScript after upload)
        $form->raw('<input type="hidden" name="featured_image" id="featured_image_url" value="' . htmlspecialchars(Form::old('featured_image', '')) . '">');
        
        // Preview container
        $form->raw('<div id="featured_image_preview_container" class="' . (Form::old('featured_image', '') ? '' : 'hidden') . '">');
        $form->raw('<div class="relative inline-block">');
        $form->raw('<img id="featured_image_preview" src="' . htmlspecialchars(Form::old('featured_image', '')) . '" alt="Featured Image Preview" class="max-w-md h-auto rounded-lg border border-slate-700">');
        $form->raw('<button type="button" id="remove_featured_image_btn" class="absolute top-2 right-2 bg-red-500 hover:bg-red-600 text-white rounded-full p-2 transition-colors">');
        $form->raw('<ion-icon name="close-outline" class="text-lg"></ion-icon>');
        $form->raw('</button>');
        $form->raw('</div>');
        $form->raw('</div>');
        
        // Upload button
        $form->raw('<div class="flex items-center gap-4">');
        $form->raw('<label for="featured_image_upload" class="cursor-pointer inline-flex items-center px-4 py-2 bg-theme-primary hover:bg-theme-primary/90 text-white font-medium rounded-lg transition-colors">');
        $form->raw('<ion-icon name="image-outline" class="mr-2"></ion-icon>');
        $form->raw('Upload Featured Image');
        $form->raw('</label>');
        $form->raw('<input type="file" id="featured_image_upload" accept="image/*" class="hidden">');
        $form->raw('<span id="featured_image_status" class="text-sm text-slate-400"></span>');
        $form->raw('</div>');
        
        if (Form::hasError('featured_image')) {
            $form->raw('<p class="text-red-400 text-sm mt-1">' . htmlspecialchars(Form::getError('featured_image')) . '</p>');
        }
        $form->raw('</div>');
        $form->raw('</div>');

        // Status
        $form->select('status', 'Status', [
            'draft' => 'Draft',
            'published' => 'Published',
            'archived' => 'Archived'
        ])
            ->required()
            ->default('draft')
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary')
            ->attribute('id', 'status_select');

        // Published At (shown when status is published)
        $form->raw('<div id="published_at_field" style="display: none;">');
        $form->text('published_at', 'Published At')
            ->attribute('type', 'datetime-local')
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary');
        $form->raw('</div>');

        // Categories (multi-select checkbox group)
        $form->raw('<div class="space-y-2">');
        $form->raw('<label class="block text-sm font-medium text-slate-300 mb-2">Categories</label>');
        $form->raw('<div id="categories_container" class="bg-slate-900/50 border border-slate-700 rounded-lg p-4 max-h-48 overflow-y-auto space-y-2">');
        if (empty($categories)) {
            $form->raw('<p class="text-slate-400 text-sm">No categories available. <a href="/' . $lang . '/dashboard/blog/categories/create" class="text-theme-primary hover:underline">Create one</a> first.</p>');
        } else {
            foreach ($categories as $catId => $catName) {
                $form->raw('<div class="flex items-center category-item">');
                $form->raw('<input type="checkbox" name="categories[]" value="' . htmlspecialchars($catId) . '" id="category_' . $catId . '" class="w-4 h-4 text-theme-primary bg-slate-900 border-slate-700 rounded focus:ring-theme-primary">');
                $form->raw('<label for="category_' . $catId . '" class="ml-2 text-sm text-slate-300">' . htmlspecialchars($catName) . '</label>');
                $form->raw('</div>');
            }
        }
        $form->raw('</div>');
        $form->raw('</div>');


        // Meta Title
        $form->text('meta_title', 'Meta Title (SEO)')
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary');

        // Meta Description
        $form->textarea('meta_description', 'Meta Description (SEO)')
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary')
            ->attribute('rows', '3');

        // Meta Keywords
        $form->text('meta_keywords', 'Meta Keywords (SEO)')
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary')
            ->attribute('placeholder', 'keyword1, keyword2, keyword3');

        // Submit button
        $form->submit('Create Post')
            ->attribute('class', 'w-full px-6 py-3 bg-theme-primary hover:bg-theme-primary/90 text-white font-medium rounded-lg transition-colors');

        echo $form->render();
        ?>
    </div>

    <!-- Back Link -->
    <div class="mt-6">
        <?php global $router; $lang = $router->lang ?? 'sr'; ?>
        <a href="/<?= $lang ?>/dashboard/blog/posts" 
           class="inline-flex items-center gap-2 text-slate-400 hover:text-white transition-colors">
            <ion-icon name="arrow-back-outline"></ion-icon>
            Back to Posts
        </a>
    </div>

</div>

<script nonce="<?= csp_nonce() ?>">
// Sync slug from title
function syncSlugFromTitle() {
    const titleInput = document.getElementById('title_input');
    const slugInput = document.getElementById('slug_input');
    
    if (!titleInput || !slugInput) return;
    
    // Only auto-fill if slug is empty
    if (!slugInput.value.trim()) {
        const title = titleInput.value.trim();
        if (title) {
            // Simple slug conversion (in production, use a proper slug function)
            slugInput.value = title.toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .trim('-');
        }
    }
}

// Featured Image Upload
function uploadFeaturedImage(input) {
    if (!input.files || !input.files[0]) {
        return;
    }

    const file = input.files[0];
    const statusEl = document.getElementById('featured_image_status');
    const previewContainer = document.getElementById('featured_image_preview_container');
    const previewImg = document.getElementById('featured_image_preview');
    const urlInput = document.getElementById('featured_image_url');

    // Show uploading status
    statusEl.textContent = 'Uploading...';
    statusEl.className = 'text-sm text-slate-400';

    // Get CSRF token
    var csrfToken = '';
    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    if (csrfMeta) {
        csrfToken = csrfMeta.getAttribute('content');
    } else {
        var csrfInput = document.querySelector('input[name="_csrf_token"]');
        if (csrfInput) {
            csrfToken = csrfInput.value;
        }
    }

    // Create FormData
    const formData = new FormData();
    formData.append('file', file);
    if (csrfToken) {
        formData.append('_csrf_token', csrfToken);
    }

    // Upload via AJAX
    const xhr = new XMLHttpRequest();
    <?php global $router; $lang = $router->lang ?? 'sr'; ?>
    xhr.open('POST', '/<?= $lang ?>/dashboard/blog/upload-featured-image');
    
    if (csrfToken) {
        xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
    }

    xhr.onload = function() {
        if (xhr.status === 200 || xhr.status === 201) {
            try {
                const response = JSON.parse(xhr.responseText);
                console.log('Upload response:', response);
                
                // Get URL from response (could be response.data.url or response.url)
                let imageUrl = null;
                if (response.data && response.data.url) {
                    imageUrl = response.data.url;
                } else if (response.url) {
                    imageUrl = response.url;
                } else if (response.data && response.data.location) {
                    imageUrl = response.data.location;
                } else if (response.location) {
                    imageUrl = response.location;
                }
                
                if (response.success && imageUrl) {
                    // Update preview
                    previewImg.src = imageUrl;
                    previewContainer.classList.remove('hidden');
                    
                    // Update hidden input
                    urlInput.value = imageUrl;
                    
                    // Show success
                    statusEl.textContent = 'Image uploaded successfully';
                    statusEl.className = 'text-sm text-green-400';
                    
                    // Clear file input
                    input.value = '';
                } else {
                    // Try to get error message from response
                    var errorMsg = 'Unknown error';
                    if (response.message) {
                        errorMsg = response.message;
                    } else if (response.data && response.data.message) {
                        errorMsg = response.data.message;
                    } else if (response.error) {
                        errorMsg = response.error;
                    }
                    statusEl.textContent = 'Upload failed: ' + errorMsg;
                    statusEl.className = 'text-sm text-red-400';
                    console.error('Upload error:', response);
                }
            } catch (e) {
                statusEl.textContent = 'Failed to parse response';
                statusEl.className = 'text-sm text-red-400';
            }
        } else {
            try {
                const response = JSON.parse(xhr.responseText);
                var errorMsg = response.message || response.error || 'HTTP ' + xhr.status;
                if (response.data && response.data.message) {
                    errorMsg = response.data.message;
                }
                statusEl.textContent = 'Upload failed: ' + errorMsg;
                console.error('Upload error response:', response);
            } catch (e) {
                statusEl.textContent = 'Upload failed: HTTP ' + xhr.status + ' - ' + xhr.responseText.substring(0, 100);
                console.error('Failed to parse error response:', e, xhr.responseText);
            }
            statusEl.className = 'text-sm text-red-400';
        }
    };

    xhr.onerror = function() {
        statusEl.textContent = 'Upload failed: Network error';
        statusEl.className = 'text-sm text-red-400';
    };

    xhr.send(formData);
}

function removeFeaturedImage() {
    const previewContainer = document.getElementById('featured_image_preview_container');
    const urlInput = document.getElementById('featured_image_url');
    const statusEl = document.getElementById('featured_image_status');
    const uploadInput = document.getElementById('featured_image_upload');
    
    previewContainer.classList.add('hidden');
    urlInput.value = '';
    statusEl.textContent = '';
    uploadInput.value = '';
}

// Toggle published_at field based on status
function togglePublishedAt() {
    const statusSelect = document.getElementById('status_select');
    const publishedAtField = document.getElementById('published_at_field');
    
    if (!statusSelect || !publishedAtField) return;
    
    if (statusSelect.value === 'published') {
        publishedAtField.style.display = 'block';
        // Set default value to current datetime if empty
        const publishedAtInput = publishedAtField.querySelector('input');
        if (publishedAtInput && !publishedAtInput.value) {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            publishedAtInput.value = `${year}-${month}-${day}T${hours}:${minutes}`;
        }
    } else {
        publishedAtField.style.display = 'none';
    }
}

// Filter categories by language
function filterCategoriesByLanguage(languageId) {
    const categoriesContainer = document.getElementById('categories_container');
    
    if (!categoriesContainer) return;
    
    // Get language code from languagesData
    <?php if (isset($languagesData) && !empty($languagesData)): ?>
    const languagesData = <?= json_encode($languagesData) ?>;
    const languageCode = languageId && languageId !== '' && languagesData[languageId] ? languagesData[languageId].code : null;
    <?php else: ?>
    const languageCode = null;
    <?php endif; ?>
    
    // Build API URL - if no language selected, show all (no filter)
    let categoriesUrl = '/api/categories';
    
    if (languageCode) {
        categoriesUrl += '?language_code=' + encodeURIComponent(languageCode);
    }
    
    // Fetch categories
    fetch(categoriesUrl)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                updateCategoriesList(data.data);
            }
        })
        .catch(error => {
            console.error('Error fetching categories:', error);
        });
}

function updateCategoriesList(categories) {
    const container = document.getElementById('categories_container');
    if (!container) return;
    
    // Store currently checked category IDs
    const checkedIds = [];
    const checkboxes = container.querySelectorAll('input[type="checkbox"]');
    checkboxes.forEach(cb => {
        if (cb.checked) {
            checkedIds.push(parseInt(cb.value));
        }
    });
    
    // Clear container
    container.innerHTML = '';
    
    if (categories.length === 0) {
        <?php global $router; $lang = $router->lang ?? 'sr'; ?>
        container.innerHTML = '<p class="text-slate-400 text-sm">No categories available. <a href="/<?= $lang ?>/dashboard/blog/categories/create" class="text-theme-primary hover:underline">Create one</a> first.</p>';
        return;
    }
    
    // Add categories
    categories.forEach(cat => {
        const div = document.createElement('div');
        div.className = 'flex items-center category-item';
        
        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.name = 'categories[]';
        checkbox.value = cat.id;
        checkbox.id = 'category_' + cat.id;
        checkbox.className = 'w-4 h-4 text-theme-primary bg-slate-900 border-slate-700 rounded focus:ring-theme-primary';
        
        // Restore checked state if it was checked before
        if (checkedIds.includes(cat.id)) {
            checkbox.checked = true;
        }
        
        const label = document.createElement('label');
        label.htmlFor = 'category_' + cat.id;
        label.className = 'ml-2 text-sm text-slate-300';
        label.textContent = cat.name;
        
        div.appendChild(checkbox);
        div.appendChild(label);
        container.appendChild(div);
    });
}


// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    togglePublishedAt();
    
    // Attach event listeners (CSP-compliant)
    const titleInput = document.getElementById('title_input');
    if (titleInput) {
        titleInput.addEventListener('input', syncSlugFromTitle);
    }
    
    const featuredImageUpload = document.getElementById('featured_image_upload');
    if (featuredImageUpload) {
        featuredImageUpload.addEventListener('change', function(e) {
            uploadFeaturedImage(this);
        });
    }
    
    const removeFeaturedImageBtn = document.getElementById('remove_featured_image_btn');
    if (removeFeaturedImageBtn) {
        removeFeaturedImageBtn.addEventListener('click', removeFeaturedImage);
    }
    
    const statusSelect = document.getElementById('status_select');
    if (statusSelect) {
        statusSelect.addEventListener('change', togglePublishedAt);
    }
    
    // Listen for language changes
    const languageSelect = document.getElementById('language_id_select');
    if (languageSelect) {
        languageSelect.addEventListener('change', function() {
            filterCategoriesByLanguage(this.value);
        });
    }
    
    // Initialize TinyMCE
    <?php global $router; $lang = $router->lang ?? 'sr'; ?>
    if (typeof tinymce !== 'undefined') {
        tinymce.init({
            selector: '#content',
            height: 500,
            menubar: true,
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'media', 'table', 'code', 'help', 'wordcount'
            ],
            toolbar: 'undo redo | blocks | ' +
                'bold italic forecolor | alignleft aligncenter ' +
                'alignright alignjustify | bullist numlist outdent indent | ' +
                'removeformat | help | image link code',
            content_style: 'body { font-family: Arial, sans-serif; font-size: 14px; color: #e2e8f0; background-color: #1e293b; }',
            skin: 'oxide-dark',
            content_css: 'dark',
            images_upload_url: '/<?= $lang ?>/dashboard/blog/upload-image',
            images_upload_handler: function (blobInfo, progress) {
                return new Promise(function (resolve, reject) {
                    var xhr = new XMLHttpRequest();
                    xhr.withCredentials = false;
                    xhr.open('POST', '/<?= $lang ?>/dashboard/blog/upload-image');
                    
                    // Get CSRF token from meta tag or form
                    var csrfToken = '';
                    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
                    if (csrfMeta) {
                        csrfToken = csrfMeta.getAttribute('content');
                    } else {
                        var csrfInput = document.querySelector('input[name="_csrf_token"]');
                        if (csrfInput) {
                            csrfToken = csrfInput.value;
                        }
                    }
                    
                    if (csrfToken) {
                        xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
                    }
                    
                    xhr.upload.onprogress = function (e) {
                        progress(e.loaded / e.total * 100);
                    };
                    
                    xhr.onload = function () {
                        if (xhr.status === 403) {
                            reject({ message: 'HTTP Error: ' + xhr.status, remove: true });
                            return;
                        }
                        
                        if (xhr.status < 200 || xhr.status >= 300) {
                            reject('HTTP Error: ' + xhr.status);
                            return;
                        }
                        
                        var json = JSON.parse(xhr.responseText);
                        
                        if (!json || typeof json.location != 'string') {
                            reject('Invalid JSON: ' + xhr.responseText);
                            return;
                        }
                        
                        resolve(json.location);
                    };
                    
                    xhr.onerror = function () {
                        reject('Image upload failed due to a XHR Transport error. Code: ' + xhr.status);
                    };
                    
                    var formData = new FormData();
                    formData.append('file', blobInfo.blob(), blobInfo.filename());
                    if (csrfToken) {
                        formData.append('_csrf_token', csrfToken);
                    }
                    
                    xhr.send(formData);
                });
            },
            automatic_uploads: true,
            file_picker_types: 'image',
            file_picker_callback: function (callback, value, meta) {
                if (meta.filetype === 'image') {
                    var input = document.createElement('input');
                    input.setAttribute('type', 'file');
                    input.setAttribute('accept', 'image/*');
                    
                    input.onchange = function () {
                        var file = this.files[0];
                        var reader = new FileReader();
                        
                        reader.onload = function () {
                            var id = 'blobid' + (new Date()).getTime();
                            var blobCache = tinymce.activeEditor.editorUpload.blobCache;
                            var base64 = reader.result.split(',')[1];
                            var blobInfo = blobCache.create(id, file, base64);
                            blobCache.add(blobInfo);
                            
                            callback(blobInfo.blobUri(), { title: file.name });
                        };
                        
                        reader.readAsDataURL(file);
                    };
                    
                    input.click();
                }
            }
        });
    }
});
</script>

<!-- TinyMCE CDN (with API key) -->
<?php $tinymceApiKey = Env::get('TINYMCE_API_KEY', 'no-api-key'); ?>
<script src="https://cdn.tiny.cloud/1/<?= htmlspecialchars($tinymceApiKey) ?>/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>

