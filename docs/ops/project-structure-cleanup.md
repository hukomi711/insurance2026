# Project Structure And Cleanup

## Current Structure

```text
app/                 Laravel application code
bootstrap/           Laravel bootstrap/cache entrypoints
config/              Laravel and service configuration
database/            Migrations, seeders, factories
deploy/              VPS and new-server deployment workflows
docker/              Docker, nginx, certbot, and container configuration
docs/                Operational, legal, export, and analysis documentation
public/              Web root and Vite build output
resources/           Vue SPA, Blade shell, CSS, and source assets
routes/              Laravel web/API routes
scripts/             Operational utility scripts
storage/             Runtime storage, logs, cache, exports
tests/               Feature/unit tests
```

## Cleaned Now

Removed local/generated artifacts that should not be part of the project tree:

- `.tmp_home.html`
- `.tmp_home2.html`
- `.tmp_manifest.json`
- `.tmp_manifest2.json`
- `.playwright-cli/`
- `insurance2026-vps.tar.gz`
- `insurance2026-release.tar.gz`
- `insurance2026`
- empty `downloads/`
- empty `todo-app/`

Repaired root documentation:

- `DEPLOYMENT-GUIDE.md` was empty but referenced from several files, so it now points to the active deployment docs instead of being deleted.

Reorganized duplicated operational files:

- Root deployment docs moved under `docs/deployment/`.
- Root operational fix/review docs moved under `docs/ops/`.
- Legacy root deployment scripts moved under `deploy/legacy/`.
- Admin/server repair scripts moved under `deploy/admin/`.
- Utility scripts grouped under `scripts/dev/`, `scripts/quality/`, `scripts/export/`, `scripts/health/`, `scripts/admin/`, and `scripts/maintenance/`.
- The stray public key file moved from the project root to `deploy/keys/`.

## Keep

These are active or potentially active project surfaces and should stay:

- `app/`, `config/`, `database/`, `resources/`, `routes/`, `tests/`
- `docker/` and `docker-compose.yml`
- `deploy/new-server/` as the main server runbook/workflow
- `deploy/lexusforbon-remote-deploy.sh` for direct remote deployment
- `deploy/legacy/` for older manual deployment paths that may still be useful
- `deploy/admin/` for server/admin repair helpers
- `scripts/export/export-production-data.sh` and related operational scripts
- `docs/legal/`, `docs/deployment/`, `docs/ops/`

## Current Target Layout

```text
deploy/
  admin/
  new-server/
  legacy/
  lexusforbon-remote-deploy.sh
docs/
  deployment/
  ops/
  exports/
  legal/
scripts/
  admin/
  dev/
  export/
  health/
  maintenance/
  quality/
```

## Verification Commands

```bash
rg --hidden -n "<old-domain-pattern>" --glob "!.git/**" --glob "!node_modules/**" --glob "!vendor/**" .
npm run build
bash -n deploy/lexusforbon-remote-deploy.sh
bash -n scripts/export/export-production-data.sh
bash -n deploy/legacy/deploy-extract-and-build.sh
bash -n deploy/admin/production-setup.sh
```
