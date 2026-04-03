# 🔌 Plugin System - Konkretni Primeri

Ovaj dokument sadrži konkretne primere implementacije plug-in sistema.

---

## 📦 Primer 1: Core Package (Basic)

### Struktura

```
aleksandar-pro-core/
├── composer.json
├── src/
│   ├── Core/
│   │   ├── Container/
│   │   │   └── Container.php
│   │   ├── Events/
│   │   │   └── EventDispatcher.php
│   │   ├── Foundation/
│   │   │   └── Application.php
│   │   ├── Routing/
│   │   │   ├── Router.php
│   │   │   ├── Route.php
│   │   │   ├── RouteCollection.php
│   │   │   └── RouteRegistrar.php
│   │   ├── Http/
│   │   │   ├── Request.php
│   │   │   └── Response.php
│   │   ├── Database/
│   │   │   ├── Database.php
│   │   │   └── QueryBuilder.php
│   │   └── MVC/
│   │       ├── Controller.php
│   │       └── Model.php
│   └── Contracts/
│       ├── PluginInterface.php
│       ├── Routing/
│       │   └── RouterInterface.php
│       └── Http/
│           └── RequestInterface.php
└── README.md
```

### `composer.json`

```json
{
    "name": "aleksandar-pro/core",
    "description": "Core framework - Basic routing and MVC",
    "type": "library",
    "version": "1.0.0",
    "require": {
        "php": "^8.1"
    },
    "autoload": {
        "psr-4": {
            "App\\Core\\": "src/Core/",
            "App\\Contracts\\": "src/Contracts/"
        }
    },
    "extra": {
        "aleksandar-pro": {
            "core": true
        }
    }
}
```

### `src/Core/Foundation/Application.php`

```php
<?php
namespace App\Core\Foundation;

use App\Core\Container\Container;
use App\Core\Events\EventDispatcher;
use App\Core\Plugins\PluginManager;
use App\Core\Routing\RouteCollection;
use App\Core\Routing\Router;
use App\Contracts\Routing\RouterInterface;
use App\Core\Routing\Route;

class Application {
    private Container $container;
    private PluginManager $pluginManager;
    private EventDispatcher $events;

    public function __construct() {
        $this->container = new Container();
        $this->events = new EventDispatcher();
        $this->pluginManager = new PluginManager($this->container, $this->events);
        
        $this->registerCoreServices();
    }

    public function bootstrap(): void {
        // Register core services
        $this->registerCoreBindings();

        // Discover and load plugins
        $this->pluginManager->discover();
        $this->pluginManager->register();
        $this->pluginManager->boot();

        // Load routes from plugins and application
        $this->loadRoutes();

        // Dispatch event
        $this->events->dispatch('application.booted');
    }

    private function registerCoreServices(): void {
        // Register container itself
        $this->container->singleton(Container::class, fn() => $this->container);
        $this->container->singleton(EventDispatcher::class, fn() => $this->events);
        $this->container->singleton(PluginManager::class, fn() => $this->pluginManager);
    }

    private function registerCoreBindings(): void {
        // Core services
        $this->container->singleton(
            \App\Contracts\Http\RequestInterface::class,
            \App\Core\Http\Request::class
        );

        $this->container->singleton(
            RouterInterface::class,
            function(Container $container) {
                $routes = new RouteCollection();
                Route::setCollection($routes);
                return new Router($routes, $container);
            }
        );
    }

    private function loadRoutes(): void {
        $routes = new RouteCollection();
        Route::setCollection($routes);

        // Load routes from plugins
        $this->pluginManager->loadRoutes($routes);

        // Load application routes
        $routesPath = base_path('routes');
        if (file_exists("{$routesPath}/web.php")) {
            require "{$routesPath}/web.php";
        }
        if (file_exists("{$routesPath}/api.php")) {
            require "{$routesPath}/api.php";
        }

        // Load dynamic routes (from database)
        $this->loadDynamicRoutes($routes);
    }

    private function loadDynamicRoutes(RouteCollection $routes): void {
        // Load routes from Page Manager, etc.
        // This could be an event listener
        $this->events->dispatch('routes.loading', ['routes' => $routes]);
    }

    public function make(string $abstract, array $parameters = []) {
        return $this->container->make($abstract, $parameters);
    }

    public function getContainer(): Container {
        return $this->container;
    }
}
```

