# Installation Guide

## Fresh Self-Hosted Install

Use this flow for a new deployment on cPanel, HestiaCP, or a VPS.

```bash
git clone <private-repo-url> mvc-forge
cd mvc-forge

composer install --working-dir=app --no-dev --optimize-autoloader
npm ci
npm run build
```

Run the hosting installer helper:

```bash
php app/bin/console install:setup --mode=hosting --domain=example.com
```

Edit `app/.env` DB credentials, then run:

```bash
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

## Local Install Helper

For local development, the helper can scaffold/update `public_html/`, create `.env` from `.env.example`, prepare storage, and generate `APP_KEY`:

```bash
php app/bin/console install:setup --mode=local --domain=localhost
```

## Hosting Diagnostics Helper

On hosting, use:

```bash
php app/bin/console install:setup --mode=hosting --domain=forgeng.dev
```

If server settings cannot be changed automatically, the command prints manual fix instructions. For example, if PHP-FPM `open_basedir` does not include the application directory, it will tell you which pool file to edit and what path to add.

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
