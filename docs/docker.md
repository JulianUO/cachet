# Docker

This repo ships a production-oriented **Dockerfile**, **`docker-compose.yml`**, and **`docker/entrypoint.sh`**.

## Image

- Multi-stage build: Composer vendor stage + PHP/Apache runtime
- Default PHP **8.4.27** (`PHP_VERSION` build arg)
- Extensions: `pdo_mysql`, `pdo_pgsql`, `pdo_sqlite`, gd, intl, zip, ldap, …
- Document root: `public/`

```bash
docker build -t cachet:3.x .
```

## Compose (local Postgres)

```bash
docker compose up --build
```

Default compose:

- Builds the app
- Starts Postgres 16
- Sets `DB_*` to the compose database
- Runs migrations on boot when `CACHET_MIGRATE_ON_BOOT=1` (default)
- Uses local-only demo passwords (`cachet`) unless you set `DB_PASSWORD` / `POSTGRES_PASSWORD`

**Do not** leave migrate-on-boot enabled against a shared or production remote database. **Do not** ship the demo DB password to production.

## Docker Hub (GitHub Actions)

Workflow: [`.github/workflows/docker-publish.yml`](../.github/workflows/docker-publish.yml).

| Event | Behavior |
| --- | --- |
| Pull request | Build image (no push); secret scan must pass first |
| Push to `3.x` | Build and push (`3.x`, `latest`, `sha-…`) when Hub credentials exist |
| Tag `v*` | Build and push semver tags when Hub credentials exist |
| `workflow_dispatch` | Same as push rules |

### Repository secrets (required to push)

Add under **Settings → Secrets and variables → Actions**:

| Secret | Purpose |
| --- | --- |
| `DOCKERHUB_USERNAME` | Docker Hub username |
| `DOCKERHUB_TOKEN` | Docker Hub access token (not your account password) |

Optional **Actions variable**:

| Variable | Purpose |
| --- | --- |
| `DOCKERHUB_REPOSITORY` | Full image name (default: `$DOCKERHUB_USERNAME/cachet`) |

Without those secrets, CI still **builds** the image on PRs/pushes but skips the push step.

### Secret scan gate

[`.github/workflows/secret-scan.yml`](../.github/workflows/secret-scan.yml) runs Gitleaks on every PR/push (config: [`.gitleaks.toml`](../.gitleaks.toml)). The Docker publish workflow **calls the same scan** and will not build if secrets are detected.

What it checks:

- The **current working tree** (files that would ship)
- Commits introduced by the PR / push (so new leaks cannot land)

Test fixtures under `tests/`, `.env.example`, and the PHPUnit bootstrap `APP_KEY` are allowlisted. Upstream git history still contains old demo API keys from 2014–2018 in deleted files; those are **not** in `HEAD` and are not treated as a current leak. Do not commit real Docker Hub tokens, cloud keys, or IdP secrets — use GitHub Actions secrets instead.

## Remote database

Point the app container at remote `DB_*` and set `CACHET_MIGRATE_ON_BOOT=0`. Prefer a dedicated compose override or plain `docker run` without the local `db` service.

## Related

- [Integrations UI](integrations-ui.md) — configure Mail/Auth/Slack after first login
- [License and attribution](license-and-attribution.md)
- Root [README](../README.md)
