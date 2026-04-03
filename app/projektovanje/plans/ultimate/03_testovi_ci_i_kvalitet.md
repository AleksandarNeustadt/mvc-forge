# Ultimate plan 03: Testovi, CI i inzenjerska disciplina

## Cilj

Pretvoriti test sloj iz "signala namere" u stvaran sigurnosni pojas koji prati refaktor, sprecava regresije i omogucava hrabrije izmene bez gubitka kontrole.

## Grupa A: Ispraviti test bootstrap i pokretanje

- [x] Popraviti pogresne putanje u postojecim testovima koji ocekuju `public/index.php` umesto stvarnog ulaza i bootstrap-a.
- [x] Napraviti jedan kanonski test bootstrap, npr. `app/tests/bootstrap.php`, koji ucitava Composer autoload, env test konfiguraciju i minimalni app setup.
- [x] U `phpunit.xml` definisati test env vrednosti tako da testovi ne mogu slucajno raditi nad produkcionom bazom.
  - [x] Dodat `app/phpunit.xml`, `.env.testing` i test bootstrap koji podrazumevano koristi `APP_ENV=testing` bez dizanja punog route/DB bootstrapa.
- [x] Proveriti da `vendor/bin/phpunit` prolazi bar sa pocetnim smoke paketom pre sledeceg velikog refaktora.
  - [x] Dodat pocetni PHPUnit paket za `Security`, `Cache` i legacy alias/controller kontrakt; `composer check` prolazi.

## Grupa B: Test piramida za ovaj sistem

- [x] Unit testovi za core klase koje nose pravila, npr. `Router`, `DynamicRouteRegistry`, `RateLimiter`, `Security`, `QueryBuilder`, `ViewEngine` parser gde je realno.
  - [x] Dodati PHPUnit testovi za `Security`, `Cache`, `Request`, `RateLimiter`, `QueryBuilder`, `Router` i `ViewEngine`.
  - [x] Dodati testove za `DynamicRouteRegistry` gde je realno bez prevelikog fixture balasta.
- [x] Service testovi za novouvedene domenske servise iz plana 02.
  - [x] Dodat PHPUnit test za `ApiResponseFormatterService::formatLanguage()`.
  - [x] Dodat service test za `DashboardPageService` normalizaciju, display options i edit transformaciju.
  - [x] Dodati jos service testova za dashboard servise sa cistom transformacionom/validacionom logikom.
    - [x] Pokriveni `DashboardLanguageService`, `DashboardNavigationService`, `DashboardGeoService` i `DashboardRoleService`.
- [x] Feature/smoke testovi za najvaznije korisnicke tokove:
  - [x] homepage i jezik redirect,
  - [x] jedna dinamicka stranica iz baze,
  - [x] login/logout,
  - [x] CSRF zasticena forma,
  - [x] jedan dashboard CRUD tok,
  - [x] jedan javni API i jedan zasticeni API tok.
  - [x] Dodati no-DB HTTP smoke testovi za `/`, `/sr/o-autoru`, `/sr/login`, guest reject na `/sr/dashboard` i protected `/api/dashboard/users` 401 JSON.
  - [x] Dodat logout smoke `POST /sr/logout` bez CSRF tokena -> `403`.
  - [x] Dodat login submit smoke `POST /sr/login` bez obaveznih polja -> `302` bez fatal greske.
  - [x] Dodat dashboard write smoke `POST /sr/dashboard/pages` kao guest -> `302` ka login toku.
  - [x] Dodat public API smoke za `POST /api/auth/login` bez kredencijala -> 400 JSON.
  - [x] Dodat DB-backed HTTP happy-path za admin login, validan logout i authenticated `POST /sr/dashboard/pages` create tok.
- [x] Minimalni security regresioni testovi za "CSRF bez tokena mora pasti", "rate limit mora blokirati posle limita", "admin ruta bez auth mora odbiti".
  - [x] Pokriven rate-limit blok/clear flow kroz `RateLimiterTest`.
  - [x] Pokriven direktan `CsrfMiddleware` negative-path, logout CSRF reject i guest auth reject smoke.
  - [x] Route-level CSRF enforcement za login/register forme pokriven kroz HTTP smoke testove i `CsrfMiddleware` na `POST /login` + `POST /register`.

## Grupa C: Test baza i fixture disciplina

- [x] Odabrati test DB strategiju: SQLite in-memory gde je kompatibilno, ili posebna MySQL/MariaDB test baza sa izolovanim `.env.testing`.
  - [x] Strategija zakljucana na posebnu MariaDB/MySQL `_test` bazu kroz `support/03_test_db_fixture_strategija.md`.
- [x] Napraviti ponovljiv seed/fixture mehanizam za minimalni skup: admin user, public page, translated page, API token.
  - [x] Dodat `Tests\Support\TestDatabaseManager`, `composer test-db:schema` i `composer test-db:seed` za schema clone + minimalni admin/language/page/token seed.
- [x] Za testove koji menjaju bazu, uvesti transakcioni rollback ili recreate/seed ciklus da testovi ostanu deterministicki.
  - [x] Fixture manager radi `TRUNCATE + seed` ciklus pod `APP_ENV=testing` + `_test` DB safety guard-om.

## Grupa D: Static checks i CI gate

- [x] Dodati Composer script-e za test i osnovne provere, npr. `composer test`.
  - [x] Dodati `composer lint`, `composer phpunit`, `composer legacy-test`, `composer test` i `composer check`.
- [x] Uvesti makar jedan staticki quality gate koji je realan za custom kodnu bazu:
  - [x] `php -l` batch lint nad PHP fajlovima,
  - [x] opcionalno PHPStan/Psalm na nizem nivou pa postepeno pojacavati.
    - [x] Uveden PHPStan `level: 0` sa uskim scope-om u `app/phpstan.neon` i vezan na `composer check`; scope kasnije siriti postepeno da se ne uvede legacy sum.
- [x] Za frontend asset deo dodati `npm run build` kao CI proveru.
- [x] Napraviti GitHub Actions workflow koji na push/PR radi:
  - [x] composer install,
  - [x] composer test / phpunit,
  - [x] PHP lint/static check,
  - [x] npm ci,
  - [x] npm run build.

## Grupa E: Pravila kvaliteta za buduce promene

- [x] Svaki novi servis ili znacajniji bugfix prati bar jedan test ili eksplicitna smoke procedura u PR opisu.
- [x] Ne dozvoliti rast novih "mega kontrolera"; ako fajl krene da raste, odmah ga cepati po odgovornostima.
- [x] Za svaki breaking API/route potez dokumentovati migracioni uticaj i rollback strategiju.
  - [x] Pravila su upisana u `support/03_quality_policy.md`.

## Kriterijumi zavrsetka

- [x] Testovi se pokrecu bez rucnog popravljanja putanja i bez rizika da pogode produkcioni `.env`.
- [x] Najkriticniji HTTP i domen tokovi imaju regresioni pokrivac.
- [x] CI postaje obavezni alarm pre spajanja vecih izmena.
- [x] Refaktor iz planova 01, 02 i 04 vise ne zavisi samo od rucnog kliktanja.
