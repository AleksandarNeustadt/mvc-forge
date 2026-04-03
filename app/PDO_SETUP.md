# 🗄️ PDO Database Setup Guide

## 📋 Pregled

Projekat koristi **PDO (PHP Data Objects)** za univerzalni pristup različitim bazama podataka:
- ✅ **MySQL** / **MariaDB**
- ✅ **PostgreSQL**
- ✅ **SQLite**

## 🔧 Podržani Driveri

Proverite da li imate instalirane PDO ekstenzije:

```bash
php --ri pdo
```

Trebali biste videti:
```
PDO support => enabled
PDO drivers => mysql, odbc, pgsql, sqlite
```

## 📝 Konfiguracija .env Fajla

Dodajte sledeće u vaš `.env` fajl:

### MySQL / MariaDB

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=aleksandar_pro
DB_USERNAME=root
DB_PASSWORD=your_password
DB_CHARSET=utf8mb4
```

### PostgreSQL

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=aleksandar_pro
DB_USERNAME=postgres
DB_PASSWORD=your_password
DB_CHARSET=utf8
```

### SQLite

```env
DB_CONNECTION=sqlite
DB_DATABASE=/path/to/database.sqlite
# DB_HOST, DB_PORT, DB_USERNAME, DB_PASSWORD nisu potrebni za SQLite
DB_CHARSET=utf8
```

## 🏗️ Arhitektura

### 1. Database Class
- **Lokacija:** `core/classes/Database.php`
- **Uloga:** Connection manager, singleton pattern
- **Metode:** `getInstance()`, `table()`, `query()`, `select()`, `execute()`, itd.

### 2. QueryBuilder Class
- **Lokacija:** `core/classes/QueryBuilder.php`
- **Uloga:** Fluent API za building queries (kao Laravel)
- **Metode:** `where()`, `join()`, `orderBy()`, `get()`, `first()`, `insert()`, `update()`, `delete()`

### 3. Model Base Class
- **Lokacija:** `core/models/Model.php`
- **Uloga:** Eloquent-style ORM
- **Metode:** `find()`, `all()`, `create()`, `save()`, `delete()`, `update()`

### 4. User Model
- **Lokacija:** `core/models/User.php`
- **Uloga:** User-specific model sa helper metodama

## 💻 Primeri Korišćenja

### QueryBuilder (Fluent API)

```php
// Get all active users
$users = Database::table('users')
    ->where('active', 1)
    ->where('age', '>', 18)
    ->orderBy('name', 'ASC')
    ->get();

// Get single user
$user = Database::table('users')
    ->where('email', 'test@example.com')
    ->first();

// Insert
Database::table('users')->insert([
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

// Update
Database::table('users')
    ->where('id', 1)
    ->update(['name' => 'Jane Doe']);

// Delete
Database::table('users')
    ->where('id', 1)
    ->delete();

// Count
$count = Database::table('users')
    ->where('active', 1)
    ->count();

// Complex query
$users = Database::table('users')
    ->select(['id', 'name', 'email'])
    ->join('profiles', 'users.id', '=', 'profiles.user_id')
    ->where('users.active', 1)
    ->whereIn('users.role', ['admin', 'moderator'])
    ->groupBy('users.id')
    ->having('COUNT(profiles.id)', '>', 1)
    ->orderBy('users.created_at', 'DESC')
    ->limit(10)
    ->get();
```

### Model (Eloquent-style)

```php
// Find by ID
$user = User::find(1);
$user = User::findOrFail(1); // Throws exception if not found

// Get all
$users = User::all();

// Get first
$user = User::first();

// Find by custom field
$user = User::findByEmail('test@example.com');
$user = User::findByUsername('johndoe');
$user = User::findBySlug('john-doe');

// Create
$user = User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => 'secret'
]);

// Or create instance and save
$user = new User();
$user->name = 'John Doe';
$user->email = 'john@example.com';
$user->save();

// Update
$user = User::find(1);
$user->name = 'Jane Doe';
$user->save();

// Or update directly
$user = User::find(1);
$user->update(['name' => 'Jane Doe']);

// Delete
$user = User::find(1);
$user->delete();

// Query with conditions
$users = User::query()
    ->where('active', 1)
    ->where('age', '>', 18)
    ->orderBy('name')
    ->get();
```

