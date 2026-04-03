# Plan 04 support: API security matrica

## Princip razdvajanja

- `routes/api.php` je Bearer-token API za programatski pristup.
- `routes/dashboard-api.php` je session/cookie API za browser dashboard.
- CSRF se primenjuje samo na session/cookie API i HTML forme.
- Bearer-token API ne forsira CSRF, ali mora imati auth + CORS + rate limit.

## `routes/api.php`

| Metoda | Putanja | Auth model | CSRF | CORS | Rate limit |
|---|---|---|---|---|---|
| POST | `/api/auth/login` | javno, username/password -> Bearer token | ne | `cors` + allowlist | `10/60s`, ključ `IP` |
| POST | `/api/auth/logout` | `ApiAuthMiddleware`, Bearer token | ne | `cors` + allowlist | `1000/60s`, ključ `Bearer fingerprint` |
| GET | `/api/auth/me` | `ApiAuthMiddleware`, Bearer token | ne | `cors` + allowlist | `1000/60s`, ključ `Bearer fingerprint` |
| GET | `/api/pages` | `ApiAuthMiddleware`, Bearer token | ne | `cors` + allowlist | `1000/60s`, ključ `Bearer fingerprint` |
| GET | `/api/pages/{id}` | `ApiAuthMiddleware`, Bearer token | ne | `cors` + allowlist | `1000/60s`, ključ `Bearer fingerprint` |
| POST | `/api/pages` | `ApiAuthMiddleware`, Bearer token | ne | `cors` + allowlist | `1000/60s`, ključ `Bearer fingerprint` |
| PUT | `/api/pages/{id}` | `ApiAuthMiddleware`, Bearer token | ne | `cors` + allowlist | `1000/60s`, ključ `Bearer fingerprint` |
| DELETE | `/api/pages/{id}` | `ApiAuthMiddleware`, Bearer token | ne | `cors` + allowlist | `1000/60s`, ključ `Bearer fingerprint` |
| GET | `/api/menus` | `ApiAuthMiddleware`, Bearer token | ne | `cors` + allowlist | `1000/60s`, ključ `Bearer fingerprint` |
| GET | `/api/menus/{id}` | `ApiAuthMiddleware`, Bearer token | ne | `cors` + allowlist | `1000/60s`, ključ `Bearer fingerprint` |
| POST | `/api/menus` | `ApiAuthMiddleware`, Bearer token | ne | `cors` + allowlist | `1000/60s`, ključ `Bearer fingerprint` |
| PUT | `/api/menus/{id}` | `ApiAuthMiddleware`, Bearer token | ne | `cors` + allowlist | `1000/60s`, ključ `Bearer fingerprint` |
| DELETE | `/api/menus/{id}` | `ApiAuthMiddleware`, Bearer token | ne | `cors` + allowlist | `1000/60s`, ključ `Bearer fingerprint` |
| GET | `/api/posts` | `ApiAuthMiddleware`, Bearer token | ne | `cors` + allowlist | `1000/60s`, ključ `Bearer fingerprint` |
| GET | `/api/posts/{id}` | `ApiAuthMiddleware`, Bearer token | ne | `cors` + allowlist | `1000/60s`, ključ `Bearer fingerprint` |
| POST | `/api/posts` | `ApiAuthMiddleware`, Bearer token | ne | `cors` + allowlist | `1000/60s`, ključ `Bearer fingerprint` |
| POST | `/api/posts/bulk` | `ApiAuthMiddleware`, Bearer token | ne | `cors` + allowlist | `1000/60s`, ključ `Bearer fingerprint` |
| PUT | `/api/posts/{id}` | `ApiAuthMiddleware`, Bearer token | ne | `cors` + allowlist | `1000/60s`, ključ `Bearer fingerprint` |
| DELETE | `/api/posts/{id}` | `ApiAuthMiddleware`, Bearer token | ne | `cors` + allowlist | `1000/60s`, ključ `Bearer fingerprint` |
| GET | `/api/categories` | `ApiAuthMiddleware`, Bearer token | ne | `cors` + allowlist | `1000/60s`, ključ `Bearer fingerprint` |
| GET | `/api/categories/{id}` | `ApiAuthMiddleware`, Bearer token | ne | `cors` + allowlist | `1000/60s`, ključ `Bearer fingerprint` |
| POST | `/api/categories` | `ApiAuthMiddleware`, Bearer token | ne | `cors` + allowlist | `1000/60s`, ključ `Bearer fingerprint` |
| PUT | `/api/categories/{id}` | `ApiAuthMiddleware`, Bearer token | ne | `cors` + allowlist | `1000/60s`, ključ `Bearer fingerprint` |
| DELETE | `/api/categories/{id}` | `ApiAuthMiddleware`, Bearer token | ne | `cors` + allowlist | `1000/60s`, ključ `Bearer fingerprint` |
| GET | `/api/tags` | `ApiAuthMiddleware`, Bearer token | ne | `cors` + allowlist | `1000/60s`, ključ `Bearer fingerprint` |
| GET | `/api/tags/{id}` | `ApiAuthMiddleware`, Bearer token | ne | `cors` + allowlist | `1000/60s`, ključ `Bearer fingerprint` |
| POST | `/api/tags` | `ApiAuthMiddleware`, Bearer token | ne | `cors` + allowlist | `1000/60s`, ključ `Bearer fingerprint` |
| PUT | `/api/tags/{id}` | `ApiAuthMiddleware`, Bearer token | ne | `cors` + allowlist | `1000/60s`, ključ `Bearer fingerprint` |
| DELETE | `/api/tags/{id}` | `ApiAuthMiddleware`, Bearer token | ne | `cors` + allowlist | `1000/60s`, ključ `Bearer fingerprint` |
| GET | `/api/languages` | `ApiAuthMiddleware`, Bearer token | ne | `cors` + allowlist | `1000/60s`, ključ `Bearer fingerprint` |
| GET | `/api/languages/{id}` | `ApiAuthMiddleware`, Bearer token | ne | `cors` + allowlist | `1000/60s`, ključ `Bearer fingerprint` |
| GET | `/api/languages/code/{code}` | `ApiAuthMiddleware`, Bearer token | ne | `cors` + allowlist | `1000/60s`, ključ `Bearer fingerprint` |
| POST | `/api/languages` | `ApiAuthMiddleware`, Bearer token | ne | `cors` + allowlist | `1000/60s`, ključ `Bearer fingerprint` |
| PUT | `/api/languages/{id}` | `ApiAuthMiddleware`, Bearer token | ne | `cors` + allowlist | `1000/60s`, ključ `Bearer fingerprint` |
| DELETE | `/api/languages/{id}` | `ApiAuthMiddleware`, Bearer token | ne | `cors` + allowlist | `1000/60s`, ključ `Bearer fingerprint` |

