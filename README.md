<p align="center">
    <picture>
      <source media="(prefers-color-scheme: dark)" srcset="art/cachet-core-dark.svg">
      <img alt="Cachet Core" src="art/cachet-core-light.svg">
    </picture>
</p>

<p align="center">
    <a href="https://github.com/cachethq/cachet/actions">
        <img src="https://github.com/cachethq/cachet/workflows/run-tests/badge.svg" alt="Build Status">
    </a>
    <a href="https://github.com/sponsors/cachethq/?sponsor=1">
        <img src="https://img.shields.io/github/sponsors/cachethq" alt="GitHub Sponsors">
    </a>
    <a href="https://github.com/cachethq/cachet/stargazers">
        <img src="https://img.shields.io/github/stars/cachethq/cachet" alt="GitHub Stars">
    </a>
</p>

Cachet, the open source self-hosted status page system.

## Main branch: `3.x`

This fork’s default development branch is **[`3.x`](https://github.com/JulianUO/cachet/tree/3.x)**. Open PRs against `3.x`.

Upstream announcement: [Cachet 3.x discussion](https://github.com/CachetHQ/Cachet/discussions/4342).

## What’s in this fork

Beyond Cachet 3.x core, operators get:

- **Status history** — 90-day uptime bars plus a public `/history` calendar
- **OIDC / LDAP** — dashboard login via OpenID Connect (Keycloak, etc.) or LDAP
- **Permission matrix** — roles mapped from IdP/LDAP groups
- **Slack notifications** — native channel posts (preferred over `/slack.rss`)
- **Integrations UI** — Mail, OIDC/LDAP, and Slack configured in the admin dashboard
- **Docker** — production-oriented `Dockerfile` + `docker-compose.yml`
- **Spanish (Argentina)** — `es_AR` locale for the status page and new admin UI

Operator docs live in **[`docs/`](docs/README.md)**.

## Requirements

- PHP **8.3.36+**, **8.4.27+**, or **8.5.12+** (required for CVE-2026-109618 / CVE-2026-109619)
- [Composer](https://getcomposer.org)
- A supported database: MariaDB, MySQL, PostgreSQL or SQLite

## Quick links

| Topic | Link |
| --- | --- |
| Operator docs (this repo) | [docs/README.md](docs/README.md) |
| Docker | [docs/docker.md](docs/docker.md) |
| Authentication (OIDC/LDAP) | [docs/authentication.md](docs/authentication.md) |
| Localization / `es_AR` | [docs/localization.md](docs/localization.md) |
| Upstream install docs | [docs.cachethq.io](https://docs.cachethq.io/v3.x/installation) |

### Demo (upstream)

- **URL:** [v3.cachethq.io/dashboard](https://v3.cachethq.io/dashboard)
- **Email:** `test@test.com` · **Password:** `test123`
- Resets every 30 minutes.

## Sponsors

<p align="center">
    <a href="https://jump24.co.uk"><img width="100px" src="https://github.com/jumptwentyfour.png" alt="Jump24"></a>
    <a href="https://xyphen-it.nl"><img width="100px" src="https://github.com/xyphen-it.png" alt="Xyphen-IT"></a>
    <a href="https://coderabbit.ai/"><img width="100px" src="https://github.com/coderabbitai.png" alt="Code Rabbit"></a>
    <a href="https://scramble.dedoc.co/"><img width="100px" src="https://github.com/dedoc.png" alt="de:doc"></a>
</p>

## Security Vulnerabilities

If you discover a security vulnerability within Cachet, please send an e-mail to [support@cachethq.io](mailto:support@cachethq.io?Cachet%20Security%20Vulnerability). All security vulnerabilities are reviewed on a case-by-case basis.
