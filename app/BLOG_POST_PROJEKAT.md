# Custom PHP MVC Framework - Opis Projekta

## Pregled

Ovaj projekat predstavlja **sopstveni PHP MVC framework** napravljen od nule, dizajniran za izgradnju modernih web aplikacija sa fokusom na bezbednost, skalabilnost i developer experience. Framework kombinuje najbolje prakse iz popularnih framework-ova poput Laravel-a, ali sa jednostavnijom arhitekturom prilagođenom specifičnim potrebama.

## Tehnologije

### Backend
- **PHP 8.1+** - Moderna verzija PHP-a sa tipiziranim svojstvima i match expressions
- **PDO** - Sigurna komunikacija sa bazom podataka kroz prepared statements
- **MongoDB** - Podrška za NoSQL bazu podataka (opciono)
- **Composer** - Upravljanje zavisnostima i PSR-4 autoloading

### Frontend
- **Vite 6.0** - Moderni build tool za brzu razvojnu i produkcijsku kompilaciju
- **TailwindCSS 4.1** - Utility-first CSS framework za brzu izgradnju UI-ja
- **Ionicons 7.0** - Biblioteka ikona
- **Flag Icons** - Za prikaz zastava u language selector-u
- **Vanilla JavaScript** - Bez teških framework-ova, čist i performantan kod

### Baza Podataka
- **MySQL/MariaDB** - Relaciona baza podataka sa migracijama
- **MongoDB** - NoSQL podrška za fleksibilno skladištenje podataka

## Arhitektura

### MVC Pattern
Framework koristi čistu **Model-View-Controller** arhitekturu:

- **Models** - Eloquent-style ORM sa automatskim timestamps, fillable fields, i relationship podrškom
- **Views** - PHP template sistem sa layout inheritance i komponentama
- **Controllers** - Base Controller klasa sa helper metodama za JSON response, validaciju, i redirect

### Routing Sistem
- **Fluent API** - Laravel-style route definisanje
- **Named Routes** - Generisanje URL-ova preko imena ruta
- **Route Groups** - Grupisanje ruta sa zajedničkim middleware-om
- **Route Constraints** - Regex validacija parametara
- **Dynamic Routes** - Automatsko registrovanje ruta iz baze podataka
- **Language-aware Routing** - Automatska detekcija i preusmeravanje na osnovu jezika

### Middleware Pipeline
Implementiran **Russian Doll Pattern** za middleware pipeline:

- **AuthMiddleware** - Provera autentifikacije korisnika
- **CsrfMiddleware** - Zaštita od CSRF napada
- **RateLimitMiddleware** - Ograničavanje broja zahteva
- **SecurityHeadersMiddleware** - Postavljanje security HTTP headera
- **PermissionMiddleware** - Provera dozvola korisnika
- **CorsMiddleware** - Cross-Origin Resource Sharing podrška

## Bezbednost

Framework implementira višeslojnu bezbednosnu strategiju:

### CSRF Zaštita
- Automatska generacija i validacija CSRF tokena
- Zaštita svih POST/PUT/DELETE zahteva
- Middleware integracija

### XSS Zaštita
- `Security::escape()` metoda za escaping HTML output-a
- Automatska sanitizacija korisničkog input-a
- Različiti tipovi sanitizacije (string, email, URL, HTML, alphanumeric, slug)

### SQL Injection Zaštita
- **QueryBuilder** sa prepared statements
- PDO wrapper sa automatskim escaping-om
- Fluent API za bezbedno građenje upita

### Security Headers
- **Content Security Policy (CSP)** - Kontrola izvora resursa
- **X-Frame-Options** - Zaštita od clickjacking-a
- **X-Content-Type-Options** - Sprečavanje MIME type sniffing-a
- **Strict-Transport-Security** - Forsiranje HTTPS-a
- **Referrer-Policy** - Kontrola referrer informacija
- **Permissions-Policy** - Onemogućavanje nepotrebnih browser funkcionalnosti

### Rate Limiting
- Ograničavanje broja zahteva po IP adresi
- Konfigurabilni limiti po rutama
- File-based storage za rate limit podatke

