# Arhitektonski audit: aleksandar.pro — custom PHP MVC
**Datum:** 01. april 2026.  
**Revizor:** Cursor (nezavisna analiza koda u repozitorijumu)  
**Status sistema:** aktivan (produkcioni ulaz: `public_html/index.php`)

---

## 1. Uvod (executive summary)

Analiziran je stek oko `aleksandar.pro`: ulazna tačka eksplicitno učitava jezgro (`app/core`), modele iz `app/mvc/models`, kontrolere preko `glob()` nad `mvc/controllers/*.php`, zatim registruje rute iz `routes/*.php` i prosleđuje zahtev `Router`-u. Sistem je **namerno tanak** (malo zavisnosti u `composer.json`: PHP 8.1+, PHPMailer; PHPUnit u dev), sa **srednjim slojem bezbednosti** (middleware, CSP sa nonce-om, CSRF, rate limiting) i **jakom višejezičnošću** ugrađenom u sam ruter.

Zaključak na visokom nivou: arhitektura je **koherentna i pogodna za dugoročno održavanje jednog vlasničkog sajta**, uz jasan tehnički dug u bootstrap-u (monolitni `require_once` lanac i delimično korišćenje Composera).

---

## 2. Detaljna sektorska analiza

### 2.1. Ruting i upravljanje zahtevom (ocena: 9/10)

**Šta je urađeno:** `Router` normalizuje URI posle jezičkog prefiksa, podržava ~30 jezika, API putanje tretira posebno (bez forsiranog jezičkog prefiksa u istom obliku kao javne stranice), podržava HTTP method spoofing preko `_method`. Statičke rute žive u `RouteCollection` / fluent `Route::get()->middleware()->name()` obrascu; dinamičke rute dolaze iz `DynamicRouteRegistry` (stranice iz baze). `Translator::init($router->lang)` vezuje jezik za ostatak zahteva.

**Prednosti:** jasna separacija „fiksne“ konfiguracije ruta i runtime registracije; dobar fit za CMS-sličan model gde admin menja sadržaj i URL-e bez deploy-a.

**Mane:** u `Router` i `DynamicRouteRegistry` ima **gustog `error_log()` poziva** na svakom zahtevu — funkcionalno korisno za debug, ali u produkciji to **povećava I/O i može da oteža forenziku** ako se logovi ne rotiraju agresivno. Nema keširanja liste dinamičkih ruta u fajl/memoriju (svaki proces zavisi od učitavanja iz baze prema trenutnoj implementaciji `loadFromDatabase`).

### 2.2. Bezbednosni sloj (ocena: 8.5/10)

**Šta je urađeno:** `SecurityHeadersMiddleware` postavlja `X-Frame-Options: DENY`, `nosniff`, strogu CSP gradnju sa **nonce-om** (`getNonce()`), HSTS kada je zahtev siguran ili kada konfiguracija to opravdava, uklanjanje `X-Powered-By` / `Server` gde je moguće. `CsrfMiddleware` štiti state-changing metode. `Input` prosleđuje ulaz kroz `Security::sanitize`. Sesija: `httponly`, `SameSite=Lax`, `secure` zastavica usklađena sa detekcijom HTTPS-a i reverse proxy zaglavljima (Cloudflare i sl.). `RateLimitMiddleware` + `RateLimiter` vezuju ključ za **IP** (`md5($key . ':' . $ip)`), što je ispravan minimum za brute-force na login/register.

**Prednosti:** CSP sa nonce-om i CSRF zajedno čine jak „frontend + form“ odbrambeni par; rate limit je konfigurisabilan po ruti (npr. login 5/60s u `routes/web.php`).

**Rizici / rupice:** API rute zahtevaju poseban pogled (CORS, tokeni, izuzeci od CSRF) — nije u ovom dokumentu end-to-end proveren ceo `api.php`, ali to je prirodna sledeća kontrolna tačka. Verbose logovanje u middleware-u (npr. CSRF) može da upiše osetljive kontekste u log ako se poruke prošire.

### 2.3. Baza podataka i model (ocena: 8/10)

**Šta je urađeno:** `Database` + `QueryBuilder` daju fluent upite sa vezivanjem parametara (smanjenje rizika od SQL injection u odnosu na sirov konkatenisani SQL). `DatabaseTableBuilder` omogućava definisanje šeme imperativno iz PHP-a (slično migracijama u većim frameworkovima).

**Prednosti:** šema i aplikacioni kod ostaju u istom jeziku; tim koji drži projekat ne mora da paralelno vodi SQL skripte „ručno“ ako proces već koristi builder.