## `routes/dashboard-api.php`

| Metoda | Putanja | Auth model | CSRF | CORS | Rate limit |
|---|---|---|---|---|---|
| GET | `/api/dashboard/{app}` | `auth` session | nije potreban za GET | same-origin browser | nema route-level limita |
| GET | `/api/dashboard/{app}/{id}/show` | `auth` session | nije potreban za GET | same-origin browser | nema route-level limita |
| POST | `/api/dashboard/{app}/create` | `auth` session | `CsrfMiddleware` | same-origin browser | nema route-level limita |
| POST | `/api/dashboard/{app}/{id}/update` | `auth` session | `CsrfMiddleware` | same-origin browser | nema route-level limita |
| PUT | `/api/dashboard/{app}/{id}` | `auth` session | `CsrfMiddleware` | same-origin browser | nema route-level limita |
| DELETE | `/api/dashboard/{app}/{id}/delete` | `auth` session | `CsrfMiddleware` | same-origin browser | nema route-level limita |
| GET | `/api/dashboard/filter-options/{filterType}` | `auth` session | nije potreban za GET | same-origin browser | nema route-level limita |
| POST | `/api/dashboard/{app}/{id}/{action}` | `auth` session | `CsrfMiddleware` | same-origin browser | nema route-level limita |

## Napomene

- `CorsMiddleware` ne dozvoljava `Access-Control-Allow-Origin: *` zajedno sa `Access-Control-Allow-Credentials: true`.
- `CORS_ALLOWED_ORIGINS` je production allowlist; ako nije zadat, fallback je `APP_URL`, a tek onda `*` bez credentialsa.
- `RateLimitMiddleware` sada gradi ključ po `method + path + identity`, gde je identity `session user`, `Bearer fingerprint`, query token fingerprint, ili IP fallback.
- API auth i login logovi više ne zapisuju sirov token, password ili ceo request body; čuva se samo hash fingerprint ili prisustvo polja.
