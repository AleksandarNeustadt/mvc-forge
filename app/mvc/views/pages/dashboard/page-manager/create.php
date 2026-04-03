<div class="p-8 max-w-4xl">
    <!-- Page Header -->
    <div class="mb-8">
    </div>

    <!-- Create Form -->
    <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-xl p-6">
        <?php
        global $router;
        $lang = $router->lang ?? 'sr';
        
        // Get errors before FormBuilder clears them from session
        $formErrors = [];
        if (Form::hasErrors()) {
            $formErrors = Form::getErrors();
        }
        
        // Display general errors if any
        if (isset($formErrors['general'])) {
            echo '<div class="mb-6 p-4 bg-red-900/20 border border-red-500/50 rounded-lg">';
            echo '<p class="text-red-400 font-semibold mb-2">Greška:</p>';
            if (is_array($formErrors['general'])) {
                foreach ($formErrors['general'] as $error) {
                    echo '<p class="text-red-300 text-sm">' . htmlspecialchars($error) . '</p>';
                }
            } else {
                echo '<p class="text-red-300 text-sm">' . htmlspecialchars($formErrors['general']) . '</p>';
            }
            echo '</div>';
        }
        
        $form = new FormBuilder("/{$lang}/dashboard/pages", 'POST');
        $form->class('space-y-6');
        
        // Pass errors to form builder
        if (!empty($formErrors)) {
            $form->errors($formErrors);
        }

        // Title
        $form->text('title', 'Title')
            ->required()
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary')
            ->attribute('placeholder', 'Page Title');

        // Slug
        $form->text('slug', 'Slug')
            ->required()
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary')
            ->attribute('placeholder', 'page-slug')
            ->attribute('id', 'slug_input');

        // Route
        $form->text('route', 'Route')
            ->required()
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary')
            ->attribute('placeholder', '/page-route (auto-filled from slug)')
            ->attribute('id', 'route_input');

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

        // Application (first select)
        $form->select('application', 'Application', [
            '' => '-- Select Application --',
            'custom' => 'Custom Page',
            'blog' => 'Blog',
            'contact' => 'Contact Form',
            'homepage' => 'Homepage'
        ])
            ->fieldClass('w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary')
            ->attribute('id', 'application_select');

        // Page Type (shown when application is selected)
        $form->raw('<div id="page_type_field" style="display: none;">');
        $form->select('page_type', 'Page Type', [
            'single_post' => 'Single Blog Post',
            'category' => 'Blog Category',
            'tag' => 'Blog Tag',
            'list' => 'Blog List (All Posts)'
        ])
            ->fieldClass('w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary');
        $form->raw('</div>');

        // Blog Post Selection (shown when page_type is single_post)
        $blogPostOptions = ['' => '-- Select Blog Post --'];
        foreach ($blogPosts as $post) {
            $blogPostOptions[$post['id']] = $post['title'] ?? 'Untitled';
        }
        $form->raw('<div id="blog_post_field" style="display: none;">');
        $form->select('blog_post_id', 'Blog Post', $blogPostOptions)
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary');
        $form->raw('</div>');

        // Blog Category Selection (shown when page_type is category)
        $blogCategoryOptions = ['' => '-- Select Blog Category --'];
        foreach ($blogCategories as $category) {
            $blogCategoryOptions[$category['id']] = $category['name'] ?? 'Untitled';
        }
        $form->raw('<div id="blog_category_field" style="display: none;">');
        $form->select('blog_category_id', 'Blog Category', $blogCategoryOptions)
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary');
        $form->raw('</div>');

        // Blog Tag Selection (shown when page_type is tag)
        $blogTagOptions = ['' => '-- Select Blog Tag --'];
        foreach ($blogTags as $tag) {
            $blogTagOptions[$tag['id']] = $tag['name'] ?? 'Untitled';
        }
        $form->raw('<div id="blog_tag_field" style="display: none;">');
        $form->select('blog_tag_id', 'Blog Tag', $blogTagOptions)
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary');
        $form->raw('</div>');

        // Display Options (shown when page_type is category, tag, or list)
        $form->raw('<div id="display_options_field" style="display: none;">');
        $form->raw('<label class="block text-sm font-medium text-slate-300 mb-4">Display Options</label>');
        
        // Hidden input that will always be sent (updated by JavaScript)
        $form->hidden('display_style')
            ->attribute('id', 'display_style_hidden')
            ->attribute('value', 'list');
        
        // Display Style (visual select - name removed when hidden, restored when visible)
        $form->select('display_style_visual', 'Display Style', [
            'list' => 'List View',
            'grid' => 'Grid View',
            'masonry' => 'Masonry View'
        ])
            ->default('list')
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary mb-4')
            ->attribute('id', 'display_style_select');

        // Grid Columns (shown only when grid is selected)
        $form->raw('<div id="grid_columns_field" style="display: none;">');
        $form->number('grid_columns', 'Grid Columns')
            ->default(3)
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary mb-4')
            ->attribute('min', '1')
            ->attribute('max', '6');
        $form->raw('</div>');

        // Posts per Page
        $form->number('posts_per_page', 'Posts per Page')
            ->default(10)
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary mb-4')
            ->attribute('min', '1')
            ->attribute('max', '100');

        // Show Excerpt
        $form->checkbox('show_excerpt', 'Show Excerpt')
            ->checked(true)
            ->attribute('class', 'w-4 h-4 text-theme-primary bg-slate-900 border-slate-700 rounded focus:ring-theme-primary mb-4');

        // Show Featured Image
        $form->checkbox('show_featured_image', 'Show Featured Image')
            ->checked(true)
            ->attribute('class', 'w-4 h-4 text-theme-primary bg-slate-900 border-slate-700 rounded focus:ring-theme-primary mb-4');

        $form->raw('</div>'); // End display_options_field

        // Homepage Options (shown when application is homepage)
        $form->raw('<div id="homepage_options_field" style="display: none;">');
        $form->raw('<label class="block text-sm font-medium text-slate-300 mb-4">Homepage Options</label>');
        
        // Enable Blog Slider
        $form->checkbox('homepage_enable_blog_slider', 'Enable Blog Slider')
            ->attribute('class', 'w-4 h-4 text-theme-primary bg-slate-900 border-slate-700 rounded focus:ring-theme-primary mb-4')
            ->attribute('id', 'homepage_enable_blog_slider');
        
        // Blog Slider Posts (multi-select) - shown when blog slider is enabled
        $form->raw('<div id="homepage_blog_slider_posts_field" style="display: none;">');
        $blogPostOptionsForSlider = [];
        foreach ($blogPosts as $post) {
            $blogPostOptionsForSlider[$post['id']] = $post['title'] ?? 'Untitled';
        }
        // Multi-select for blog posts (using select with multiple attribute)
        $form->raw('<label for="homepage_blog_slider_posts" class="block text-sm font-medium text-slate-300 mb-2">Select Blog Posts for Slider</label>');
        $form->raw('<select name="homepage_blog_slider_posts[]" id="homepage_blog_slider_posts" multiple size="8" class="w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary mb-4">');
        foreach ($blogPostOptionsForSlider as $id => $title) {
            $form->raw('<option value="' . htmlspecialchars($id) . '">' . htmlspecialchars($title) . '</option>');
        }
        $form->raw('</select>');
        $form->raw('<p class="text-xs text-slate-400 mb-4">Hold Ctrl (or Cmd on Mac) to select multiple posts</p>');
        $form->raw('</div>');
        
        // Enable Login Form
        $form->checkbox('homepage_enable_login_form', 'Enable Login Form')
            ->attribute('class', 'w-4 h-4 text-theme-primary bg-slate-900 border-slate-700 rounded focus:ring-theme-primary mb-4')
            ->attribute('id', 'homepage_enable_login_form');
        
        // Enable Contact Form
        $form->checkbox('homepage_enable_contact_form', 'Enable Contact Form')
            ->attribute('class', 'w-4 h-4 text-theme-primary bg-slate-900 border-slate-700 rounded focus:ring-theme-primary mb-4')
            ->attribute('id', 'homepage_enable_contact_form');
        
        $form->raw('</div>'); // End homepage_options_field

        // Content (for custom pages)
        $form->raw('<div id="content_field">');
        $form->textarea('content', 'Content')
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary')
            ->attribute('rows', '10')
            ->attribute('placeholder', 'Page content (HTML/Markdown supported)');
        $form->raw('</div>');

        // Template
        $form->text('template', 'Template')
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary')
            ->attribute('placeholder', 'default');

        // Navigation Menu
        $navbarOptions = ['' => '-- No Navigation Menu --'];
        if (isset($navigationMenus) && is_array($navigationMenus)) {
            foreach ($navigationMenus as $id => $name) {
                $navbarOptions[$id] = $name;
            }
        }
        $form->select('navbar_id', 'Navigation Menu', $navbarOptions)
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary');

        // Parent Page
        $parentPageOptions = ['' => '-- No Parent --'];
        foreach ($parentPages as $id => $title) {
            $parentPageOptions[$id] = $title;
        }
        $form->select('parent_page_id', 'Parent Page', $parentPageOptions)
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary');

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

        // Is Active
        $form->checkbox('is_active', 'Active')
            ->attribute('class', 'w-4 h-4 text-theme-primary bg-slate-900 border-slate-700 rounded focus:ring-theme-primary')
            ->checked(true);

        // Is In Menu
        $form->checkbox('is_in_menu', 'Show in Menu')
            ->attribute('class', 'w-4 h-4 text-theme-primary bg-slate-900 border-slate-700 rounded focus:ring-theme-primary')
            ->checked(true);

        // Menu Order
        $form->number('menu_order', 'Menu Order')
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary')
            ->attribute('value', '0')
            ->attribute('min', '0');

        // Submit button
        $form->submit('Create Page')
            ->attribute('class', 'w-full px-6 py-3 bg-theme-primary hover:bg-theme-primary/90 text-white font-medium rounded-lg transition-colors');

        echo $form->render();
        ?>
    </div>

    <!-- Back Link -->
    <div class="mt-6">
        <?php global $router; $lang = $router->lang ?? 'sr'; ?>
        <a href="/<?= $lang ?>/dashboard/pages" 
           class="inline-flex items-center gap-2 text-slate-400 hover:text-white transition-colors">
            <ion-icon name="arrow-back-outline"></ion-icon>
            Back to Pages
        </a>
    </div>

