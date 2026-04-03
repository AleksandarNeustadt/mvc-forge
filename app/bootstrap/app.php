<?php

if (!function_exists('__')) {
    function __($key, $default = null)
    {
        return Translator::get($key, $default);
    }
}

if (!function_exists('ap_bootstrap_http_application')) {
    function ap_bootstrap_http_application(string $appPath, string $publicPath): void
    {
        ap_configure_bootstrap_logging($appPath);

        ap_require_composer_autoload($appPath);
        ap_register_legacy_namespace_aliases($appPath);

        ap_load_environment($appPath);
        ap_configure_runtime_error_reporting();

        ExceptionHandler::register();
        PermissionRegistry::loadDefaults();

        $container = Container::getInstance();
        ap_register_core_services($container);

        ap_configure_session();
        session_start();

        $request = Request::capture();
        $GLOBALS['request'] = $request;

        $routeCollection = new RouteCollection();
        Route::setCollection($routeCollection);
        $GLOBALS['routeCollection'] = $routeCollection;

        $container->instance('request', $request);
        $container->instance(Request::class, $request);
        $container->instance('routes', $routeCollection);
        $container->instance(RouteCollection::class, $routeCollection);

        require_once $appPath . '/routes/web.php';
        require_once $appPath . '/routes/dashboard-api.php';
        require_once $appPath . '/routes/api.php';

        $router = new Router($routeCollection);
        $GLOBALS['router'] = $router;

        $container->instance('router', $router);
        $container->instance(Router::class, $router);

        $router->registerMiddleware('auth', AuthMiddleware::class);
        $router->registerMiddleware('cors', CorsMiddleware::class);
        $router->registerMiddleware('security', SecurityHeadersMiddleware::class);
        $router->registerMiddleware('csrf', CsrfMiddleware::class);
        $router->registerMiddleware('ratelimit', RateLimitMiddleware::class);

        Translator::init($router->lang);

        $container->instance('translator', new Translator());

        ap_serve_favicon_if_requested($publicPath);

        \App\Core\logging\Logger::debug('Dispatching HTTP request');
        $router->dispatch();
    }
}

if (!function_exists('ap_bootstrap_cli_application')) {
    function ap_bootstrap_cli_application(string $appPath): Container
    {
        ap_configure_bootstrap_logging($appPath);

        ap_require_composer_autoload($appPath);
        ap_register_legacy_namespace_aliases($appPath);

        ap_load_environment($appPath);

        if (!isset($_SERVER['REQUEST_URI'])) {
            $_SERVER['REQUEST_URI'] = '/sr';
        }

        if (!isset($_SERVER['REQUEST_METHOD'])) {
            $_SERVER['REQUEST_METHOD'] = 'GET';
        }

        ExceptionHandler::register();
        PermissionRegistry::loadDefaults();

        $container = Container::getInstance();
        ap_register_core_services($container);

        $request = Request::capture();
        $GLOBALS['request'] = $request;

        $routeCollection = new RouteCollection();
        Route::setCollection($routeCollection);
        $GLOBALS['routeCollection'] = $routeCollection;

        $container->instance('request', $request);
        $container->instance(Request::class, $request);
        $container->instance('routes', $routeCollection);
        $container->instance(RouteCollection::class, $routeCollection);

        require_once $appPath . '/routes/web.php';
        require_once $appPath . '/routes/dashboard-api.php';
        require_once $appPath . '/routes/api.php';

        $router = new Router($routeCollection);
        $GLOBALS['router'] = $router;

        $container->instance('router', $router);
        $container->instance(Router::class, $router);

        Translator::init($router->lang);
        $container->instance('translator', new Translator());

        return $container;
    }
}

