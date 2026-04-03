{{-- Create Page - Page Manager --}}

@php
global $router;
$lang = $router->lang ?? 'sr';

// Get errors before FormBuilder clears them from session
$formErrors = [];
if (class_exists('Form') && method_exists('Form', 'hasErrors') && Form::hasErrors()) {
    $formErrors = Form::getErrors();
}

$blogPosts = $blogPosts ?? [];
$blogCategories = $blogCategories ?? [];
$blogTags = $blogTags ?? [];
$navigationMenus = $navigationMenus ?? [];
$parentPages = $parentPages ?? [];
$languagesData = $languagesData ?? [];
$languages = $languages ?? [];

// Build options arrays
$blogPostOptions = ['' => '-- Select Blog Post --'];
foreach ($blogPosts as $post) {
    $postId = is_array($post) ? ($post['id'] ?? null) : ($post->id ?? null);
    $postTitle = is_array($post) ? ($post['title'] ?? 'Untitled') : ($post->title ?? 'Untitled');
    if ($postId) {
        $blogPostOptions[$postId] = $postTitle;
    }
}

$blogCategoryOptions = ['' => '-- Select Blog Category --'];
foreach ($blogCategories as $category) {
    $catId = is_array($category) ? ($category['id'] ?? null) : ($category->id ?? null);
    $catName = is_array($category) ? ($category['name'] ?? 'Untitled') : ($category->name ?? 'Untitled');
    if ($catId) {
        $blogCategoryOptions[$catId] = $catName;
    }
}

$blogTagOptions = ['' => '-- Select Blog Tag --'];
foreach ($blogTags as $tag) {
    $tagId = is_array($tag) ? ($tag['id'] ?? null) : ($tag->id ?? null);
    $tagName = is_array($tag) ? ($tag['name'] ?? 'Untitled') : ($tag->name ?? 'Untitled');
    if ($tagId) {
        $blogTagOptions[$tagId] = $tagName;
    }
}

$blogPostOptionsForSlider = [];
foreach ($blogPosts as $post) {
    $postId = is_array($post) ? ($post['id'] ?? null) : ($post->id ?? null);
    $postTitle = is_array($post) ? ($post['title'] ?? 'Untitled') : ($post->title ?? 'Untitled');
    if ($postId) {
        $blogPostOptionsForSlider[$postId] = $postTitle;
    }
}

$navbarOptions = ['' => '-- No Navigation Menu --'];
if (is_array($navigationMenus)) {
    foreach ($navigationMenus as $id => $name) {
        $navbarOptions[$id] = $name;
    }
}

$parentPageOptions = ['' => '-- No Parent --'];
foreach ($parentPages as $id => $title) {
    $parentPageOptions[$id] = $title;
}

$hasGeneralError = isset($formErrors['general']) && !empty($formErrors['general']);
$generalErrors = $hasGeneralError ? (is_array($formErrors['general']) ? $formErrors['general'] : [$formErrors['general']]) : [];
@endphp

