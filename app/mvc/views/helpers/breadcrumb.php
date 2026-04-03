<?php
/**
 * Breadcrumb Helper
 * 
 * Generates breadcrumb navigation based on current URL
 * Supports custom breadcrumbs via $breadcrumbs variable in views
 * 
 * @param array|null $customBreadcrumbs Optional custom breadcrumb array
 * @param bool $inline Optional - if true, renders inline without bottom margin (for header)
 * @return void
 */
function renderBreadcrumb(?array $customBreadcrumbs = null, bool $inline = false): void {
    global $router;
    $lang = $router->lang ?? 'sr';
    
    // If custom breadcrumbs provided, use them
    if ($customBreadcrumbs !== null && !empty($customBreadcrumbs)) {
        $breadcrumbs = $customBreadcrumbs;
    } else {
        // Auto-generate breadcrumbs from URL
        $breadcrumbs = generateBreadcrumbsFromUrl($lang);
    }
    
    // Don't render if no breadcrumbs
    if (empty($breadcrumbs)) {
        return;
    }
    
    // Only show breadcrumb if there's more than just "Home"
    if (count($breadcrumbs) <= 1) {
        return;
    }
    
    $marginClass = $inline ? '' : 'mb-2';
    $sizeClass = $inline ? 'text-xs' : 'text-sm';
    echo '<nav class="breadcrumb-nav ' . $marginClass . '" aria-label="Breadcrumb">';
    echo '<ol class="flex flex-wrap items-center gap-1.5 ' . $sizeClass . '">';
    
    $lastIndex = count($breadcrumbs) - 1;
    foreach ($breadcrumbs as $index => $crumb) {
        $isLast = ($index === $lastIndex);
        
        if ($isLast) {
            // Last item - not clickable, highlighted
            $lastSizeClass = $inline ? 'text-sm' : 'text-base';
            echo '<li class="flex items-center" aria-current="page">';
            echo '<span class="text-white font-semibold ' . $lastSizeClass . '">' . e($crumb['label']) . '</span>';
            echo '</li>';
        } else {
            // Clickable item
            if ($index === 0) {
                // Home icon for first item
                echo '<li class="flex items-center">';
                echo '<a href="' . e($crumb['url']) . '" class="hover:text-theme-primary transition-colors flex items-center gap-1.5 text-slate-400 hover:text-slate-300">';
                echo '<ion-icon name="home-outline" class="text-base"></ion-icon>';
                echo '<span>' . e($crumb['label']) . '</span>';
                echo '</a>';
            } else {
                echo '<li class="flex items-center">';
                echo '<a href="' . e($crumb['url']) . '" class="hover:text-theme-primary transition-colors flex items-center gap-1.5 text-slate-400 hover:text-slate-300">';
                echo e($crumb['label']);
                echo '</a>';
            }
            echo '<ion-icon name="chevron-forward-outline" class="ml-1.5 text-slate-600 text-xs flex-shrink-0"></ion-icon>';
            echo '</li>';
        }
    }
    
    echo '</ol>';
    echo '</nav>';
}

/**
 * Generate breadcrumbs automatically from current URL
 * 
 * @param string $lang Current language code
 * @return array Breadcrumb items
 */
function generateBreadcrumbsFromUrl(string $lang): array {
    $breadcrumbs = [];
    
    // Always start with Home
    $breadcrumbs[] = [
        'label' => 'Home',
        'url' => '/' . $lang . '/'
    ];
    
    // Get current URI
    global $router;
    $uri = $router->getUri() ?? '/';
    
    // Remove leading/trailing slashes and split
    $pathParts = array_filter(explode('/', trim($uri, '/')));
    
    // If we're on homepage, return just Home
    if (empty($pathParts) || (count($pathParts) === 1 && $pathParts[0] === '')) {
        return $breadcrumbs;
    }
    
    // Build breadcrumbs progressively
    $currentPath = '';
    $breadcrumbLabels = [
        'dashboard' => 'Dashboard',
        'users' => 'Users',
        'roles' => 'Roles',
        'permissions' => 'Permissions',
        'pages' => 'Pages',
        'navigation-menus' => 'Navigation Menus',
        'blog' => 'Blog',
        'posts' => 'Posts',
        'categories' => 'Categories',
        'languages' => 'Languages',
        'continents' => 'Continents',
        'regions' => 'Regions',
        'database' => 'Database',
        'tables' => 'Tables',
        'columns' => 'Columns',
        'ip-tracking' => 'IP Tracking',
        'contact-messages' => 'Contact Messages',
        'contact' => 'Contact',
        'profile' => 'Profile',
        'login' => 'Login',
        'register' => 'Register',
        'search' => 'Search',
        'user' => 'User',
    ];
    
    // Action labels
    $actionLabels = [
        'create' => 'Create',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'preview' => 'Preview',
        'show' => 'View',
    ];
    
    $previousPart = null;
    foreach ($pathParts as $index => $part) {
        // Skip numeric IDs (they're parameters, not breadcrumb items)
        // But include them in the path for URL building
        if (is_numeric($part)) {
            $currentPath .= '/' . $part;
            $previousPart = $part;
            continue;
        }
        
        $currentPath .= '/' . $part;
        
        // Check if it's an action
        if (isset($actionLabels[$part])) {
            $label = $actionLabels[$part];
        } elseif (isset($breadcrumbLabels[$part])) {
            $label = $breadcrumbLabels[$part];
        } else {
            // Capitalize and format the part
            $label = ucfirst(str_replace(['-', '_'], ' ', $part));
        }
        
        // Build URL with language prefix
        $url = '/' . $lang . $currentPath;
        
        $breadcrumbs[] = [
            'label' => $label,
            'url' => $url
        ];
        
        $previousPart = $part;
    }
    
    // Try to get custom title from page/blogPost data
    if (isset($page) && is_array($page) && !empty($page['title'])) {
        $lastIndex = count($breadcrumbs) - 1;
        if ($lastIndex >= 0) {
            $breadcrumbs[$lastIndex]['label'] = $page['title'];
        }
    } elseif (isset($blogPost) && is_array($blogPost) && !empty($blogPost['title'])) {
        $lastIndex = count($breadcrumbs) - 1;
        if ($lastIndex >= 0) {
            $breadcrumbs[$lastIndex]['label'] = $blogPost['title'];
        }
    }
    
    return $breadcrumbs;
}
