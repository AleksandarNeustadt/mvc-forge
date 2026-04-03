{{-- Edit Page --}}

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
    {{-- Page Header --}}
    <div class="mb-8">
        <p class="text-slate-400">Update page information</p>
    </div>

    {{-- Edit Form --}}
    @php
    global $router;
    $lang = $router->lang ?? 'sr';
    $form = new FormBuilder("/{$lang}/dashboard/pages/{$page['id']}", 'PUT');
    $form->class('space-y-6');
    $form->withOld($page);

    // Display form errors if any
    if (class_exists('Form') && method_exists('Form', 'hasErrors') && Form::hasErrors()) {
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
        require_once ViewEngine::getViewPath() . '/helpers/language-select.php';
        $selectedLangId = class_exists('Form') && method_exists('Form', 'old') ? Form::old('language_id', $page['language_id'] ?? '') : ($page['language_id'] ?? '');
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
    
    // Ensure displayStyle has a valid value
    if (!in_array($displayStyle, ['list', 'grid', 'masonry'])) {
        $displayStyle = 'list';
    }
    
    // Set the value in the page array so FormBuilder can use it
    $page['display_style_visual'] = $displayStyle;
    
    $form->raw('<div id="display_options_field" style="display: ' . ($showDisplayOptions ? 'block' : 'none') . ';">');
    $form->raw('<label class="block text-sm font-medium text-slate-300 mb-4">Display Options</label>');
    
    // Hidden input that will always be sent (updated by JavaScript)
    $form->hidden('display_style')
        ->attribute('id', 'display_style_hidden')
        ->attribute('value', $displayStyle);
    
    // Display Style (visual select)
    $form->select('display_style_visual', 'Display Style', [
        'list' => 'List View',
        'grid' => 'Grid View',
        'masonry' => 'Masonry View'
    ])
        ->default($displayStyle)
        ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary mb-4')
        ->attribute('id', 'display_style_select')
        ->attribute('data-default-value', $displayStyle);

    // Grid Columns (shown only when grid or masonry is selected)
    $gridColumns = $page['grid_columns'] ?? 3;
    $showGridColumns = ($displayStyle === 'grid' || $displayStyle === 'masonry');
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

    echo $form->render();
    @endphp

    {{-- Back Link --}}
    <div class="mt-6">
        <a href="/{{ $lang }}/dashboard/pages" 
           class="inline-flex items-center gap-2 text-slate-400 hover:text-white transition-colors">
            <ion-icon name="arrow-back-outline"></ion-icon>
            Back to Pages
        </a>
    </div>
</div>

<script nonce="{{ csp_nonce() }}">
{{-- Toggle application fields --}}
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
        togglePageTypeFields();
        
        if (routeInput) {
            routeInput.readOnly = false;
            routeInput.style.backgroundColor = '';
            routeInput.style.borderColor = '';
            const infoMsg = document.getElementById('contact_route_info');
            if (infoMsg) infoMsg.remove();
        }
    } else if (application === 'contact') {
        pageTypeField.style.display = 'none';
        if (contentField) contentField.style.display = 'none';
        
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
        
        if (routeInput) {
            routeInput.readOnly = false;
            routeInput.style.backgroundColor = '';
            routeInput.style.borderColor = '';
            
            const homepageInfoMsg = document.getElementById('homepage_route_info');
            if (homepageInfoMsg) homepageInfoMsg.remove();
            
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
        pageTypeField.style.display = 'none';
        if (contentField) contentField.style.display = 'none';
        
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
        
        if (routeInput) {
            routeInput.readOnly = false;
            routeInput.style.backgroundColor = '';
            routeInput.style.borderColor = '';
            
            const contactInfoMsg = document.getElementById('contact_route_info');
            if (contactInfoMsg) contactInfoMsg.remove();
            
            let homepageInfoMsg = document.getElementById('homepage_route_info');
            if (!homepageInfoMsg) {
                homepageInfoMsg = document.createElement('div');
                homepageInfoMsg.id = 'homepage_route_info';
                homepageInfoMsg.className = 'mt-2 p-3 bg-green-900/20 border border-green-800/50 rounded-lg text-sm text-green-300';
                homepageInfoMsg.innerHTML = '<ion-icon name="information-circle-outline" class="inline mr-1"></ion-icon> Homepage aplikacija - obično se postavlja na route <code class="bg-green-900/30 px-1 rounded">/</code> ili <code class="bg-green-900/30 px-1 rounded">/home</code>.';
                routeInput.parentElement.appendChild(homepageInfoMsg);
            }
        }
        
        toggleHomepageBlogSlider();
    } else if (application === 'custom' || application === '') {
        pageTypeField.style.display = 'none';
        if (contentField) contentField.style.display = 'block';
        
        const blogPostField = document.getElementById('blog_post_field');
        const blogCategoryField = document.getElementById('blog_category_field');
        const blogTagField = document.getElementById('blog_tag_field');
        const displayOptionsField = document.getElementById('display_options_field');
        
        if (blogPostField) blogPostField.style.display = 'none';
        if (blogCategoryField) blogCategoryField.style.display = 'none';
        if (blogTagField) blogTagField.style.display = 'none';
        if (displayOptionsField) displayOptionsField.style.display = 'none';
        
        const homepageOptionsField = document.getElementById('homepage_options_field');
        if (homepageOptionsField) homepageOptionsField.style.display = 'none';
        
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

{{-- Toggle homepage blog slider posts field --}}
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

{{-- Toggle page type fields --}}
function togglePageTypeFields() {
    const pageTypeSelect = document.getElementById('page_type') || document.querySelector('select[name="page_type"]');
    if (!pageTypeSelect) return;
    
    const pageType = pageTypeSelect.value;
    const blogPostField = document.getElementById('blog_post_field');
    const blogCategoryField = document.getElementById('blog_category_field');
    const blogTagField = document.getElementById('blog_tag_field');
    const displayOptionsField = document.getElementById('display_options_field');
    
    if (blogPostField) blogPostField.style.display = 'none';
    if (blogCategoryField) blogCategoryField.style.display = 'none';
    if (blogTagField) blogTagField.style.display = 'none';
    if (displayOptionsField) displayOptionsField.style.display = 'none';
    
    if (pageType === 'single_post' && blogPostField) {
        blogPostField.style.display = 'block';
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

{{-- Toggle display options --}}
function toggleDisplayOptions() {
    const pageTypeSelect = document.getElementById('page_type') || document.querySelector('select[name="page_type"]');
    if (!pageTypeSelect) return;
    
    const pageType = pageTypeSelect.value;
    const displayOptionsField = document.getElementById('display_options_field');
    
    if (!displayOptionsField) return;
    
    if (pageType === 'category' || pageType === 'tag' || pageType === 'list') {
        displayOptionsField.style.display = 'block';
        setTimeout(toggleGridColumns, 10);
    } else {
        displayOptionsField.style.display = 'none';
    }
}

{{-- Update hidden display_style field --}}
function updateDisplayStyleHidden() {
    const select = document.getElementById('display_style_select') || document.querySelector('select[name="display_style_visual"]');
    const hidden = document.getElementById('display_style_hidden');
    if (select && hidden) {
        hidden.value = select.value;
    }
}

{{-- Toggle grid columns field --}}
function toggleGridColumns() {
    let select = document.getElementById('display_style_select');
    if (!select) {
        select = document.querySelector('select[name="display_style_visual"]');
    }
    
    let gridColumnsField = document.getElementById('grid_columns_field');
    if (!gridColumnsField) {
        const gridInput = document.querySelector('input[name="grid_columns"]');
        if (gridInput) {
            gridColumnsField = gridInput.closest('div');
        }
    }
    
    if (!select || !gridColumnsField) {
        return false;
    }
    
    const value = select.value || select.getAttribute('data-default-value');
    
    if (value === 'grid' || value === 'masonry') {
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

{{-- Initialize on page load --}}
document.addEventListener('DOMContentLoaded', function() {
    const applicationSelect = document.getElementById('application') || document.querySelector('select[name="application"]');
    if (applicationSelect) {
        applicationSelect.addEventListener('change', toggleApplicationFields);
        toggleApplicationFields();
    }
    
    const pageTypeSelect = document.getElementById('page_type') || document.querySelector('select[name="page_type"]');
    if (pageTypeSelect) {
        pageTypeSelect.addEventListener('change', togglePageTypeFields);
    }
    
    const homepageBlogSliderCheckbox = document.getElementById('homepage_enable_blog_slider');
    if (homepageBlogSliderCheckbox) {
        homepageBlogSliderCheckbox.addEventListener('change', toggleHomepageBlogSlider);
    }
    
    const displayStyleSelect = document.getElementById('display_style_select');
    if (displayStyleSelect) {
        displayStyleSelect.addEventListener('change', updateDisplayStyleHidden);
        displayStyleSelect.addEventListener('change', toggleGridColumns);
        updateDisplayStyleHidden();
        setTimeout(toggleGridColumns, 100);
    }
    
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const hidden = document.getElementById('display_style_hidden');
            const select = document.getElementById('display_style_select') || document.querySelector('select[name="display_style_visual"]');
            if (hidden && select) {
                hidden.value = select.value;
            }
        });
    }
    
    toggleApplicationFields();
    
    window.addEventListener('load', function() {
        setTimeout(function() {
            toggleGridColumns();
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
