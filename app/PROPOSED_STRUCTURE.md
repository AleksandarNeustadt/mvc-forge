# 📁 Predložena Struktura `core/classes/`

## 🎯 Vaša Ideja - Analiza

Vaša ideja je **odlična** i logična! Evo predloga kako bi to trebalo da izgleda:

## 📂 Predložena Struktura

```
core/classes/
├── dashboard/
│   └── database/
│       ├── DatabaseTableBuilder.php  (za kreiranje tabela u bazi)
│       └── DatabaseBuilder.php   (za upravljanje bazom)
│
├── view/
│   ├── FormBuilder.php               (za HTML forme u view-ovima)
│   ├── Form.php                      (Form facade)
│   ├── TableBuilder.php              (za HTML tabele u view-ovima)
│   └── PageBuilder.php               (za dinamičko kreiranje stranica - FUTURE)
│
├── security/
│   ├── Security.php
│   ├── CSRF.php
│   └── RateLimiter.php
│
├── mvc/
│   ├── Model.php                     (base Model klasa)
│   ├── Controller.php                (base Controller klasa)
│   └── View.php                      (base View klasa - ako postoji)
│
└── [ostale klase]/
    ├── Database.php                  (PDO wrapper)
    ├── QueryBuilder.php              (fluent query builder)
    ├── Router.php                    (routing)
    ├── Route.php
    ├── RouteCollection.php
    ├── RouteRegistrar.php
    ├── Request.php
    ├── Translator.php
    ├── Env.php
    ├── Input.php
    └── Debug.php
```

## 💡 Objašnjenje

### 1. Dashboard/Database ✅
- Sve klase za rad sa bazom podataka
- `DatabaseTableBuilder` - za kreiranje tabela (migracije)
- `DatabaseBuilder` - za upravljanje bazom (info, kolone, itd.)

### 2. View ✅
- **FormBuilder** - za kreiranje HTML formi (Login, Register, Dashboard forme)
- **TableBuilder** - za listanje podataka u HTML tabelama
- **PageBuilder** - za dinamičko kreiranje stranica (FUTURE)

### 3. Security ✅
- Sve security klase na jednom mestu

### 4. MVC (NOVO)
- **Model.php** - base klasa za sve modele
- **Controller.php** - base klasa za sve controllere
- **View.php** - base klasa za view (ako postoji)

**Prednost:** Omogućava da klase koje nasleđuju ove core klase budu u `core/models/`, `core/controllers/`, `core/views/` (van `core/classes/`), što je čistije.

### 5. Ostale klase
- Core funkcionalnosti: Database, Router, Request, itd.
- Ostaju u `core/classes/` root-u

## 🔄 Šta treba uraditi:

1. ✅ Dashboard/Database - već postoji
2. ✅ View - TableBuilder postoji, treba dodati FormBuilder i Form
3. ✅ Security - već postoji
4. ⚠️ MVC - treba kreirati folder i premestiti Model.php i Controller.php
5. ✅ Ostale klase - već su tu

## ❓ Pitanje:

Gde je trenutno `Model.php`?
- Ako je u `core/models/Model.php` - možda je bolje ostaviti tamo?
- Ili premestiti u `core/classes/mvc/Model.php`?

**Predlog:** Ostaviti `Model.php` u `core/models/` jer su konkretni modeli (`User.php`) tamo, ali možemo dodati alias ili referencu.

