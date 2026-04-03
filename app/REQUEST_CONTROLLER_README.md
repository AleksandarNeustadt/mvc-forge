# Request Controller Documentation

## Pregled

Implementiran je kompletan Request/Response sistem za rukovanje CRUD operacijama sa pripremom za dodavanje bezbednosnih funkcionalnosti.

## Nove klase

### 1. Request (`core/classes/Request.php`)

Centralna klasa za rukovanje HTTP zahtevima.

#### Osnovne metode:

```php
$request = Request::capture();

// HTTP metode
$request->method();           // GET, POST, PUT, DELETE, etc.
$request->isGet();            // true/false
$request->isPost();           // true/false
$request->isPut();            // true/false
$request->isDelete();         // true/false
$request->isAjax();           // Provera AJAX zahteva
$request->wantsJson();        // Provera da li klijent očekuje JSON

// Pristup podacima
$request->all();              // Svi input podaci
$request->input('key');       // Specifična vrednost
$request->input('key', 'default'); // Sa default vrednošću
$request->has('key');         // Provera da li postoji
$request->only(['name', 'email']); // Samo specifična polja
$request->except(['password']);    // Sve osim...

// Query i POST parametri
$request->query('page', 1);   // GET parametar
$request->post('name');       // POST parametar

// Fajlovi
$request->hasFile('avatar');  // Provera upload-a
$request->file('avatar');     // Pristup fajlu
$request->files();            // Svi fajlovi

// Headers
$request->header('Content-Type');
$request->headers();          // Svi headers

// Ostalo
$request->ip();               // Klijent IP
$request->userAgent();        // User agent string
$request->uri();              // URI path
$request->rawBody();          // Raw request body
```

#### Validacija:

```php
$errors = $request->validate([
    'name' => 'required|min:3|max:50',
    'email' => 'required|email',
    'age' => 'numeric',
    'website' => 'url'
]);

if (!empty($errors)) {
    // Ima grešaka
    // $errors = ['name' => ['Field name is required'], ...]
}
```

Dostupna validaciona pravila:
- `required` - Polje je obavezno
- `email` - Validna email adresa
- `min:n` - Minimalna dužina
- `max:n` - Maksimalna dužina
- `numeric` - Numerička vrednost
- `url` - Validna URL adresa

#### Sanitizacija:

```php
$clean = $request->sanitize('name', 'string');  // HTML escape
$email = $request->sanitize('email', 'email');  // Email filter
$url = $request->sanitize('url', 'url');        // URL filter
$number = $request->sanitize('age', 'int');     // Integer filter
$float = $request->sanitize('price', 'float');  // Float filter
```

---

### 2. Controller (`core/classes/Controller.php`)

Bazna klasa za sve kontrolere sa metodama za JSON odgovore i view rendering.

#### JSON odgovori:

```php
class MyController extends Controller {
    public function example() {
        // Opšti JSON odgovor
        $this->json(['data' => 'value'], 200);

        // Success odgovor
        $this->success($data, 'Operation successful', 201);

        // Error odgovor
        $this->error('Something went wrong', $errors, 400);

        // Validation error
        $this->validationError($errors);
    }
}
```

#### View rendering:

```php
$this->view('users/index', ['users' => $users]);
```

#### Validacija u kontroleru:

```php
$validation = $this->validate([
    'name' => 'required|min:3',
    'email' => 'required|email'
]);

if ($validation !== true) {
    $this->validationError($validation);
}
```

#### Redirekcija:

```php
$this->redirect('/home');
$this->redirectBack();
```

#### Abort:

```php
$this->abort(404, 'Resource not found');
```

---

### 3. ResourceController (`core/controllers/ResourceController.php`)

Primer CRUD kontrolera koji demonstrira najbolje prakse.

#### REST Endpoints:

```php
GET    /resource          -> index()     - Lista svih resursa
GET    /resource/{id}     -> show($id)   - Pojedinačni resurs
POST   /resource          -> store()     - Kreiranje novog
PUT    /resource/{id}     -> update($id) - Ažuriranje
DELETE /resource/{id}     -> destroy($id)- Brisanje
POST   /resource/bulk     -> bulkStore() - Bulk kreiranje
POST   /resource/upload   -> upload()    - File upload
```

#### Primer korišćenja u kontroleru:

