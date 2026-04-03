# Ultimate plan 01: Arhitektura, PSR-4 bootstrap i dependency injection

## Cilj

Pretvoriti trenutni rucno sastavljeni bootstrap i `glob()` ucitavanje kontrolera u stabilan PSR-4 + minimalni composition root, a zatim postepeno uvesti DI container i smanjiti staticki coupling bez lomljenja postojeceg runtime ponasanja.

## Grupa A: Inventar, granice i namespace mapa

- [x] Popisati sve klase u `app/core`, `app/mvc/models`, `app/mvc/controllers` i proveriti da li postoje duplikati imena ili fajlovi koji krse PSR-4 pravilo "ime fajla = ime klase".
- [x] Definisati konacnu namespace mapu, preporuka:
  - [x] `App\Core\` -> `app/core/`
  - [x] `App\Models\` -> `app/mvc/models/`
  - [x] `App\Controllers\` -> `app/mvc/controllers/`
- [x] Popisati sva mesta gde se handleri pozivaju preko string imena klase, `class_exists('Page')`, `new $controller`, route definicija i dynamic route registry, jer ce to traziti namespace korekciju.
  - [x] Popis i trenutno bridge stanje dokumentovani u `support/01_bootstrap_namespace_i_cli_status.md`.
- [x] Identifikovati helper funkcije koje moraju ostati globalne i izdvojiti ih od klasa koje treba namespacovati.

## Grupa B: PSR-4 migracija i ciscenje ulaza

- [x] Prosiriti `app/composer.json` PSR-4 mapom za `Core`, `Models` i `Controllers`, uz zadrzavanje `files: ["core/helpers.php"]` ako globalni helperi ostaju globalni.
- [x] Dodati namespace deklaracije u sve core klase, modele i kontrolere, pa urediti `use` import-e.
  - [x] Uveden kompatibilni namespace alias bridge u bootstrap-u, tako da `App\Core\...`, `App\Models\...` i `App\Controllers\...` reference vec rade iz route fajlova i dynamic route registry-ja.
  - [x] Fizicki `namespace ...;` rewrite core/model/controller klasa je odradjen, uz reverzni legacy alias map u bootstrap-u za kompatibilnost sa starim globalnim imenima.
- [x] Azurirati sve route fajlove i dynamic route lookup da koriste nove namespace klase, a ne implicitni globalni prostor imena.
  - [x] `web.php`, `api.php`, `dashboard-api.php` i `DynamicRouteRegistry` koriste `App\Controllers\...` / `App\Models\...` reference preko bootstrap alias bridge-a.
- [x] Pokrenuti `composer dump-autoload -o` i ispraviti svaku PSR-4 nekonzistentnost.
- [x] Zameniti rucni require lanac u `public_html/index.php` minimalnim autoload + bootstrap ulazom.
- [x] Ukloniti `glob()` petlju za ucitavanje kontrolera.

## Grupa C: Novi composition root

- [x] Kreirati jedan centralni bootstrap fajl, npr. `app/bootstrap/app.php`, koji radi samo:
  - [x] ucitavanje env konfiguracije,
  - [x] registraciju error/exception handlera,
  - [x] registraciju core servisa,
  - [x] ucitavanje route definicija,
  - [x] kreiranje aplikacionog router/dispatcher toka.
- [x] Ostaviti `public_html/index.php` kao tanak HTTP front controller koji poziva bootstrap i dispatchuje zahtev.
- [x] Ako postoji CLI ulaz, obezbediti da i on koristi isti bootstrap sloj, ali bez web-specific pretpostavki.
  - [x] Dodan `ap_bootstrap_cli_application()` i svi `app/scripts/*.php` ulazi prebaceni na njega.

## Grupa D: DI container kao postepena tranzicija, ne sok terapija

- [x] Uvesti malu `Container` klasu sa `bind`, `singleton`, `make` i opcionalnim reflection fallback-om.
- [x] Registrovati osnovne servise: `Database`, `Router`, `Translator`, `Logger`, `ViewEngine`, config/env pristup.
- [x] Router instanciranje kontrolera prebaciti na container-aware rezoluciju, tako da konstruktor kontrolera moze postepeno primati zavisnosti.
- [x] Za pocetak zadrzati kompatibilni facade/static wrapper tamo gde bi potpuni refaktor bio preskup, ali unutra ga osloniti na container.
- [x] Uklanjati `global $router` i slicne obrasce tek kada za konkretan tok postoji container-safe zamena.
  - [x] `route()`, `current_lang()`, `form()` i `PageController` prebaceni na container-aware `app_router()` / constructor injection uz legacy fallback.

## Grupa E: Regresiona provera

- [x] Testirati homepage bez prefiksa jezika i sa prefiksom jezika.
  - [x] CLI smoke zahtev za `/` prolazi bez fatal greske; `/sr/login` renderuje HTML.
- [x] Testirati jednu staticku rutu, jednu dinamicku DB rutu i jedan API endpoint.
  - [x] `/sr/login` staticka ruta radi, `/sr/o-autoru` dinamicka DB ruta renderuje sadrzaj, `/api/status` vraca JSON odgovor.
- [x] Testirati login/logout, CSRF formu i admin ulaz.
  - [x] `/sr/login` renderuje CSRF token u formi i meta tagu; `/sr/dashboard` protected ulaz prolazi bez fatal greske u guest smoke scenariju.
- [x] Pokrenuti PHPUnit i popraviti test bootstrap ako PSR-4 promena otkrije stare putanje.
  - [x] `composer dump-autoload -o`, `php tests/UserTest.php` i `php tests/DashboardControllerTest.php` prolaze.

## Kriterijumi zavrsetka

- [x] `public_html/index.php` vise nije monolitni orkestrator aplikacije.
- [x] Composer autoload je jedini standardni mehanizam za ucitavanje aplikacionih klasa.
  - [x] HTTP i CLI bootstrap sada zahtevaju `vendor/autoload.php`; fallback `ap_load_application_files()` vise nije runtime put.
- [x] Nove klase se dodaju bez rucnog upisivanja `require_once` u centralni ulaz.
- [x] Container postoji i koristi se bar za osnovne core servise i instanciranje novih ili refaktorisanih kontrolera.
- [x] Plan 01 zatvoren kroz lint, autoload, test i smoke proveru za homepage, staticku rutu, dinamicku DB rutu, login formu, dashboard guard i API odgovor.
