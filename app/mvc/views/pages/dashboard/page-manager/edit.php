<style>
/* Ensure grid columns field is visible when grid is selected */
#grid_columns_field[style*="display: block"],
#grid_columns_field.grid-visible {
    display: block !important;
}

/* Two-column form layout */
.page-edit-form {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.5rem;
}

@media (min-width: 1024px) {
    .page-edit-form {
        grid-template-columns: 1fr 1fr;
    }
}

.form-column {
    background: rgba(30, 41, 59, 0.5);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(51, 65, 85, 0.5);
    border-radius: 0.75rem;
    padding: 1.5rem;
}

.form-section-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: white;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid rgba(51, 65, 85, 1);
}

/* Organize form fields into columns */
.page-edit-form .form-group[data-field="title"],
.page-edit-form .form-group[data-field="slug"],
.page-edit-form .form-group[data-field="route"],
.page-edit-form .form-group[data-field="application"],
.page-edit-form .form-group[data-field="page_type"],
.page-edit-form .form-group[data-field="blog_post_id"],
.page-edit-form .form-group[data-field="blog_category_id"],
.page-edit-form .form-group[data-field="blog_tag_id"],
.page-edit-form .form-group[data-field="display_options"],
.page-edit-form .form-group[data-field="content"],
.page-edit-form .form-group[data-field="template"],
.page-edit-form .form-group[data-field="parent_page_id"],
.page-edit-form .form-group[data-field="is_active"],
.page-edit-form .form-group[data-field="is_in_menu"],
.page-edit-form .form-group[data-field="menu_order"] {
    grid-column: 1;
}

.page-edit-form .form-group[data-field="meta_title"],
.page-edit-form .form-group[data-field="meta_description"],
.page-edit-form .form-group[data-field="meta_keywords"] {
    grid-column: 2;
}

@media (max-width: 1023px) {
    .page-edit-form .form-group {
        grid-column: 1 !important;
    }
}
</style>

