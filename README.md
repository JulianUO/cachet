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

This repository is a **fork** of the official [Cachet](https://github.com/cachethq/cachet) project. It is **not** an official Cachet product and is **not** affiliated with, endorsed by, or sponsored by Alt Three Services Limited, Cachet Inc., or the Cachet maintainers. We are grateful to **James Brooks**, **Alt Three Services Limited**, and the [CachetHQ](https://github.com/cachethq) community for building and maintaining Cachet.

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

## Credits, license, and trademarks

Deep thanks to the original authors and maintainers of Cachet — especially **James Brooks** and **Alt Three Services Limited** — and to everyone who contributes upstream at [cachethq/cachet](https://github.com/cachethq/cachet) and [cachethq/core](https://github.com/cachethq/core).

This software remains under the **Cachet license** from Alt Three Services Limited. A verbatim copy ships in [`LICENSE.md`](LICENSE.md). Copyright notices in source files must be preserved. Distributions of this software must include that license and pass along its warranty disclaimer and limitation of liability.

**Cachet** names and logos are trademarks of Cachet / Alt Three (see [`TRADEMARKS.md`](TRADEMARKS.md)). This fork does not claim those marks. Use of Cachet trademarks must follow the upstream [trademark policy](https://github.com/cachethq/core/blob/main/TRADEMARKS.md). Truthful statements such as “derived from the Cachet software” or “powered by Cachet” are fine; do not imply official endorsement.

Operator summary: [`docs/license-and-attribution.md`](docs/license-and-attribution.md).

## Security Vulnerabilities

If you discover a security vulnerability within Cachet, please send an e-mail to [support@cachethq.io](mailto:support@cachethq.io?Cachet%20Security%20Vulnerability). All security vulnerabilities are reviewed on a case-by-case basis.
