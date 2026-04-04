<?php
/**
 * Dashboard Sidebar Navigation
 * 
 * Provides navigation for dashboard sections
 */

// Get current user for permission checks
// Note: User, Role, and Permission classes should already be loaded in public/index.php
$currentUser = null;
if (isset($_SESSION['user_id']) && class_exists('User')) {
    try {
        $currentUser = User::find($_SESSION['user_id']);
    } catch (Exception $e) {
        error_log("Sidebar: Error loading user: " . $e->getMessage());
        $currentUser = null;
    } catch (Error $e) {
        error_log("Sidebar: Fatal error loading user: " . $e->getMessage());
        $currentUser = null;
    }
}

// Helper function to check if user can see menu item
function canSeeMenuItem($item, $currentUser) {
    if (!$currentUser) {
        return false;
    }
    
    // Super admin can see everything
    if ($currentUser->isSuperAdmin()) {
        return true;
    }
    
    // Check permission requirement for menu item
    $requiredPermission = $item['permission'] ?? null;
    if ($requiredPermission) {
        return $currentUser->hasPermission($requiredPermission);
    }
    
    // Default: show if user has dashboard access
    return $currentUser->hasPermission('system.dashboard');
}

// Helper function to check if any child is visible
function hasVisibleChildren($item, $currentUser) {
    if (!isset($item['children'])) {
        return false;
    }
    
    foreach ($item['children'] as $child) {
        if (canSeeMenuItem($child, $currentUser)) {
            return true;
        }
    }
    
    return false;
}

// Get current route to highlight active item
global $router;
$currentPath = $_SERVER['REQUEST_URI'] ?? '';
$currentPath = parse_url($currentPath, PHP_URL_PATH);

// Extract base path (e.g., /sr/dashboard or /sr/dashboard/users)
$pathParts = explode('/', trim($currentPath, '/'));
$baseSection = $pathParts[count($pathParts) - 1] ?? 'index';
if (count($pathParts) > 2 && $pathParts[1] === 'dashboard') {
    $baseSection = $pathParts[2] ?? 'index';
}

$navItems = [
    [
        'label' => 'Dashboard',
        'route' => 'dashboard',
        'url' => '/dashboard',
        'icon' => 'grid-outline',
        'section' => 'index',
        'permission' => 'system.dashboard'
    ],
    [
        'label' => 'Pages',
        'url' => '#',
        'icon' => 'document-text-outline',
        'section' => 'pages',
        'permission' => 'pages.view',
        'hasChildren' => true,
        'children' => [
            [
                'label' => 'All Pages',
                'route' => 'dashboard.pages',
                'url' => '/dashboard/pages',
                'icon' => 'document-outline',
                'section' => 'pages.list',
                'permission' => 'pages.view'
            ],
            [
                'label' => 'Menus',
                'route' => 'dashboard.navigation-menus',
                'url' => '/dashboard/navigation-menus',
                'icon' => 'menu-outline',
                'section' => 'pages.menus',
                'permission' => 'pages.view'
            ],
        ]
    ],
    [
        'label' => 'Blog',
        'url' => '#',
        'icon' => 'book-outline',
        'section' => 'blog',
        'permission' => 'blog.view',
        'hasChildren' => true,
        'children' => [
            [
                'label' => 'Posts',
                'route' => 'dashboard.blog.posts',
                'url' => '/dashboard/blog/posts',
                'icon' => 'newspaper-outline',
                'section' => 'blog.posts',
                'permission' => 'blog.view'
            ],
            [
                'label' => 'Categories',
                'route' => 'dashboard.blog.categories',
                'url' => '/dashboard/blog/categories',
                'icon' => 'folder-outline',
                'section' => 'blog.categories',
                'permission' => 'blog.manage-categories'
            ],
        ]
    ],
    [
        'label' => 'Users',
        'url' => '#',
        'icon' => 'people-outline',
        'section' => 'users',
        'permission' => 'users.view',
        'hasChildren' => true,
        'children' => [
            [
                'label' => 'All Users',
                'route' => 'dashboard.users',
                'url' => '/dashboard/users',
                'icon' => 'person-outline',
                'section' => 'users.list',
                'permission' => 'users.view'
            ],
            [
                'label' => 'Roles',
                'route' => 'dashboard.users.roles',
                'url' => '/dashboard/users/roles',
                'icon' => 'shield-outline',
                'section' => 'users.roles',
                'permission' => 'users.manage-roles'
            ],
            [
                'label' => 'Permissions',
                'route' => 'dashboard.users.permissions',
                'url' => '/dashboard/users/permissions',
                'icon' => 'key-outline',
                'section' => 'users.permissions',
                'permission' => 'users.manage-permissions'
            ],
        ]
    ],
    [
        'label' => 'World',
        'url' => '#',
        'icon' => 'globe-outline',
        'section' => 'world',
        'permission' => 'system.languages',
        'hasChildren' => true,
        'children' => [
            [
                'label' => 'Languages',
                'route' => 'dashboard.languages',
                'url' => '/dashboard/languages',
                'icon' => 'language-outline',
                'section' => 'world.languages',
                'permission' => 'system.languages'
            ],
            [
                'label' => 'Continents',
                'route' => 'dashboard.continents',
                'url' => '/dashboard/continents',
                'icon' => 'map-outline',
                'section' => 'world.continents',
                'permission' => 'system.languages'
            ],
            [
                'label' => 'Regions',
                'route' => 'dashboard.regions',
                'url' => '/dashboard/regions',
                'icon' => 'location-outline',
                'section' => 'world.regions',
                'permission' => 'system.languages'
            ],
        ]
    ],
    [
        'label' => 'Database Management',
        'route' => 'dashboard.database',
        'url' => '/dashboard/database',
        'icon' => 'server-outline',
        'section' => 'database',
        'permission' => 'system.database'
    ],
    [
        'label' => 'IP Tracking',
        'route' => 'dashboard.ip-tracking',
        'url' => '/dashboard/ip-tracking',
        'icon' => 'map-outline',
        'section' => 'ip-tracking',
        'permission' => 'system.dashboard'
    ],
    [
        'label' => 'Kontakt Poruke',
        'route' => 'dashboard.contact.messages',
        'url' => '/dashboard/contact-messages',
        'icon' => 'mail-outline',
        'section' => 'contact-messages',
        'permission' => 'contact.view'
    ],
];
?>