### Session Security
- HttpOnly cookies - sprečavanje XSS pristupa
- Secure cookies - samo preko HTTPS-a
- SameSite cookies - zaštita od CSRF
- Konfigurabilni session lifetime

### Password Security
- Bcrypt hashing sa automatskim salt-om
- Password verification funkcije
- Secure password generation

### Input Validacija
- Kompletan sistem validacije sa više validatora:
  - Tipovi: required, nullable, string, integer, float, boolean, array, email, url, ip
  - Dužina: min, max, minLength, maxLength, length
  - Format: regex, alpha, alphaNumeric, numeric
  - Datum: date, dateTime, before, after
  - Baza: exists, unique, in, notIn
  - Custom validatori

## Baza Podataka

### Query Builder
Fluent API za građenje SQL upita:
- WHERE klauzule sa različitim operatorima
- JOIN operacije
- ORDER BY, GROUP BY, HAVING
- LIMIT i OFFSET
- Aggregation funkcije (COUNT, SUM, AVG, MAX, MIN)
- Subqueries podrška

### Migracije
Sistem migracija za verzionisanje baze podataka:
- Kreiranje tabela sa različitim tipovima kolona
- Dodavanje/uklanjanje kolona
- Indeksi i foreign keys
- Rollback mogućnost

### ORM (Object-Relational Mapping)
Eloquent-style Model klasa:
- Automatski timestamps (created_at, updated_at)
- Fillable i hidden fields
- Type casting (int, float, bool, json, date)
- Relationship podrška (spremano za implementaciju)
- Mass assignment zaštita

### Database Builder
Alati za upravljanje bazom:
- Kreiranje tabela kroz UI
- Dodavanje/uklanjanje kolona
- Pregled strukture tabele
- Database info i statistika

## Multi-jezička Podrška

### 30 Podržanih Jezika
Framework podržava 30 jezika:
- Evropski: srpski, engleski, nemački, francuski, španski, italijanski, portugalski, holandski, poljski, ruski, ukrajinski, češki, mađarski, grčki, rumunski, hrvatski, bugarski, slovački, švedski, danski, norveški, finski, litvanski, estonski, latvijski, slovenački
- Azijski: kineski, japanski, korejski
- Turski

### Geo-location Detekcija
- Automatska detekcija jezika na osnovu IP adrese korisnika
- Preusmeravanje na odgovarajući jezik u URL-u
- Session caching za optimizaciju API poziva

### Translation Sistem
- JSONC format za translation fajlove (podrška za komentare)
- Nested keys za organizaciju prevoda
- Fallback na default jezik (engleski)
- Helper funkcija `__()` za lakše korišćenje
- Router integracija za automatsko učitavanje jezika

### URL Struktura
- Language prefix u URL-u (`/sr/`, `/en/`, itd.)
- SEO-friendly URL-ovi
- Automatsko preusmeravanje ako jezik nije prisutan

## Dashboard i Administracija

### User Management
Kompletan sistem za upravljanje korisnicima:
- Lista svih korisnika sa filtriranjem
- Kreiranje novih korisnika
- Editovanje korisničkih podataka
- Brisanje korisnika (sa zaštitom od brisanja sopstvenog naloga)
- Ban/Unban funkcionalnost
- Approval sistem za nove korisnike
- Pregled korisničkih dozvola i uloga

### Role-Based Access Control (RBAC)
Sistem uloga i dozvola:
- **Roles** - Uloge korisnika (admin, moderator, user, itd.)
- **Permissions** - Granularne dozvole (users.create, users.edit, system.dashboard, itd.)
- **Role-Permission Pivot** - Many-to-many veza između uloga i dozvola
- **User-Role Pivot** - Korisnici mogu imati više uloga
- **Permission Middleware** - Automatska provera dozvola na rutama
- **Permission Registry** - Centralizovani registar svih dozvola

### Database Management UI
Vizuelni interfejs za upravljanje bazom:
- Pregled svih tabela
- Kreiranje novih tabela kroz formu
- Dodavanje kolona u postojeće tabele
- Brisanje tabela i kolona
- Pregled podataka u tabelama
- Database statistika

