# Plan 05 support: hosting transfer, release paket i rollback

## Selidba hostinga sa SSH pristupom

- Napravi backup baze i `app/storage/` foldera.
- Deployuj source ili release tag.
- Prenesi server-specific `app/.env` vrednosti.
- Pokreni:
  - `composer install --working-dir=app --no-dev --optimize-autoloader`
  - `npm ci`
  - `npm run build`
  - `php app/bin/console storage:prepare`
  - `php app/bin/console install:check`
  - `php app/bin/console db:migrate`
  - `php app/bin/console cache:clear`
- Uradi smoke test `/`, `/sr/login`, `/sr/dashboard`, `/api/status`.
  - Za CLI-only smoke posle fresh install-a validirani su `/sr`, `/sr/login` i `/sr/demo`; `/` moze vratiti prazan body ako aplikacija salje redirect header bez HTML tela.

## Hosting bez SSH / bez Composer i NPM

- Lokalno napravi paket:
  - `bash scripts/build-release-package.sh`
- Uploaduj ZIP na hosting i raspakuj.
- Kopiraj `app/.env.example` u `app/.env` i upiši DB + domen vrednosti kroz hosting file manager.
- Proveri da je web root usmeren na `public_html/`.
- Proveri da su writable:
  - `app/storage/cache`
  - `app/storage/logs`
  - `app/storage/rate_limits`
  - `app/storage/uploads`
  - `app/storage/avatars`
- Ako hosting ima terminal samo za PHP, pokreni:
  - `php app/bin/console install:check`
  - `php app/bin/console db:migrate --seed`
  - `php app/bin/console cache:clear`

## Rollback pravilo

- Ako release menja samo PHP/JS kod bez nereverzibilne DB migracije, vrati prethodni ZIP ili prethodni Git tag i pusti `php app/bin/console cache:clear`.
- Ako release ima DB migracije bez `db:rollback`, rollback podrazumeva restore DB backup-a i restore `app/storage/` backup-a.
- Pre svakog deploy-a koji dira DB, backup baze je obavezan.
