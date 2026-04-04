<!DOCTYPE html>
<html lang="<?= $lang ?? 'sr' ?>" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml" sizes="any">
    
    <?php
    $isDebug = Env::get('APP_DEBUG', false) === true || Env::get('APP_DEBUG', 'false') === 'true' || Env::get('APP_DEBUG', '0') === '1';
    // Vite HMR only when debug AND browser host is loopback, or VITE_DEV_SERVER_ORIGIN is set.
    // Otherwise use /dist/* so production (e.g. aleksandar.de) never runs unstyled when APP_DEBUG leaks true.
    $httpHost = $_SERVER['HTTP_HOST'] ?? parse_url((string) Env::get('APP_URL', 'https://localhost'), PHP_URL_HOST) ?: 'localhost';
    $requestScheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        ? 'https'
        : (parse_url((string) Env::get('APP_URL', 'https://localhost'), PHP_URL_SCHEME) ?: 'https');
    $siteOrigin = $requestScheme . '://' . $httpHost;
    $isLoopbackHost = (bool) preg_match('#^(localhost|127\.0\.0\.1|\[::1\])(:\d+)?$#i', $httpHost);
    $configuredVite = trim((string) Env::get('VITE_DEV_SERVER_ORIGIN', ''));
    $viteOrigin = '';
    if ($configuredVite !== '') {
        $viteOrigin = rtrim($configuredVite, '/');
    } elseif ($isLoopbackHost && $isDebug) {
        $viteOrigin = 'http://localhost:5173';
    }
    $useViteDev = $isDebug && $viteOrigin !== '';
    $jsPath = $useViteDev ? "{$viteOrigin}/app/resources/js/app.js" : '/dist/app.js';

    // SEO Meta Tags
    $pageTitle = Env::get('BRAND_TAGLINE', 'Dark Protocol') . ' | ' . Env::get('BRAND_NAME', 'aleksandar.pro');
    $metaDescription = '';
    $metaKeywords = '';
    $ogImage = '';
    $ogUrl = '';
    
    // Check if we have page/blogPost data with SEO info
    if (isset($page) && is_array($page)) {
        if (!empty($page['meta_title'])) {
            $pageTitle = htmlspecialchars($page['meta_title']) . ' | ' . Env::get('BRAND_NAME', 'aleksandar.pro');
        } elseif (!empty($page['title'])) {
            $pageTitle = htmlspecialchars($page['title']) . ' | ' . Env::get('BRAND_NAME', 'aleksandar.pro');
        }
        
        if (!empty($page['meta_description'])) {
            $metaDescription = htmlspecialchars($page['meta_description']);
        }
        
        if (!empty($page['meta_keywords'])) {
            $metaKeywords = htmlspecialchars($page['meta_keywords']);
        }
    }
    
    // Check if we have blogPost data
    if (isset($blogPost) && is_array($blogPost)) {
        if (!empty($blogPost['meta_title'])) {
            $pageTitle = htmlspecialchars($blogPost['meta_title']) . ' | ' . Env::get('BRAND_NAME', 'aleksandar.pro');
        } elseif (!empty($blogPost['title'])) {
            $pageTitle = htmlspecialchars($blogPost['title']) . ' | ' . Env::get('BRAND_NAME', 'aleksandar.pro');
        }
        
        if (!empty($blogPost['meta_description'])) {
            $metaDescription = htmlspecialchars($blogPost['meta_description']);
        } elseif (!empty($blogPost['excerpt'])) {
            $metaDescription = htmlspecialchars(strip_tags($blogPost['excerpt']));
        }
        
        if (!empty($blogPost['meta_keywords'])) {
            $metaKeywords = htmlspecialchars($blogPost['meta_keywords']);
        }
        
        if (!empty($blogPost['featured_image'])) {
            $ogImage = htmlspecialchars($blogPost['featured_image']);
        }
        
        if (!empty($blogPost['url'])) {
            global $router;
            $currentLang = $router->lang ?? 'sr';
            $ogUrl = $siteOrigin . htmlspecialchars($blogPost['url']);
        }
    }
    
    // Build canonical URL
    global $router;
    $currentLang = $router->lang ?? 'sr';
    $canonicalUrl = $siteOrigin . localized_path($router->getUri() ?? '/', $currentLang);

    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $isDashboardRoute = (bool) preg_match('#^/(?:[a-z]{2}/)?dashboard(?:/|$)#', $requestPath);
    ?>
    
    <title><?= $pageTitle ?></title>
    
    <?php if (!empty($metaDescription)): ?>
    <meta name="description" content="<?= $metaDescription ?>">
    <?php endif; ?>
    
    <?php if (!empty($metaKeywords)): ?>
    <meta name="keywords" content="<?= $metaKeywords ?>">
    <?php endif; ?>
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= $ogUrl ?: $canonicalUrl ?>">
    <meta property="og:title" content="<?= $pageTitle ?>">
    <?php if (!empty($metaDescription)): ?>
    <meta property="og:description" content="<?= $metaDescription ?>">
    <?php endif; ?>
    <?php if (!empty($ogImage)): ?>
    <meta property="og:image" content="<?= $ogImage ?>">
    <?php endif; ?>
    
    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?= $ogUrl ?: $canonicalUrl ?>">
    <meta property="twitter:title" content="<?= $pageTitle ?>">
    <?php if (!empty($metaDescription)): ?>
    <meta property="twitter:description" content="<?= $metaDescription ?>">
    <?php endif; ?>
    <?php if (!empty($ogImage)): ?>
    <meta property="twitter:image" content="<?= $ogImage ?>">
    <?php endif; ?>
    
    <!-- Canonical URL -->
    <link rel="canonical" href="<?= $canonicalUrl ?>">
    
    <?= CSRF::meta() ?>

    <?php $v = '1.0.' . time(); ?>

    <!-- Assets -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flag-icons@7.5.0/css/flag-icons.min.css">
    
    <?php if (!$useViteDev): ?>
    <link rel="stylesheet" href="/dist/app.css?v=<?= $v ?>">
    <?php endif; ?>

    <!-- Inline CSP Nonce for any critical scripts -->
    <script nonce="<?= function_exists('csp_nonce') ? csp_nonce() : '' ?>">
        window.appConfig = {
            url: '<?= htmlspecialchars($siteOrigin, ENT_QUOTES, 'UTF-8') ?>',
            lang: '<?= $currentLang ?>'
        };
    </script>
