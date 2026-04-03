# Plan 03 support: test DB i fixture strategija

## Izabrana test DB strategija

- Kanonski test rezim je `APP_ENV=testing` + `app/.env.testing`.
- Test baza treba da bude odvojena MariaDB/MySQL baza sa `_test` sufiksom, npr. `aleksandar_pro_test`.
- `phpunit.xml` forsira `APP_ENV=testing`, `APP_ENV_FILE=.env.testing` i test DB kredencijale da test runner ne padne na produkcioni `.env` slucajno.
- SQLite in-memory se trenutno ne bira kao primarna opcija jer query/schema sloj vec ima MySQL-specific grane i administrativne DDL tokove.
- Schema bootstrap je automatizovan kroz `composer test-db:schema`, koji kopira samo `BASE TABLE` definicije iz glavne baze u `_test` bazu uz `_test` suffix guard.

## Fixture pravila za sledeci korak

- Minimalni seed treba da sadrzi:
  - admin korisnika sa poznatim kredencijalima,
  - default jezik + bar jedan dodatni jezik,
  - jednu javnu aktivnu stranicu za dinamicki route smoke,
  - jedan blog post/category/tag set,
  - jedan API token za protected API happy-path test.
- Test baza se ne sme puniti rucno kroz dashboard; seed mora biti ponovljiv kroz skriptu ili SQL fixture.
- Destruktivni testovi treba da rade nad `_test` bazom i da imaju ili transaction rollback ili recreate+seed ciklus.

## Trenutni status

- Unit/service/no-DB smoke testovi su uvedeni i zeleni.
- Dodat je `Tests\Support\TestDatabaseManager` i `composer test-db:seed` / `app/scripts/seed-test-database.php` za ponovljiv recreate+seed ciklus nad `_test` bazom.
- Dodat je i `app/scripts/sync-test-database-schema.php` + `composer test-db:schema` za kloniranje samo schema sloja u `_test` bazu.
- `.env.testing` sada koristi lokalni `hadmin_al_pro_test` target sa Hestia userom `hadmin_al_pro_test`.
- `composer test-db:schema` i `composer test-db:seed` prolaze nad lokalnom `_test` bazom.
- DB-backed HTTP feature testovi su uvedeni za admin login/logout i dashboard page create happy-path.
- Route-level CSRF je aktiviran na `POST /login` i `POST /register`, a regresija je pokrivena HTTP smoke testovima i direktnim `CsrfMiddleware` testom.

## Lokalni test DB redosled

```bash
cd /path/to/project/app
composer test-db:schema
composer test-db:seed
composer check
```
