# Localization

Cachet supports regional locales via `config/cachet.php` → `supported_locales`.

This fork adds **`es_AR` — Español (Argentina)** for:

- Status history / calendar strings (`lang/es_AR/status_history.php`)
- New Integrations / Authorization / login UI (`lang/es_AR.json`)
- Cachet core admin + status strings (`lang/vendor/cachet/es_AR/…`)
- Role and permission labels; Slack notification message copy (uses the app locale)

## How to select Spanish Argentina

### A. Site default (status page + app)

1. Sign in to the dashboard as an admin.
2. Open **Settings → Manage localization** (Administrar localización).
3. Set **Locale / Idioma** to **Español (Argentina)** (`es_AR`).
4. Save.

The public status page and dashboard follow that site locale (and per-user preferred locale when set).

### B. Environment default

In `.env`:

```bash
APP_LOCALE=es_AR
APP_FALLBACK_LOCALE=en
```

Use this for fresh installs or CLI/queue workers that should emit Spanish copy (for example Slack messages).

### C. Browser / user preference

Cachet’s localization settings include a browser-default option for some displays. Prefer setting the site locale to `es_AR` explicitly so operators and visitors get consistent Argentina Spanish. Users can still pick a preferred locale in their profile when that feature is available.

## Notes

- `es_ES` remains available as Español (España).
- New feature English sources live under `lang/en/` (for example `status_history.php`); keep `es_AR` in sync when adding strings.
- Wording uses Argentina-friendly forms (e.g. *ingresá*, *configurá*) in the new UI strings while keeping neutral operator Spanish for longer help text.