<aside
    class="fixed left-0 w-64 bg-slate-900/50 backdrop-blur-sm border-t border-r border-slate-800/50 flex-shrink-0 z-40 flex flex-col"
    style="top: 4rem; bottom: 0;"
>
    <!-- Navigation - Scrollable -->
    <div class="flex-1 overflow-y-auto p-4 pb-6">
        <nav class="space-y-1">
            <?php foreach ($navItems as $item): ?>
                <?php
                // Skip menu item if user doesn't have permission
                if (!canSeeMenuItem($item, $currentUser)) {
                    continue;
                }
                ?>
                <?php
                $hasChildren = isset($item['hasChildren']) && $item['hasChildren'];
                $isBlogActive = false;
                $isUsersActive = false;
                $isPagesActive = false;
                $isWorldActive = false;
                $isItemActive = false;
                
                if ($item['section'] === 'index') {
                    // Dashboard home is active when we're on /dashboard and not on other sections
                    $isItemActive = ($baseSection === 'dashboard' || $baseSection === 'index') && 
                                !in_array($pathParts[2] ?? '', ['users', 'database', 'pages', 'navigation-menus', 'blog']);
                } elseif ($item['section'] === 'pages') {
                    // Pages parent is active if any pages submenu is active
                    $isPagesActive = (($pathParts[2] ?? '') === 'pages' || ($pathParts[2] ?? '') === 'navigation-menus');
                    $isItemActive = $isPagesActive;
                } elseif ($item['section'] === 'pages.list') {
                    $isItemActive = (($pathParts[2] ?? '') === 'pages');
                } elseif ($item['section'] === 'pages.menus') {
                    $isItemActive = (($pathParts[2] ?? '') === 'navigation-menus');
                } elseif ($item['section'] === 'blog') {
                    // Blog parent is active if any blog submenu is active
                    $isBlogActive = (($pathParts[2] ?? '') === 'blog');
                    $isItemActive = $isBlogActive;
                } elseif ($item['section'] === 'blog.posts') {
                    $isItemActive = (($pathParts[2] ?? '') === 'blog' && ($pathParts[3] ?? '') === 'posts');
                } elseif ($item['section'] === 'blog.categories') {
                    $isItemActive = (($pathParts[2] ?? '') === 'blog' && ($pathParts[3] ?? '') === 'categories');
                } elseif ($item['section'] === 'users') {
                    // User Management parent is active if any user submenu is active
                    $isUsersActive = (($pathParts[2] ?? '') === 'users');
                    $isItemActive = $isUsersActive;
                } elseif ($item['section'] === 'users.list') {
                    $isItemActive = (($pathParts[2] ?? '') === 'users' && !in_array($pathParts[3] ?? '', ['roles', 'permissions', 'create', 'edit']));
                } elseif ($item['section'] === 'users.roles') {
                    $isItemActive = (($pathParts[2] ?? '') === 'users' && ($pathParts[3] ?? '') === 'roles');
                } elseif ($item['section'] === 'users.permissions') {
                    $isItemActive = (($pathParts[2] ?? '') === 'users' && ($pathParts[3] ?? '') === 'permissions');
                } elseif ($item['section'] === 'world') {
                    // World parent is active if any world submenu is active
                    $isWorldActive = in_array($pathParts[2] ?? '', ['languages', 'continents', 'regions']);
                    $isItemActive = $isWorldActive;
                } elseif ($item['section'] === 'world.languages') {
                    $isItemActive = (($pathParts[2] ?? '') === 'languages');
                } elseif ($item['section'] === 'world.continents') {
                    $isItemActive = (($pathParts[2] ?? '') === 'continents');
                } elseif ($item['section'] === 'world.regions') {
                    $isItemActive = (($pathParts[2] ?? '') === 'regions');
                } elseif ($item['section'] === 'database') {
                    $isItemActive = ($baseSection === 'database' || $baseSection === 'tables');
                } elseif ($item['section'] === 'ip-tracking') {
                    $isItemActive = (($pathParts[2] ?? '') === 'ip-tracking');
                } elseif ($item['section'] === 'contact-messages') {
                    $isItemActive = (($pathParts[2] ?? '') === 'contact-messages');
                }
                
                if ($hasChildren):
                    // Skip parent if no children are visible
                    if (!hasVisibleChildren($item, $currentUser)) {
                        continue;
                    }
                    
                    // Parent item with children (Blog, User Management)
                    $parentActiveClass = $isItemActive 
                        ? 'bg-theme-primary/20 text-theme-primary border-l-4 border-theme-primary' 
                        : 'text-slate-300 hover:bg-slate-800/50 hover:text-white border-l-4 border-transparent';
                    
                    // Check if any child is active to determine if submenu should be open
                    $hasActiveChild = false;
                    $menuId = 'menu-' . str_replace(['.', ' '], '-', $item['section']);
                    $isParentActive = $isBlogActive || $isUsersActive || $isPagesActive || $isWorldActive;
                    
                    if (isset($item['children'])) {
                        foreach ($item['children'] as $child) {
                            // Skip if child is not visible
                            if (!canSeeMenuItem($child, $currentUser)) {
                                continue;
                            }
                            
                            $childSection = $child['section'] ?? '';
                            
                            // Blog children
                            if ($childSection === 'blog.posts' && (($pathParts[2] ?? '') === 'blog' && ($pathParts[3] ?? '') === 'posts')) {
                                $hasActiveChild = true;
                                break;
                            } elseif ($childSection === 'blog.categories' && (($pathParts[2] ?? '') === 'blog' && ($pathParts[3] ?? '') === 'categories')) {
                                $hasActiveChild = true;
                                break;
                            }
                            
                            // User Management children
                            elseif ($childSection === 'users.list' && (($pathParts[2] ?? '') === 'users' && !in_array($pathParts[3] ?? '', ['roles', 'permissions', 'create', 'edit']))) {
                                $hasActiveChild = true;
                                break;
                            } elseif ($childSection === 'users.roles' && (($pathParts[2] ?? '') === 'users' && ($pathParts[3] ?? '') === 'roles')) {
                                $hasActiveChild = true;
                                break;
                            } elseif ($childSection === 'users.permissions' && (($pathParts[2] ?? '') === 'users' && ($pathParts[3] ?? '') === 'permissions')) {
                                $hasActiveChild = true;
                                break;
                            }
                            
                            // Pages children
                            elseif ($childSection === 'pages.list' && (($pathParts[2] ?? '') === 'pages')) {
                                $hasActiveChild = true;
                                break;
                            } elseif ($childSection === 'pages.menus' && (($pathParts[2] ?? '') === 'navigation-menus')) {
                                $hasActiveChild = true;
                                break;
                            }
                            // World children
                            elseif ($childSection === 'world.languages' && (($pathParts[2] ?? '') === 'languages')) {
                                $hasActiveChild = true;
                                break;
                            } elseif ($childSection === 'world.continents' && (($pathParts[2] ?? '') === 'continents')) {
                                $hasActiveChild = true;
                                break;
                            } elseif ($childSection === 'world.regions' && (($pathParts[2] ?? '') === 'regions')) {
                                $hasActiveChild = true;
                                break;
                            }
                        }
                    }
                    $submenuOpen = $hasActiveChild || $isParentActive;
                ?>
                    <div class="<?= $menuId ?>-item">
                        <button type="button" 
                                class="w-full flex items-center justify-between gap-3 px-4 py-3 rounded-lg transition-colors <?= $parentActiveClass ?> <?= $menuId ?>-toggle"
                                data-menu-id="<?= e($menuId) ?>">
                            <div class="flex items-center gap-3">
                                <ion-icon name="<?= $item['icon'] ?>" class="text-xl text-theme-primary"></ion-icon>
                                <span class="font-medium"><?= e($item['label']) ?></span>
                            </div>
                            <ion-icon name="chevron-<?= $submenuOpen ? 'up' : 'down' ?>-outline" class="text-lg text-theme-primary <?= $menuId ?>-chevron transition-transform"></ion-icon>
                        </button>
                        
                        <div class="<?= $menuId ?>-submenu overflow-hidden transition-all duration-300 ease-in-out <?= $submenuOpen ? 'opacity-100' : 'max-h-0 opacity-0' ?>" <?= $submenuOpen ? 'data-init-open="true"' : 'style="max-height: 0;"' ?>>
                            <div class="pl-4 pt-1 space-y-1">
                                <?php foreach ($item['children'] as $child): ?>
                                    <?php
                                    // Skip child menu item if user doesn't have permission
                                    if (!canSeeMenuItem($child, $currentUser)) {
                                        continue;
                                    }
                                    ?>
                                    <?php
                                    $childIsActive = false;
                                    $childSection = $child['section'] ?? '';
                                    
                                    // Blog children
                                    if ($childSection === 'blog.posts') {
                                        $childIsActive = (($pathParts[2] ?? '') === 'blog' && ($pathParts[3] ?? '') === 'posts');
                                    } elseif ($childSection === 'blog.categories') {
                                        $childIsActive = (($pathParts[2] ?? '') === 'blog' && ($pathParts[3] ?? '') === 'categories');
                                    }
                                    // User Management children
                                    elseif ($childSection === 'users.list') {
                                        $childIsActive = (($pathParts[2] ?? '') === 'users' && !in_array($pathParts[3] ?? '', ['roles', 'permissions', 'create', 'edit']));
                                    } elseif ($childSection === 'users.roles') {
                                        $childIsActive = (($pathParts[2] ?? '') === 'users' && ($pathParts[3] ?? '') === 'roles');
                                    } elseif ($childSection === 'users.permissions') {
                                        $childIsActive = (($pathParts[2] ?? '') === 'users' && ($pathParts[3] ?? '') === 'permissions');
                                    }
                                    // Pages children
                                    elseif ($childSection === 'pages.list') {
                                        $childIsActive = (($pathParts[2] ?? '') === 'pages');
                                    } elseif ($childSection === 'pages.menus') {
                                        $childIsActive = (($pathParts[2] ?? '') === 'navigation-menus');
                                    }
                                    // World children
                                    elseif ($childSection === 'world.languages') {
                                        $childIsActive = (($pathParts[2] ?? '') === 'languages');
                                    } elseif ($childSection === 'world.continents') {
                                        $childIsActive = (($pathParts[2] ?? '') === 'continents');
                                    } elseif ($childSection === 'world.regions') {
                                        $childIsActive = (($pathParts[2] ?? '') === 'regions');
                                    }
                                    
                                    $childActiveClass = $childIsActive 
                                        ? 'bg-theme-primary/20 text-theme-primary' 
                                        : 'text-slate-300 hover:bg-slate-800/50 hover:text-white';
                                    
                                    $childRouteUrl = $child['url'] ?? route($child['route'] ?? '');
                                    if (isset($child['url'])) {
                                        global $router;
                                        $lang = $router->lang ?? 'sr';
                                        $childRouteUrl = '/' . $lang . $childRouteUrl;
                                    }
                                    ?>
                                    <a href="<?= htmlspecialchars($childRouteUrl) ?>" 
                                       class="flex items-center gap-3 px-4 py-2 rounded-lg transition-colors <?= $childActiveClass ?>">
                                        <ion-icon name="<?= $child['icon'] ?? 'circle-outline' ?>" class="text-lg text-theme-primary"></ion-icon>
                                        <span class="text-sm font-medium"><?= e($child['label']) ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php else:
                    // Regular item without children
                    $activeClass = $isItemActive 
                        ? 'bg-theme-primary/20 text-theme-primary border-l-4 border-theme-primary' 
                        : 'text-slate-300 hover:bg-slate-800/50 hover:text-white border-l-4 border-transparent';
                    
                    $routeUrl = $item['url'] ?? route($item['route'] ?? '');
                    if (isset($item['url']) && $item['url'] !== '#') {
                        global $router;
                        $lang = $router->lang ?? 'sr';
                        $routeUrl = '/' . $lang . $routeUrl;
                    }
                ?>
                    <a href="<?= htmlspecialchars($routeUrl) ?>" 
                       class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?= $activeClass ?>">
                        <ion-icon name="<?= $item['icon'] ?>" class="text-xl text-theme-primary"></ion-icon>
                        <span class="font-medium"><?= e($item['label']) ?></span>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
    </div>
