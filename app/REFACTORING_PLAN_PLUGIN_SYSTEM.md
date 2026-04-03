# 🎯 Plan Refaktorisanja - Modularni Plug-and-Play Sistem

## 📋 Pregled

Cilj je refaktorisati kod u **modularni sistem** gde se mogu distribuirati paketi:
- **Core/Basic** - Osnovni framework sa ruterom i MVC
- **User Management Plugin** - Kompletan user management sistem
- **Language Plugin** - Multi-language support
- **Payment Plugin** - Sistem za plaćanja
- **Message System Plugin** - Chat/message sistem
- **Blog Plugin** - Blog funkcionalnost
- itd.

Korisnik može:
1. Instalirati **Basic paket** i razvijati sajt kako želi
2. Dodati pluginove po potrebi (npr. `composer require aleksandar-pro/user-management`)
3. Svi pluginovi se automatski integrišu u sistem

---

## 🔍 Trenutna Situacija - Analiza

### ❌ Glavni Problemi

1. **Manual require_once** - 50+ poziva u `index.php`
2. **Global namespace** - Rizik kolizija, nemoguće multiple pakete
3. **Hard-coded dependencies** - Singleton/static pattern svuda
4. **Nema Service Container** - Nemoguće dependency injection
5. **Nema plugin discovery** - Sve mora biti ručno dodato
6. **Tight coupling** - Komponente su čvrsto povezane
7. **Nema event system** - Nemoguće hook-ovanje u sistem

### ✅ Šta već radi dobro

- Routing sistem (Route facade)
- Middleware sistema
- Model/Controller base klase
- Database wrapper (PDO)
- Migracije sistem

---

## 🎯 Ciljna Arhitektura

### Struktura Direktorijuma

```
aleksandar-pro/
├── src/                          # Core framework (Basic paket)
│   ├── Core/
│   │   ├── Container/            # Service Container
│   │   ├── Events/               # Event Dispatcher
│   │   ├── Routing/              # Router, Route, RouteCollection
│   │   ├── Http/                 # Request, Response
│   │   ├── Database/             # Database, QueryBuilder
│   │   ├── Middleware/           # Middleware system
│   │   └── Foundation/           # Application base
│   ├── Contracts/                # Interfaces
│   │   ├── PluginInterface.php
│   │   ├── ServiceProviderInterface.php
│   │   └── ...
│   └── Support/                  # Helper classes
│
├── packages/                     # Distributed packages
│   ├── user-management/          # User Management Plugin
│   │   ├── src/
│   │   │   ├── UserManagementPlugin.php
│   │   │   ├── Models/
│   │   │   ├── Controllers/
│   │   │   ├── Migrations/
│   │   │   └── Routes/
│   │   ├── config/
│   │   │   └── plugin.php
│   │   ├── composer.json
│   │   └── README.md
│   │
│   ├── language/                 # Language Plugin
│   │   └── ...
│   │
│   └── payment/                  # Payment Plugin
│       └── ...
│
├── application/                  # User's application
│   ├── config/
│   │   ├── app.php
│   │   ├── plugins.php           # Enabled plugins
│   │   └── database.php
│   ├── controllers/
│   ├── models/
│   ├── views/
│   └── routes/
│
├── public/
│   └── index.php                 # Entry point (sada mnogo jednostavniji)
│
└── vendor/                       # Composer packages
```

---

## 🏗️ Arhitektura Komponenti

### 1. Service Container (Dependency Injection)

```php
namespace App\Core\Container;

class Container {
    private array $bindings = [];
    private array $instances = [];
    private array $aliases = [];

    public function bind(string $abstract, $concrete, bool $singleton = false): void;
    public function singleton(string $abstract, $concrete): void;
    public function make(string $abstract, array $parameters = []);
    public function resolve(string $abstract);
}
```

**Koristi se svuda umesto static/singleton:**
```php
// ❌ STARO
$router = new Router($routes);
$request = Request::capture();

// ✅ NOVO
$router = $container->make(RouterInterface::class);
$request = $container->make(RequestInterface::class);
```