---

## 🔌 Primer 2: User Management Plugin

### Struktura

```
aleksandar-pro-user-management/
├── composer.json
├── src/
│   ├── UserManagementPlugin.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Role.php
│   │   └── Permission.php
│   ├── Controllers/
│   │   ├── AuthController.php
│   │   └── UserController.php
│   ├── Services/
│   │   ├── AuthService.php
│   │   ├── UserRepository.php
│   │   └── PermissionService.php
│   └── Middleware/
│       └── AuthMiddleware.php
├── database/
│   └── migrations/
│       ├── 001_create_users_table.php
│       ├── 002_create_roles_table.php
│       └── 003_create_permissions_table.php
├── config/
│   └── plugin.php
└── README.md
```

### `composer.json`

```json
{
    "name": "aleksandar-pro/user-management",
    "description": "User Management Plugin for Aleksandar Pro Framework",
    "type": "aleksandar-pro-plugin",
    "version": "1.0.0",
    "require": {
        "php": "^8.1",
        "aleksandar-pro/core": "^1.0"
    },
    "autoload": {
        "psr-4": {
            "App\\Plugins\\UserManagement\\": "src/"
        },
        "files": [
            "src/bootstrap.php"
        ]
    },
    "extra": {
        "aleksandar-pro": {
            "plugin-class": "App\\Plugins\\UserManagement\\UserManagementPlugin"
        }
    }
}
```

### `src/bootstrap.php`

```php
<?php
return new App\Plugins\UserManagement\UserManagementPlugin();
```

### `src/UserManagementPlugin.php`