</div>

<script nonce="<?= csp_nonce() ?>">
// Sync route from slug
let lastSyncedSlug = '';
function syncRouteFromSlug() {
    const slugInput = document.getElementById('slug_input');
    const routeInput = document.getElementById('route_input');
    
    if (!slugInput || !routeInput) return;
    
    const slug = slugInput.value.trim();
    
    if (!routeInput.value || routeInput.value === '/' + lastSyncedSlug || routeInput.value === lastSyncedSlug) {
        if (slug) {
            routeInput.value = '/' + slug;
            lastSyncedSlug = slug;
        } else {
            routeInput.value = '';
            lastSyncedSlug = '';
        }
    }
}

// Toggle application fields
function toggleApplicationFields() {
    const applicationSelect = document.getElementById('application_select') || document.getElementById('application') || document.querySelector('select[name="application"]');
    if (!applicationSelect) return;
    
    const application = applicationSelect.value;
    const pageTypeField = document.getElementById('page_type_field');
    const contentField = document.getElementById('content_field');
    const routeInput = document.getElementById('route_input');
    
    if (!pageTypeField) return;
    
    if (application === 'blog') {
        pageTypeField.style.display = 'block';
        if (contentField) contentField.style.display = 'none';
        togglePageTypeFields(); // Also update page type fields
    } else if (application === 'contact') {
        // Contact application: hide page type, hide blog fields, allow dynamic route
        pageTypeField.style.display = 'none';
        if (contentField) contentField.style.display = 'none';
        
        // Hide all blog-related fields
        const blogPostField = document.getElementById('blog_post_field');
        const blogCategoryField = document.getElementById('blog_category_field');
        const blogTagField = document.getElementById('blog_tag_field');
        const displayOptionsField = document.getElementById('display_options_field');
        const homepageOptionsField = document.getElementById('homepage_options_field');
        
        if (blogPostField) blogPostField.style.display = 'none';
        if (blogCategoryField) blogCategoryField.style.display = 'none';
        if (blogTagField) blogTagField.style.display = 'none';
        if (displayOptionsField) displayOptionsField.style.display = 'none';
        if (homepageOptionsField) homepageOptionsField.style.display = 'none';
        
        // Show info message about contact application
        if (routeInput) {
            routeInput.readOnly = false;
            routeInput.style.backgroundColor = '';
            routeInput.style.borderColor = '';
            
            // Show info message
            let infoMsg = document.getElementById('contact_route_info');
            if (!infoMsg) {
                infoMsg = document.createElement('div');
                infoMsg.id = 'contact_route_info';
                infoMsg.className = 'mt-2 p-3 bg-blue-900/20 border border-blue-800/50 rounded-lg text-sm text-blue-300';
                infoMsg.innerHTML = '<ion-icon name="information-circle-outline" class="inline mr-1"></ion-icon> Contact aplikacija - možete postaviti bilo koji route (npr. <code class="bg-blue-900/30 px-1 rounded">/contact</code>, <code class="bg-blue-900/30 px-1 rounded">/kontakt</code>, <code class="bg-blue-900/30 px-1 rounded">/contact-us</code>).';
                routeInput.parentElement.appendChild(infoMsg);
            }
        }
    } else if (application === 'homepage') {
        // Homepage application: hide page type, hide blog fields, hide content, show homepage options
        pageTypeField.style.display = 'none';
        if (contentField) contentField.style.display = 'none';
        
        // Hide all blog-related fields
        const blogPostField = document.getElementById('blog_post_field');
        const blogCategoryField = document.getElementById('blog_category_field');
        const blogTagField = document.getElementById('blog_tag_field');
        const displayOptionsField = document.getElementById('display_options_field');
        const homepageOptionsField = document.getElementById('homepage_options_field');
        
        if (blogPostField) blogPostField.style.display = 'none';
        if (blogCategoryField) blogCategoryField.style.display = 'none';
        if (blogTagField) blogTagField.style.display = 'none';
        if (displayOptionsField) displayOptionsField.style.display = 'none';
        if (homepageOptionsField) homepageOptionsField.style.display = 'block';
        
        // Remove contact route info if exists
        if (routeInput) {
            routeInput.readOnly = false;
            routeInput.style.backgroundColor = '';
            routeInput.style.borderColor = '';
            
            const infoMsg = document.getElementById('contact_route_info');
            if (infoMsg) {
                infoMsg.remove();
            }
            
            // Show info message about homepage application
            let homepageInfoMsg = document.getElementById('homepage_route_info');
            if (!homepageInfoMsg) {
                homepageInfoMsg = document.createElement('div');
                homepageInfoMsg.id = 'homepage_route_info';
                homepageInfoMsg.className = 'mt-2 p-3 bg-green-900/20 border border-green-800/50 rounded-lg text-sm text-green-300';
                homepageInfoMsg.innerHTML = '<ion-icon name="information-circle-outline" class="inline mr-1"></ion-icon> Homepage aplikacija - obično se postavlja na route <code class="bg-green-900/30 px-1 rounded">/</code> ili <code class="bg-green-900/30 px-1 rounded">/home</code>.';
                routeInput.parentElement.appendChild(homepageInfoMsg);
            }
        }
        
        // Toggle blog slider posts field based on checkbox
        toggleHomepageBlogSlider();
    } else if (application === 'custom' || application === '') {
        pageTypeField.style.display = 'none';
        if (contentField) contentField.style.display = 'block';
        
        // Hide all blog-related fields
        const blogPostField = document.getElementById('blog_post_field');
        const blogCategoryField = document.getElementById('blog_category_field');
        const blogTagField = document.getElementById('blog_tag_field');
        const displayOptionsField = document.getElementById('display_options_field');
        
        if (blogPostField) blogPostField.style.display = 'none';
        if (blogCategoryField) blogCategoryField.style.display = 'none';
        if (blogTagField) blogTagField.style.display = 'none';
        if (displayOptionsField) displayOptionsField.style.display = 'none';
        
        // Remove contact route lock if exists
        if (routeInput) {
            routeInput.readOnly = false;
            routeInput.style.backgroundColor = '';
            routeInput.style.borderColor = '';
            
            // Remove info message
            const infoMsg = document.getElementById('contact_route_info');
            if (infoMsg) {
                infoMsg.remove();
            }
        }
    }
}

