# 📁 Finalna Struktura `core/classes/`

## ✅ Implementirana Struktura

```
core/classes/
├── dashboard/
│   └── database/
│       ├── DatabaseTableBuilder.php  ✅ (za kreiranje tabela u bazi)
│       └── DatabaseBuilder.php       ✅ (za upravljanje bazom)
│
├── view/
│   ├── FormBuilder.php               ✅ (za HTML forme u view-ovima)
│   ├── Form.php                      ✅ (Form facade)
│   └── TableBuilder.php              ✅ (za HTML tabele u view-ovima)
│
├── security/
│   ├── Security.php                  ✅
│   ├── CSRF.php                      ✅
│   └── RateLimiter.php               ✅
│
├── mvc/
│   └── Controller.php                ✅ (base Controller klasa)
│
└── [ostale klase]/
    ├── Database.php                  ✅ (PDO wrapper)
    ├── QueryBuilder.php              ✅ (fluent query builder)
    ├── Router.php                    ✅ (routing)
    ├── Route.php                     ✅
    ├── RouteCollection.php           ✅
    ├── RouteRegistrar.php            ✅
    ├── Request.php                   ✅
    ├── Translator.php                ✅
    ├── Env.php                       ✅
    ├── Input.php                     ✅
    └── Debug.php                     ✅
```

## 📝 Objašnjenje

### 1. Dashboard/Database ✅
- **DatabaseTableBuilder** - za kreiranje tabela u bazi (migracije)
- **DatabaseBuilder** - za upravljanje bazom (info, kolone, itd.)

### 2. View ✅
- **FormBuilder** - za kreiranje HTML formi (Login, Register, Dashboard forme)
- **Form** - Form facade za lakše korišćenje
- **TableBuilder** - za listanje podataka u HTML tabelama
- **PageBuilder** - (FUTURE) za dinamičko kreiranje stranica

### 3. Security ✅
- Sve security klase na jednom mestu

### 4. MVC ✅
- **Controller.php** - base klasa za sve controllere
- **Model.php** - ostaje u `core/models/Model.php` (jer su konkretni modeli tamo)
- **View.php** - (FUTURE) base klasa za view ako bude potrebna

**Prednost:** Omogućava da klase koje nasleđuju ove core klase budu u:
- `core/controllers/` (konkretni controlleri)
- `core/models/` (konkretni modeli)
- `core/views/` (view fajlovi)

### 5. Ostale klase
- Core funkcionalnosti: Database, Router, Request, itd.
- Ostaju u `core/classes/` root-u

## 🎯 Lokacije Klasa

### FormBuilder i TableBuilder za View:
- ✅ `core/classes/view/FormBuilder.php`
- ✅ `core/classes/view/Form.php`
- ✅ `core/classes/view/TableBuilder.php`

### Korišćenje u View fajlovima:
```php
// Form
echo Form::open('/login', 'POST')
    ->email('email', 'Email')->required()
    ->password('password', 'Password')->required()
    ->submit('Login')
    ->close();

// Table
echo Table::open()
    ->header(['Name', 'Email', 'Actions'])
    ->row(['John', 'john@example.com', '<button>Edit</button>'])
    ->close();
```

## ✅ Status

Sve je implementirano i spremno za korišćenje!

