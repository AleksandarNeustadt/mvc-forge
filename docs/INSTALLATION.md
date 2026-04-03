# Installation Guide

## Fresh Self-Hosted Install

Use this flow for a new deployment on cPanel, HestiaCP, or a VPS.

```bash
git clone <private-repo-url> mvc-forge
cd mvc-forge

composer install --working-dir=app --no-dev --optimize-autoloader
npm ci
npm run build

cp app/.env.example app/.env
```

Edit `app/.env`, then run:

```bash
php app/bin/console storage:prepare
php app/bin/console app:key
php app/bin/console install:check
php app/bin/console db:migrate --seed --admin-email=admin@example.com --admin-username=admin
php app/bin/console cache:clear
```

Point the web server document root to `public_html/`.

## Existing Hosting Transfer

Before migration, back up the database and `app/storage/`.

```bash
composer install --working-dir=app --no-dev --optimize-autoloader
npm ci
npm run build

php app/bin/console storage:prepare
php app/bin/console install:check
php app/bin/console app:key --force
php app/bin/console db:migrate --baseline
php app/bin/console cache:clear
```

Use `db:migrate --baseline` only once on legacy databases that already have the schema but do not yet have `schema_migrations` records.

## Shared Hosting Without SSH

Build a release package locally:

```bash
bash scripts/build-release-package.sh
```

Upload the generated ZIP from `release/`, extract it on the hosting server, create `app/.env` from `app/.env.example`, and then run the CLI install commands if SSH/PHP CLI is available on that host.

## Troubleshooting Checklist

- `public_html/index.php` exists and `public_html/` is the document root.
- `app/.env` exists and has correct DB credentials and `APP_URL`.
- Storage directories under `app/storage/` are writable.
- Required PHP extensions are installed.
- `php app/bin/console install:check` returns `[OK] install:check passed`.