**Mane:** nema punog ORM-a — modeli ostaju **ručni** i često će nositi specifične upite; to je prihvatljivo za ovakav obim, ali otežava generičke repozitorijume i testove. U `composer.json` postoji PSR-4 mapiranje `App\` → `core/`, ali **bootstrap ne koristi autoload za `mvc/*`**; umesto toga je dugi niz `require_once` — dupliranje izvora istine u odnosu na ono što Composer već nudi.

### 2.4. View engine (ocena: 7.5/10)

**Šta je urađeno:** `ViewEngine` je lagan, „blade-like“ kompilator sa direktivama dokumentovanim u zaglavlju klase; podržava keš kompilata na disku, `@extends` / `@section` / `@yield`, uslove, petlje, komponente. U šablonima je predviđen `cspNonce` za usklađenost sa CSP.

**Prednosti:** predvidljivost i brzina u odnosu na teške template engine-e; dobar kompromis za sajt sa umerenim dinamizmom.

**Mane:** regex-kompilacija i izvršavanje kompilovanog PHP-a znače da **greške u šablonu** i dalje mogu da budu nezgodne za dijagnostiku ako handler ne mapira jasno „koji view“; kvalitet poruka zavisi od `ExceptionHandler` i debug režima (ovde nije dubinski revidirano svako mesto bacanja izuzetaka u engine-u).

---

## 3. Kritički osvrt

### Šta je arhitektonski jako

1. **Dinamičke rute + Page manager model:** spajanje baze stranica u ruting (`DynamicRouteRegistry`) daje produktnu fleksibilnost bez redeploy-a celog rutinga.
2. **I18n u ruteru, ne kao naknadni „plugin“:** jezik je deo prvog koraka obrade zahteva, što smanjuje greške u linkovanju i dupliranju sadržaja.
3. **Slojeviti middleware:** bezbednosna zaglavlja, CSRF i rate limit mogu da se kombinuju deklarativno na nivou rute.

### Šta je rizično ili skupo za održavanje

1. **Monolitni bootstrap u `index.php`:** redosled `require_once` mora da ostane tačan; svaka nova jezgrena klasa zahteva ručan dodatak. To je **fragilnije** nego jedan PSR-4 autoload + eventualno jedan „service provider“ ili minimalni container.
2. **`glob()` za kontrolere:** skalira se linearno sa brojem fajlova i uvodi mali filesystem overhead na svakom requestu; paralelno, **dva fajla sa istim imenom klase** i dalje mogu da naprave konflikt na nivou PHP-a.
3. **Intenzivno logovanje na produkciji:** ako `APP_DEBUG` nije jedini prekidač za detaljne logove u `Router` / `DynamicRouteRegistry` / `index.php`, trošak i privatnost logova postaju praktičan problem.

---

## 4. Šta nedostaje (roadmap)

1. **Autoloading:** prebaciti `core` i `mvc` na jedan konzistentan PSR-4 autoload (proširiti `composer.json` i ukloniti većinu ručnih `require_once` iz ulaza).
2. **Opcioni route / view cache:** fajl-keš ili APCu za dinamičke rute i kompilovane view-e u produkciji, sa invalidacijom pri izmeni stranice.
3. **Jedan centralni prekidač za log nivo:** npr. `APP_LOG_LEVEL` koji gasi verbose `error_log` u jezgru kada nije potrebno.
4. **CLI sloj:** mali `bin/console` za generisanje skeleta kontrolera/modela i pokretanje migracija/buildera — smanjuje copy-paste greške.
5. **Agregacija grešaka:** integracija sa spoljnim sinkom (Sentry, e-mail za kritične greške) pored lokalnog `storage/logs`.

---

## 5. Završni sud

**Pitanje:** Da li sistem „radi na mišiće“ ili po jasnom projektu?  
**Odgovor:** **Po projektu.** Vidljiv je jedan dizajn: tanak framework, jaka kontrola nad HTTP slojem, bezbednost i višejezičnost ugrađeni rano, CMS-kompatibilan ruting.

**Ukupna ocena: 8/10 (preporuka)**  
Arhitektura je zdrava za namenu; najveći skok kvaliteta bi dao **ujednačen autoloading i smanjenje bootstrap šuma**, uz disciplinu oko produkcionog logovanja.

---

*Audit završen nezavisnim pregledom koda u radnom stablu; nije kopiran ni parafraziran tekst drugih audit izveštaja.*
