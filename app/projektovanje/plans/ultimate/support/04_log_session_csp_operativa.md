# Plan 04 support: log rotacija, alerting, session i CSP operativa

## Log rotacija i alerting

- Aplikacioni logger piše u `app/storage/logs/app-YYYY-MM-DD.log`, što daje dnevnu rotaciju na nivou fajla.
- Na Hestia/hostingu treba ostaviti uključenu standardnu sistemsku rotaciju za webserver/PHP logove, jer `php_error.log` i panel logovi nisu isto što i aplikacioni JSON log.
- `APP_LOG_LEVEL` kontroliše debug šum; za produkciju default ide na `INFO`.
- `CRITICAL` događaji mogu opcionalno da pošalju email preko `APP_LOG_ALERT_EMAIL`.
- `APP_LOG_ALERT_THROTTLE_SECONDS` sprečava flood i ne dozvoljava interval manji od 60s.

## Session i auth hardening

- `AuthController::login()` radi `session_regenerate_id(true)` posle uspešnog logina.
- `AuthController::logout()` briše session state, invalidira session cookie i zatim regeneriše novi session ID.
- `AuthMiddleware` proverava promenu `User-Agent`, beleži IP promenu, i prekida sesiju posle 30 minuta neaktivnosti.
- Session cookie policy se postavlja u bootstrap-u sa `HttpOnly`, `SameSite=Lax`, i `Secure` na HTTPS-u ili preko `SESSION_SECURE=true`.
- Remember-me cookie sada zadržava `SameSite`, `Secure` i `HttpOnly` flagove iz session policy-ja.
- Auth/API logovi ne upisuju raw password, raw bearer token, ceo request body, ni session ID.

## CSP i frontend kompromisi

- Inline JavaScript blokovi u pregledanim view fajlovima dobijaju `nonce`.
- `script-src` ostaje `self + nonce + TinyMCE/CDN allowlist + jedan hash` za postojeći editor tok.
- `style-src 'unsafe-inline'` ostaje nameran kompromis zbog JS-driven inline style mutacija i postojećih UI biblioteka; scope je i dalje vezan na `self` + poznate CDN izvore.
- Fallback error stranice iz `ExceptionHandler` sada šalju osnovna security zaglavlja i restriktivan CSP, čak i ako zahtev pukne pre middleware pipeline-a.
- Ako se kasnije izbacuju inline style zavisnosti iz editora/UI-a, prva sledeća meta je uklanjanje `unsafe-inline` iz `style-src`.
