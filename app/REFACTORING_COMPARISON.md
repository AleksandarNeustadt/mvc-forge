# 🔄 Refaktorisanje - Poređenje Pre i Posle

## 📊 Pregled Promena

### Pre Refaktorisanja ❌

**Trenutno stanje:**
- Monolitska aplikacija
- Manual `require_once` za sve fajlove
- Global namespace - rizik kolizija
- Static/singleton pattern svuda
- Hard-coded dependencies
- Nemoguće distribuirati kao pakete
- Nemoguće plug-and-play

### Posle Refaktorisanja ✅

**Ciljno stanje:**
- Modularni sistem
- PSR-4 autoloading
- Namespace-evi svuda
- Dependency Injection
- Service Container
- Plugin sistem
- Možemo distribuirati kao pakete
- Plug-and-play funkcioniše

---

## 🔍 Detaljno Poređenje

### 1. Entry Point

#### Pre: `public/index.php` (215 linija)

```php
<?php
// Manual require_once za sve fajlove
require_once __DIR__ . '/../core/security/Security.php';
require_once __DIR__ . '/../core/http/Input.php';
require_once __DIR__ . '/../core/security/CSRF.php';
require_once __DIR__ . '/../core/security/RateLimiter.php';
require_once __DIR__ . '/../core/debug/Debug.php';
// ... 50+ require_once poziva

require_once __DIR__ . '/../core/database/Database.php';
require_once __DIR__ . '/../core/database/QueryBuilder.php';
// ... još 20+ poziva

// Manual loading of models
require_once __DIR__ . '/../mvc/models/User.php';
require_once __DIR__ . '/../mvc/models/Page.php';
// ... 15+ poziva

// Manual loading of controllers
foreach (glob(__DIR__ . '/../mvc/controllers/*.php') as $controller) {
    require_once $controller;
}

// Manual route loading
require_once __DIR__ . '/../routes/web.php';
require_once __DIR__ . '/../routes/dashboard-api.php';
require_once __DIR__ . '/../routes/api.php';

// Manual middleware registration
$router->registerMiddleware('auth', AuthMiddleware::class);
$router->registerMiddleware('cors', CorsMiddleware::class);
// ...

// Manual initialization
$request = Request::capture();
$routeCollection = new RouteCollection();
$router = new Router($routeCollection);
Translator::init($router->lang);

// Dispatch
$router->dispatch();
```

**Problemi:**
- ❌ 215 linija koda
- ❌ Manual `require_once` za svaki fajl
- ❌ Svaka promena zahteva izmenu `index.php`
- ❌ Nemoguće dodati plugin bez menjanja koda
- ❌ Tight coupling

#### Posle: `public/index.php` (15 linija)

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Foundation\Application;

// Load environment
require_once __DIR__ . '/../core/config/Env.php';
Env::load(__DIR__ . '/../.env');

// Configure error reporting
$isDebug = Env::get('APP_DEBUG', false) === true;
error_reporting($isDebug ? E_ALL : 0);
ini_set('display_errors', $isDebug ? '1' : '0');

// Bootstrap application (automatically loads plugins, routes, middleware)
$app = new Application();
$app->bootstrap();

// Dispatch
$router = $app->make(\App\Contracts\Routing\RouterInterface::class);
$router->dispatch();
```

**Prednosti:**
- ✅ 15 linija koda (93% manje!)
- ✅ PSR-4 autoloading - nema manual `require_once`
- ✅ Plugin-ovi se automatski učitavaju
- ✅ Sve je dependency injection
- ✅ Loose coupling

---

### 2. Class Loading

#### Pre: Manual Loading

```php
// core/routing/Router.php
class Router {  // Global namespace!
    // ...
}

// public/index.php
require_once __DIR__ . '/../core/routing/Router.php';
require_once __DIR__ . '/../core/routing/Route.php';
require_once __DIR__ . '/../core/routing/RouteCollection.php';
// Svaki fajl mora biti ručno uključen
```

**Problemi:**
- ❌ Global namespace - rizik kolizija
- ❌ Manual `require_once` svuda
- ❌ Teško praćenje zavisnosti
- ❌ Nemoguće učitati plugin klasu bez znanja putanje

#### Posle: PSR-4 Autoloading

```php
// src/Core/Routing/Router.php
namespace App\Core\Routing;