<div class="p-8">
    <!-- Page Header -->
    <div class="mb-8">
        <p class="text-slate-400">Update page information</p>
    </div>

    <!-- Edit Form - Two Column Layout -->
    <?php
    global $router;
    $lang = $router->lang ?? 'sr';
    $form = new FormBuilder("/{$lang}/dashboard/pages/{$page['id']}", 'PUT');
    $form->class('space-y-6');
    $form->withOld($page);

    // Display form errors if any
    if (Form::hasErrors()) {
        $form->errors(Form::getErrors());
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
            ->attribute('placeholder', 'page-slug');

        // Route
        $form->text('route', 'Route')
            ->required()
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary')
            ->attribute('placeholder', '/page-route')
            ->attribute('id', 'route_input');

        // Language (custom select with flags)
        if (isset($languagesData) && !empty($languagesData)) {
            require_once __DIR__ . '/../../../helpers/language-select.php';
            $selectedLangId = Form::old('language_id', $page['language_id'] ?? '');
            renderLanguageSelect($form, $languagesData, $selectedLangId);
        } elseif (isset($languages) && !empty($languages)) {
            $form->select('language_id', 'Language', $languages)
                ->fieldClass('w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary')
                ->attribute('placeholder', 'Select language');
        }

        // Application (first select)
        $currentApplication = $page['application'] ?? 'custom';
        $form->select('application', 'Application', [
            '' => '-- Select Application --',
            'custom' => 'Custom Page',
            'blog' => 'Blog',
            'contact' => 'Contact Form',
            'homepage' => 'Homepage'
        ])
            ->default($currentApplication)
            ->fieldClass('w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary')
            ->attribute('id', 'application_select');

        // Page Type (shown when application is blog)
        $currentPageType = $page['page_type'] ?? '';
        $form->raw('<div id="page_type_field" style="display: ' . ($currentApplication === 'blog' ? 'block' : 'none') . ';">');
        $form->select('page_type', 'Page Type', [
            'single_post' => 'Single Blog Post',
            'category' => 'Blog Category',
            'tag' => 'Blog Tag',
            'list' => 'Blog List (All Posts)'
        ])
            ->default($currentPageType)
            ->fieldClass('w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary');
        $form->raw('</div>');

        // Blog Post Selection (shown when page_type is single_post)
        $blogPostOptions = ['' => '-- Select Blog Post --'];
        foreach ($blogPosts as $post) {
            $blogPostOptions[$post['id']] = $post['title'] ?? 'Untitled';
        }
        $selectedPostId = $page['blog_post_id'] ?? '';
        $form->raw('<div id="blog_post_field" style="display: ' . ($currentPageType === 'single_post' ? 'block' : 'none') . ';">');
        $form->select('blog_post_id', 'Blog Post', $blogPostOptions)
            ->default($selectedPostId)
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary');
        $form->raw('</div>');

        // Blog Category Selection (shown when page_type is category)
        $blogCategoryOptions = ['' => '-- Select Blog Category --'];
        foreach ($blogCategories as $category) {
            $blogCategoryOptions[$category['id']] = $category['name'] ?? 'Untitled';
        }
        $selectedCategoryId = $page['blog_category_id'] ?? '';
        $form->raw('<div id="blog_category_field" style="display: ' . ($currentPageType === 'category' ? 'block' : 'none') . ';">');
        $form->select('blog_category_id', 'Blog Category', $blogCategoryOptions)
            ->default($selectedCategoryId)
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary');
        $form->raw('</div>');

        // Blog Tag Selection (shown when page_type is tag)
        $blogTagOptions = ['' => '-- Select Blog Tag --'];
        foreach ($blogTags as $tag) {
            $blogTagOptions[$tag['id']] = $tag['name'] ?? 'Untitled';
        }
        $selectedTagId = $page['blog_tag_id'] ?? '';
        $form->raw('<div id="blog_tag_field" style="display: ' . ($currentPageType === 'tag' ? 'block' : 'none') . ';">');
        $form->select('blog_tag_id', 'Blog Tag', $blogTagOptions)
            ->default($selectedTagId)
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary');
        $form->raw('</div>');

        // Display Options (shown when page_type is category, tag, or list)
        $showDisplayOptions = in_array($currentPageType, ['category', 'tag', 'list']);
        $displayStyle = $page['display_style'] ?? 'list';
        $postsPerPage = $page['posts_per_page'] ?? 10;
        $showExcerpt = $page['show_excerpt'] ?? false;
        $showFeaturedImage = $page['show_featured_image'] ?? false;
        
        // Debug: Log display style
        error_log('DEBUG edit view - displayStyle: ' . $displayStyle . ', grid_columns: ' . ($page['grid_columns'] ?? 'not set'));
        
        $form->raw('<div id="display_options_field" style="display: ' . ($showDisplayOptions ? 'block' : 'none') . ';">');
        $form->raw('<label class="block text-sm font-medium text-slate-300 mb-4">Display Options</label>');
        
        // Hidden input that will always be sent (updated by JavaScript)
        $form->hidden('display_style')
            ->attribute('id', 'display_style_hidden')
            ->attribute('value', $displayStyle);
        
        // Display Style (visual select - name removed when hidden, restored when visible)
        // Ensure displayStyle has a valid value
        if (!in_array($displayStyle, ['list', 'grid', 'masonry'])) {
            $displayStyle = 'list';
        }
        error_log('DEBUG edit view - Final displayStyle value: ' . $displayStyle);
        
        // Set the value in the page array so FormBuilder can use it
        $page['display_style_visual'] = $displayStyle;
        
        $form->select('display_style_visual', 'Display Style', [
            'list' => 'List View',
            'grid' => 'Grid View',
            'masonry' => 'Masonry View'
        ])
            ->default($displayStyle)
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary mb-4')
            ->attribute('id', 'display_style_select')
            ->attribute('data-default-value', $displayStyle); // Store default value in data attribute

        // Grid Columns (shown only when grid or masonry is selected)
        $gridColumns = $page['grid_columns'] ?? 3;
        $showGridColumns = ($displayStyle === 'grid' || $displayStyle === 'masonry');
        error_log('DEBUG edit view - showGridColumns: ' . ($showGridColumns ? 'true' : 'false') . ', displayStyle: ' . $displayStyle);
        $form->raw('<div id="grid_columns_field" style="display: ' . ($showGridColumns ? 'block' : 'none') . ';">');
        $form->number('grid_columns', 'Grid Columns')
            ->default($gridColumns)
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary mb-4')
            ->attribute('min', '1')
            ->attribute('max', '6');
        $form->raw('</div>');

        // Posts per Page
        $form->number('posts_per_page', 'Posts per Page')
            ->default($postsPerPage)
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary mb-4')
            ->attribute('min', '1')
            ->attribute('max', '100');

        // Show Excerpt
        $form->checkbox('show_excerpt', 'Show Excerpt')
            ->checked($showExcerpt)
            ->attribute('class', 'w-4 h-4 text-theme-primary bg-slate-900 border-slate-700 rounded focus:ring-theme-primary mb-4');

        // Show Featured Image
        $form->checkbox('show_featured_image', 'Show Featured Image')
            ->checked($showFeaturedImage)
            ->attribute('class', 'w-4 h-4 text-theme-primary bg-slate-900 border-slate-700 rounded focus:ring-theme-primary mb-4');

        $form->raw('</div>'); // End display_options_field

        // Homepage Options (shown when application is homepage)
        // Parse homepage options from display_options JSON
        $homepageOptions = [];
        if ($currentApplication === 'homepage' && isset($page['display_options'])) {
            if (is_string($page['display_options'])) {
                $homepageOptions = json_decode($page['display_options'], true) ?? [];
            } elseif (is_array($page['display_options'])) {
                $homepageOptions = $page['display_options'];
            }
        }
        
        $homepageEnableBlogSlider = $homepageOptions['enable_blog_slider'] ?? false;
        $homepageBlogSliderPosts = $homepageOptions['blog_slider_posts'] ?? [];
        $homepageEnableLoginForm = $homepageOptions['enable_login_form'] ?? false;
        $homepageEnableContactForm = $homepageOptions['enable_contact_form'] ?? false;
        
        $showHomepageOptions = ($currentApplication === 'homepage');
        $form->raw('<div id="homepage_options_field" style="display: ' . ($showHomepageOptions ? 'block' : 'none') . ';">');
        $form->raw('<label class="block text-sm font-medium text-slate-300 mb-4">Homepage Options</label>');
        
        // Enable Blog Slider
        $form->checkbox('homepage_enable_blog_slider', 'Enable Blog Slider')
            ->checked($homepageEnableBlogSlider)
            ->attribute('class', 'w-4 h-4 text-theme-primary bg-slate-900 border-slate-700 rounded focus:ring-theme-primary mb-4')
            ->attribute('id', 'homepage_enable_blog_slider');
        
        // Blog Slider Posts (multi-select) - shown when blog slider is enabled
        $form->raw('<div id="homepage_blog_slider_posts_field" style="display: ' . ($homepageEnableBlogSlider ? 'block' : 'none') . ';">');
        $blogPostOptionsForSlider = [];
        foreach ($blogPosts as $post) {
            $blogPostOptionsForSlider[$post['id']] = $post['title'] ?? 'Untitled';
        }
        // Multi-select for blog posts (using select with multiple attribute)
        $form->raw('<label for="homepage_blog_slider_posts" class="block text-sm font-medium text-slate-300 mb-2">Select Blog Posts for Slider</label>');
        $form->raw('<select name="homepage_blog_slider_posts[]" id="homepage_blog_slider_posts" multiple size="8" class="w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary mb-4">');
        foreach ($blogPostOptionsForSlider as $id => $title) {
            $selected = in_array($id, $homepageBlogSliderPosts) ? ' selected' : '';
            $form->raw('<option value="' . htmlspecialchars($id) . '"' . $selected . '>' . htmlspecialchars($title) . '</option>');
        }
        $form->raw('</select>');
        $form->raw('<p class="text-xs text-slate-400 mb-4">Hold Ctrl (or Cmd on Mac) to select multiple posts</p>');
        $form->raw('</div>');
        
        // Enable Login Form
        $form->checkbox('homepage_enable_login_form', 'Enable Login Form')
            ->checked($homepageEnableLoginForm)
            ->attribute('class', 'w-4 h-4 text-theme-primary bg-slate-900 border-slate-700 rounded focus:ring-theme-primary mb-4')
            ->attribute('id', 'homepage_enable_login_form');
        
        // Enable Contact Form
        $form->checkbox('homepage_enable_contact_form', 'Enable Contact Form')
            ->checked($homepageEnableContactForm)
            ->attribute('class', 'w-4 h-4 text-theme-primary bg-slate-900 border-slate-700 rounded focus:ring-theme-primary mb-4')
            ->attribute('id', 'homepage_enable_contact_form');
        
        $form->raw('</div>'); // End homepage_options_field

        // Content (for custom pages)
        $showContent = ($currentApplication === 'custom' || empty($currentApplication));
        $form->raw('<div id="content_field" style="display: ' . ($showContent ? 'block' : 'none') . ';">');
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
        $selectedNavbarId = $page['navbar_id'] ?? '';
        $form->select('navbar_id', 'Navigation Menu', $navbarOptions)
            ->default($selectedNavbarId)
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary');

        // Parent Page
        $parentPageOptions = ['' => '-- No Parent --'];
        foreach ($parentPages as $id => $title) {
            $parentPageOptions[$id] = $title;
        }
        $form->select('parent_page_id', 'Parent Page', $parentPageOptions)
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary');

        // Is Active
        $form->checkbox('is_active', 'Active')
            ->attribute('class', 'w-4 h-4 text-theme-primary bg-slate-900 border-slate-700 rounded focus:ring-theme-primary');

        // Is In Menu
        $form->checkbox('is_in_menu', 'Show in Menu')
            ->attribute('class', 'w-4 h-4 text-theme-primary bg-slate-900 border-slate-700 rounded focus:ring-theme-primary');

        // Menu Order
        $form->number('menu_order', 'Menu Order')
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary')
            ->attribute('min', '0');

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
        $form->submit('Update Page')
            ->attribute('class', 'w-full px-6 py-3 bg-theme-primary hover:bg-theme-primary/90 text-white font-medium rounded-lg transition-colors');

        // Render form - single column, full width
        echo '<div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-xl p-6">';
        echo $form->render();
        echo '</div>';
    ?>

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
// Toggle application fields
function toggleApplicationFields() {
    const applicationSelect = document.getElementById('application_select') || document.getElementById('application') || document.querySelector('select[name="application"]');
    if (!applicationSelect) {
        console.error('Application select not found in toggleApplicationFields');
        return;
    }
    
    const application = applicationSelect.value;
    const pageTypeField = document.getElementById('page_type_field');
    const contentField = document.getElementById('content_field');
    const routeInput = document.getElementById('route_input');
    
    if (!pageTypeField) return;
    
    if (application === 'blog') {
        pageTypeField.style.display = 'block';
        if (contentField) contentField.style.display = 'none';
        togglePageTypeFields(); // Also update page type fields
        
        // Remove contact route lock if exists
        if (routeInput) {
            routeInput.readOnly = false;
            routeInput.style.backgroundColor = '';
            routeInput.style.borderColor = '';
            const infoMsg = document.getElementById('contact_route_info');
            if (infoMsg) infoMsg.remove();
        }
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
            
            // Remove homepage route info if exists
            const homepageInfoMsg = document.getElementById('homepage_route_info');
            if (homepageInfoMsg) homepageInfoMsg.remove();
            
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
            
            const contactInfoMsg = document.getElementById('contact_route_info');
            if (contactInfoMsg) contactInfoMsg.remove();
            
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
        
        // Hide homepage options field
        const homepageOptionsField = document.getElementById('homepage_options_field');
        if (homepageOptionsField) homepageOptionsField.style.display = 'none';
        
        // Remove contact and homepage route info if exists
        if (routeInput) {
            routeInput.readOnly = false;
            routeInput.style.backgroundColor = '';
            routeInput.style.borderColor = '';
            const contactInfoMsg = document.getElementById('contact_route_info');
            if (contactInfoMsg) contactInfoMsg.remove();
            const homepageInfoMsg = document.getElementById('homepage_route_info');
            if (homepageInfoMsg) homepageInfoMsg.remove();
        }
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
    const select = document.getElementById('display_style_select') || document.querySelector('select[name="display_style_visual"]');
    const hidden = document.getElementById('display_style_hidden');
    if (select && hidden) {
        hidden.value = select.value;
    }
}

// Toggle grid columns field based on display style
function toggleGridColumns() {
    try {
        // Try multiple selectors to find the select element
        // First try by name (more reliable)
        var select = document.querySelector('select[name="display_style_visual"]');
        
        // If not found, try by ID, but make sure it's a SELECT element
        if (!select) {
            var elementById = document.getElementById('display_style_select');
            if (elementById && elementById.tagName === 'SELECT') {
                select = elementById;
            }
        }
        
        if (!select) {
            // Last resort - find by label
            var labels = document.querySelectorAll('label');
            for (var i = 0; i < labels.length; i++) {
                var label = labels[i];
                if (label.textContent && label.textContent.includes('Display Style')) {
                    var nextSelect = label.nextElementSibling;
                    if (nextSelect && nextSelect.tagName === 'SELECT') {
                        select = nextSelect;
                        break;
                    }
                    // Also check if label has a 'for' attribute pointing to select
                    var labelFor = label.getAttribute('for');
                    if (labelFor) {
                        var selectById = document.getElementById(labelFor);
                        if (selectById && selectById.tagName === 'SELECT') {
                            select = selectById;
                            break;
                        }
                    }
                }
            }
        }
        
        // Make sure we have a SELECT element
        if (select && select.tagName !== 'SELECT') {
            console.error('DEBUG toggleGridColumns - Element found is not a SELECT:', select.tagName);
            // Try to find select inside the found element
            var selectInside = select.querySelector('select[name="display_style_visual"]');
            if (selectInside) {
                select = selectInside;
            } else {
                select = null;
            }
        }
        
        // Try multiple selectors to find the grid columns field
        var gridColumnsField = document.getElementById('grid_columns_field');
        if (!gridColumnsField) {
            // Try to find by looking for the number input with name="grid_columns"
            var gridInput = document.querySelector('input[name="grid_columns"]');
            if (gridInput) {
                gridColumnsField = gridInput.closest('div');
            }
        }
        
        if (!select) {
            console.error('toggleGridColumns: select not found');
            return false;
        }
        
        if (!gridColumnsField) {
            console.error('toggleGridColumns: gridColumnsField not found');
            return false;
        }
        
        // Get the actual selected value (handle case where value property is not set)
        var value = null;
        
        console.log('DEBUG toggleGridColumns - Starting value detection');
        console.log('DEBUG toggleGridColumns - select element:', select);
        console.log('DEBUG toggleGridColumns - select.value:', select ? select.value : 'select is null');
        
        // First try to get from select.value (most direct)
        try {
            if (select && select.value && select.value !== '' && select.value !== 'undefined') {
                value = select.value;
                console.log('DEBUG toggleGridColumns - Got value from select.value:', value);
            }
        } catch (e) {
            console.error('DEBUG toggleGridColumns - Error reading select.value:', e);
        }
        
        // If no value, try to get from data attribute (set by PHP)
        if (!value) {
            try {
                if (select && select.hasAttribute && select.hasAttribute('data-default-value')) {
                    value = select.getAttribute('data-default-value');
                    console.log('DEBUG toggleGridColumns - Got value from data-default-value:', value);
                    // Also set it as the select value if it's not set
                    if (value && (!select.value || select.value === '' || select.value === 'undefined')) {
                        select.value = value;
                        console.log('DEBUG toggleGridColumns - Set select.value to:', value);
                    }
                } else {
                    console.log('DEBUG toggleGridColumns - No data-default-value attribute found');
                }
            } catch (e) {
                console.error('DEBUG toggleGridColumns - Error reading data-default-value:', e);
            }
        }
        
        // Then try direct value
        try {
            if (!value && select.value && select.value !== 'undefined' && select.value !== '' && select.value !== null) {
                value = select.value;
            }
        } catch (e) {
            console.error('Error reading select.value:', e);
        }
        
        // If value is still not set, try to get from selected option
        if (!value || value === 'undefined' || value === '' || value === null) {
            // Check if select has options property
            if (select && typeof select === 'object') {
                try {
                    // Check if options exists
                    if ('options' in select && select.options) {
                        // Try to get from selected index
                        if ('selectedIndex' in select) {
                            var selectedIdx = select.selectedIndex;
                            if (selectedIdx !== null && selectedIdx !== undefined && !isNaN(selectedIdx) && selectedIdx >= 0) {
                                try {
                                    if (selectedIdx < select.options.length) {
                                        var selectedOption = select.options[selectedIdx];
                                        if (selectedOption && typeof selectedOption === 'object' && 'value' in selectedOption) {
                                            value = selectedOption.value;
                                        }
                                    }
                                } catch (e) {
                                    console.error('Error accessing selected option by index:', e);
                                }
                            }
                        }
                        
                        // If still no value, find selected option manually
                        if (!value || value === 'undefined' || value === '') {
                            try {
                                if ('length' in select.options && select.options.length > 0) {
                                    var optionsLength = select.options.length;
                                    for (var j = 0; j < optionsLength; j++) {
                                        try {
                                            var option = select.options[j];
                                            if (option && typeof option === 'object') {
                                                var isSelected = false;
                                                try {
                                                    isSelected = (option.selected === true);
                                                } catch (e) {
                                                    // Ignore
                                                }
                                                if (!isSelected && option.hasAttribute) {
                                                    try {
                                                        isSelected = option.hasAttribute('selected');
                                                    } catch (e) {
                                                        // Ignore
                                                    }
                                                }
                                                if (isSelected && 'value' in option) {
                                                    value = option.value;
                                                    break;
                                                }
                                            }
                                        } catch (e) {
                                            // Skip this option
                                        }
                                    }
                                }
                            } catch (e) {
                                console.error('Error iterating options:', e);
                            }
                        }
                    }
                } catch (e) {
                    console.error('Error accessing select.options:', e);
                }
            }
        }
        
        console.log('DEBUG toggleGridColumns - Final value:', value);
        console.log('DEBUG toggleGridColumns - gridColumnsField:', gridColumnsField);
        
        if (value === 'grid' || value === 'masonry') {
            console.log('DEBUG toggleGridColumns - Showing grid columns field (value is grid or masonry)');
            // Use multiple methods to ensure it shows - with !important via style
            gridColumnsField.setAttribute('style', 'display: block !important;');
            gridColumnsField.style.display = 'block';
            gridColumnsField.style.visibility = 'visible';
            gridColumnsField.removeAttribute('hidden');
            gridColumnsField.classList.remove('hidden');
            gridColumnsField.classList.add('grid-visible');
            return true;
        } else {
            console.log('DEBUG toggleGridColumns - Hiding grid columns field (value is not grid or masonry)');
            gridColumnsField.style.display = 'none';
            gridColumnsField.classList.remove('grid-visible');
            return true;
        }
    } catch (e) {
        console.error('Error in toggleGridColumns:', e);
        return false;
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Add event listener to application select (FormBuilder uses name as id)
    const applicationSelect = document.getElementById('application') || document.querySelector('select[name="application"]');
    if (applicationSelect) {
        applicationSelect.addEventListener('change', toggleApplicationFields);
        // Initialize on page load
        toggleApplicationFields();
    }
    
    // Add event listener to page_type select
    const pageTypeSelect = document.getElementById('page_type') || document.querySelector('select[name="page_type"]');
    if (pageTypeSelect) {
        pageTypeSelect.addEventListener('change', togglePageTypeFields);
    }
    
    // Add event listener to homepage blog slider checkbox
    const homepageBlogSliderCheckbox = document.getElementById('homepage_enable_blog_slider');
    if (homepageBlogSliderCheckbox) {
        homepageBlogSliderCheckbox.addEventListener('change', toggleHomepageBlogSlider);
        // Initialize on page load
        toggleHomepageBlogSlider();
    }
    
    // Function to get the selected value from a select element
    function getSelectValue(select) {
        try {
            if (!select) {
                return null;
            }
            
            // Try direct value first (most reliable)
            if (select.value !== null && select.value !== undefined && select.value !== '' && select.value !== 'undefined') {
                return select.value;
            }
            
            // Check if select has options property and it's an array-like object
            if (!select.options) {
                return null;
            }
            
            // Check if options array exists and has length
            if (typeof select.options.length === 'undefined' || select.options.length === 0) {
                return null;
            }
            
            // Try to get selected index
            var selectedIndex = select.selectedIndex;
            if (selectedIndex !== null && selectedIndex !== undefined && selectedIndex >= 0 && selectedIndex < select.options.length) {
                var selectedOption = select.options[selectedIndex];
                if (selectedOption && selectedOption.value) {
                    return selectedOption.value;
                }
            }
            
            // Last resort - find selected option manually
            for (var i = 0; i < select.options.length; i++) {
                var option = select.options[i];
                if (option) {
                    if (option.selected === true) {
                        return option.value || null;
                    }
                }
            }
        } catch (e) {
            console.error('Error in getSelectValue:', e);
            return null;
        }
        
        return null;
    }
    
    // Function to initialize display style select
    function initDisplayStyleSelect() {
        try {
            // First try by name (more reliable) - avoid ID conflicts
            var displayStyleSelect = document.querySelector('select[name="display_style_visual"]');
            
            // If not found, try by ID, but make sure it's a SELECT element
            if (!displayStyleSelect) {
                var elementById = document.getElementById('display_style_select');
                if (elementById && elementById.tagName === 'SELECT') {
                    displayStyleSelect = elementById;
                }
            }
            
            if (!displayStyleSelect) {
                console.error('DEBUG initDisplayStyleSelect - Select element not found!');
                return false;
            }
            
            // Make sure we have a SELECT element
            if (displayStyleSelect.tagName !== 'SELECT') {
                console.error('DEBUG initDisplayStyleSelect - Element found is not a SELECT:', displayStyleSelect.tagName);
                // Try to find select inside the found element
                var selectInside = displayStyleSelect.querySelector('select[name="display_style_visual"]');
                if (selectInside) {
                    displayStyleSelect = selectInside;
                } else {
                    return false;
                }
            }
            
            // Get the actual selected value safely
            var currentValue = null;
            
            console.log('DEBUG initDisplayStyleSelect - Starting initialization');
            console.log('DEBUG initDisplayStyleSelect - displayStyleSelect:', displayStyleSelect);
            console.log('DEBUG initDisplayStyleSelect - displayStyleSelect.tagName:', displayStyleSelect.tagName);
            console.log('DEBUG initDisplayStyleSelect - displayStyleSelect.value:', displayStyleSelect ? displayStyleSelect.value : 'select is null');
            console.log('DEBUG initDisplayStyleSelect - All selects with name="display_style_visual":', document.querySelectorAll('select[name="display_style_visual"]'));
            console.log('DEBUG initDisplayStyleSelect - Element with id="display_style_select":', document.getElementById('display_style_select'));
            
            // First try to get from data-default-value attribute (set by PHP)
            try {
                if (displayStyleSelect.hasAttribute && displayStyleSelect.hasAttribute('data-default-value')) {
                    currentValue = displayStyleSelect.getAttribute('data-default-value');
                    console.log('DEBUG initDisplayStyleSelect - Got value from data-default-value:', currentValue);
                } else {
                    console.log('DEBUG initDisplayStyleSelect - No data-default-value attribute');
                }
            } catch (e) {
                console.error('DEBUG initDisplayStyleSelect - Error reading data-default-value:', e);
            }
            
            // Then try to get from select value
            try {
                if (!currentValue && displayStyleSelect.value && displayStyleSelect.value !== 'undefined' && displayStyleSelect.value !== '') {
                    currentValue = displayStyleSelect.value;
                    console.log('DEBUG initDisplayStyleSelect - Got value from select.value:', currentValue);
                }
            } catch (e) {
                console.error('DEBUG initDisplayStyleSelect - Error reading select.value:', e);
            }
            
            // If still no value, try getSelectValue function
            if (!currentValue) {
                try {
                    currentValue = getSelectValue(displayStyleSelect);
                    console.log('DEBUG initDisplayStyleSelect - Got value from getSelectValue:', currentValue);
                } catch (e) {
                    console.error('DEBUG initDisplayStyleSelect - Error in getSelectValue:', e);
                }
            }
            
            console.log('DEBUG initDisplayStyleSelect - Final currentValue:', currentValue);
            
            // If value is not set but we have a value from data attribute or getSelectValue, update the select
            if (currentValue && (!displayStyleSelect.value || displayStyleSelect.value === 'undefined' || displayStyleSelect.value === '')) {
                try {
                    displayStyleSelect.value = currentValue;
                    console.log('DEBUG initDisplayStyleSelect - Set select.value to:', currentValue);
                } catch (e) {
                    console.error('DEBUG initDisplayStyleSelect - Error setting select value:', e);
                }
            }
            
            displayStyleSelect.addEventListener('change', function() {
                updateDisplayStyleHidden();
                toggleGridColumns();
            });
            
            // Initialize hidden field immediately
            updateDisplayStyleHidden();
            
            // Set the value if we have currentValue
            if (currentValue) {
                try {
                    displayStyleSelect.value = currentValue;
                } catch (e) {
                    console.error('Error setting select value in init:', e);
                }
            }
            
            // Initialize grid columns field visibility immediately (after setting value)
            setTimeout(function() {
                toggleGridColumns();
            }, 10);
            
            // Also retry after a delay to ensure DOM is fully ready
            setTimeout(function() {
                try {
                    toggleGridColumns();
                } catch (e) {
                    console.error('Error in toggleGridColumns during retry:', e);
                }
            }, 200);
            
            return true;
        } catch (e) {
            console.error('Error in initDisplayStyleSelect:', e);
            return false;
        }
    }
    
    // Try to initialize immediately
    if (!initDisplayStyleSelect()) {
    }
    
    // Retry multiple times to ensure DOM is ready
    let retryCount = 0;
    const maxRetries = 10;
    const retryInterval = setInterval(function() {
        retryCount++;
        if (initDisplayStyleSelect() || retryCount >= maxRetries) {
            clearInterval(retryInterval);
            if (retryCount >= maxRetries) {
                console.error('Display style select not found after', maxRetries, 'retries');
            } else {
            }
        }
    }, 100);
    
    // Ensure display_style is updated before form submit
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            // Always update hidden from select before submit
            const hidden = document.getElementById('display_style_hidden');
            const select = document.getElementById('display_style_select') || document.querySelector('select[name="display_style_visual"]');
            if (hidden && select) {
                hidden.value = select.value;
            } else {
                console.error('Form submit - hidden or select not found!', {hidden: !!hidden, select: !!select});
            }
        });
    }
    
    // Initial state check
    toggleApplicationFields();
    
    // Force check grid columns field one more time after everything is loaded
    window.addEventListener('load', function() {
        setTimeout(function() {
            toggleGridColumns();
            
            // Also check if the field exists and force show if grid or masonry is selected
            const select = document.getElementById('display_style_select') || document.querySelector('select[name="display_style_visual"]');
            const gridColumnsField = document.getElementById('grid_columns_field');
            if (select && gridColumnsField && (select.value === 'grid' || select.value === 'masonry')) {
                gridColumnsField.style.display = 'block';
                gridColumnsField.setAttribute('style', 'display: block !important;');
            }
        }, 500);
    });
});
</script>