```php
<?php
namespace App\Plugins\UserManagement;

use App\Contracts\PluginInterface;
use App\Core\Container\Container;
use App\Core\Routing\RouteCollection;
use App\Core\Events\EventDispatcher;
use App\Plugins\UserManagement\Services\AuthService;
use App\Plugins\UserManagement\Services\UserRepository;
use App\Plugins\UserManagement\Middleware\AuthMiddleware;
use App\Plugins\UserManagement\Controllers\AuthController;
use App\Plugins\UserManagement\Controllers\UserController;

class UserManagementPlugin implements PluginInterface {
    private string $name = 'user-management';
    private string $version = '1.0.0';

    public function getName(): string {
        return $this->name;
    }

    public function getVersion(): string {
        return $this->version;
    }

    public function getDescription(): string {
        return 'User Management Plugin - Authentication, Authorization, and User CRUD';
    }

    public function register(Container $container): void {
        // Register services
        $container->singleton(
            \App\Plugins\UserManagement\Contracts\UserRepositoryInterface::class,
            \App\Plugins\UserManagement\Services\UserRepository::class
        );

        $container->singleton(
            \App\Plugins\UserManagement\Contracts\AuthServiceInterface::class,
            function(Container $container) {
                return new AuthService(
                    $container->make(\App\Plugins\UserManagement\Contracts\UserRepositoryInterface::class)
                );
            }
        );

        // Register middleware
        $container->singleton('auth.middleware', AuthMiddleware::class);

        // Register controllers
        $container->bind(AuthController::class, function(Container $container) {
            return new AuthController(
                $container->make(\App\Contracts\Http\RequestInterface::class),
                $container->make(\App\Plugins\UserManagement\Contracts\AuthServiceInterface::class)
            );
        });

        $container->bind(UserController::class, function(Container $container) {
            return new UserController(
                $container->make(\App\Contracts\Http\RequestInterface::class),
                $container->make(\App\Plugins\UserManagement\Contracts\UserRepositoryInterface::class)
            );
        });
    }

    public function boot(Container $container): void {
        // Initialize services
        $authService = $container->make(\App\Plugins\UserManagement\Contracts\AuthServiceInterface::class);
        
        // Register event listeners
        $events = $container->make(\App\Core\Events\EventDispatcher::class);
        
        $events->listen('user.registered', function($user) use ($authService) {
            // Send welcome email, etc.
            error_log("User registered: {$user->email}");
        });

        $events->listen('user.login', function($user) {
            // Log login, update last_login, etc.
            error_log("User logged in: {$user->email}");
        });
    }

    public function registerRoutes(RouteCollection $routes): void {
        // Authentication routes
        $routes->group(['prefix' => ''], function($routes) {
            // Login
            $routes->get('/login', [AuthController::class, 'showLogin'])
                ->name('login');
            $routes->post('/login', [AuthController::class, 'login'])
                ->middleware(['ratelimit:5,60'])
                ->name('login.submit');

            // Register
            $routes->get('/register', [AuthController::class, 'showRegister'])
                ->name('register');
            $routes->post('/register', [AuthController::class, 'register'])
                ->middleware(['ratelimit:3,300'])
                ->name('register.submit');

            // Logout
            $routes->post('/logout', [AuthController::class, 'logout'])
                ->name('logout');

            // Password reset
            $routes->get('/forgot-password', [AuthController::class, 'showForgotPassword'])
                ->name('password.forgot');
            $routes->post('/forgot-password', [AuthController::class, 'forgotPassword'])
                ->middleware(['ratelimit:3,600'])
                ->name('password.forgot.submit');
        });

        // User routes
        $routes->group(['prefix' => '/user'], function($routes) {
            // Public profile
            $routes->get('/{slug}', [UserController::class, 'show'])
                ->where(['slug' => '[a-z0-9-]+'])
                ->name('user.show');

            // Protected routes
            $routes->group(['middleware' => ['auth']], function($routes) {
                $routes->get('/profile', [UserController::class, 'profile'])
                    ->name('user.profile');
                $routes->get('/profile/edit', [UserController::class, 'editProfile'])
                    ->name('user.profile.edit');
                $routes->post('/profile/update', [UserController::class, 'updateProfile'])
                    ->name('user.profile.update');
            });
        });
    }

    public function registerMiddleware(\App\Core\Routing\Router $router): void {
        $router->registerMiddleware('auth', 'auth.middleware');
    }

    public function getMigrationsPath(): ?string {
        return __DIR__ . '/../database/migrations';
    }

    public function getConfigPath(): ?string {
        return __DIR__ . '/../config';
    }

    public function getViewsPath(): ?string {
        return __DIR__ . '/../views';
    }

    public function getAssetsPath(): ?string {
        return __DIR__ . '/../assets';
    }
}
```

### `src/Controllers/AuthController.php`

```php
<?php
namespace App\Plugins\UserManagement\Controllers;

use App\Core\MVC\Controller;
use App\Contracts\Http\RequestInterface;
use App\Plugins\UserManagement\Contracts\AuthServiceInterface;
use App\Core\Events\EventDispatcher;

class AuthController extends Controller {
    private AuthServiceInterface $authService;
    private EventDispatcher $events;

    public function __construct(
        RequestInterface $request,
        AuthServiceInterface $authService,
        EventDispatcher $events
    ) {
        parent::__construct($request);
        $this->authService = $authService;
        $this->events = $events;
    }

    public function showLogin() {
        return view('user-management::login');
    }

    public function login() {
        $email = $this->request->post('email');
        $password = $this->request->post('password');

        $user = $this->authService->attemptLogin($email, $password);

        if (!$user) {
            return $this->error('Invalid credentials', null, 401);
        }

        // Dispatch event
        $this->events->dispatch('user.login', ['user' => $user]);

        return $this->success([
            'user' => $user,
            'redirect' => route('user.profile')
        ]);
    }

    public function register() {
        $data = [
            'name' => $this->request->post('name'),
            'email' => $this->request->post('email'),
            'password' => $this->request->post('password'),
        ];

        $user = $this->authService->register($data);

        // Dispatch event
        $this->events->dispatch('user.registered', ['user' => $user]);

        return $this->success([
            'user' => $user,
            'message' => 'Registration successful. Please check your email.'
        ]);
    }
}
```