</aside>

<script nonce="<?= csp_nonce() ?>">
document.addEventListener('DOMContentLoaded', function() {
    // Initialize sidebar submenu toggles with accordion behavior
    const menuToggles = document.querySelectorAll('[data-menu-id]');
    
    // Initialize open submenus on page load
    document.querySelectorAll('[data-init-open="true"]').forEach(submenu => {
        // Set natural height for initially open submenus
        submenu.style.maxHeight = submenu.scrollHeight + 'px';
    });
    
    // Helper function to close a submenu
    function closeSubmenu(menuId) {
        const submenu = document.querySelector('.' + menuId + '-submenu');
        const chevron = document.querySelector('.' + menuId + '-chevron');
        
        if (!submenu || !chevron) return;
        
        // Only close if it's open
        if (!submenu.classList.contains('max-h-0')) {
            // Get current height for smooth animation
            const currentHeight = submenu.scrollHeight;
            submenu.style.maxHeight = currentHeight + 'px';
            
            // Force reflow to ensure transition works
            void submenu.offsetHeight;
            
            // Animate close
            requestAnimationFrame(() => {
                submenu.style.maxHeight = '0px';
                submenu.classList.remove('opacity-100');
                submenu.classList.add('max-h-0', 'opacity-0');
                chevron.setAttribute('name', 'chevron-down-outline');
            });
        }
    }
    
    // Helper function to open a submenu
    function openSubmenu(menuId) {
        const submenu = document.querySelector('.' + menuId + '-submenu');
        const chevron = document.querySelector('.' + menuId + '-chevron');
        
        if (!submenu || !chevron) return;
        
        // Remove max-h-0 class and set initial state
        submenu.classList.remove('max-h-0');
        submenu.style.maxHeight = '0px';
        submenu.classList.remove('opacity-0');
        submenu.classList.add('opacity-100');
        
        // Force reflow
        void submenu.offsetHeight;
        
        // Get actual height and animate open
        const targetHeight = submenu.scrollHeight;
        
        requestAnimationFrame(() => {
            submenu.style.maxHeight = targetHeight + 'px';
            chevron.setAttribute('name', 'chevron-up-outline');
            
            // After animation completes, allow natural height
            setTimeout(() => {
                if (!submenu.classList.contains('max-h-0')) {
                    submenu.style.maxHeight = 'none';
                }
            }, 300);
        });
    }
    
    menuToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const menuId = this.getAttribute('data-menu-id');
            const submenu = document.querySelector('.' + menuId + '-submenu');
            
            if (!submenu) return;
            
            const isCurrentlyOpen = !submenu.classList.contains('max-h-0');
            
            // Accordion behavior: close all other submenus first
            if (!isCurrentlyOpen) {
                // Close all other open submenus
                menuToggles.forEach(otherToggle => {
                    const otherMenuId = otherToggle.getAttribute('data-menu-id');
                    if (otherMenuId !== menuId) {
                        closeSubmenu(otherMenuId);
                    }
                });
                
                // Small delay for smoother visual transition
                setTimeout(() => {
                    openSubmenu(menuId);
                }, 100);
            } else {
                // Close this submenu
                closeSubmenu(menuId);
            }
        });
    });
    
    // Handle window resize - recalculate open submenu heights
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            menuToggles.forEach(toggle => {
                const menuId = toggle.getAttribute('data-menu-id');
                const submenu = document.querySelector('.' + menuId + '-submenu');
                
                // Recalculate height if submenu is open
                if (submenu && !submenu.classList.contains('max-h-0')) {
                    const currentMaxHeight = submenu.style.maxHeight;
                    // Only update if not set to 'none'
                    if (currentMaxHeight && currentMaxHeight !== 'none') {
                        submenu.style.maxHeight = 'none';
                        const newHeight = submenu.scrollHeight;
                        submenu.style.maxHeight = newHeight + 'px';
                        
                        // Reset to none after transition
                        setTimeout(() => {
                            if (!submenu.classList.contains('max-h-0')) {
                                submenu.style.maxHeight = 'none';
                            }
                        }, 300);
                    }
                }
            });
        }, 250);
    });
});
</script>
