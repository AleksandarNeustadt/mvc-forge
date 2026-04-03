# Nezavisni tehnički audit posle refaktora

Datum audita: 2026-04-03  
Autor: Codex  
Opseg: `/public_html`, `/app/bootstrap`, `/app/core`, `/app/mvc`, `/app/routes`, `/app/bin`, `/app/tests`, instalacioni i DB tokovi  

## Metodologija

Ovaj audit je rađen kao svež pregled koda i operativnog toka, bez preuzimanja zaključaka iz planskih dokumenata. Korišćeni su statički pregled fajlova, metrički presek veličine ključnih modula, ručni pregled reprezentativnih ruta/bootstrapa/middleware-a/migracija i verifikacija postojećeg test/build gate-a.

Provereno stanje:
- `composer check` prolazi
- PHPUnit: 52 testa / 197 asercija
- legacy `UserTest`: 10/10
- legacy `DashboardControllerTest`: 6/6
- `npm run build` prolazi
- fresh-install smoke je potvrđen kroz čist checkout i seed/migrate tok

Brzi metrički presek:
- ~317 PHP fajlova u `app/` bez `vendor/` i `claude-env/`
- ~63k linija PHP koda
- 21 PHP servis u `app/core/services`
- 22 PHP test fajla u `app/tests`
- `public_html/index.php`: 9 linija
- `app/bootstrap/app.php`: 605 linija
- `app/mvc/controllers/DashboardController.php`: 2846 linija
- `app/mvc/controllers/ApiController.php`: 1274 linije
- `app/mvc/controllers/DashboardApiController.php`: 543 linije
- `app/mvc/views/helpers/crud-table.php`: 1255 linija
- `app/core/view/ViewEngine.php`: 697 linija
- `app/core/logging/Logger.php`: 328 linija
- oko 301 pojavljivanje `require_once` u PHP kodu
- oko 73 pojavljivanja `error_log(` u PHP kodu
- veliki broj `global $router` referenci i dalje postoji u kontrolerima, middleware-u i view sloju

## Sažetak ocene

Ukupna ocena trenutnog stanja: **8.6 / 10**

Po oblastima:
- Arhitektura i bootstrap: **8.8 / 10**
- Održivost i modularnost: **8.1 / 10**
- Bezbednost: **8.9 / 10**
- Testabilnost i CI spremnost: **8.7 / 10**
- Performanse i opservabilnost: **8.6 / 10**
- Prenosivost i instalacija: **9.1 / 10**

Moj nezavisni zaključak: projekat je sada primetno iznad stanja “veliki monolit sa ručnim bootstrapom” i već ulazi u zonu održive custom platforme, ali još nije “čist modularni framework-style sistem” zbog ostatka legacy coupling-a u view/controller sloju, prevelikog `DashboardController` agregatora i migracija koje su istorijski i dalje skriptolike.

## Šta je sada jako dobro

### 1) Front controller i bootstrap su jasno razdvojeni

`public_html/index.php` je sveden na tanak ulaz, a realni runtime boot je pomeren u `app/bootstrap/app.php`. To je velika arhitektonska dobit jer web root više nije mesto gde se ručno sklapa ceo framework.

Dokaz:
- `public_html/index.php` ima 9 linija
- `app/bootstrap/app.php` centralizuje HTTP i CLI boot, service registration, route loading, middleware registraciju, translator init i error handling

### 2) Postoji realan servisni sloj iza dashboard funkcionalnosti

`DashboardController` i `DashboardApiController` više ne nose svu domensku logiku sami; postoji niz servisnih klasa za korisnike, stranice, navigaciju, jezike, geo taksonomiju, blog, role, media, IP tracking, schema admin i contact poruke.

Primeri:
- `app/core/services/DashboardUserService.php`
- `app/core/services/DashboardPageService.php`
- `app/core/services/DashboardBlogPostService.php`
- `app/core/services/DashboardApiQueryService.php`

To znači da je refaktor dao stvarni pomak, a ne samo kozmetičko pomeranje koda.

### 3) Instalacija i prenosivost su sada ozbiljno bolji

Postoji `app/bin/console`, `.env.example`, root `README.md`, `CHANGELOG.md`, `.gitignore`, release packaging skript i testirana fresh install putanja. To je već nivo koji omogućava selidbu hostinga i reproducibilan onboarding iz checkout-a.

Posebno pozitivno:
- `db:migrate` pamti izvršene migracije kroz `schema_migrations`
- postoji `--baseline` zaštita za legacy bazu
- postoji `db:seed` i opcioni `--demo`
- `storage:prepare`, `cache:clear`, `install:check`, `app:key` pokrivaju realan operativni minimum