---

## 🌍 Primer 3: Language Plugin

### Struktura

```
aleksandar-pro-language/
├── composer.json
├── src/
│   ├── LanguagePlugin.php
│   ├── Services/
│   │   └── Translator.php
│   ├── Middleware/
│   │   └── LanguageMiddleware.php
│   └── Models/
│       └── Language.php
├── database/
│   └── migrations/
│       └── 001_create_languages_table.php
└── config/
    └── plugin.php
```

### `src/LanguagePlugin.php`

```php
<?php
namespace App\Plugins\Language;

use App\Contracts\PluginInterface;
use App\Core\Container\Container;
use App\Core\Routing\RouteCollection;
use App\Plugins\Language\Services\Translator;
use App\Plugins\Language\Middleware\LanguageMiddleware;

class LanguagePlugin implements PluginInterface {
    public function getName(): string {
        return 'language';
    }

    public function register(Container $container): void {
        $container->singleton(
            \App\Plugins\Language\Contracts\TranslatorInterface::class,
            Translator::class
        );

        $container->singleton('language.middleware', LanguageMiddleware::class);
    }

    public function boot(Container $container): void {
        $translator = $container->make(\App\Plugins\Language\Contracts\TranslatorInterface::class);
        $router = $container->make(\App\Contracts\Routing\RouterInterface::class);
        
        // Initialize translator with detected language
        $lang = $router->getLanguage() ?? config('language.default', 'sr');
        $translator->setLanguage($lang);
    }

    public function registerRoutes(RouteCollection $routes): void {
        // Language switcher route
        $routes->post('/language/switch', [LanguageController::class, 'switch'])
            ->name('language.switch');
    }

    public function registerMiddleware(\App\Core\Routing\Router $router): void {
        // Language middleware runs early to detect language from URL
        $router->registerMiddleware('language', 'language.middleware');
        $router->prependMiddleware('language'); // Run before other middleware
    }
}
```

---

## 💳 Primer 4: Payment Plugin

### Struktura

```
aleksandar-pro-payment/
├── composer.json
├── src/
│   ├── PaymentPlugin.php
│   ├── Services/
│   │   ├── PaymentGateway.php
│   │   └── PaymentService.php
│   ├── Controllers/
│   │   └── PaymentController.php
│   └── Models/
│       └── Transaction.php
├── database/
│   └── migrations/
│       └── 001_create_transactions_table.php
└── config/
    └── plugin.php
```

### `src/PaymentPlugin.php`

```php
<?php
namespace App\Plugins\Payment;

use App\Contracts\PluginInterface;
use App\Core\Container\Container;
use App\Core\Routing\RouteCollection;

class PaymentPlugin implements PluginInterface {
    public function getName(): string {
        return 'payment';
    }

    public function register(Container $container): void {
        // Register payment gateway based on config
        $gateway = config('payment.gateway', 'stripe');
        
        $container->singleton(
            \App\Plugins\Payment\Contracts\PaymentGatewayInterface::class,
            match($gateway) {
                'stripe' => \App\Plugins\Payment\Gateways\StripeGateway::class,
                'paypal' => \App\Plugins\Payment\Gateways\PayPalGateway::class,
                default => throw new \Exception("Unknown payment gateway: {$gateway}")
            }
        );

        $container->singleton(
            \App\Plugins\Payment\Contracts\PaymentServiceInterface::class,
            \App\Plugins\Payment\Services\PaymentService::class
        );
    }

    public function boot(Container $container): void {
        $events = $container->make(\App\Core\Events\EventDispatcher::class);

        // Listen for payment events
        $events->listen('payment.completed', function($transaction) {
            // Send confirmation email, update order status, etc.
            error_log("Payment completed: {$transaction->id}");
        });

        $events->listen('payment.failed', function($transaction) {
            // Handle failed payment
            error_log("Payment failed: {$transaction->id}");
        });
    }

    public function registerRoutes(RouteCollection $routes): void {
        $routes->group(['prefix' => '/payment', 'middleware' => ['auth']], function($routes) {
            $routes->post('/process', [PaymentController::class, 'process'])
                ->name('payment.process');
            
            $routes->get('/callback/{gateway}', [PaymentController::class, 'callback'])
                ->name('payment.callback');
            
            $routes->get('/success', [PaymentController::class, 'success'])
                ->name('payment.success');
            
            $routes->get('/cancel', [PaymentController::class, 'cancel'])
                ->name('payment.cancel');
        });
    }
}
```