// Toggle page type fields
function togglePageTypeFields() {
    const pageTypeSelect = document.getElementById('page_type') || document.querySelector('select[name="page_type"]');
    if (!pageTypeSelect) return;
    
    const pageType = pageTypeSelect.value;
    const blogPostField = document.getElementById('blog_post_field');
    const blogCategoryField = document.getElementById('blog_category_field');
    const blogTagField = document.getElementById('blog_tag_field');
    const displayOptionsField = document.getElementById('display_options_field');
    
    // Hide all fields first
    if (blogPostField) blogPostField.style.display = 'none';
    if (blogCategoryField) blogCategoryField.style.display = 'none';
    if (blogTagField) blogTagField.style.display = 'none';
    if (displayOptionsField) displayOptionsField.style.display = 'none';
    
    // Show relevant field based on page type
    if (pageType === 'single_post' && blogPostField) {
        blogPostField.style.display = 'block';
        // Hide display options for single post
        if (displayOptionsField) displayOptionsField.style.display = 'none';
    } else if (pageType === 'category' && blogCategoryField) {
        blogCategoryField.style.display = 'block';
        toggleDisplayOptions();
    } else if (pageType === 'tag' && blogTagField) {
        blogTagField.style.display = 'block';
        toggleDisplayOptions();
    } else if (pageType === 'list' && displayOptionsField) {
        displayOptionsField.style.display = 'block';
    }
}

