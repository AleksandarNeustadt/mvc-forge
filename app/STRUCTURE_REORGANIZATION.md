# 📁 Reorganizacija Strukture Projekta

## 🎯 Cilj

Postići logičniju i čitljiviju strukturu fajlova organizovanu po funkcionalnosti.

## 📂 Nova Struktura

```
core/classes/
├── dashboard/
│   └── database/
│       ├── DatabaseTableBuilder.php  (za kreiranje tabela u bazi)
│       └── DatabaseBuilder.php      (za upravljanje bazom)
├── view/
│   ├── FormBuilder.php              (za HTML forme)
│   ├── Form.php                     (Form facade)
│   └── TableBuilder.php             (za HTML tabele u view-u) - NOVO
├── security/
│   ├── Security.php
│   ├── CSRF.php
│   └── RateLimiter.php
└── cms/
    └── (future CMS classes)
```

## 🔄 Promene

### 1. Dashboard/Database klase
- `TableBuilder.php` → `core/classes/dashboard/database/DatabaseTableBuilder.php`
- `DatabaseBuilder.php` → `core/classes/dashboard/database/DatabaseBuilder.php`

### 2. View Builders
- `FormBuilder.php` → `core/classes/view/FormBuilder.php`
- `Form.php` → `core/classes/view/Form.php`
- **NOVO:** `TableBuilder.php` → `core/classes/view/TableBuilder.php` (za HTML tabele)

### 3. Security klase
- `Security.php` → `core/classes/security/Security.php`
- `CSRF.php` → `core/classes/security/CSRF.php`
- `RateLimiter.php` → `core/classes/security/RateLimiter.php`

## 📝 Fajlovi za ažuriranje

1. `public/index.php` - ažurirati require_once putanje
2. `core/controllers/DashboardController.php` - ažurirati reference
3. `core/controllers/AuthController.php` - ažurirati reference
4. `setup-initial-database.php` - ažurirati putanje
5. `core/classes/database/migrations/*.php` - ažurirati putanje
6. `core/classes/database/seeds/*.php` - ažurirati putanje
7. `core/helpers.php` - proveriti reference
8. `core/middleware/*.php` - proveriti reference

## ⚠️ Važno

- `TableBuilder` za bazu → `DatabaseTableBuilder`
- `TableBuilder` za view → novi `TableBuilder` (za HTML tabele)