### Page Manager
Sistem za upravljanje stranicama:
- Dinamičko kreiranje stranica
- SEO-friendly URL-ovi
- Multi-jezički sadržaj
- Status management (draft, published)

### Blog Sistem
Kompletan blog modul:
- Blog kategorije
- Blog postovi sa rich text editor-om
- Tag sistem
- Kategorizacija postova
- SEO optimizacija

## Developer Experience

### Helper Funkcije
Set korisnih globalnih funkcija:
- `dd()` - Dump and die za debugging
- `e()` - Escape HTML output
- `input()` - Pristup request podacima
- `route()` - Generisanje URL-ova iz named routes
- `__()` - Translation helper
- `old()` - Pristup starim input vrednostima nakon validacije

### Error Handling
- **ExceptionHandler** - Centralizovano rukovanje exception-ima
- Strukturisano logovanje u JSON formatu
- User-friendly error pages (404, 500, 403)
- JSON response za API zahteve
- Debug mode sa detaljnim informacijama

### Debugging
- **Debug klasa** - Centralizovani debugging alati
- Error logging u fajlove
- Access logging
- Request/Response logging

### Form Builder
Helper klase za kreiranje formi:
- **FormBuilder** - Fluent API za HTML forme
- **Form Facade** - Jednostavniji pristup
- Automatska CSRF token integracija
- Validacija i error display
- Old input support

### Table Builder
Helper klasa za prikaz podataka:
- **TableBuilder** - Kreiranje HTML tabela
- Sortiranje kolona
- Paginacija (spremano za implementaciju)
- Responsive dizajn

## Servisi

### GeoLocation
- Detekcija lokacije na osnovu IP adrese
- Language detection
- Country detection
- API integracija za geo podatke

### Upload Manager
- Sigurno upload-ovanje fajlova
- Validacija tipa i veličine
- Secure file storage
- Avatar upload podrška

## Frontend Features

### Modern UI
- **TailwindCSS** - Utility-first pristup za brzu izgradnju
- **Dark Theme** - Tamna tema sa custom color picker-om
- **Responsive Design** - Mobile-first pristup
- **Smooth Animations** - CSS transitions i animations

### Komponente
- Language selector sa zastavama
- Theme switcher sa localStorage
- Navigation sa active state
- Form komponente sa validacijom
- Modal dialogs
- Toast notifications (spremano za implementaciju)

### JavaScript Features
- Starfield animacija za vizuelni efekat
- AJAX podrška za dinamičke zahteve
- Form validation na frontend-u
- Smooth scrolling

## Performanse

### Optimizacije
- Vite build za minifikaciju CSS i JS
- Asset optimization
- Database query optimization kroz QueryBuilder
- Session caching za geo-location
- Prepared statements za brže izvršavanje

### Skalabilnost
- Modularna arhitektura za lako dodavanje funkcionalnosti
- Middleware pipeline za fleksibilno proširenje
- Service layer za business logiku
- Separation of concerns

## Buduća Proširenja

Framework je dizajniran sa mogućnostima za proširenje:
- Email sistem sa SMTP podrškom
- Caching sistem (file-based, sa mogućnošću Redis integracije)
- Event/Listener sistem
- Queue sistem za asinhrone zadatke
- API dokumentacija (OpenAPI/Swagger)
- Unit i integration testovi
- Docker containerizacija

## Zaključak

Ovaj custom PHP MVC framework predstavlja kompletan sistem za izgradnju modernih web aplikacija sa fokusom na bezbednost, performanse i developer experience. Kombinuje najbolje prakse iz industrije sa jednostavnom arhitekturom koja omogućava brzu razvojnu brzinu bez kompromisa u kvalitetu koda.

Framework je pogodan za:
- Lične projekte i portfolije
- Srednje i velike web aplikacije
- SaaS platforme
- CMS sisteme
- E-commerce platforme (sa dodatnim modulima)

Sve je napravljeno sa fokusom na čist kod, sigurnost, i lako održavanje.