use App\Contracts\Routing\RouterInterface;

class Router implements RouterInterface {
    // ...
}

// public/index.php
// Nema require_once! Composer autoloader automatski učitava
use App\Core\Routing\Router;
$router = $app->make(RouterInterface::class);
```

**Prednosti:**
- ✅ Namespace-evi - nema kolizija
- ✅ PSR-4 autoloading - automatsko učitavanje
- ✅ Lako dodavanje novih klasa
- ✅ Plugin klase se automatski učitavaju

---

### 3. Dependency Injection

#### Pre: Static/Singleton Pattern

```php
// Controller.php
class Controller {
    public function __construct() {
        $this->request = Request::capture(); // Static!
    }
}

// Router.php
class Router {
    public function __construct(RouteCollection $routes) {
        // Hard-coded dependency
    }
}

// Usage
$router = new Router($routeCollection); // Cannot inject dependencies
$controller = new Controller(); // Request is hard-coded
```

**Problemi:**
- ❌ Static/singleton - teško testiranje
- ❌ Hard-coded dependencies
- ❌ Nemoguće zameniti implementaciju
- ❌ Tight coupling

#### Posle: Dependency Injection

```php
// Controller.php
namespace App\Core\MVC;

use App\Contracts\Http\RequestInterface;

class Controller {
    protected RequestInterface $request;

    public function __construct(RequestInterface $request) {
        $this->request = $request; // Injected!
    }
}

// Router.php
namespace App\Core\Routing;

use App\Core\Container\Container;

class Router {
    public function __construct(
        RouteCollection $routes,
        Container $container
    ) {
        $this->routes = $routes;
        $this->container = $container;
    }
}

// Usage
$container->singleton(RequestInterface::class, Request::class);
$container->singleton(RouterInterface::class, Router::class);
$router = $container->make(RouterInterface::class); // Dependencies auto-injected
$controller = $container->make(Controller::class); // Request auto-injected
```

**Prednosti:**
- ✅ Dependency Injection - lako testiranje
- ✅ Možemo zameniti implementaciju
- ✅ Loose coupling
- ✅ Mock-ovanje u testovima

---

### 4. Plugin System

#### Pre: Nemoguće Dodati Plugin

```php
// Da dodamo payment plugin, moramo:
// 1. Editovati index.php
require_once __DIR__ . '/../plugins/payment/PaymentController.php';
require_once __DIR__ . '/../plugins/payment/PaymentService.php';
// ...

// 2. Editovati routes/web.php
Route::post('/payment/process', [PaymentController::class, 'process']);

// 3. Editovati composer.json (ako hoćemo autoload)
// ...

// 4. Ažurirati dokumentaciju
// ...
```

**Problemi:**
- ❌ Mora se menjati core kod
- ❌ Svaki plugin zahteva izmene u više mesta
- ❌ Nemoguće ukloniti plugin bez izmena
- ❌ Teško održavanje

#### Posle: Plug-and-Play

```php
// 1. Install plugin
composer require aleksandar-pro/payment

// 2. Enable plugin
// config/plugins.php
'enabled' => [
    'payment',
],

// 3. That's it! Plugin se automatski učitava
// - Routes se registruju automatski
// - Services se registruju automatski
// - Middleware se registruje automatski
// - Migrations se pokreću automatski

// 4. Remove plugin
composer remove aleksandar-pro/payment
// Plugin se automatski uklanja
```

**Prednosti:**
- ✅ Ne menjamo core kod
- ✅ Plugin se automatski integriše
- ✅ Lako dodavanje/uklanjanje
- ✅ Modularan sistem

---

### 5. Route Registration

#### Pre: Manual Route Loading

```php
// routes/web.php
Route::get('/', [MainController::class, 'home']);
Route::get('/login', [AuthController::class, 'showLogin']);
Route::get('/register', [AuthController::class, 'showRegister']);
// ...

// routes/api.php
Route::get('/api/users', [ApiController::class, 'users']);
// ...

// public/index.php
require_once __DIR__ . '/../routes/web.php';
require_once __DIR__ . '/../routes/dashboard-api.php';
require_once __DIR__ . '/../routes/api.php';

