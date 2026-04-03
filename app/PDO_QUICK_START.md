# ⚡ Quick Start: PDO Database Setup

## ✅ Šta je Gotovo

1. ✅ **Database Class** - PDO connection manager
2. ✅ **QueryBuilder** - Fluent API (kao Laravel)
3. ✅ **Model Base Class** - Eloquent-style ORM
4. ✅ **User Model** - Spreman za korišćenje
5. ✅ **dd() / dp()** - Debug funkcije već postoje!

## 📝 Korak 1: Konfigurišite .env

Dodajte u vaš `.env` fajl (zamenite sa vašim podacima):

### Za MySQL:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=aleksandar_pro
DB_USERNAME=root
DB_PASSWORD=your_password
DB_CHARSET=utf8mb4
```

### Za PostgreSQL:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=aleksandar_pro
DB_USERNAME=postgres
DB_PASSWORD=your_password
DB_CHARSET=utf8
```

### Za SQLite:

```env
DB_CONNECTION=sqlite
DB_DATABASE=/var/www/aleksandar.pro/database.sqlite
DB_CHARSET=utf8
```

## ✅ Korak 2: Test Konekcije

```bash
php test-pdo.php
```

## 📊 Korak 3: Kreirajte Database Schema

Koristite SQL iz `PDO_SETUP.md` da kreirate `users` tabelu.

## 💻 Primeri Korišćenja

### QueryBuilder

```php
// Get all active users
$users = Database::table('users')
    ->where('active', 1)
    ->get();

// Find user
$user = Database::table('users')
    ->where('email', 'test@example.com')
    ->first();
```

### Model (Eloquent-style)

```php
// Find user
$user = User::find(1);
$user = User::findByEmail('test@example.com');

// Create user
$user = User::createUser([
    'username' => 'johndoe',
    'email' => 'john@example.com',
    'password' => 'secret'
]);

// Update
$user->name = 'Jane';
$user->save();
```

### Debugging

```php
// Debug query
$query = Database::table('users')->where('active', 1);
dd($query->toRawSql());

// Debug model
$user = User::find(1);
dd($user->toArray());
```

## 📚 Detaljna Dokumentacija

Pogledajte `PDO_SETUP.md` za kompletnu dokumentaciju!