### 4) Security sloj je vidno zreliji nego ranije

Pozitivne tačke:
- CSRF je uveden i route-level strožije primenjen za auth forme
- session cookie handling je ojačan
- `AuthMiddleware` ima idle-timeout i user-agent hijack guard
- `CorsMiddleware` ima allowlist pristup i `Vary: Origin`
- exception output je razdvojen na debug/prod režim
- logger ima nivoe i `request_id`
- CSP nonce se koristi u ključnim inline script blokovima
- rate-limit identitet je precizniji nego čista globalna metoda+URI kombinacija

### 5) Test i build gate su postali stvarni sigurnosni pojas

To što `composer check` i `npm run build` prolaze i što postoji i PHPUnit i legacy test sloj znači da projekat više nije “refaktorišemo pa ručno klikćemo”. Ovo je važan kvalitativni skok.

## Glavni nalazi i preostali rizici

### P1 — `DashboardController` je i dalje prevelik agregator i zadržava previše HTTP orkestracije

Iako je dosta domenske logike izvučeno u servise, `app/mvc/controllers/DashboardController.php` i dalje ima **2846 linija**, 12 servisnih zavisnosti u konstruktoru i veliki broj ručnih flow-ova za validaciju, flash, redirect i `global $router` pristup.

Dokaz:
- `app/mvc/controllers/DashboardController.php:61-103` ima 12 servisnih polja i širok konstruktor
- `app/mvc/controllers/DashboardController.php:171-217` pokazuje da kontroler i dalje ručno orkestrira validaciju, JSON/html grananje, flash i redirect za schema deo

Zašto je bitno:
- servisni sloj postoji, ali HTTP sloj još nije domenski podeljen na manje resursne kontrolere
- svaka promena u jednom dashboard modulu i dalje nosi rizik slučajnog side efekta u istom mega-kontroleru

Preporuka:
- sledeći refaktor uraditi kao `DashboardUsersController`, `DashboardPagesController`, `DashboardLanguagesController`, `DashboardNavigationController`, `DashboardBlogPostsController`, `DashboardBlogTaxonomyController`, `DashboardGeoController`, `DashboardSchemaController`, `DashboardContactMessagesController`, gde sadašnji `DashboardController` ostaje samo privremeni adapter ili se postepeno gasi
- paralelno izvući ponavljajući pattern “validate -> if JSON -> flash old/errors -> redirectBack” u shared HTTP helper/response layer

### P1 — Legacy `global $router` coupling je i dalje široko prisutan

Iako postoji container i helper bridge, veliki broj fajlova i dalje direktno čita `global $router`, uključujući middleware, kontrolere, layout i mnoge view fajlove.

Dokaz:
- `app/core/middleware/AuthMiddleware.php:54-58`, `93-97`, `135-139`
- `app/mvc/views/layout.php:75-85`, `169-170`, `199-200`
- `app/mvc/controllers/MainController.php:47`
- veliki broj dashboard i page view fajlova i dalje ima `global $router`

Zašto je bitno:
- testabilnost i izolacija render sloja ostaju slabiji nego što bi mogli biti
- hidden dependency otežava refaktor i povećava rizik runtime regresije ako se bootstrap ili request context promeni

Preporuka:
- uvesti eksplicitan `ViewContext` / `RequestContext` objekat koji nosi `lang`, `uri`, `siteOrigin`, `currentUser`, `csrfNonce`
- view helperi i layout treba da dobijaju taj context kroz data payload ili shared view state, ne preko globalne promenljive
- middleware redirect logiku vezati za `Request`/`Router` servise preko DI, a ne `global $router`

### P1 — Migracije su funkcionalne, ali istorijski format je i dalje mešavina “skripta” i “migration klase”

`db:migrate` sada radi i bolje podnosi class-based migracije, ali sami migration fajlovi i dalje imaju dosta legacy skript paterna:
- ručni `require_once`
- direktan `Env::load()`
- `echo` logging iz same migracije
- interaktivni stdin prompt
- `exit(0)` / `exit(1)` grane

Dokaz:
- `app/core/database/migrations/002_create_pages_table.php:9-17`
- `app/core/database/migrations/002_create_pages_table.php:24-38`
- `app/core/database/migrations/002_create_pages_table.php:109-112`
- `app/core/database/migrations/001_create_users_table.php:22-36`

Zašto je bitno:
- novi install sada prolazi, ali migracioni sloj još nije potpuno standardizovan
- skriptolike migracije su teže za rollback, batch kontrolu, dry-run i audit trail