if (!function_exists('ap_register_legacy_namespace_aliases')) {
    function ap_register_legacy_namespace_aliases(string $appPath): void
    {
        static $registered = false;
        if ($registered) {
            return;
        }

        $legacyClassMap = ap_build_legacy_class_map($appPath);

        spl_autoload_register(static function (string $class) use ($legacyClassMap): void {
            $prefixes = [
                'App\\Core\\',
                'App\\Models\\',
                'App\\Controllers\\',
            ];

            foreach ($prefixes as $prefix) {
                if (!str_starts_with($class, $prefix)) {
                    continue;
                }

                $legacyClass = basename(str_replace('\\', '/', $class));
                if (class_exists($legacyClass) || interface_exists($legacyClass) || trait_exists($legacyClass)) {
                    if (!class_exists($class, false) && !interface_exists($class, false) && !trait_exists($class, false)) {
                        class_alias($legacyClass, $class);
                    }
                }

                return;
            }

            if (!str_contains($class, '\\') && isset($legacyClassMap[$class])) {
                $namespacedClass = $legacyClassMap[$class];
                if (class_exists($namespacedClass) || interface_exists($namespacedClass) || trait_exists($namespacedClass)) {
                    if (!class_exists($class, false) && !interface_exists($class, false) && !trait_exists($class, false)) {
                        class_alias($namespacedClass, $class);
                    }
                }
            }
        });

        $registered = true;
    }
}

if (!function_exists('ap_build_legacy_class_map')) {
    function ap_build_legacy_class_map(string $appPath): array
    {
        $scanRoots = [
            $appPath . '/core' => 'App\\Core',
            $appPath . '/mvc/models' => 'App\\Models',
            $appPath . '/mvc/controllers' => 'App\\Controllers',
        ];

        $map = [];

        foreach ($scanRoots as $scanRoot => $namespaceRoot) {
            if (!is_dir($scanRoot)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($scanRoot, FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $fileInfo) {
                if ($fileInfo->getExtension() !== 'php') {
                    continue;
                }

                $filePath = $fileInfo->getPathname();
                $code = file_get_contents($filePath);
                if ($code === false) {
                    continue;
                }

                $declaredNamespace = $namespaceRoot;
                $relativeDir = trim(str_replace('\\', '/', dirname(substr($filePath, strlen($scanRoot)))), '/.');
                if ($namespaceRoot === 'App\\Core' && $relativeDir !== '') {
                    $declaredNamespace .= '\\' . str_replace('/', '\\', $relativeDir);
                }

                $tokens = token_get_all($code);
                $tokenCount = count($tokens);
                for ($i = 0; $i < $tokenCount; $i++) {
                    $token = $tokens[$i];
                    if (!is_array($token) || !in_array($token[0], [T_CLASS, T_INTERFACE, T_TRAIT], true)) {
                        continue;
                    }

                    $prevIndex = $i - 1;
                    while (
                        $prevIndex >= 0
                        && is_array($tokens[$prevIndex])
                        && in_array($tokens[$prevIndex][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)
                    ) {
                        $prevIndex--;
                    }

                    if ($prevIndex >= 0 && is_array($tokens[$prevIndex]) && $tokens[$prevIndex][0] === T_NEW) {
                        continue;
                    }

                    $nameIndex = $i + 1;
                    while ($nameIndex < $tokenCount && is_array($tokens[$nameIndex]) && $tokens[$nameIndex][0] === T_WHITESPACE) {
                        $nameIndex++;
                    }

                    if ($nameIndex < $tokenCount && is_array($tokens[$nameIndex]) && $tokens[$nameIndex][0] === T_STRING) {
                        $map[$tokens[$nameIndex][1]] = $declaredNamespace . '\\' . $tokens[$nameIndex][1];
                    }
                }
            }
        }

        return $map;
    }
}

if (!function_exists('ap_require_composer_autoload')) {
    function ap_require_composer_autoload(string $appPath): void
    {
        $autoloadPath = $appPath . '/vendor/autoload.php';
        if (file_exists($autoloadPath)) {
            require_once $autoloadPath;
            return;
        }

        $message = "Missing Composer autoload file at {$autoloadPath}. Run `composer dump-autoload -o` inside {$appPath}.";
        error_log('[bootstrap] ' . $message);

        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, $message . PHP_EOL);
            exit(1);
        }

        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        echo $message . "\n";
        exit(1);
    }
}

if (!function_exists('ap_configure_bootstrap_logging')) {
    function ap_configure_bootstrap_logging(string $appPath): void
    {
        $logDir = $appPath . '/storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }

        $bootstrapLogFile = $logDir . '/error.log';
        ini_set('log_errors', '1');
        ini_set('error_log', $bootstrapLogFile);

        register_shutdown_function(static function () use ($bootstrapLogFile): void {
            $e = error_get_last();
            if ($e === null) {
                return;
            }

            $fatal = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];
            if (!in_array($e['type'], $fatal, true)) {
                return;
            }

            $line = date('c') . " [fatal] {$e['message']} in {$e['file']}:{$e['line']}\n";
            @file_put_contents($bootstrapLogFile, $line, FILE_APPEND | LOCK_EX);
        });
    }
}

