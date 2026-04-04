# CLI Reference

The operational entrypoint is `app/bin/console`.

```bash
php app/bin/console <command> [options]
```

## Commands

### `install:setup`

Prepares installation scaffolding and environment files.

Local mode:

```bash
php app/bin/console install:setup --mode=local --domain=localhost --site-name="MVC Forge Local"
```

Hosting mode:

```bash
php app/bin/console install:setup --mode=hosting --domain=forgeng.dev --site-name="ForgeNG"
```

Options:

- `--mode=local|hosting`
- `--domain=<domain>`
- `--site-name=<name>` - value written to `BRAND_NAME` in `app/.env`
- `--public-path=<path>`
- `--force` - overwrite generated `public_html/index.php`, `bootstrap_paths.php`, and `.htaccess`

If `app/.env` already exists, `install:setup` updates `APP_URL`, `CORS_ALLOWED_ORIGINS`, and `BRAND_NAME` while preserving existing DB credentials and `APP_KEY`.

### `install:check`

Checks PHP version, required extensions, `.env`, web root entrypoint, writable storage directories, and database connectivity.

```bash
php app/bin/console install:check
```

### `storage:prepare`

Creates required runtime directories under `app/storage/` and verifies they are writable.

```bash
php app/bin/console storage:prepare
```

### `cache:clear`

Clears dynamic route cache, file cache, and compiled views.

```bash
php app/bin/console cache:clear
```

### `app:key`

Creates `APP_KEY` in `app/.env`.

```bash
php app/bin/console app:key
```

Rotate an existing key:

```bash
php app/bin/console app:key --force
```

### `db:migrate`

Runs pending migrations from `app/core/database/migrations/`.

```bash
php app/bin/console db:migrate
```

Seed immediately after migrations:

```bash
php app/bin/console db:migrate --seed --admin-email=admin@example.com --admin-username=admin
```

Baseline an existing legacy database once:

```bash
php app/bin/console db:migrate --baseline
```

### `db:seed`

Seeds system data and an admin user.

```bash
php app/bin/console db:seed --admin-email=admin@example.com --admin-username=admin
```

Options:

- `--admin-email=<email>`
- `--admin-password=<password>`
- `--admin-username=<username>`
- `--demo`

If `--admin-password` is omitted, a generated password is printed once in the terminal.