---

## 🚀 Primer 5: Application Setup

### `public/index.php` (NOVO - samo 15 linija!)

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Foundation\Application;

// Load environment variables
require_once __DIR__ . '/../core/config/Env.php';
Env::load(__DIR__ . '/../.env');

// Configure error reporting
$isDebug = Env::get('APP_DEBUG', false) === true;
error_reporting($isDebug ? E_ALL : 0);
ini_set('display_errors', $isDebug ? '1' : '0');

// Bootstrap application
$app = new Application();
$app->bootstrap();

// Get router and dispatch
$router = $app->make(\App\Contracts\Routing\RouterInterface::class);
$router->dispatch();
```

### `config/plugins.php`

```php
<?php
return [
    /*
    |--------------------------------------------------------------------------
    | Enabled Plugins
    |--------------------------------------------------------------------------
    */
    'enabled' => [
        'user-management',
        'language',
        'payment',
    ],

    /*
    |--------------------------------------------------------------------------
    | Plugin Configuration
    |--------------------------------------------------------------------------
    */
    'user-management' => [
        'email_verification_required' => true,
        'password_min_length' => 8,
    ],

    'language' => [
        'default' => 'sr',
        'supported' => ['sr', 'en', 'de'],
        'auto_detect' => true,
    ],

    'payment' => [
        'gateway' => 'stripe',
        'stripe_key' => env('STRIPE_KEY'),
        'stripe_secret' => env('STRIPE_SECRET'),
    ],
];
```

### `composer.json` (User's Application)

```json
{
    "name": "user/app",
    "require": {
        "aleksandar-pro/core": "^1.0",
        "aleksandar-pro/user-management": "^1.0",
        "aleksandar-pro/language": "^1.0",
        "aleksandar-pro/payment": "^1.0"
    },
    "autoload": {
        "psr-4": {
            "App\\": "application/"
        }
    }
}
```

### Instalacija

```bash
# 1. Install core
composer require aleksandar-pro/core

# 2. Install plugins
composer require aleksandar-pro/user-management
composer require aleksandar-pro/language
composer require aleksandar-pro/payment

# 3. Configure plugins
# Edit config/plugins.php to enable desired plugins

# 4. Run migrations
php artisan migrate

