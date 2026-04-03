# Release Process

## Versioning

Use Semantic Versioning for Git tags and release artifacts.

Recommended starting point for this framework package:

- `v0.1.0` for the first private package release,
- minor increments while the public API is still evolving,
- `v1.0.0` only after install flow, package boundaries, and upgrade guarantees are stable.

## Release Checklist

- Run backend checks:

```bash
cd app
composer check
```

- Run a production frontend build:

```bash
npm run build
```

- Update `CHANGELOG.md`.
- Tag the release:

```bash
git tag v0.1.0
git push origin master --tags
```

- Build a hosting ZIP package:

```bash
bash scripts/build-release-package.sh
```

## Rollback

- Restore the previous Git tag or release ZIP.
- Restore database backup if migrations are not reversible.
- Restore `app/storage/` backup if uploads or runtime data were affected.
- Run `php app/bin/console cache:clear`.
