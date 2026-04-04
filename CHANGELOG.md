# Changelog

All notable changes to MVC Forge are documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and releases use SemVer tags such as `v0.1.0`.

## [Unreleased]

### Added
- Private GitHub/Composer installation guide
- Architecture, CLI, installation, environment, release, support, security, and contribution documentation
- GitHub issue templates and pull request template
- `install:setup` command with local/hosting modes, public scaffold generation, `.env` bootstrap, and hosting diagnostics
- `docs/AI_CONTENT_WORKFLOW.md` with prompt templates and API-first publishing workflow for AI agents

## [0.1.0] - 2026-04-03

### Added
- `app/bin/console` install/deploy komande: `install:check`, `storage:prepare`, `cache:clear`, `db:migrate`, `db:seed`
- Root `README.md` fresh install i hosting transfer tok
- Release packaging skripta `scripts/build-release-package.sh`
- `schema_migrations` evidencija izvršenih migracija

### Changed
- Package identity renamed to `mvc-forge` / `mvc-forge/framework`
- Plan 04 security/logging/cache hardening
- Plan 05 install/deploy standardizacija i GitHub-ready repozitorijum

### Security
- API/CORS/CSRF matrica i session hardening
- Maskiranje auth/token logova i request-id log korelacija