# 5. Done! Plugins are automatically loaded
```

---

## 🔄 Primer 6: Plugin Dependencies

### Payment Plugin zavisi od User Management

```php
// PaymentPlugin.php
public function boot(Container $container): void {
    // Check if user-management plugin is loaded
    $pluginManager = $container->make(\App\Core\Plugins\PluginManager::class);
    
    if (!$pluginManager->isLoaded('user-management')) {
        throw new \Exception('Payment plugin requires user-management plugin');
    }

    // Now we can safely use user management services
    $authService = $container->make(\App\Plugins\UserManagement\Contracts\AuthServiceInterface::class);
    // ...
}
```

### `composer.json` dependency

```json
{
    "name": "aleksandar-pro/payment",
    "require": {
        "aleksandar-pro/core": "^1.0",
        "aleksandar-pro/user-management": "^1.0"
    }
}
```

---

## 🎨 Primer 7: Plugin Template Generator

### Artisan Command

```php
// commands/GeneratePluginCommand.php
namespace App\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GeneratePluginCommand extends Command {
    protected function configure() {
        $this->setName('make:plugin')
            ->setDescription('Generate a new plugin')
            ->addArgument('name', InputArgument::REQUIRED, 'Plugin name');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $name = $input->getArgument('name');
        $pluginPath = base_path("packages/{$name}");

        // Create directory structure
        $this->createDirectoryStructure($pluginPath);

        // Generate files
        $this->generateComposerJson($pluginPath, $name);
        $this->generatePluginClass($pluginPath, $name);
        $this->generateReadme($pluginPath, $name);

        $output->writeln("Plugin '{$name}' created successfully at: {$pluginPath}");
        return Command::SUCCESS;
    }
}
```

### Usage

```bash
php artisan make:plugin payment
# Creates packages/payment/ with all necessary files
```

---

## 🧪 Primer 8: Testing Plugin

### Plugin Test Example

```php
// tests/Plugins/UserManagementPluginTest.php
namespace Tests\Plugins;

use PHPUnit\Framework\TestCase;
use App\Core\Foundation\Application;
use App\Core\Container\Container;
use App\Plugins\UserManagement\UserManagementPlugin;

class UserManagementPluginTest extends TestCase {
    public function test_plugin_can_be_registered() {
        $container = new Container();
        $plugin = new UserManagementPlugin();

        $plugin->register($container);

        $this->assertTrue($container->bound(
            \App\Plugins\UserManagement\Contracts\AuthServiceInterface::class
        ));
    }

    public function test_plugin_registers_routes() {
        $routes = new \App\Core\Routing\RouteCollection();
        $plugin = new UserManagementPlugin();

        $plugin->registerRoutes($routes);

        $this->assertTrue($routes->hasRoute('login'));
        $this->assertTrue($routes->hasRoute('register'));
    }
}
```

---

## 📚 Primer 9: Plugin Documentation Template

### `README.md` Template

```markdown
# User Management Plugin

## Installation

```bash
composer require aleksandar-pro/user-management
```

## Configuration

Edit `config/plugins.php`:

```php
'user-management' => [
    'email_verification_required' => true,
    'password_min_length' => 8,
],
```

## Features

- User registration
- User authentication
- Password reset
- Email verification
- Role-based access control

## Routes

- `GET /login` - Login form
- `POST /login` - Process login
- `GET /register` - Registration form
- `POST /register` - Process registration
- `GET /user/{slug}` - User profile
- `GET /user/profile` - Current user profile (protected)

## Usage

```php
use App\Plugins\UserManagement\Contracts\AuthServiceInterface;

$authService = app()->make(AuthServiceInterface::class);
$user = $authService->attemptLogin($email, $password);
```

## Dependencies

- aleksandar-pro/core: ^1.0

## License

MIT
```

---

## ✅ Checklist za Plugin Development

- [ ] Plugin ima `composer.json` sa `aleksandar-pro-plugin` type-om
- [ ] Plugin implementira `PluginInterface`
- [ ] Plugin ima `bootstrap.php` ili je definisan u `composer.json` extra
- [ ] Plugin registruje sve servise u `register()` metodi
- [ ] Plugin inicijalizuje servise u `boot()` metodi
- [ ] Plugin registruje rute u `registerRoutes()` metodi
- [ ] Plugin registruje middleware u `registerMiddleware()` metodi
- [ ] Plugin ima migracije (ako treba)
- [ ] Plugin ima konfiguraciju (ako treba)
- [ ] Plugin ima README.md sa dokumentacijom
- [ ] Plugin ima testove
- [ ] Plugin je testiran sa osnovnim scenarijima

---

Ovaj dokument pokazuje kako bi sve funkcionisalo u praksi. Svaki plugin je nezavisan Composer paket koji se može instalirati/ukloniti bez menjanja core koda.
