# Support

## Private Project Support

MVC Forge is currently maintained as a private framework package. Support is handled directly by the repository owner and approved collaborators.

## Before Asking for Help

- Run `php app/bin/console install:check`.
- Check that `public_html/` is the active document root.
- Verify `app/.env` values, database credentials, and `APP_URL`.
- Run `php app/bin/console storage:prepare` and `php app/bin/console cache:clear`.
- Review server/PHP error logs and application logs in `app/storage/logs/`.

## Useful References

- [Installation Guide](docs/INSTALLATION.md)
- [CLI Reference](docs/CLI.md)
- [Environment Reference](docs/ENVIRONMENT.md)
- [Private GitHub Composer Setup](docs/PRIVATE_GITHUB_INSTALL.md)
