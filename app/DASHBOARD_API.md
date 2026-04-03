# Dashboard RESTful API

RESTful API za CRUD operacije u dashboard-u sa podrškom za različite aplikacije i dodatne operacije.

## 📋 Struktura Ruta

### Standardne CRUD Rute

```
GET    /dashboard/{app}                    - Lista svih resursa
GET    /dashboard/{app}/{id}/show         - Prikaz jednog resursa
POST   /dashboard/{app}/create            - Kreiranje novog resursa
POST   /dashboard/{app}/{id}/update       - Ažuriranje resursa
PUT    /dashboard/{app}/{id}              - Ažuriranje resursa (alternativa)
DELETE /dashboard/{app}/{id}/delete      - Brisanje resursa
```

### Dodatne Operacije

```
POST   /dashboard/{app}/{id}/{action}     - Izvršavanje dodatnih akcija
```

## 🎯 Podržane Aplikacije

- `users` - Upravljanje korisnicima
- `blog` / `blog-posts` - Upravljanje blog postovima
- `blog-categories` - Upravljanje kategorijama
- `blog-tags` - Upravljanje tagovima
- `pages` - Upravljanje stranicama

## 📝 Primeri Korišćenja

### Lista korisnika

```http
GET /dashboard/users
```

**Query parametri:**
- `page` - Broj stranice (default: 1)
- `limit` - Broj rezultata po stranici (default: 50)
- `search` - Pretraga
- `sort` - Polje za sortiranje (default: 'id')
- `order` - Poredak (asc/desc, default: 'desc')

**Odgovor:**
```json
{
  "success": true,
  "message": "users retrieved successfully",
  "data": {
    "data": [...],
    "pagination": {
      "current_page": 1,
      "per_page": 50,
      "total": 100,
      "last_page": 2
    }
  }
}
```

### Prikaz korisnika

```http
GET /dashboard/users/1/show
```

**Odgovor:**
```json
{
  "success": true,
  "message": "users retrieved successfully",
  "data": {
    "id": 1,
    "username": "john_doe",
    "email": "john@example.com",
    ...
  }
}
```

### Kreiranje korisnika

```http
POST /dashboard/users/create
Content-Type: application/json

{
  "username": "jane_doe",
  "email": "jane@example.com",
  "password": "securepassword123",
  "first_name": "Jane",
  "last_name": "Doe"
}
```

### Ažuriranje korisnika

```http
POST /dashboard/users/1/update
Content-Type: application/json

{
  "username": "jane_doe_updated",
  "email": "jane.updated@example.com",
  "first_name": "Jane Updated"
}
```

### Brisanje korisnika

```http
DELETE /dashboard/users/1/delete
```

### Dodatne Operacije za Korisnike

#### Ban korisnika

```http
POST /dashboard/users/1/ban
```

#### Unban korisnika

```http
POST /dashboard/users/1/unban
```

#### Odobri korisnika

```http
POST /dashboard/users/1/approve
```

## 🔧 Dodavanje Nove Aplikacije

### 1. Dodaj Model u `DashboardApiController`

U `mvc/controllers/DashboardApiController.php`, dodaj u `$appModels`:

```php
private array $appModels = [
    'users' => User::class,
    'your-app' => YourModel::class,  // Dodaj ovde
];
```

### 2. Dodaj Validacione Pravila

U metodi `getValidationRules()`, dodaj pravila:

```php
'your-app' => [
    'create' => [
        'field1' => 'required|minLength:3',
        'field2' => 'required|email',
    ],
    'update' => [
        'field1' => 'minLength:3',
        'field2' => 'email',
    ],
],
```

### 3. Dodaj Dodatne Operacije (opciono)

Ako aplikacija ima dodatne operacije, dodaj u `$appActions`:

```php
private array $appActions = [
    'users' => ['ban', 'unban', 'approve'],
    'your-app' => ['custom-action-1', 'custom-action-2'],  // Dodaj ovde
];
```

Zatim implementiraj handler u metodi `handleCustomAction()`:

```php
case 'your-app':
    return $this->handleYourAppAction($action, $resource);
```

## 🔐 Autentifikacija

Sve rute zahtevaju autentifikaciju (`auth` middleware). Korisnik mora biti ulogovan.

## 📦 Odgovori

### Uspešan Odgovor

```json
{
  "success": true,
  "message": "Operation successful",
  "data": {...}
}
```

### Greška

```json
{
  "success": false,
  "message": "Error message",
  "errors": {...}  // Opciono, za validacione greške
}
```

### HTTP Status Kodovi

- `200` - Uspešno
- `201` - Kreirano
- `400` - Loš zahtev
- `403` - Zabranjeno
- `404` - Nije pronađeno
- `422` - Validaciona greška
- `500` - Greška servera

## 📁 Fajlovi

- **Kontroler:** `mvc/controllers/DashboardApiController.php`
- **Rute:** `routes/dashboard-api.php`
- **Učitavanje:** `public/index.php` (automatski)

## 🚀 Napredne Funkcionalnosti

### Pretraga

Koristi `search` query parametar za pretragu kroz sva polja resursa.

### Sortiranje

Koristi `sort` i `order` query parametre za sortiranje.

### Paginacija

Automatska paginacija sa `page` i `limit` parametrima.