### 2. Plugin System

#### Plugin Interface

```php
namespace App\Contracts;

interface PluginInterface {
    public function getName(): string;
    public function getVersion(): string;
    public function getDescription(): string;
    
    /**
     * Register services in container
     */
    public function register(Container $container): void;
    
    /**
     * Boot plugin (called after all plugins registered)
     */
    public function boot(Container $container): void;
    
    /**
     * Register routes
     */
    public function registerRoutes(RouteCollection $routes): void;
    
    /**
     * Register middleware
     */
    public function registerMiddleware(Router $router): void;
    
    /**
     * Get migrations path
     */
    public function getMigrationsPath(): ?string;
    
    /**
     * Get config path
     */
    public function getConfigPath(): ?string;
}
```

#### Service Provider Pattern

```php
namespace App\Contracts;

interface ServiceProviderInterface {
    public function register(Container $container): void;
    public function boot(Container $container): void;
}
```

#### Primjer Plugin-a (User Management)

```php
namespace App\Plugins\UserManagement;

use App\Contracts\PluginInterface;
use App\Core\Container\Container;
use App\Core\Routing\RouteCollection;

class UserManagementPlugin implements PluginInterface {
    public function getName(): string {
        return 'user-management';
    }
    
    public function register(Container $container): void {
        // Bind services
        $container->singleton(UserRepositoryInterface::class, UserRepository::class);
        $container->singleton(AuthServiceInterface::class, AuthService::class);
    }
    
    public function boot(Container $container): void {
        // Initialize services
        $authService = $container->make(AuthServiceInterface::class);
        $authService->initialize();
    }
    
    public function registerRoutes(RouteCollection $routes): void {
        $routes->group(['prefix' => '/user'], function($routes) {
            $routes->get('/{slug}', [UserController::class, 'show']);
            $routes->get('/profile', [UserController::class, 'profile'])->middleware('auth');
        });
    }
    
    public function getMigrationsPath(): ?string {
        return __DIR__ . '/database/migrations';
    }
}
```

### 3. Plugin Discovery & Loading

```php
namespace App\Core\Plugins;

class PluginManager {
    private Container $container;
    private array $plugins = [];
    private array $enabledPlugins = [];

    public function __construct(Container $container) {
        $this->container = $container;
    }

    /**
     * Discover plugins from:
     * 1. packages/ directory (local development)
     * 2. vendor/ (installed via Composer)
     * 3. config/plugins.php (enabled plugins list)
     */
    public function discover(): void {
        // Load from config
        $this->enabledPlugins = require base_path('config/plugins.php');
        
        // Discover from packages/
        $this->discoverFromPackages();
        
        // Discover from vendor/
        $this->discoverFromVendor();
    }

    /**
     * Register all plugins
     */
    public function register(): void {
        foreach ($this->plugins as $plugin) {
            if ($this->isEnabled($plugin)) {
                $plugin->register($this->container);
            }
        }
    }

    /**
     * Boot all plugins
     */
    public function boot(): void {
        foreach ($this->plugins as $plugin) {
            if ($this->isEnabled($plugin)) {
                $plugin->boot($this->container);
            }
        }
    }

    /**
     * Load routes from all plugins
     */
    public function loadRoutes(RouteCollection $routes): void {
        foreach ($this->plugins as $plugin) {
            if ($this->isEnabled($plugin)) {
                $plugin->registerRoutes($routes);
            }
        }
    }
}
```

### 4. Event System

```php
namespace App\Core\Events;

class EventDispatcher {
    private array $listeners = [];

    public function listen(string $event, callable $listener, int $priority = 0): void;
    public function dispatch(string $event, $payload = null): void;
    public function subscribe(object $subscriber): void;
}
```

