# Authentication (OIDC / LDAP)

Dashboard login supports:

- **Local** email/password
- **OIDC** (Keycloak and other standard OpenID Connect providers)
- **LDAP** (bind + search)

## Configure (dashboard — preferred)

After you can log in once (local admin):

1. Open **Integrations → Authentication**.
2. Toggle local / OIDC / LDAP as needed.
3. Fill issuer, client credentials, LDAP host/base DN, etc.
4. Save. **Dashboard values win** over environment bootstrap after the first save.

The status cards on that page show whether each provider is enabled and whether secrets are stored (never re-displayed).

## Environment bootstrap (optional)

Before the first dashboard save, you can seed config via `.env` (see `.env.example`): `OIDC_*`, `LDAP_*`, `AUTH_LOCAL_ENABLED`. Those vars are bootstrap only once Authentication settings are saved in the UI.

### OIDC essentials

- Discovery: `{issuer}/.well-known/openid-configuration`
- Callback: `{APP_URL}/auth/oidc/callback` (register this with the IdP)
- Optional auto-provision and group → role map (also under **Settings → Authorization**)

### LDAP essentials

- Host, port, STARTTLS/LDAPS, base DN, user filter with `{username}`
- Optional bind DN/password and group attribute / search filter
- Users can sign in on the same Filament login form

## Related

- [Authorization](authorization.md)
- [Integrations UI](integrations-ui.md)
