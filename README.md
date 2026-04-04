# MVC Forge

MVC Forge is a private PHP MVC/CMS framework for SEO-oriented publishing websites, multilingual content, admin workflows, and self-hosted deployments with `public_html/` as the web root.

## Highlights

- Custom PHP MVC application structure with PSR-4 autoloading
- Multilingual routing, pages, blog posts, categories, tags, and navigation menus
- Authentication, roles/permissions, CSRF protection, security headers, and rate limiting
- CLI install/deploy workflow through `app/bin/console`
- Reproducible database migrations, system seed data, and optional demo content
- Vite/Tailwind frontend build pipeline with distributable release ZIP packaging
- PHPUnit, PHPStan, lint checks, and GitHub Actions CI

## Requirements

- PHP 8.2+
- MariaDB/MySQL
- PHP extensions: `pdo`, `pdo_mysql`, `json`, `mbstring`, `openssl`, `fileinfo`
- Composer 2
- Node.js 18+ and npm
- Web server document root pointing to `public_html/`

## Quick Start

```bash
git clone <private-repo-url> mvc-forge
cd mvc-forge

composer install --working-dir=app --no-dev --optimize-autoloader
npm ci
npm run build

php app/bin/console install:setup --mode=hosting --domain=example.com
# Review DB_* values in app/.env
php app/bin/console install:check
php app/bin/console db:migrate --seed --admin-email=admin@example.com --admin-username=admin
php app/bin/console cache:clear
```

If `--admin-password` is omitted, `db:seed` generates a one-time password and prints it in the terminal.

## Documentation

- [Installation Guide](docs/INSTALLATION.md)
- [Private GitHub Composer Setup](docs/PRIVATE_GITHUB_INSTALL.md)
- [Architecture Overview](docs/ARCHITECTURE.md)
- [CLI Reference](docs/CLI.md)
- [Environment Reference](docs/ENVIRONMENT.md)
- [Release Process](docs/RELEASE_PROCESS.md)
- [Security Policy](SECURITY.md)
- [Contributing Guide](CONTRIBUTING.md)
- [Support](SUPPORT.md)
- [Changelog](CHANGELOG.md)

## Development

Local scaffold/update:

```bash
php app/bin/console install:setup --mode=local --domain=localhost
```

```bash
cd app
composer check
```

```bash
npm run build
```

## Release Packaging

```bash
bash scripts/build-release-package.sh
```

The script builds a timestamped `mvc-forge-v<version>-<timestamp>.zip` package under `release/`, including production assets and Composer dependencies, while excluding `.env`, logs, cache, uploads, and local tooling directories.

## License

This repository is private and distributed under a proprietary, all-rights-reserved license. See [LICENSE](LICENSE).