**Primjeri eventova:**
```php
Event::listen('route.registered', function($route) {
    // Plugin može reagovati na novu rutu
});

Event::listen('user.registered', function($user) {
    // Send welcome email, etc.
});

Event::dispatch('plugin.loaded', ['plugin' => $plugin]);
```

### 5. Application Foundation

```php
namespace App\Core\Foundation;

class Application {
    private Container $container;
    private PluginManager $pluginManager;
    private EventDispatcher $events;

    public function __construct() {
        $this->container = new Container();
        $this->events = new EventDispatcher();
        $this->pluginManager = new PluginManager($this->container);
        
        $this->registerCoreServices();
    }

    public function bootstrap(): void {
        // 1. Discover plugins
        $this->pluginManager->discover();
        
        // 2. Register plugins
        $this->pluginManager->register();
        
        // 3. Boot plugins
        $this->pluginManager->boot();
        
        // 4. Load routes from plugins
        $routes = $this->container->make(RouteCollection::class);
        $this->pluginManager->loadRoutes($routes);
        
        // 5. Load application routes
        $this->loadApplicationRoutes($routes);
    }

    private function registerCoreServices(): void {
        $this->container->singleton(Container::class, fn() => $this->container);
        $this->container->singleton(EventDispatcher::class, fn() => $this->events);
        $this->container->singleton(PluginManager::class, fn() => $this->pluginManager);
        
        // Core services
        $this->container->singleton(RequestInterface::class, Request::class);
        $this->container->singleton(RouterInterface::class, Router::class);
        $this->container->singleton(DatabaseInterface::class, Database::class);
    }
}
```

### 6. Novi `public/index.php` (mnogo jednostavniji!)

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Foundation\Application;

// Bootstrap application
$app = new Application();
$app->bootstrap();