### User Model Specific Methods

```php
// Create user with hashed password
$user = User::createUser([
    'username' => 'johndoe',
    'email' => 'john@example.com',
    'password' => 'secret123',
    'first_name' => 'John',
    'last_name' => 'Doe'
]);

// Check if exists
if (User::emailExists('test@example.com')) {
    // Email already exists
}

if (User::usernameExists('johndoe')) {
    // Username already exists
}

// Verify password
$user = User::findByEmail('test@example.com');
if ($user && $user->verifyPassword('secret123')) {
    // Password correct
}

// Update password
$user->updatePassword('newpassword');

// Update last login
$user->updateLastLogin($_SERVER['REMOTE_ADDR']);

// Get full name
$fullName = $user->getFullName();
```

## 🔍 Debugging Queries

Koristite `dd()` i `dp()` funkcije za debugging:

```php
// Debug query builder
$query = Database::table('users')->where('active', 1);
dd($query->toRawSql()); // Prikaži SQL sa bindings

// Debug model
$user = User::find(1);
dd($user->toArray()); // Prikaži model kao array

// Debug query results
$users = User::all();
dp($users); // Prikaži ali nastavi execution
```

## ✅ Test Konekcije

Kreirajte test fajl `test-pdo.php`:

```php
<?php
require_once 'vendor/autoload.php';
require_once 'core/classes/Env.php';
require_once 'core/classes/Database.php';

Env::load(__DIR__ . '/.env');

if (Database::test()) {
    echo "✅ Database connection successful!\n";
} else {
    echo "❌ Database connection failed!\n";
}
```

Pokrenite:
```bash
php test-pdo.php
```

## 📊 Database Schema Primer

### MySQL/MariaDB

```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    slug VARCHAR(255) UNIQUE,
    avatar VARCHAR(255),
    newsletter BOOLEAN DEFAULT FALSE,
    email_verified_at TIMESTAMP NULL,
    last_login_at TIMESTAMP NULL,
    last_login_ip VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_username (username),
    INDEX idx_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### PostgreSQL

```sql
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    slug VARCHAR(255) UNIQUE,
    avatar VARCHAR(255),
    newsletter BOOLEAN DEFAULT FALSE,
    email_verified_at TIMESTAMP NULL,
    last_login_at TIMESTAMP NULL,
    last_login_ip VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_email ON users(email);
CREATE INDEX idx_username ON users(username);
CREATE INDEX idx_slug ON users(slug);
```

### SQLite

```sql
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    slug VARCHAR(255) UNIQUE,
    avatar VARCHAR(255),
    newsletter INTEGER DEFAULT 0,
    email_verified_at INTEGER NULL,
    last_login_at INTEGER NULL,
    last_login_ip VARCHAR(45),
    created_at INTEGER DEFAULT (strftime('%s', 'now')),
    updated_at INTEGER DEFAULT (strftime('%s', 'now'))
);

CREATE INDEX idx_email ON users(email);
CREATE INDEX idx_username ON users(username);
CREATE INDEX idx_slug ON users(slug);
```

## 🆘 Troubleshooting

### Problem: "SQLSTATE[HY000] [2002] Connection refused"

**Rešenje:**
- Proverite da li je database server pokrenut
- Proverite `DB_HOST` i `DB_PORT` u `.env`
- Proverite firewall settings

### Problem: "SQLSTATE[HY000] [1045] Access denied"

**Rešenje:**
- Proverite `DB_USERNAME` i `DB_PASSWORD` u `.env`
- Proverite database user permissions

### Problem: "SQLSTATE[HY000] [1049] Unknown database"

**Rešenje:**
- Kreirajte database pre konekcije
- Proverite `DB_DATABASE` ime u `.env`

---

## 📚 Dodatni Resursi

- [PDO Documentation](https://www.php.net/manual/en/book.pdo.php)
- [Query Builder Examples](core/classes/QueryBuilder.php)
- [Model Examples](core/models/Model.php)

