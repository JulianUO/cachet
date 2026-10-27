# `cachethq/core` vs this app

Cachet 3.x is split across two repositories:

| Repository | Role |
| --- | --- |
| [`JulianUO/cachet`](https://github.com/JulianUO/cachet) (this repo) | Deployable Laravel app: Docker, env, fork features (OIDC/LDAP, Slack, history routes, docs) |
| [`JulianUO/cachet-core`](https://github.com/JulianUO/cachet-core) | Composer package `cachethq/core` — dashboard, models, status page, API, locales |

Upstream originals: [`cachethq/cachet`](https://github.com/cachethq/cachet) and [`cachethq/core`](https://github.com/cachethq/core). Neither fork is an official Cachet product.

## How this app uses the core fork

`composer.json` declares a VCS repository pointing at `JulianUO/cachet-core` and still requires the package name `cachethq/core` (`dev-main`). Composer resolves the package from your fork instead of Packagist/upstream.

```bash
composer update cachethq/core
```

## Security pins validated in `cachet-core`

- PHP `^8.3.36 \|\| ^8.4.27 \|\| ^8.5.12` (CVE-2026-109618 / CVE-2026-109619)
- `livewire/livewire` `^4.4.8` (GHSA-mhf7-xp6r-6qrc)
- CI: `.github/workflows/security.yml` on the core fork

## Related

- [Localization](localization.md) — `es_AR` also lives in the core package langs
- [License and attribution](license-and-attribution.md)
