# Docker

This repo ships a production-oriented **Dockerfile**, **`docker-compose.yml`**, and **`docker/entrypoint.sh`**.

## Image

- Multi-stage build: Composer vendor stage + PHP/Apache runtime
- Default PHP **8.4.27** (`PHP_VERSION` build arg)
- Extensions: `pdo_mysql`, `pdo_pgsql`, `pdo_sqlite`, gd, intl, zip, …
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

**Do not** leave migrate-on-boot enabled against a shared or production remote database.

## Remote database

Point the app container at remote `DB_*` and set `CACHET_MIGRATE_ON_BOOT=0`. Prefer a dedicated compose override or plain `docker run` without the local `db` service.

## Related

- [Integrations UI](integrations-ui.md) — configure Mail/Auth/Slack after first login
- Root [README](../README.md)