if (!function_exists('ap_load_environment')) {
    function ap_load_environment(string $appPath): void
    {
        try {
            if (!is_file($appPath . '/core/config/Env.php')) {
                throw new RuntimeException("Missing Env.php - check \$appPath (expected {$appPath})");
            }

            require_once $appPath . '/core/config/Env.php';

            $envFile = getenv('APP_ENV_FILE') ?: null;
            if (!$envFile && getenv('APP_ENV') === 'testing' && is_file($appPath . '/.env.testing')) {
                $envFile = '.env.testing';
            }

            $envPath = $envFile
                ? ($envFile[0] === '/' ? $envFile : $appPath . '/' . ltrim($envFile, '/'))
                : $appPath . '/.env';

            Env::load($envPath);

            ViewEngine::setCacheEnabled(
                ap_env_bool('VIEW_CACHE', !ap_env_bool('APP_DEBUG', false))
            );
        } catch (Throwable $e) {
            error_log('[bootstrap] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            http_response_code(500);
            header('Content-Type: text/plain; charset=utf-8');
            echo "Server configuration error. Check app/.env exists, paths are correct, and storage/logs is writable.\n";
            echo 'Reason: ' . $e->getMessage() . "\n";
            exit(1);
        }
    }
}

if (!function_exists('ap_env_bool')) {
    function ap_env_bool(string $key, bool $default = false): bool
    {
        $value = $_ENV[$key] ?? getenv($key);
        if ($value === null || $value === false || $value === '') {
            return $default;
        }

        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }
}

if (!function_exists('ap_configure_runtime_error_reporting')) {
    function ap_configure_runtime_error_reporting(): void
    {
        if (str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/')) {
            set_time_limit(300);
            ini_set('max_execution_time', '300');
        }

        \App\Core\logging\Logger::debug('Incoming HTTP request bootstrap context', [
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'N/A',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'N/A',
            'https' => $_SERVER['HTTPS'] ?? 'not set',
            'server_port' => $_SERVER['SERVER_PORT'] ?? 'not set',
            'http_host' => $_SERVER['HTTP_HOST'] ?? 'not set',
            'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not set',
            'content_length' => $_SERVER['CONTENT_LENGTH'] ?? 'not set',
        ]);

        $isDebug = Env::get('APP_DEBUG', false) === true
            || Env::get('APP_DEBUG', 'false') === 'true'
            || Env::get('APP_DEBUG', '0') === '1';

        error_reporting(E_ALL);
        ini_set('display_errors', $isDebug ? '1' : '0');
        ini_set('display_startup_errors', '1');
        ini_set('log_errors', '1');
    }
}

if (!function_exists('ap_load_application_files')) {
    function ap_load_application_files(string $appPath): void
    {
        $files = [
            '/core/security/Security.php',
            '/core/http/Input.php',
            '/core/security/CSRF.php',
            '/core/security/RateLimiter.php',
            '/core/debug/Debug.php',
            '/core/exceptions/ExceptionHandler.php',
            '/core/view/FormBuilder.php',
            '/core/view/Form.php',
            '/core/view/TableBuilder.php',
            '/core/view/ViewEngine.php',
            '/core/helpers.php',
            '/core/database/Database.php',
            '/core/cache/Cache.php',
            '/core/logging/Logger.php',
            '/core/services/ApiResponseFormatterService.php',
            '/core/services/DashboardApiQueryService.php',
            '/core/services/DashboardApiResourceService.php',
            '/core/services/DashboardBlogPostService.php',
            '/core/services/DashboardBlogTaxonomyService.php',
            '/core/services/DashboardContactMessageService.php',
            '/core/services/DashboardGeoService.php',
            '/core/services/DashboardIpTrackingService.php',
            '/core/services/DashboardLanguageService.php',
            '/core/services/DashboardMediaService.php',
            '/core/services/DashboardNavigationService.php',
            '/core/services/DashboardPageService.php',
            '/core/services/DashboardRoleService.php',
            '/core/services/DashboardSchemaService.php',
            '/core/services/DashboardUserService.php',
            '/core/services/EmailService.php',
            '/core/database/QueryBuilder.php',
            '/core/database/DatabaseTableBuilder.php',
            '/core/database/DatabaseBuilder.php',
            '/core/http/Request.php',
            '/core/mvc/Controller.php',
            '/core/i18n/Translator.php',
            '/core/routing/RouteCollection.php',
            '/core/routing/RouteRegistrar.php',
            '/core/routing/Route.php',
            '/core/routing/DynamicRouteRegistry.php',
            '/core/routing/Router.php',
            '/core/mvc/Model.php',
            '/mvc/models/User.php',
            '/mvc/models/Page.php',
            '/mvc/models/NavigationMenu.php',
            '/mvc/models/BlogCategory.php',
            '/mvc/models/BlogPost.php',
            '/mvc/models/BlogTag.php',
            '/mvc/models/Role.php',
            '/mvc/models/Permission.php',
            '/mvc/models/AuditLog.php',
            '/mvc/models/IpTracking.php',
            '/mvc/models/ContactMessage.php',
            '/mvc/models/Language.php',
            '/mvc/models/ApiToken.php',
            '/mvc/models/Continent.php',
            '/mvc/models/Region.php',
            '/core/services/GeoLocation.php',
            '/core/services/UploadManager.php',
            '/core/services/KnownServiceDetector.php',
            '/core/middleware/MiddlewareInterface.php',
            '/core/middleware/CorsMiddleware.php',
            '/core/middleware/AuthMiddleware.php',
            '/core/middleware/ApiAuthMiddleware.php',
            '/core/middleware/SecurityHeadersMiddleware.php',
            '/core/middleware/RateLimitMiddleware.php',
            '/core/middleware/CsrfMiddleware.php',
            '/core/middleware/PermissionMiddleware.php',
            '/core/middleware/IpTrackingMiddleware.php',
            '/mvc/controllers/DashboardApiController.php',
            '/mvc/controllers/ApiController.php',
            '/mvc/controllers/DashboardController.php',
            '/mvc/controllers/DashboardHomeController.php',
            '/mvc/controllers/DashboardSchemaController.php',
            '/mvc/controllers/DashboardUserController.php',
            '/mvc/controllers/DashboardRoleController.php',
            '/mvc/controllers/DashboardPageController.php',
            '/mvc/controllers/DashboardNavigationController.php',
            '/mvc/controllers/DashboardLanguageController.php',
            '/mvc/controllers/DashboardGeoController.php',
            '/mvc/controllers/DashboardBlogController.php',
            '/mvc/controllers/DashboardContactMessageController.php',
            '/mvc/controllers/ApiAuthController.php',
            '/mvc/controllers/ApiPageController.php',
            '/mvc/controllers/ApiMenuController.php',
            '/mvc/controllers/ApiPostController.php',
            '/mvc/controllers/ApiCategoryController.php',
            '/mvc/controllers/ApiTagController.php',
            '/mvc/controllers/ApiLanguageController.php',
            '/mvc/controllers/PageController.php',
            '/mvc/controllers/UserController.php',
            '/mvc/controllers/ResourceController.php',
            '/mvc/controllers/AuthController.php',
            '/mvc/controllers/MainController.php',
            '/core/permissions/PermissionRegistry.php',
        ];

        foreach ($files as $file) {
            require_once $appPath . $file;
        }
    }
}

if (!function_exists('ap_register_core_services')) {
    function ap_register_core_services(Container $container): void
    {
        $container->singleton(Database::class, Database::class);
        $container->singleton(ApiResponseFormatterService::class, ApiResponseFormatterService::class);
        $container->singleton(DashboardApiQueryService::class, DashboardApiQueryService::class);
        $container->singleton(DashboardApiResourceService::class, DashboardApiResourceService::class);
        $container->singleton(DashboardBlogPostService::class, DashboardBlogPostService::class);
        $container->singleton(DashboardBlogTaxonomyService::class, DashboardBlogTaxonomyService::class);
        $container->singleton(DashboardContactMessageService::class, DashboardContactMessageService::class);
        $container->singleton(DashboardGeoService::class, DashboardGeoService::class);
        $container->singleton(DashboardIpTrackingService::class, DashboardIpTrackingService::class);
        $container->singleton(DashboardLanguageService::class, DashboardLanguageService::class);
        $container->singleton(DashboardMediaService::class, DashboardMediaService::class);
        $container->singleton(DashboardNavigationService::class, DashboardNavigationService::class);
        $container->singleton(DashboardPageService::class, DashboardPageService::class);
        $container->singleton(DashboardRoleService::class, DashboardRoleService::class);
        $container->singleton(DashboardSchemaService::class, DashboardSchemaService::class);
        $container->singleton(DashboardUserService::class, DashboardUserService::class);
        $container->singleton(Logger::class, Logger::class);
        $container->singleton(ViewEngine::class, ViewEngine::class);
        $container->singleton(Translator::class, Translator::class);

        $container->singleton('db', Database::class);
        $container->singleton('api.formatter', ApiResponseFormatterService::class);
        $container->singleton('dashboard.api.query', DashboardApiQueryService::class);
        $container->singleton('dashboard.api.resources', DashboardApiResourceService::class);
        $container->singleton('dashboard.blogPosts', DashboardBlogPostService::class);
        $container->singleton('dashboard.blogTaxonomy', DashboardBlogTaxonomyService::class);
        $container->singleton('dashboard.contactMessages', DashboardContactMessageService::class);
        $container->singleton('dashboard.geo', DashboardGeoService::class);
        $container->singleton('dashboard.ipTracking', DashboardIpTrackingService::class);
        $container->singleton('dashboard.languages', DashboardLanguageService::class);
        $container->singleton('dashboard.media', DashboardMediaService::class);
        $container->singleton('dashboard.navigation', DashboardNavigationService::class);
        $container->singleton('dashboard.pages', DashboardPageService::class);
        $container->singleton('dashboard.roles', DashboardRoleService::class);
        $container->singleton('dashboard.schema', DashboardSchemaService::class);
        $container->singleton('dashboard.users', DashboardUserService::class);
        $container->singleton('logger', Logger::class);
        $container->singleton('view', ViewEngine::class);
        $container->singleton('translator', Translator::class);
        $container->instance('env', $_ENV);
    }
}

if (!function_exists('ap_configure_session')) {
    function ap_configure_session(): void
    {
        $sessionLifetime = (int) Env::get('SESSION_LIFETIME', 120) * 60;
        $isSecure = ap_is_https_request();

        $sessionSecure = $isSecure;
        $sessionSecureEnv = Env::get('SESSION_SECURE');
        if ($sessionSecureEnv === true || $sessionSecureEnv === 'true') {
            $sessionSecure = true;
        } elseif (($sessionSecureEnv === false || $sessionSecureEnv === 'false') && !$isSecure) {
            $sessionSecure = false;
        }

        ini_set('session.gc_maxlifetime', (string) $sessionLifetime);
        ini_set('session.cookie_lifetime', (string) $sessionLifetime);
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Lax');
        ini_set('session.cookie_secure', $sessionSecure ? '1' : '0');
    }
}

if (!function_exists('ap_is_https_request')) {
    function ap_is_https_request(): bool
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            return true;
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') {
            return true;
        }

        if (!empty($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443) {
            return true;
        }

        if (!empty($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] === 'https') {
            return true;
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_SCHEME']) && $_SERVER['HTTP_X_FORWARDED_SCHEME'] === 'https') {
            return true;
        }

        if (!empty($_SERVER['HTTP_CF_VISITOR']) && str_contains($_SERVER['HTTP_CF_VISITOR'], '"scheme":"https"')) {
            return true;
        }

        $appUrl = Env::get('APP_URL', '');

        return $appUrl && str_starts_with($appUrl, 'https://');
    }
}

if (!function_exists('ap_serve_favicon_if_requested')) {
    function ap_serve_favicon_if_requested(string $publicPath): void
    {
        $faviconPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
        if (!preg_match('#(^|/)favicon\\.ico$#i', $faviconPath)) {
            return;
        }

        $iconFile = rtrim($publicPath, '/\\') . '/favicon.svg';
        if (!is_readable($iconFile)) {
            return;
        }

        header('Content-Type: image/svg+xml; charset=utf-8');
        header('Cache-Control: public, max-age=604800');
        readfile($iconFile);
        exit;
    }
}