<div class="p-8 max-w-4xl">
    {{-- Page Header --}}
    <div class="mb-8">
    </div>

    {{-- Create Form --}}
    <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-xl p-6">
        {{-- Display general errors if any --}}
        @if ($hasGeneralError)
            <div class="mb-6 p-4 bg-red-900/20 border border-red-500/50 rounded-lg">
                <p class="text-red-400 font-semibold mb-2">Greška:</p>
                @foreach ($generalErrors as $error)
                    <p class="text-red-300 text-sm">{{ e($error) }}</p>
                @endforeach
            </div>
        @endif

        @php
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
        if (!empty($languagesData)) {
            require_once ViewEngine::getViewPath() . '/helpers/language-select.php';
            $selectedLangId = class_exists('Form') && method_exists('Form', 'old') ? Form::old('language_id', '') : '';
            renderLanguageSelect($form, $languagesData, $selectedLangId);
        } elseif (!empty($languages)) {
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
        $form->raw('<div id="blog_post_field" style="display: none;">');
        $form->select('blog_post_id', 'Blog Post', $blogPostOptions)
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary');
        $form->raw('</div>');

        // Blog Category Selection (shown when page_type is category)
        $form->raw('<div id="blog_category_field" style="display: none;">');
        $form->select('blog_category_id', 'Blog Category', $blogCategoryOptions)
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary');
        $form->raw('</div>');

        // Blog Tag Selection (shown when page_type is tag)
        $form->raw('<div id="blog_tag_field" style="display: none;">');
        $form->select('blog_tag_id', 'Blog Tag', $blogTagOptions)
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary');
        $form->raw('</div>');

        // Display Options (shown when page_type is category, tag, or list)
        $form->raw('<div id="display_options_field" style="display: none;">');
        $form->raw('<label class="block text-sm font-medium text-slate-300 mb-4">Display Options</label>');
        $form->hidden('display_style')
            ->attribute('id', 'display_style_hidden')
            ->attribute('value', 'list');
        $form->select('display_style_visual', 'Display Style', [
            'list' => 'List View',
            'grid' => 'Grid View',
            'masonry' => 'Masonry View'
        ])
            ->default('list')
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary mb-4')
            ->attribute('id', 'display_style_select');
        $form->raw('<div id="grid_columns_field" style="display: none;">');
        $form->number('grid_columns', 'Grid Columns')
            ->default(3)
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary mb-4')
            ->attribute('min', '1')
            ->attribute('max', '6');
        $form->raw('</div>');
        $form->number('posts_per_page', 'Posts per Page')
            ->default(10)
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary mb-4')
            ->attribute('min', '1')
            ->attribute('max', '100');
        $form->checkbox('show_excerpt', 'Show Excerpt')
            ->checked(true)
            ->attribute('class', 'w-4 h-4 text-theme-primary bg-slate-900 border-slate-700 rounded focus:ring-theme-primary mb-4');
        $form->checkbox('show_featured_image', 'Show Featured Image')
            ->checked(true)
            ->attribute('class', 'w-4 h-4 text-theme-primary bg-slate-900 border-slate-700 rounded focus:ring-theme-primary mb-4');
        $form->raw('</div>');

        // Homepage Options (shown when application is homepage)
        $form->raw('<div id="homepage_options_field" style="display: none;">');
        $form->raw('<label class="block text-sm font-medium text-slate-300 mb-4">Homepage Options</label>');
        $form->checkbox('homepage_enable_blog_slider', 'Enable Blog Slider')
            ->attribute('class', 'w-4 h-4 text-theme-primary bg-slate-900 border-slate-700 rounded focus:ring-theme-primary mb-4')
            ->attribute('id', 'homepage_enable_blog_slider');
        $form->raw('<div id="homepage_blog_slider_posts_field" style="display: none;">');
        $form->raw('<label for="homepage_blog_slider_posts" class="block text-sm font-medium text-slate-300 mb-2">Select Blog Posts for Slider</label>');
        $form->raw('<select name="homepage_blog_slider_posts[]" id="homepage_blog_slider_posts" multiple size="8" class="w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary mb-4">');
        foreach ($blogPostOptionsForSlider as $id => $title) {
            $form->raw('<option value="' . htmlspecialchars($id) . '">' . htmlspecialchars($title) . '</option>');
        }
        $form->raw('</select>');
        $form->raw('<p class="text-xs text-slate-400 mb-4">Hold Ctrl (or Cmd on Mac) to select multiple posts</p>');
        $form->raw('</div>');
        $form->checkbox('homepage_enable_login_form', 'Enable Login Form')
            ->attribute('class', 'w-4 h-4 text-theme-primary bg-slate-900 border-slate-700 rounded focus:ring-theme-primary mb-4')
            ->attribute('id', 'homepage_enable_login_form');
        $form->checkbox('homepage_enable_contact_form', 'Enable Contact Form')
            ->attribute('class', 'w-4 h-4 text-theme-primary bg-slate-900 border-slate-700 rounded focus:ring-theme-primary mb-4')
            ->attribute('id', 'homepage_enable_contact_form');
        $form->raw('</div>');

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
        $form->select('navbar_id', 'Navigation Menu', $navbarOptions)
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary');

        // Parent Page
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
        @endphp
    </div>

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
{{-- Sync route from slug --}}
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

{{-- Toggle application fields --}}
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
        togglePageTypeFields();
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
            const infoMsg = document.getElementById('contact_route_info');
            if (infoMsg) infoMsg.remove();
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
        if (routeInput) {
            routeInput.readOnly = false;
            routeInput.style.backgroundColor = '';
            routeInput.style.borderColor = '';
            const infoMsg = document.getElementById('contact_route_info');
            if (infoMsg) infoMsg.remove();
        }
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
    const select = document.getElementById('display_style_select');
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
    
    const value = select.value;
    
    if (value === 'grid') {
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

{{-- Initialize on page load --}}
document.addEventListener('DOMContentLoaded', function() {
    const slugInput = document.getElementById('slug_input');
    if (slugInput) {
        slugInput.addEventListener('input', syncRouteFromSlug);
    }
    
    const applicationSelect = document.getElementById('application') || document.querySelector('select[name="application"]');
    if (applicationSelect) {
        applicationSelect.addEventListener('change', toggleApplicationFields);
        toggleApplicationFields();
    }
    
    const homepageBlogSliderCheckbox = document.getElementById('homepage_enable_blog_slider');
    if (homepageBlogSliderCheckbox) {
        homepageBlogSliderCheckbox.addEventListener('change', toggleHomepageBlogSlider);
    }
    
    const pageTypeSelect = document.getElementById('page_type') || document.querySelector('select[name="page_type"]');
    if (pageTypeSelect) {
        pageTypeSelect.addEventListener('change', togglePageTypeFields);
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
            const select = document.getElementById('display_style_select');
            if (hidden && select) {
                hidden.value = select.value;
            }
        });
    }
    
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