// Toggle display options (shown for category, tag, and list)
function toggleDisplayOptions() {
    const pageTypeSelect = document.getElementById('page_type') || document.querySelector('select[name="page_type"]');
    if (!pageTypeSelect) return;
    
    const pageType = pageTypeSelect.value;
    const displayOptionsField = document.getElementById('display_options_field');
    
    if (!displayOptionsField) return;
    
    if (pageType === 'category' || pageType === 'tag' || pageType === 'list') {
        displayOptionsField.style.display = 'block';
        // After showing display options, check if grid columns should be shown
        setTimeout(toggleGridColumns, 10);
    } else {
        displayOptionsField.style.display = 'none';
    }
}

// Update hidden display_style field when select changes
function updateDisplayStyleHidden() {
    const select = document.getElementById('display_style_select');
    const hidden = document.getElementById('display_style_hidden');
    if (select && hidden) {
        hidden.value = select.value;
    }
}

// Toggle grid columns field based on display style
function toggleGridColumns() {
    // Try multiple selectors to find the select element
    let select = document.getElementById('display_style_select');
    if (!select) {
        select = document.querySelector('select[name="display_style_visual"]');
    }
    
    // Try multiple selectors to find the grid columns field
    let gridColumnsField = document.getElementById('grid_columns_field');
    if (!gridColumnsField) {
        // Try to find by looking for the number input with name="grid_columns"
        const gridInput = document.querySelector('input[name="grid_columns"]');
        if (gridInput) {
            gridColumnsField = gridInput.closest('div');
        }
    }
    
    if (!select || !gridColumnsField) {
        return false;
    }
    
    const value = select.value;
    
    if (value === 'grid') {
        // Use multiple methods to ensure it shows
        gridColumnsField.setAttribute('style', 'display: block !important;');
        gridColumnsField.style.display = 'block';
        gridColumnsField.classList.add('grid-visible');
        return true;
    } else {
        gridColumnsField.style.display = 'none';
        gridColumnsField.classList.remove('grid-visible');
        return true;
    }
}

