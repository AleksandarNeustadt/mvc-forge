# Universal CRUD System

## 📋 Pregled

Implementiran je globalni CRUD sistem sa univerzalnim toolbar-om koji omogućava:
- **Pretragu** - pretraga po svim poljima
- **Sortiranje** - sortiranje po bilo kojoj koloni
- **Filtriranje po jeziku** - prikazivanje samo podataka za odabrani jezik
- **Paginaciju** - automatska paginacija sa navigacijom
- **Konfigurabilnost** - svaki view definiše svoje kolone i opcije

## 🎯 Arhitektura

### 1. API Endpoint (`DashboardApiController`)

**Ruta:** `GET /dashboard/{app}`

**Query parametri:**
- `page` - broj stranice (default: 1)
- `limit` - broj rezultata po stranici (default: 50)
- `search` - pretraga (LIKE po svim searchable poljima)
- `sort` - kolona za sortiranje (default: 'id')
- `order` - poredak (asc/desc, default: 'desc')
- `language_id` - ID jezika za filtriranje
- `language_code` - kod jezika za filtriranje (alternativa)

**Primer:**
```
GET /dashboard/blog-posts?page=1&limit=50&search=test&sort=created_at&order=desc&language_code=de
```

### 2. PHP Helper Komponenta (`crud-table.php`)

Helper funkcija `renderCrudTable()` koja renderuje:
- Toolbar sa pretragom, filterom po jeziku, sortiranjem
- Tabelu sa konfigurabilnim kolonama
- Paginaciju

**Primer korišćenja:**
```php
require_once __DIR__ . '/../../../../helpers/crud-table.php';

renderCrudTable([
    'app' => 'blog-posts',
    'title' => 'Blog Posts',
    'description' => 'Manage your blog posts',
    'createUrl' => "/{$lang}/dashboard/blog/posts/create",
    'editUrl' => "/{$lang}/dashboard/blog/posts/{id}/edit",
    'deleteUrl' => "/{$lang}/dashboard/blog/posts/{id}/delete",
    'enableLanguageFilter' => true,
    'enableSearch' => true,
    'enableSort' => true,
    'defaultSort' => 'created_at',
    'defaultOrder' => 'desc',
    'perPage' => 50,
    'columns' => [
        ['key' => 'id', 'label' => 'ID', 'sortable' => true],
        ['key' => 'title', 'label' => 'Title', 'sortable' => true],
        // ... više kolona
    ]
]);
```

### 3. JavaScript Komponenta (`crud-table.js`)

Klasa `CrudTable` koja:
- Učitava podatke preko API-ja
- Upravlja toolbar-om (pretraga, sortiranje, filter)
- Renderuje tabelu i paginaciju
- Automatski ažurira prikaz pri promeni filtera

## 🔧 Implementacija

### Korak 1: Ažuriranje API Endpoint-a

`DashboardApiController::index()` je ažuriran da podržava:
- Filtriranje po jeziku (`language_id` ili `language_code`)
- Naprednu pretragu preko QueryBuilder-a
- Enrichment podataka sa relationships (language, author, itd.)

### Korak 2: Kreiranje Helper Komponente

`mvc/views/helpers/crud-table.php` - PHP helper koji renderuje:
- HTML strukturu tabele
- Toolbar sa svim opcijama
- JavaScript konfiguraciju

### Korak 3: Kreiranje JavaScript Komponente

`resources/js/crud-table.js` - JavaScript klasa koja:
- Komunicira sa API-jem
- Upravlja stanjem (pretraga, sortiranje, filter, paginacija)
- Renderuje tabelu i paginaciju dinamički

### Korak 4: Ažuriranje View-a

`mvc/views/pages/dashboard/blog-manager/posts/index.php` je ažuriran da koristi novi sistem.

## 📝 Primer Konfiguracije

```php
renderCrudTable([
    'app' => 'blog-posts',
    'title' => 'Blog Posts',
    'createUrl' => "/{$lang}/dashboard/blog/posts/create",
    'editUrl' => "/{$lang}/dashboard/blog/posts/{id}/edit",
    'deleteUrl' => "/{$lang}/dashboard/blog/posts/{id}/delete",
    'columns' => [
        [
            'key' => 'title',
            'label' => 'Title',
            'sortable' => true,
            'render' => function($row) {
                return '<div>' . htmlspecialchars($row['title']) . '</div>';
            }
        ],
        // ... više kolona
    ]
]);
```

## ✅ Prednosti

1. **DRY Princip** - Jedna komponenta za sve CRUD tabele
2. **Konzistentnost** - Isti toolbar i funkcionalnost svuda
3. **Fleksibilnost** - Svaki view definiše svoje kolone
4. **Performanse** - Server-side paginacija i filtriranje
5. **UX** - Automatska pretraga, sortiranje, filter po jeziku

## 🚀 Kako Koristiti

1. U view fajlu, uključi helper:
```php
require_once __DIR__ . '/../../../../helpers/crud-table.php';
```

2. Pozovi `renderCrudTable()` sa konfiguracijom

3. JavaScript će automatski učitati podatke i renderovati tabelu

## 📌 Napomene

- API endpoint mora biti dostupan na `/dashboard/{app}`
- Model mora imati `getFillable()` metodu za pretragu
- Za filtriranje po jeziku, model mora imati `language_id` polje
