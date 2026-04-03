# Plan 01 support: bootstrap, namespace bridge i CLI stanje

## Dogovorena namespace mapa

- `App\Core\` -> `app/core/`
- `App\Models\` -> `app/mvc/models/`
- `App\Controllers\` -> `app/mvc/controllers/`

## Trenutni runtime bridge

- `composer.json` sada sadrzi PSR-4 mapu za `App\Core\`, `App\Models\` i `App\Controllers\`, uz `files: ["core/helpers.php"]`.
- Core klase, modeli i kontroleri sada imaju fizicke `namespace ...;` deklaracije po dogovorenoj mapi.
- `app/bootstrap/app.php` i dalje registruje kompatibilni alias bridge u oba smera:
  - `App\...\ClassName` moze da mapira na legacy globalno ime kada je potrebno,
  - legacy globalno `ClassName` moze da se premosti na novu namespacovanu klasu kroz generisanu reverse mapu.
- Rute i `DynamicRouteRegistry` koriste `App\Controllers\...` / `App\Models\...` reference, dok stariji runtime delovi mogu postepeno da ostanu na globalnim imenima bez fatal greske.

## Popis runtime mesta sa class-handler rezolucijom

- `app/routes/web.php`
- `app/routes/api.php`
- `app/routes/dashboard-api.php`
- `app/core/routing/DynamicRouteRegistry.php`
- `app/core/routing/Router.php` (`invokeController`, `resolveMiddleware`)
- `app/bootstrap/app.php` (Composer autoload, alias bridge, container registracija)

## CLI bootstrap stanje

- `ap_bootstrap_cli_application()` je dodat u `app/bootstrap/app.php`.
- Skripte u `app/scripts/*.php` sada koriste isti bootstrap ulaz, bez sopstvenog `require_once` lanca.
- CLI bootstrap postavlja bezbedan `REQUEST_URI=/sr` i `REQUEST_METHOD=GET` fallback da `Router` ne okine language redirect.

## Global router stanje

- `route()`, `current_lang()` i `form()` vise ne citaju `global $router`, vec koriste `app_router()` helper sa container-first rezolucijom.
- `PageController` prima `Router` kroz konstruktor i ima fallback resolver bez `global $router`.
- `$GLOBALS['router']` i dalje postoji u bootstrap-u kao legacy fallback za delove view/runtime-a koji jos nisu prebaceni na container.
