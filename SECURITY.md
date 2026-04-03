# Security Policy

MVC Forge is maintained as a private repository. Please report security issues privately and do not open public issues for vulnerabilities.

## Reporting a Vulnerability

Send a direct report to the repository owner with:

- a short vulnerability summary,
- affected endpoint, command, or component,
- reproduction steps,
- impact assessment,
- suggested remediation if available.

## Security Baseline

- Secrets must live only in `app/.env`.
- Never commit credentials, API tokens, mail passwords, production logs, or private uploads.
- Keep `APP_DEBUG=false` in production.
- Rotate `APP_KEY` only during planned maintenance and understand that session invalidation may occur.
- Run `php app/bin/console install:check` after deployment or migration.
- Review CSP, CORS, CSRF, rate-limit, and session changes with extra care.

## Dependency Checks

```bash
cd app
composer audit
```

Frontend dependencies should be reviewed before upgrades and validated with a production build.