// Problem: Da dodamo plugin rute, moramo:
// 1. Kreirati routes/payment.php
// 2. Dodati require_once u index.php
// 3. Ažurirati dokumentaciju
```

**Problemi:**
- ❌ Manual require za svaki route file
- ❌ Plugin rute moraju biti dodate ručno
- ❌ Teško praćenje svih ruta

#### Posle: Automatic Route Loading

```php
// Application.php
private function loadRoutes(): void {
    $routes = new RouteCollection();
    Route::setCollection($routes);

    // 1. Load routes from plugins (automatic!)
    $this->pluginManager->loadRoutes($routes);

    // 2. Load application routes
    if (file_exists("{$routesPath}/web.php")) {
        require "{$routesPath}/web.php";
    }

    // 3. Load dynamic routes (from database)
    $this->loadDynamicRoutes($routes);
}

// Plugin automatski registruje svoje rute
// PaymentPlugin.php
public function registerRoutes(RouteCollection $routes): void {
    $routes->group(['prefix' => '/payment'], function($routes) {
        $routes->post('/process', [PaymentController::class, 'process']);
    });
}
```

**Prednosti:**
- ✅ Automatsko učitavanje ruta iz pluginova
- ✅ Ne moramo dodavati require za svaki plugin
- ✅ Plugin sam registruje svoje rute

---

### 6. Middleware Registration

#### Pre: Manual Middleware Registration

```php
// public/index.php
$router->registerMiddleware('auth', AuthMiddleware::class);
$router->registerMiddleware('cors', CorsMiddleware::class);
$router->registerMiddleware('security', SecurityHeadersMiddleware::class);
$router->registerMiddleware('csrf', CsrfMiddleware::class);
$router->registerMiddleware('ratelimit', RateLimitMiddleware::class);

// Problem: Da dodamo plugin middleware, moramo:
// 1. Dodati u index.php
$router->registerMiddleware('payment', PaymentMiddleware::class);
// 2. Ažurirati dokumentaciju
```

**Problemi:**
- ❌ Svaki middleware mora biti ručno registrovan
- ❌ Plugin middleware mora biti dodato u core kod

#### Posle: Automatic Middleware Registration

```php
// Application.php
private function registerMiddleware(): void {
    // Core middleware
    $this->registerCoreMiddleware();

    // Plugin middleware (automatic!)
    $this->pluginManager->registerMiddleware($router);
}

// Plugin automatski registruje svoj middleware
// PaymentPlugin.php
public function registerMiddleware(Router $router): void {
    $router->registerMiddleware('payment', PaymentMiddleware::class);
}
```

**Prednosti:**
- ✅ Plugin automatski registruje middleware
- ✅ Ne menjamo core kod za plugin middleware

---

### 7. Service Registration

#### Pre: Direct Instantiation

```php
// Controller.php
class Controller {
    public function __construct() {
        $this->request = Request::capture(); // Direct!
    }
}

// AuthController.php
class AuthController extends Controller {
    public function login() {
        $authService = new AuthService(); // Direct!
        $userRepository = new UserRepository(); // Direct!
        // ...
    }
}
```

**Problemi:**
- ❌ Direct instantiation - teško testiranje
- ❌ Hard-coded dependencies
- ❌ Nemoguće zameniti implementaciju

#### Posle: Service Container

```php
// Controller.php
class Controller {
    protected RequestInterface $request;

    public function __construct(RequestInterface $request) {
        $this->request = $request; // Injected!
    }
}

// AuthController.php
class AuthController extends Controller {
    private AuthServiceInterface $authService;

    public function __construct(
        RequestInterface $request,
        AuthServiceInterface $authService
    ) {
        parent::__construct($request);
        $this->authService = $authService; // Injected!
    }

    public function login() {
        $user = $this->authService->attemptLogin(...);
    }
}

// Plugin registruje svoje servise
// UserManagementPlugin.php
public function register(Container $container): void {
    $container->singleton(
        AuthServiceInterface::class,
        AuthService::class
    );
}

