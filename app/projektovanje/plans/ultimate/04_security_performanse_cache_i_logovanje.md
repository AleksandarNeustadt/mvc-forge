# Ultimate plan 04: Security hardening, performanse, cache i opservabilnost

## Cilj

Zadrzati jake postojece bezbednosne osnove, ali ukloniti operativni sum, zatvoriti API/CORS/CSRF nedoumice, ubrzati rutiranje i view render kroz cache, i dobiti cist centralni logging + alerting model.

## Grupa A: Centralni logger i nivo logovanja

- [x] Uvesti ili standardizovati jedan logger API sa nivoima `debug`, `info`, `warning`, `error`, `critical`.
- [x] Dodati `APP_LOG_LEVEL` u `.env.example` i mapirati default ponasanje za dev i production.
- [x] Zameniti verbose `error_log()` pozive u `Router`, `DynamicRouteRegistry`, `index.php`, `CsrfMiddleware` i slicnim mestima centralnim loggerom na `debug` nivou.
  - [x] Zamenjeni direktni `error_log()` pozivi u `Router`, `DynamicRouteRegistry` i `CsrfMiddleware`; preostala mesta se gase iterativno u istom obrascu.
- [x] Dodati jedan standardni format log poruke sa vremenom, nivoom, request id-jem i JSON kontekstom.
- [x] Uvesti `X-Request-Id` ili interni request correlation id da se jedan HTTP zahtev moze ispratiti kroz log.
- [x] Proveriti rotaciju log fajlova na hostingu; ako nije automatska, dodati dokumentovan `logrotate` ili aplikacionu daily rotaciju.
  - [x] Logger piše u dnevne `app-YYYY-MM-DD.log` fajlove; operativne napomene su dokumentovane u `support/04_log_session_csp_operativa.md`.
- [x] Za `critical` greske dodati opcioni alert kanal, npr. e-mail/Sentry, sa throttling-om da ne nastane flood.
  - [x] Dodat opcioni `APP_LOG_ALERT_EMAIL` + `APP_LOG_ALERT_THROTTLE_SECONDS` throttling.

## Grupa B: Exception handling i view engine dijagnostika

- [x] U `ExceptionHandler` jasno razdvojiti dev i production prikaz.
- [x] U dev rezimu prikazati koristan kontekst bez curenja `.env` tajni.
- [x] U production rezimu posetilac vidi kontrolisanu 500/404 stranicu, a detalj ide samo u log.
  - [x] JSON i HTML error odgovori sada nose `request_id`, a detaljan exception kontekst ostaje vezan za `APP_DEBUG=true`.
- [x] U `ViewEngine` greskama dodati naziv template-a i putanju izvornog fajla.
- [x] Spakovati parse/compile greske tako da developer dobije citljivu poruku, a ne "beli ekran".
- [x] Sprediti ostavljanje polupisanog view cache fajla atomarnim upisom ili brisanjem temp artefakta pri neuspehu.

## Grupa C: Cache ruta i view kompilata

- [x] Izmeriti trenutno stanje: broj DB citanja route liste po requestu i baseline vreme za nekoliko tipicnih ruta.
  - [x] Dodat support zapis `support/04_route_cache_i_view_cache.md` sa cold/warm mikro benchmarkom za `/o-autoru`.
- [x] Uvesti cache dinamikih ruta iz baze, preporuka file/APCu-friendly PHP cache sa verzijom ili `updated_at` invalidacijom.
- [x] Implementirati pouzdanu invalidaciju route cache-a na create/update/delete page operacijama.
- [x] Dodati fallback: ako je cache fajl korumpiran, obrisati ga, povuci iz baze i regenerisati.
- [x] Standardizovati `VIEW_CACHE=true|false` ponasanje po okruzenju.
- [x] Obezbediti atomarni upis cache fajlova i ispravne dozvole nad `app/storage/cache`.

## Grupa D: API security matrica

- [x] Popisati sve rute iz `routes/api.php` i `routes/dashboard-api.php` u tabelu: metoda, putanja, auth model, CSRF status, CORS status, rate limit.
  - [x] Dodata matrica `support/04_api_security_matrica.md`.
- [x] Jasno razdvojiti session-based browser API od Bearer-token API ruta.
- [x] Za Bearer-token API proveriti da CSRF nije pogresno forsiran, ali da CORS i auth jesu korektno definisani.
- [x] Za cookie/session API proveriti da state-changing zahtevi imaju CSRF ili strict origin model.
  - [x] `routes/dashboard-api.php` sada koristi `auth + CsrfMiddleware`.
- [x] U `CorsMiddleware` zabraniti opasan `*` + credentials scenario i definisati production allowlist.
- [x] Za javne i osetljive endpoint-e proveriti rate-limit pokrivenost i adekvatnost kljuca limita.
  - [x] Rate-limit ključ sada uključuje sesijskog korisnika, Bearer/query-token fingerprint ili IP fallback.

## Grupa E: Session i autentikacioni hardening

- [x] Potvrditi `session_regenerate_id` posle login-a i jasnu invalidaciju sesije na logout.
- [x] Proveriti timeout, idle expiry i session cookie flagove (`HttpOnly`, `Secure`, `SameSite`).
- [x] Pregledati da li auth/debug logovi ikad upisuju token, password, session id ili previse detaljan request body; sve takve zapise ukloniti ili maskirati.
  - [x] API auth/login logovi sada beleže samo prisustvo polja i hash fingerprint tokena, bez raw secret vrednosti.

## Grupa F: CSP, frontend bezbednost i admin editor izuzeci

- [x] Pregledati `SecurityHeadersMiddleware::buildCSP()` i proveriti da svi inline script/style blokovi koriste nonce ili su uklonjeni.
  - [x] Dodati nonce na preostale inline `<script>` blokove u user/blog formama i `language-select` helper-u.
- [x] Ako admin editor ili third-party CDN traze CSP izuzetak, dokumentovati tacan kompromis i svesti ga na najmanji moguci scope.
  - [x] Kompromisi za TinyMCE/CDN i `style-src 'unsafe-inline'` dokumentovani u `support/04_log_session_csp_operativa.md`.
- [x] Potvrditi da error stranice i dalje dobijaju bezbednosna zaglavlja.
  - [x] `ExceptionHandler` fallback sada šalje osnovna security zaglavlja i restriktivan CSP.

## Kriterijumi zavrsetka

- [x] Produkcijski request vise ne puni `error.log` debug sumom, ali dev rezim i dalje daje detaljnu trasu.
- [x] Dinamicke rute i view render postaju merljivo brzi ili barem sa manje ponovljenih DB i filesystem troskova.
- [x] API security model je dokumentovan po ruti i nema nejasnog CSRF/CORS/Auth ponasanja.
- [x] Posetilac u produkciji ne vidi sirove PHP/SQL greske, a developer u dev-u vidi precizan izvor problema.