Preporuka:
- definisati jedan migration contract: npr. `up(): void`, `down(): void`, bez stdin prompta, bez direktnog `Env::load()`, bez `exit()`
- postojeće skriptolike migracije postepeno prevesti na isti format
- `db:migrate` neka bude jedino mesto koje upravlja outputom i greškama

### P2 — `bootstrap/app.php` je postao novi centralni “god bootstrap” fajl

Pozitivno je što je bootstrap izvučen iz web root-a, ali `app/bootstrap/app.php` već ima **605 linija** i kombinuje više uloga:
- HTTP boot
- CLI boot
- session config
- container registration
- legacy alias autoload bridge
- env normalization
- favicon serving
- error/logging bootstrap

Dokaz:
- `app/bootstrap/app.php:10-66`
- `app/bootstrap/app.php:69-119`
- `app/bootstrap/app.php:122-239`

Zašto je bitno:
- rizik se samo delimično pomerio iz front controllera u jedan veliki bootstrap modul
- legacy alias bridge je koristan za tranziciju, ali dugoročno je to kompatibilnosni sloj koji treba smanjivati, ne širiti

Preporuka:
- razbiti bootstrap na manje fajlove/klase: `HttpKernelBootstrap`, `CliKernelBootstrap`, `ServiceProviderRegistry`, `LegacyAliasBridge`, `SessionConfigurator`, `EnvironmentLoader`
- dodati test koji posebno proverava da legacy alias map i dalje radi dok se bridge ne ukloni

### P2 — View sloj je i dalje dosta “fat PHP template” i meša HTML, query/context logiku i helper fallback-e

`layout.php`, `crud-table.php`, `header.php` i više page template fajlova i dalje rade dosta PHP logike direktno u view sloju.

Dokaz:
- `app/mvc/views/layout.php:35-85` sklapa SEO, canonical i URL context direktno u template-u
- `app/mvc/views/layout.php:163-210` direktno vuče navigaciju i jezike kroz model/helper pozive
- `app/mvc/views/helpers/crud-table.php` ima 1255 linija
- `app/mvc/views/components/header.php` ima 665 linija

Zašto je bitno:
- teškoća testiranja render logike
- teže postepeno uvođenje čistih view-modela
- veći rizik XSS/encoding propusta kad je logika rasuta po template-ima

Preporuka:
- za layout/header uvesti `LayoutViewModel` i `NavigationViewModel`
- `crud-table.php` podeliti na manje komponente i prebaciti pripremu podataka van template helpera
- dogovoriti pravilo: template prikazuje, servis/controller priprema

### P2 — Kod je funkcionalno napredovao, ali ima vidljive mehaničke style artefakte iz namespace refaktora

U dosta fajlova postoje spojeni importi i preširoki `use` blokovi koji očigledno nisu ručno kurirani.

Dokaz:
- `app/mvc/controllers/DashboardController.php:32-52` ima `use App\Models\User;use BadMethodCallException;` i veliki set importovanih SPL/Reflection klasa koje ovom kontroleru nisu očigledno potrebne
- `app/core/middleware/AuthMiddleware.php:6-27` pokazuje isti obrazac

Zašto je bitno:
- nije runtime bug, ali smanjuje čitljivost i signalizira da refaktor još nije prošao završni style/import cleanup

Preporuka:
- uvesti automatski PHP-CS fixer ili PHP_CodeSniffer profile i očistiti import blokove
- pravilo: nema spojenih `use ...;use ...;`, nema masovnog importovanja nepotrebnih klasa

### P2 — Direktni `error_log()` pozivi još nisu potpuno eliminisani

Centralni logger postoji i deo sistema ga koristi, ali `error_log(` se i dalje pojavljuje na desetinama mesta.

Zašto je bitno:
- log format, request correlation i log level disciplina nisu potpuno konzistentni ako deo koda i dalje piše mimo `Logger` klase

Preporuka:
- završiti migraciju ka `Logger::{debug,info,warning,error,critical}`
- uvesti statičku proveru ili grep gate koji zabranjuje novi `error_log()` osim u bootstrap fallback-u

### P3 — Bootstrap kompatibilnosni bridge i `require_once` dug su još prisutni, ali sada više kao evolutivni nego blokirajući problem

`app/bootstrap/app.php` i mnoge migracije i dalje sadrže compatibility sloj, `class_alias`, ručne include tačke i fallback-e. To trenutno pomaže stabilnosti, ali dugoročno produžava život legacy modela izvršavanja.