</head>
<body class="h-full bg-slate-950 text-slate-200 selection:bg-indigo-500/30">
    <div class="min-h-full flex flex-col">
        <!-- Navigation -->
        <nav class="sticky top-0 z-50 bg-slate-950/80 backdrop-blur-md border-b border-slate-800">
            <div class="<?= $isDashboardRoute ? 'max-w-none px-6' : 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8' ?>">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <a href="<?= htmlspecialchars(localized_path('/', $currentLang)) ?>" class="flex items-center space-x-3 group">
                            <div class="w-10 h-10 bg-gradient-to-br from-indigo-600 to-violet-600 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-500/20 group-hover:scale-110 transition-transform">
                                <ion-icon name="code-working" class="text-2xl text-white"></ion-icon>
                            </div>
                            <span class="text-xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-white to-slate-400 group-hover:to-indigo-400 transition-colors">
                                aleksandar.pro
                            </span>
                        </a>
                    </div>

                    <!-- Desktop Navigation -->
                    <div class="hidden md:flex items-center space-x-8">
                        <?php 
                        // Load primary navigation menu
                        if (class_exists('NavigationMenu')) {
                            $primaryMenu = NavigationMenu::getByName('Primary Navigation', $currentLang);
                            if ($primaryMenu && !empty($primaryMenu['items'])) {
                                foreach ($primaryMenu['items'] as $item) {
                                    $activeClass = ($router->getUri() === $item['url']) ? 'text-indigo-400' : 'text-slate-300 hover:text-white';
                                    echo '<a href="' . htmlspecialchars(localized_path($item['url'], $currentLang)) . '" class="text-sm font-medium transition-colors ' . $activeClass . '">' . htmlspecialchars($item['title']) . '</a>';
                                }
                            }
                        }
                        ?>
                        
                        <?php if (site_is_multilingual()): ?>
                        <!-- Language Switcher -->
                        <div class="relative group">
                            <button class="flex items-center space-x-2 text-sm font-medium text-slate-300 hover:text-white transition-colors py-2">
                                <span class="fi fi-<?= get_flag_code($currentLang) ?> rounded-sm"></span>
                                <span class="uppercase"><?= $currentLang ?></span>
                                <ion-icon name="chevron-down-outline" class="text-xs"></ion-icon>
                            </button>
                            <div class="absolute right-0 mt-0 w-64 bg-slate-900 border border-slate-800 rounded-xl shadow-2xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 py-4 transform origin-top-right group-hover:translate-y-1">
                                <div class="px-4 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wider border-b border-slate-800 mb-2">
                                    <?= __('languages', 'Languages') ?>
                                </div>
                                <div class="max-h-96 overflow-y-auto custom-scrollbar">
                                    <?php 
                                    if (class_exists('Language')) {
                                        $languages = Language::getActive();
                                        // Group languages by region/continent if needed, or just list them
                                        $regions = ['Balkan' => ['sr', 'hr', 'bg', 'ro', 'sl', 'el', 'mk'], 'Western Europe' => ['en', 'de', 'fr', 'es', 'it', 'pt', 'nl']];
                                        
                                        foreach ($regions as $regionName => $codes) {
                                            echo '<div class="px-4 py-1 text-[10px] font-bold text-indigo-500/70 uppercase">' . $regionName . '</div>';
                                            foreach ($languages as $l) {
                                                if (in_array($l->code, $codes)) {
                                                    $activeClass = ($currentLang === $l->code) ? 'bg-indigo-500/10 text-indigo-400' : 'text-slate-300 hover:bg-slate-800 hover:text-white';
                                                    echo '<a href="' . htmlspecialchars(translated_content_path($l->code, $router->getUri() ?? '/', $currentLang)) . '" class="flex items-center space-x-3 px-4 py-2.5 text-sm transition-colors ' . $activeClass . '">';
                                                    echo '<span class="fi fi-' . get_flag_code($l->code) . ' rounded-sm"></span>';
                                                    echo '<span>' . htmlspecialchars($l->name) . '</span>';
                                                    if ($currentLang === $l->code) {
                                                        echo '<ion-icon name="checkmark-outline" class="ml-auto"></ion-icon>';
                                                    }
                                                    echo '</a>';
                                                }
                                            }
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Mobile menu button -->
                    <div class="md:hidden flex items-center">
                        <button type="button" onclick="document.getElementById('mobile-menu').classList.toggle('hidden')" class="p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors">
                            <ion-icon name="menu-outline" class="text-2xl"></ion-icon>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile menu -->
            <div id="mobile-menu" class="hidden md:hidden bg-slate-900 border-b border-slate-800">
                <div class="px-4 pt-2 pb-3 space-y-1">
                    <?php 
                    if (isset($primaryMenu) && !empty($primaryMenu['items'])) {
                        foreach ($primaryMenu['items'] as $item) {
                            $activeClass = ($router->getUri() === $item['url']) ? 'bg-indigo-500/10 text-indigo-400' : 'text-slate-300 hover:bg-slate-800 hover:text-white';
                            echo '<a href="' . htmlspecialchars(localized_path($item['url'], $currentLang)) . '" class="block px-3 py-2 rounded-lg text-base font-medium ' . $activeClass . '">' . htmlspecialchars($item['title']) . '</a>';
                        }
                    }
                    ?>
                </div>
                <?php if (site_is_multilingual()): ?>
                <div class="pt-4 pb-3 border-t border-slate-800">
                    <div class="px-4 flex items-center">
                        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            <?= __('select_language', 'Select Language') ?>
                        </div>
                    </div>
                    <div class="mt-3 px-2 grid grid-cols-2 gap-2">
                        <?php 
                        if (isset($languages)) {
                            foreach ($languages as $l) {
                                if (in_array($l->code, ['sr', 'en', 'de'])) {
                                    $activeClass = ($currentLang === $l->code) ? 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20' : 'text-slate-400 border-transparent';
                                    echo '<a href="' . htmlspecialchars(translated_content_path($l->code, $router->getUri() ?? '/', $currentLang)) . '" class="flex items-center space-x-2 px-3 py-2 rounded-lg border text-sm ' . $activeClass . '">';
                                    echo '<span class="fi fi-' . get_flag_code($l->code) . ' rounded-sm"></span>';
                                    echo '<span>' . htmlspecialchars($l->name) . '</span>';
                                    echo '</a>';
                                }
                            }
                        }
                        ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </nav>

        <?php if ($isDashboardRoute): ?>
            <?php require __DIR__ . '/pages/dashboard/partials/sidebar.php'; ?>
        <?php endif; ?>

        <!-- Main Content -->
        <main
            class="<?= $isDashboardRoute ? 'max-w-none' : 'flex-grow w-full max-w-5xl mx-auto px-4 sm:px-6 lg:px-8' ?>"
            <?php if ($isDashboardRoute): ?>
                style="margin-left: 17.5rem; width: calc(100% - 19rem); min-height: calc(100vh - 4rem - 1px); padding-top: 2rem; padding-right: 1.5rem;"
            <?php endif; ?>
        >
            <?php if (isset($viewContent)) echo $viewContent; ?>
        </main>

        <?php if (!$isDashboardRoute): ?>
        <!-- Footer -->
        <footer class="bg-slate-950 border-t border-slate-800 py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-12">
                    <div class="col-span-1 md:col-span-2">
                        <a href="<?= htmlspecialchars(localized_path('/', $currentLang)) ?>" class="flex items-center space-x-3 mb-6">
                            <div class="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center">
                                <ion-icon name="code-working" class="text-2xl text-white"></ion-icon>
                            </div>
                            <span class="text-xl font-bold text-white">aleksandar.pro</span>
                        </a>
                        <p class="text-slate-400 max-w-sm leading-relaxed">
                            Custom-built ecosystem focusing on high-performance architecture, security, and multilingual content delivery.
                        </p>
                    </div>
                    <div>
                        <h3 class="text-white font-semibold mb-6"><?= __('quick_links', 'Quick Links') ?></h3>
                        <ul class="space-y-4">
                            <li><a href="<?= htmlspecialchars(localized_path('/projekti', $currentLang)) ?>" class="text-slate-400 hover:text-indigo-400 transition-colors"><?= __('projects', 'Projects') ?></a></li>
                            <li><a href="<?= htmlspecialchars(localized_path('/novosti', $currentLang)) ?>" class="text-slate-400 hover:text-indigo-400 transition-colors"><?= __('news', 'News') ?></a></li>
                            <li><a href="<?= htmlspecialchars(localized_path('/o-autoru', $currentLang)) ?>" class="text-slate-400 hover:text-indigo-400 transition-colors"><?= __('about_me', 'About Me') ?></a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-white font-semibold mb-6"><?= __('legal', 'Legal') ?></h3>
                        <ul class="space-y-4">
                            <li><a href="<?= htmlspecialchars(localized_path('/politika-privatnosti', $currentLang)) ?>" class="text-slate-400 hover:text-indigo-400 transition-colors"><?= __('privacy_policy', 'Privacy Policy') ?></a></li>
                            <li><a href="<?= htmlspecialchars(localized_path('/uslovi-koriscenja', $currentLang)) ?>" class="text-slate-400 hover:text-indigo-400 transition-colors"><?= __('terms_of_service', 'Terms of Use') ?></a></li>
                        </ul>
                    </div>
                </div>
                <div class="mt-12 pt-8 border-t border-slate-900 flex flex-col md:flex-row justify-between items-center">
                    <p class="text-slate-500 text-sm">
                        &copy; <?= date('Y') ?> aleksandar.pro. All rights reserved.
                    </p>
                    <div class="flex space-x-6 mt-6 md:mt-0">
                        <a href="https://github.com" class="text-slate-500 hover:text-white transition-colors text-xl"><ion-icon name="logo-github"></ion-icon></a>
                        <a href="https://linkedin.com" class="text-slate-500 hover:text-white transition-colors text-xl"><ion-icon name="logo-linkedin"></ion-icon></a>
                    </div>
                </div>
            </div>
        </footer>
        <?php endif; ?>
    </div>

    <!-- Scripts -->
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <script type="module" src="<?= $useViteDev ? $jsPath : $jsPath . '?v=' . $v ?>"></script>
</body>
</html>