// Get router from container and dispatch
$router = $app->make(RouterInterface::class);
$router->dispatch();
```

**Samo 10 linija umesto 215!**

---

## 📦 Package System

### Composer Package Structure

Svaki plugin je **Composer paket**:

```
aleksandar-pro/user-management/
├── composer.json
├── src/
│   ├── UserManagementPlugin.php
│   ├── Models/
│   ├── Controllers/
│   └── Services/
├── database/
│   └── migrations/
├── config/
│   └── plugin.php
└── README.md
```

#### `composer.json` za plugin

```json
{
    "name": "aleksandar-pro/user-management",
    "type": "aleksandar-pro-plugin",
    "description": "User Management Plugin for Aleksandar Pro Framework",
    "version": "1.0.0",
    "require": {
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

#### Plugin Bootstrap File

```php
// src/bootstrap.php
return new App\Plugins\UserManagement\UserManagementPlugin();
```

### Package Distribution

**Basic Package:**
```json
{
    "name": "aleksandar-pro/core",
    "description": "Core framework - Basic routing and MVC",
    "version": "1.0.0"
}
```

**User Management Plugin:**
```bash
composer require aleksandar-pro/user-management
```

**Language Plugin:**
```bash
composer require aleksandar-pro/language
```

### Plugin Discovery from Composer

```php
public function discoverFromVendor(): void {
    $composer = json_decode(file_get_contents(base_path('composer.json')), true);
    $packages = $composer['require'] ?? [];
    
    foreach ($packages as $package => $version) {
        if (str_starts_with($package, 'aleksandar-pro/')) {
            $plugin = $this->loadPluginFromPackage($package);
            if ($plugin) {
                $this->plugins[] = $plugin;
            }
        }
    }
}

private function loadPluginFromPackage(string $package): ?PluginInterface {
    $packagePath = base_path("vendor/{$package}");
    $composerJson = json_decode(
        file_get_contents("{$packagePath}/composer.json"),
        true
    );
    
    $pluginClass = $composerJson['extra']['aleksandar-pro']['plugin-class'] ?? null;
    if ($pluginClass && class_exists($pluginClass)) {
        return new $pluginClass();
    }
    
    // Try bootstrap.php
    if (file_exists("{$packagePath}/src/bootstrap.php")) {
        return require "{$packagePath}/src/bootstrap.php";
    }
    
    return null;
}
```

---

## 🔄 Migration Strategy

### Faza 1: Core Refactoring (Week 1-2)

**Cilj:** Pripremiti osnovnu infrastrukturu

1. ✅ Implementirati PSR-4 autoloading sa namespaces
   - Refaktorisati sve klase sa namespace-om
   - Ažurirati `composer.json`
   - Testirati autoloading

2. ✅ Implementirati Service Container
   - Kreirati `App\Core\Container\Container`
   - Refaktorisati core klase da koriste Container
   - Zameniti static/singleton pattern

3. ✅ Implementirati Event System
   - Kreirati `App\Core\Events\EventDispatcher`
   - Dodati event-ove u kritične tačke

**Deliverables:**
- Service Container funkcioniše
- Event system funkcioniše
- Core klase koriste DI

### Faza 2: Plugin Infrastructure (Week 3-4)

**Cilj:** Kreirati plugin sistem

1. ✅ Kreirati Plugin Interface
2. ✅ Implementirati PluginManager
3. ✅ Implementirati plugin discovery
4. ✅ Kreirati plugin template

**Deliverables:**
- Plugin sistem funkcioniše
- Možemo kreirati prvi test plugin

### Faza 3: Extract First Plugin (Week 5-6)

**Cilj:** Izdvojiti prvi plugin kao proof-of-concept

1. ✅ Izdvojiti User Management u plugin
2. ✅ Kreirati `aleksandar-pro/user-management` paket
3. ✅ Testirati instalaciju i uklanjanje plugin-a

**Deliverables:**
- User Management je plugin
- Možemo instalirati/ukloniti bez menjanja core koda

### Faza 4: Refactor Core (Week 7-8)

**Cilj:** Očistiti core od plugin specifičnog koda

1. ✅ Izdvojiti Language u plugin
2. ✅ Izdvojiti Blog u plugin
3. ✅ Očistiti core - ostaviti samo osnovno (routing, MVC, database)

**Deliverables:**
- Core je minimalan i čist
- Sve funkcionalnosti su pluginovi

### Faza 5: Package Distribution (Week 9-10)

**Cilj:** Pripremiti pakete za distribuciju

1. ✅ Kreirati paket strukturu
2. ✅ Dokumentacija za svaki paket
3. ✅ Versioning strategy
4. ✅ Package repository (može biti privatni Packagist ili GitHub Packages)

**Deliverables:**
- Paketi su spremni za distribuciju
- Dokumentacija je kompletna

### Faza 6: Testing & Documentation (Week 11-12)

**Cilj:** Testirati i dokumentovati

1. ✅ Integration tests za plugin sistem
2. ✅ Dokumentacija za developere
3. ✅ Plugin development guide
4. ✅ Migration guide za postojeće aplikacije

**Deliverables:**
- Testovi pokrivaju kritične delove
- Dokumentacija je kompletna

---

## 📝 Detaljni Refactoring Plan

### Step 1: Namespace Migration

**Trenutno:**
```php
// core/routing/Router.php
class Router { ... }
```

**Refaktorisano:**
```php
// src/Core/Routing/Router.php
namespace App\Core\Routing;

use App\Core\Container\Container;
use App\Contracts\Routing\RouterInterface;

class Router implements RouterInterface {
    // ...
}
```

**Akcije:**
1. Kreirati namespace strukturu
2. Refaktorisati sve klase (core/ → src/Core/)
3. Ažurirati sve `use` statemente
4. Ažurirati autoloading u `composer.json`

### Step 2: Service Container Integration

**Trenutno:**
```php
// Controller.php
public function __construct() {
    $this->request = Request::capture(); // Static
}
```

**Refaktorisano:**
```php
// Controller.php
namespace App\Core\MVC;

use App\Contracts\Http\RequestInterface;

class Controller {
    protected RequestInterface $request;

    public function __construct(RequestInterface $request) {
        $this->request = $request;
    }
}
```

**Akcije:**
1. Kreirati Container klasu
2. Refaktorisati Controller da prima DI
3. Refaktorisati Router da prima DI
4. Refaktorisati sve servise da koriste DI

### Step 3: Interface Contracts

**Kreirati interfaces za sve glavne komponente:**

```php
// src/Contracts/Routing/RouterInterface.php
namespace App\Contracts\Routing;

interface RouterInterface {
    public function dispatch(): void;
    public function registerMiddleware(string $name, string $middleware): void;
}

// src/Contracts/Http/RequestInterface.php
namespace App\Contracts\Http;

interface RequestInterface {
    public function get(string $key, $default = null);
    public function method(): string;
    public function uri(): string;
}
```

**Akcije:**
1. Kreirati sve potrebne interface-e
2. Implementirati ih u postojećim klasama
3. Koristiti interface-e u type hints

### Step 4: Plugin System

**Kreirati plugin infrastrukturu:**
1. `PluginInterface` - kontrakt
2. `PluginManager` - discovery i loading
3. Plugin template/boilerplate
4. Plugin config system

### Step 5: Extract Plugins

**Redosled izdvajanja:**
1. **User Management** - najkompleksniji, dobar test
2. **Language** - relativno izolovan
3. **Blog** - zavisi od User Management
4. **Payment** - potpuno novi, dobar primer

---

## 🎨 Paket Struktura - Predlog

### Core Package (Basic)

**Nudi:**
- ✅ Routing (Router, Route, RouteCollection)
- ✅ MVC (Controller, Model base klase)
- ✅ HTTP (Request, Response)
- ✅ Database (PDO wrapper, QueryBuilder)
- ✅ Middleware sistem
- ✅ Service Container
- ✅ Event System
- ✅ View Engine (osnovni)

**Ne nudi:**
- ❌ User Management
- ❌ Authentication
- ❌ Language support
- ❌ Blog
- ❌ CMS features

**Cilj:** Minimalan core koji omogućava razvoj aplikacije

### User Management Plugin

**Nudi:**
- ✅ User model i CRUD
- ✅ Authentication (login, register, logout)
- ✅ Authorization (roles, permissions)
- ✅ Email verification
- ✅ Password reset
- ✅ Profile management
- ✅ User routes (`/login`, `/register`, `/user/{slug}`)
- ✅ Auth middleware

**Zavisi od:**
- Core package
- Email Service (može biti u Core ili zaseban plugin)

### Language Plugin

**Nudi:**
- ✅ Multi-language routing (`/sr/`, `/en/`, `/de/`)
- ✅ Translation system (Translator)
- ✅ Language detection (IP-based, browser-based)
- ✅ Language switcher component
- ✅ Language routes middleware

**Zavisi od:**
- Core package

### Blog Plugin

**Nudi:**
- ✅ BlogPost, BlogCategory, BlogTag models
- ✅ Blog routes (`/blog`, `/blog/{slug}`)
- ✅ Blog admin interface
- ✅ RSS feed

**Zavisi od:**
- Core package
- User Management plugin (za autorstvo)

### Payment Plugin

**Nudi:**
- ✅ Payment gateway integration
- ✅ Transaction model
- ✅ Payment routes (`/payment`, `/payment/callback`)
- ✅ Payment webhooks

**Zavisi od:**
- Core package
- User Management plugin (za korisnike)

---

## 🔧 Konfiguracija Plugins

### `config/plugins.php`

```php
<?php
return [
    /*
    |--------------------------------------------------------------------------
    | Enabled Plugins
    |--------------------------------------------------------------------------
    |
    | Lista omogućenih pluginova. Plugin mora biti:
    | 1. Instaliran (vendor/ ili packages/)
    | 2. Ovde omogućen
    |
    */
    'enabled' => [
        'user-management',
        'language',
        'blog',
    ],

    /*
    |--------------------------------------------------------------------------
    | Plugin Settings
    |--------------------------------------------------------------------------
    */
    'settings' => [
        'user-management' => [
            'email_verification_required' => true,
            'password_min_length' => 8,
        ],
        'language' => [
            'default' => 'sr',
            'supported' => ['sr', 'en', 'de'],
        ],
    ],
];
```

---

## 📚 Developer Experience

### Kreiranje Novog Plugina

```bash
# 1. Install plugin generator
composer require aleksandar-pro/plugin-generator

# 2. Generate plugin
php artisan make:plugin Payment

# 3. Plugin structure created:
# packages/payment/
# ├── src/
# │   ├── PaymentPlugin.php
# │   ├── Models/
# │   ├── Controllers/
# │   └── Services/
# ├── database/migrations/
# ├── config/plugin.php
# └── composer.json
```

### Plugin Template

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

    public function getVersion(): string {
        return '1.0.0';
    }

    public function getDescription(): string {
        return 'Payment system plugin';
    }

    public function register(Container $container): void {
        $container->singleton(PaymentServiceInterface::class, PaymentService::class);
    }

    public function boot(Container $container): void {
        // Initialize payment gateway
    }

    public function registerRoutes(RouteCollection $routes): void {
        $routes->group(['prefix' => '/payment', 'middleware' => 'auth'], function($routes) {
            $routes->post('/process', [PaymentController::class, 'process']);
            $routes->get('/callback', [PaymentController::class, 'callback']);
        });
    }

    public function registerMiddleware(Router $router): void {
        // Register custom middleware if needed
    }

    public function getMigrationsPath(): ?string {
        return __DIR__ . '/database/migrations';
    }

    public function getConfigPath(): ?string {
        return __DIR__ . '/config';
    }
}
```

---

## 🧪 Testing Strategy

### Unit Tests
- Service Container
- Event Dispatcher
- Plugin Manager
- Individual plugins

### Integration Tests
- Plugin discovery and loading
- Route registration from plugins
- Middleware registration
- Service registration

### End-to-End Tests
- Install plugin → Use functionality → Uninstall plugin

---

## 🚀 Migration Checklist

### Pre-Refactoring
- [ ] Backup trenutnog koda
- [ ] Kreirati feature branch `feature/plugin-system`
- [ ] Dokumentovati sve breaking changes

### Core Refactoring
- [ ] Implementirati PSR-4 autoloading
- [ ] Implementirati Service Container
- [ ] Refaktorisati sve klase sa namespace-om
- [ ] Zameniti static/singleton sa DI
- [ ] Kreirati Interface Contracts
- [ ] Implementirati Event System

### Plugin System
- [ ] Kreirati PluginInterface
- [ ] Implementirati PluginManager
- [ ] Implementirati plugin discovery
- [ ] Kreirati plugin template

### First Plugin
- [ ] Izdvojiti User Management
- [ ] Testirati instalaciju
- [ ] Testirati uklanjanje
- [ ] Dokumentacija

### Additional Plugins
- [ ] Izdvojiti Language plugin
- [ ] Izdvojiti Blog plugin
- [ ] Izdvojiti ostale feature-e

### Package Distribution
- [ ] Pripremiti pakete za distribuciju
- [ ] Kreirati package repository
- [ ] Dokumentacija za svaki paket

### Final
- [ ] Integration tests
- [ ] Dokumentacija za developere
- [ ] Migration guide
- [ ] Release

---

## 📖 Dokumentacija Potrebna

1. **Plugin Development Guide**
   - Kako kreirati plugin
   - Best practices
   - Testing plugins
   - Publishing plugins

2. **Package Installation Guide**
   - Kako instalirati pakete
   - Kako konfigurisati pluginove
   - Troubleshooting

3. **Migration Guide**
   - Kako migrirati postojeću aplikaciju
   - Breaking changes
   - Compatibility

4. **API Documentation**
   - Service Container API
   - Event System API
   - Plugin Interface API

---

## ⚠️ Breaking Changes

### Zašto je potrebno?

**Trenutno:**
- Kod je monolitski
- Nemoguće je distribuirati kao pakete
- Nemoguće je plug-and-play

**Posle refaktorisanja:**
- Modulari sistem
- Možemo distribuirati pakete
- Plug-and-play funkcioniše
- Korisnici mogu birati šta im treba

### Breaking Changes Lista

1. **Namespace Changes**
   - Sve klase će imati namespace
   - Stari kod koji direktno instancira klase neće raditi

2. **Service Container**
   - Static metode zamenjene sa DI
   - `Request::capture()` → `$container->make(RequestInterface::class)`

3. **Plugin Structure**
   - Feature-i će biti pluginovi
   - Konfiguracija će se menjati

---

## 💡 Prednosti Ovog Pristupa

### Za Korisnike
- ✅ Instaliraju samo šta im treba
- ✅ Lako dodaju/uklanjaju funkcionalnosti
- ✅ Čistiji kod u njihovoj aplikaciji
- ✅ Lako upgrade-ovanje pojedinačnih pluginova

### Za Developere
- ✅ Modulari kod
- ✅ Lako testiranje
- ✅ Lako održavanje
- ✅ Možemo distribuirati kao pakete
- ✅ Lako dodavanje novih feature-a

### Za Business
- ✅ Možemo nuditi različite pakete (Basic, Pro, Enterprise)
- ✅ Možemo monetizovati pluginove
- ✅ Lakše skaliranje

---

## 🎯 Success Metrics

Refaktorisanje je uspešno ako:

1. ✅ Korisnik može instalirati Basic paket bez ikakvih feature-a
2. ✅ Korisnik može dodati plugin sa `composer require`
3. ✅ Plugin se automatski integriše (rute, middleware, servisi)
4. ✅ Korisnik može ukloniti plugin bez menjanja core koda
5. ✅ Core kod je čist i ne zavisi od pluginova
6. ✅ Možemo distribuirati pakete nezavisno

---

## 📅 Timeline Summary

- **Weeks 1-2:** Core Refactoring (PSR-4, Container, Events)
- **Weeks 3-4:** Plugin Infrastructure
- **Weeks 5-6:** First Plugin (User Management)
- **Weeks 7-8:** Extract Additional Plugins
- **Weeks 9-10:** Package Distribution
- **Weeks 11-12:** Testing & Documentation

**Ukupno: ~3 meseca** za kompletan refactoring

Možemo ići i iterativno:
- **Iteracija 1:** Core + First Plugin (2 meseca)
- **Iteracija 2:** Additional Plugins (1 mesec)
- **Iteracija 3:** Polish & Documentation (1 mesec)

---

## 🚦 Prvi Koraci

1. **Kreirati feature branch**
2. **Implementirati Service Container** (najkritičniji)
3. **Implementirati PSR-4 autoloading**
4. **Refaktorisati jednu klasu kao proof-of-concept** (npr. Router)
5. **Testirati da sve funkcioniše**
6. **Nastaviti sa ostatkom**

---

## ❓ Pitanja za Razmatranje

1. **Composer Repository:** 
   - Privatni Packagist?
   - GitHub Packages?
   - Satis (self-hosted)?

2. **Versioning:**
   - Semantic Versioning (1.0.0, 1.1.0, 2.0.0)?
   - Kako handle-ovati breaking changes?

3. **Backward Compatibility:**
   - Da li treba podržati stari kod?
   - Migration helper tools?

4. **Plugin Dependencies:**
   - Da li plugin može zavisiti od drugog plugina?
   - Kako handle-ovati dependency resolution?

---

## ✅ Odluka

**Da li počinjemo sa refaktorisanjem?**

Preporučujem da počnemo sa:
1. **Service Container** - najvažniji, omogućava sve ostalo
2. **PSR-4 Namespaces** - osnova za modulare pakete
3. **Plugin Interface** - definiše kontrakt

Možemo ići iterativno i testirati svaku fazu.
