# Contributing

Thanks for improving MVC Forge.

## Workflow

- Create a feature branch from `master`.
- Keep changes focused and avoid mixing unrelated refactors with feature work.
- Do not commit `app/.env`, secrets, logs, `node_modules/`, `app/vendor/`, or generated runtime files.
- Update documentation and `CHANGELOG.md` when behavior, install steps, or release packaging changes.
- Add or update tests for application logic, database behavior, routing, and security-sensitive code.

## Local Checks

```bash
cd app
composer check
```

```bash
npm run build
```

## Code Style

- Use strict PHP typing for new code where practical.
- Keep controllers thin and move reusable logic into core services or model layers.
- Preserve PSR-4 namespaces and existing directory conventions.
- Prefer explicit validation, authorization, and CSRF checks over implicit trust in request data.

## Pull Requests

- Describe the problem, implementation, and test coverage.
- Document database migration impact, environment variable changes, and rollback notes.
- Include screenshots for UI changes when relevant.
- Wait for CI to pass before merging.