```php
class UserController extends Controller {
    public function store() {
        // 1. Validacija
        $validation = $this->validate([
            'name' => 'required|min:3|max:100',
            'email' => 'required|email',
            'age' => 'numeric'
        ]);

        if ($validation !== true) {
            $this->validationError($validation);
        }

        // 2. Sanitizacija
        $name = $this->request->sanitize('name', 'string');
        $email = $this->request->sanitize('email', 'email');

        // 3. Samo potrebna polja
        $data = $this->request->only(['name', 'email', 'age']);

        // 4. Sačuvaj u bazu (TODO: implementirati)
        // $user = User::create($data);

        // 5. Odgovor
        $this->success($data, 'User created successfully', 201);
    }

    public function update($id) {
        // Proveri da li resurs postoji
        // $user = User::find($id);
        // if (!$user) {
        //     $this->abort(404, 'User not found');
        // }

        $validation = $this->validate([
            'name' => 'min:3|max:100',
            'email' => 'email'
        ]);

        if ($validation !== true) {
            $this->validationError($validation);
        }

        $data = $this->request->only(['name', 'email', 'age']);

        // Ažuriraj u bazi
        // $user->update($data);

        $this->success($data, 'User updated successfully');
    }

    public function uploadAvatar() {
        if (!$this->request->hasFile('avatar')) {
            $this->error('No file uploaded', null, 400);
        }

        $file = $this->request->file('avatar');

        // Validacija fajla
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        if (!in_array($file['type'], $allowedTypes)) {
            $this->error('Invalid file type');
        }

        if ($file['size'] > $maxSize) {
            $this->error('File too large');
        }

        // Sačuvaj fajl
        $destination = __DIR__ . '/../../storage/uploads/' . basename($file['name']);
        move_uploaded_file($file['tmp_name'], $destination);

        $this->success(['filename' => $file['name']], 'Avatar uploaded');
    }
}
```

---

## Testiranje

Otvori `http://localhost/test-request.php` u browser-u za interaktivno testiranje:

- Pregled trenutnog zahteva
- Test validacije
- Test JSON zahteva
- Test file upload-a

Alternativno, testiraj sa cURL:

```bash
# GET sa query parametrima
curl "http://localhost/test-request.php?page=1&search=test"

# POST sa JSON-om
curl -X POST http://localhost/test-request.php \
  -H "Content-Type: application/json" \
  -H "X-Requested-With: XMLHttpRequest" \
  -d '{"name":"John","email":"john@example.com","age":30}'

# POST form data
curl -X POST http://localhost/test-request.php \
  -d "name=John Doe&email=john@example.com&age=30"

# File upload
curl -X POST http://localhost/test-request.php \
  -F "testfile=@/path/to/file.jpg"
```

---

## Sledeći koraci za bezbednost

Kada budeš spreman da dodaš bezbednost, evo preporuka:

### 1. CSRF Protection
```php
// U Request klasu dodaj:
public function csrfToken() { ... }
public function verifyCsrfToken() { ... }
```

### 2. Authentication Middleware
```php
class AuthMiddleware {
    public function handle($request) {
        // Proveri JWT token ili session
    }
}
```

### 3. Rate Limiting
```php
class RateLimiter {
    public function check($ip, $limit = 60) {
        // Proveri broj zahteva po minuti
    }
}
```

### 4. Input Sanitization (već implementirano)
- HTML escape (već radi)
- SQL injection prevention (koristi prepared statements)
- XSS prevention (već ima htmlspecialchars)

### 5. Authorization
```php
class Policy {
    public function canEdit($user, $resource) {
        return $user->id === $resource->user_id;
    }
}
```

---

## Integracija sa postojećim Router-om

Za potpunu integraciju, ažuriraj `Router` klasu da podržava:
- HTTP metode (ne samo GET)
- Dinamičke parametre (npr. `/user/{id}`)
- Controller routing umesto view routing

Primer:

```php
// U Router klasi
private $routes = [
    'GET /api/users' => 'UserController@index',
    'POST /api/users' => 'UserController@store',
    'GET /api/users/{id}' => 'UserController@show',
    'PUT /api/users/{id}' => 'UserController@update',
    'DELETE /api/users/{id}' => 'UserController@destroy',
];
```

---

## Pitanja ili problemi?

Sistem je spreman za dodavanje:
- Database layer (Model klase)
- Authentication (JWT, Sessions)
- CSRF protection
- Rate limiting
- Authorization policies
- Middleware system

Sve bezbednosne funkcionalnosti mogu se lako dodati kao nova "ravan" iznad postojećeg Request/Controller sistema.