// Usage
$controller = $container->make(AuthController::class);
// Dependencies automatski injected!
```

**Prednosti:**
- ✅ Service Container - dependency injection
- ✅ Možemo zameniti implementaciju
- ✅ Plugin registruje svoje servise
- ✅ Lako testiranje (mock injection)

---

### 8. Adding New Feature

#### Pre: Edit Core Code

```php
// Da dodamo payment feature:

// 1. Kreirati PaymentController.php
// 2. Kreirati PaymentService.php
// 3. Editovati index.php
require_once __DIR__ . '/../app/PaymentController.php';
require_once __DIR__ . '/../app/PaymentService.php';

// 4. Editovati routes/web.php
Route::post('/payment/process', [PaymentController::class, 'process']);

// 5. Editovati composer.json (ako hoćemo autoload)

// 6. Kreirati migration
// 7. Ažurirati dokumentaciju
```

**Problemi:**
- ❌ Mora se menjati core kod
- ❌ Teško održavanje
- ❌ Nemoguće distribuirati kao paket

#### Posle: Create Plugin

```php
// 1. Generate plugin
php artisan make:plugin payment

// 2. Implement plugin
// packages/payment/src/PaymentPlugin.php
class PaymentPlugin implements PluginInterface {
    // Implement interface methods
}

// 3. Install plugin
composer require aleksandar-pro/payment

// 4. Enable plugin
// config/plugins.php
'enabled' => ['payment'],

// 5. That's it!
```

**Prednosti:**
- ✅ Ne menjamo core kod
- ✅ Plugin je nezavisan paket
- ✅ Možemo distribuirati
- ✅ Lako održavanje

---

## 📊 Statistike

### Kod Redukcija

| Metrika | Pre | Posle | Redukcija |
|---------|-----|-------|-----------|
| `index.php` linije | 215 | 15 | **93%** |
| Manual `require_once` | 50+ | 0 | **100%** |
| Global namespace klase | 100+ | 0 | **100%** |
| Static/singleton | 30+ | 0 | **100%** |

### Maintainability

| Metrika | Pre | Posle | Poboljšanje |
|---------|-----|-------|-------------|
| Dodavanje novog feature-a | Edit core kod | Create plugin | **10x lakše** |
| Testiranje | Teško (static) | Lako (DI) | **5x lakše** |
| Distribucija | Nemoguće | Moguće | **∞** |
| Plug-and-play | Nemoguće | Moguće | **∞** |

### Scalability

| Metrika | Pre | Posle | Poboljšanje |
|---------|-----|-------|-------------|
| Broj pluginova | 0 | Unlimited | **∞** |
| Code coupling | Tight | Loose | **10x bolje** |
| Dependency management | Manual | Automatic | **10x lakše** |

---

## 🎯 Zaključak

### Pre Refaktorisanja
- ❌ Monolitska aplikacija
- ❌ Teško održavanje
- ❌ Nemoguće distribuirati kao pakete
- ❌ Nemoguće plug-and-play
- ❌ Teško testiranje

### Posle Refaktorisanja
- ✅ Modularni sistem
- ✅ Lako održavanje
- ✅ Možemo distribuirati kao pakete
- ✅ Plug-and-play funkcioniše
- ✅ Lako testiranje

**Refaktorisanje je neophodno da bi postigli plug-and-play sistem!**

---

## 📝 Next Steps

1. ✅ **Odobriti plan** - Proveriti da li plan odgovara ciljevima
2. ✅ **Kreirati feature branch** - `feature/plugin-system`
3. ✅ **Implementirati Service Container** - Najkritičniji deo
4. ✅ **Implementirati PSR-4 autoloading** - Osnova za pakete
5. ✅ **Implementirati Plugin System** - Core funkcionalnost
6. ✅ **Testirati sa prvim pluginom** - Proof-of-concept
7. ✅ **Refaktorisati postojeći kod** - Izdvojiti feature-e u pluginove
8. ✅ **Pripremiti pakete za distribuciju** - Composer paketi
9. ✅ **Dokumentacija** - Plugin development guide
10. ✅ **Release** - Publish pakete

---

Ovaj dokument jasno pokazuje zašto je refaktorisanje neophodno i kako će izgledati posle refaktorisanja. **Sve promene su fokusirane na postizanje plug-and-play sistema.**