// Toggle homepage blog slider posts field
function toggleHomepageBlogSlider() {
    const enableBlogSliderCheckbox = document.getElementById('homepage_enable_blog_slider');
    const blogSliderPostsField = document.getElementById('homepage_blog_slider_posts_field');
    
    if (!enableBlogSliderCheckbox || !blogSliderPostsField) return;
    
    if (enableBlogSliderCheckbox.checked) {
        blogSliderPostsField.style.display = 'block';
    } else {
        blogSliderPostsField.style.display = 'none';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Add event listener to slug input for syncing route
    const slugInput = document.getElementById('slug_input');
    if (slugInput) {
        slugInput.addEventListener('input', syncRouteFromSlug);
    }
    
    // Add event listener to application select
    const applicationSelect = document.getElementById('application') || document.querySelector('select[name="application"]');
    if (applicationSelect) {
        applicationSelect.addEventListener('change', toggleApplicationFields);
        // Initialize on page load
        toggleApplicationFields();
    }
    
    // Add event listener to homepage blog slider checkbox
    const homepageBlogSliderCheckbox = document.getElementById('homepage_enable_blog_slider');
    if (homepageBlogSliderCheckbox) {
        homepageBlogSliderCheckbox.addEventListener('change', toggleHomepageBlogSlider);
    }
    
    // Add event listener to page_type select
    const pageTypeSelect = document.getElementById('page_type') || document.querySelector('select[name="page_type"]');
    if (pageTypeSelect) {
        pageTypeSelect.addEventListener('change', togglePageTypeFields);
    }
    
    // Add event listener to display_style select
    const displayStyleSelect = document.getElementById('display_style_select');
    if (displayStyleSelect) {
        displayStyleSelect.addEventListener('change', updateDisplayStyleHidden);
        displayStyleSelect.addEventListener('change', toggleGridColumns);
        // Initialize hidden field immediately
        updateDisplayStyleHidden();
        // Initialize grid columns field visibility after a short delay to ensure DOM is ready
        setTimeout(toggleGridColumns, 100);
    }
    
    // Ensure display_style is updated before form submit
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            // Always update hidden from select before submit
            const hidden = document.getElementById('display_style_hidden');
            const select = document.getElementById('display_style_select');
            if (hidden && select) {
                hidden.value = select.value;
            }
        });
    }
    
    // Initial state check
    toggleApplicationFields();
    
    const routeInput = document.getElementById('route_input');
    if (routeInput) {
        routeInput.addEventListener('input', function() {
            const slugInput = document.getElementById('slug_input');
            if (slugInput && slugInput.value) {
                lastSyncedSlug = '';
            }
        });
    }
});
</script>