Dokaz:
- `app/bootstrap/app.php:122-239` dinamički gradi legacy alias mapu skeniranjem fajlova i `token_get_all`
- `app/bootstrap/app.php:242-260` ima fallback ponašanje kad `vendor/autoload.php` ne postoji
- `require_once` je i dalje prisutan oko 301 puta u PHP kodu

Preporuka:
- držati bridge kao tranzicioni sloj sa jasnim planom gašenja
- prvo ukloniti `global $router` i skriptolike migracije, pa tek onda agresivnije smanjivati legacy alias map

## Kvalitativna ocena po slojevima

### Routing / HTTP kernel

Stanje je znatno bolje nego ranije: postoji centralni router, middleware registracija, route collection i tanak web entrypoint. Ipak, redirect i language context još curi kroz `global $router` umesto kroz eksplicitan request context.

Ocena: **8.6 / 10**

### Domain/service sloj

Servisi su veliki dobitak i jasno pokazuju pravac sistema. Ipak, `DashboardController` još nije fizički razbijen po resursnim kontrolerima, a pojedini servisi su već dosta veliki (`DashboardApiQueryService.php` ~943 linije), što znači da treba nastaviti sa manjim domenskim granicama.

Ocena: **8.2 / 10**

### Model/DB sloj

`Model`, `QueryBuilder`, transakcije, schema builder i test DB tok su korisni i sada zreliji. Glavna slabost je istorijski migration format i to što migracije još nisu uniforman framework-style sloj.

Ocena: **8.4 / 10**

### View sloj

UI je funkcionalno bogat, ali template sloj je i dalje najlegacy deo sistema: puno PHP logike, `global $router`, masivni helper fajlovi, i delimično dupliranje `.php` i `.template.php` varijanti.

Ocena: **7.6 / 10**

### Security

Na osnovu trenutnog pregleda, security posture je sada solidan do jak: CSRF, session hardening, CORS allowlist, CSP nonce, request-id logging i rate-limit identitet su realni pomaci. Preostali rizik je više u dugoročnoj konzistentnosti i auditabilnosti template sloja nego u očiglednoj jednoj fatalnoj rupi.

Ocena: **8.9 / 10**

### Test/CI

Gate sada postoji i prolazi, što je veliki plus. Ipak, broj feature testova je i dalje relativno mali u odnosu na veličinu aplikacije i posebno na širinu dashboard domena.

Ocena: **8.7 / 10**

### Deploy/operacije

Ovo je trenutno jedna od jačih oblasti posle refaktora: CLI komande, seed/migrate, `.env.example`, release skript i smoke install daju mnogo bolju prenosivost nego ranije.

Ocena: **9.1 / 10**

## Prioriteti za sledeću rundu

### Top 5 tehničkih poteza

1. Razbiti `DashboardController` na domenske dashboard kontrolere i uvesti shared response/flash helper.
2. Izbaciti `global $router` iz middleware-a, controller-a i layout/view sloja kroz eksplicitan `RequestContext/ViewContext`.
3. Standardizovati migracije na jedan contract bez `require_once`, stdin prompta i `exit()`.
4. Refaktorisati `layout.php`, `header.php`, `crud-table.php` i veće dashboard template-e ka manjim view komponentama i view-modelima.
5. Uvesti PHP-CS fixer/PHPCS i jedan cleanup pass za import blokove, style artefakte i preostali `error_log()` dug.

### Predlog praga za “v1 hardening complete”

Smatrao bih da je sistem ušao u “v1 hardening complete” tek kada su ispunjeni ovi uslovi:
- nijedan request path više ne zavisi od `global $router`
- `DashboardController.php` padne ispod ~800-1000 linija ili bude zamenjen skupom manjih kontrolera
- migracije imaju jedan standardizovan format i ne sadrže interaktivni stdin prompt
- `error_log()` ostane samo u jednom-dva bootstrap fallback mesta
- feature test pokriva login, dashboard CRUD, public page render, language switch i bar jedan API flow

## Finalni sud

Ovaj kodbase je posle refaktora **značajno zreliji, prenosiviji i testabilniji** nego pre, i sada ima realnu osnovu za GitHub distribuciju i selidbu hostinga. Najveći preostali dug više nije “sistem nema temelj”, nego “legacy coupling i veliki view/controller moduli još nisu do kraja demontirani”.

Drugim rečima: temelj je sada dovoljno dobar da se sledeća runda refaktora radi disciplinovano i bez panike, ali ako želiš da projekat zaista pređe iz “vrlo dobar custom monolit” u “elegantna modularna platforma”, sledeći glavni front borbe su **view/context sloj**, **preostali `global $router`**, **dashboard controller dekompozicija** i **uniformne migracije**.
