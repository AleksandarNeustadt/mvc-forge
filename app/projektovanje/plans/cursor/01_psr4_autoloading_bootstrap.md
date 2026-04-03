# Plan 01: PSR-4 autoloading i pojednostavljenje bootstrap-a

**Cilj:** Ukloniti monolitni lanac `require_once` u `public_html/index.php`, ukloniti `glob()` za kontrolere i imati jedan izvor istine preko Composera, bez promene ponašanja aplikacije u runtime-u.

**Izlaz:** `index.php` učitava `vendor/autoload.php` i minimalan skup bootstrap fajlova (npr. samo `Env`, eventualno jedan `bootstrap/app.php`), sve ostale klase se učitavaju po potrebi.

---

## Faza A: Inventar i odluke

- [ ] Napraviti listu svih klasa u `app/core/` (uključujući podfoldere: `config`, `security`, `routing`, `middleware`, itd.) i zabeležiti trenutno ime klase i putanju fajla.
- [ ] Napraviti listu svih klasa u `app/mvc/models/` i `app/mvc/controllers/`.
- [ ] Proveriti da li postoje klase sa istim imenom u različitim folderima (konflikt nakon autoload-a).
- [ ] Odlučiti mapu namespace-a (predlog koji minimizuje refaktor):
  - [ ] Opcija A: jedan namespace `App\` mapiran na više PSR-4 prefiksa (nije validno — PSR-4 jedan prefix jedan base path).
  - [ ] Opcija B: `App\Core\` → `app/core/`, `App\Models\` → `app/mvc/models/`, `App\Controllers\` → `app/mvc/controllers/` (preporučeno).
- [ ] Proveriti `composer.json`: trenutno `App\` → `core/` — odlučiti da li se `App\` koristi samo za `core` ili se menja u `App\Core\` i širi na `mvc`.

---

## Faza B: Ažuriranje `composer.json`

- [ ] Dodati PSR-4 mape za sve ciljane namespace-e i putanje (relativno od `app/` gde stoji `composer.json`).
- [ ] Zadržati `"files": ["core/helpers.php"]` ako globalne funkcije moraju ostati globalne.
- [ ] Pokrenuti iz `app/`: `composer dump-autoload -o` i proveriti da nema upozorenja.
- [ ] Dokumentovati u komentaru u `composer.json` ili u ovom planu zašto je izabrana tačna mapa.

---

## Faza C: Namespace na klasama

- [ ] Za svaku klasu u `core/`: dodati `namespace App\Core\...` usklađen sa podfolderom (npr. `App\Core\Routing` za `core/routing/Router.php`).
- [ ] Za svaki model: `namespace App\Models;`.
- [ ] Za svaki kontroler: `namespace App\Controllers;`.
- [ ] U svakom fajlu dodati `use` za reference na druge klase (npr. kontroler `use App\Core\Mvc\Controller;`).
- [ ] U `routes/*.php` zameniti reference tipa `[MainController::class, 'home']` sa punim imenima ili `use` na vrhu fajla ruta.
- [ ] U `DynamicRouteRegistry` i drugim mestima gde se koristi `PageController::class`, `class_exists('Page')` itd. — ažurirati na nove namespace-e ili `::class` sa importom.

---

## Faza D: Uklanjanje ručnog učitavanja iz `index.php`

- [ ] Zameniti sve `require_once` za klase jednim `require_once` ka `app/vendor/autoload.php` (putanja ostaje kao sada, relativno ili apsolutno).
- [ ] Ukloniti `foreach (glob(...controllers/*.php'))` petlju.
- [ ] Ostaviti samo ono što autoload ne može: npr. učitavanje `.env` preko `Env` ako mora biti pre autoload (minimalno — idealno `Env` je takođe u namespace-u i učitava se preko autoload).
- [ ] Proveriti redosled: `Env::load` pre bilo čega što čita `$_ENV`; sesija posle što je potrebno.

---

## Faza E: Regresiono testiranje

- [ ] Ručno: početna stranica sa jezičkim prefiksom i bez njega (redirect).
- [ ] Ručno: login, logout, forma sa CSRF-om.
- [ ] Ručno: jedna dinamička stranica iz baze (Page manager ruta).
- [ ] Ručno: bar jedan `api/` endpoint ako postoji u upotrebi.
- [ ] Ako postoji PHPUnit: pokrenuti `composer test` / `vendor/bin/phpunit` i popraviti importe u testovima.

---

## Kriterijumi završetka

- [ ] U `index.php` nema liste od 50+ `require_once` za aplikacione klase.
- [ ] Nema `glob()` za učitavanje kontrolera.
- [ ] `composer validate` prolazi; `composer dump-autoload -o` bez greške.
- [ ] Nema fatal error „class not found“ na glavnim user flow-ovima.

---

## Rizici i mitigacija

| Rizik | Mitigacija |
|-------|------------|
| Pogrešan namespace / putanja fajla | Striktno PSR-4: ime fajla = ime klase + `.php` |
| String imena klasa u bazi (ako postoje) | Migracija podataka ili alias mapa pri rezoluciji handlera |
| Veliki jednokratni diff | Rad po fazama: prvo `core`, pa `models`, pa `controllers`, pa rute |
