# Authorization (permission matrix)

Cachet roles control what non-admin users can do in the dashboard. Users with **`is_admin`** always bypass the matrix.

## Roles

| Role | Typical use |
| --- | --- |
| `public_viewer` | Status page only |
| `registered_viewer` | Read-only dashboard |
| `it_support` | Manage incidents / components / schedules |
| `devops` | Broader manage + settings / integrations |

Default for newly provisioned users: `registered_viewer` (`AUTH_DEFAULT_ROLE` / config).

## Permission matrix UI

**Settings → Authorization** (permission matrix):

- Checkbox matrix per role
- **OIDC / Keycloak** group → role map
- **LDAP** group CN/DN → role map
- Table to assign roles to existing users
- Save matrix / reset to config defaults

The same group maps are editable under **Integrations → Authentication**.

## Mapping precedence

When a user matches multiple external groups, the **highest privilege** role wins (`devops` > `it_support` > …).

## Related

- [Authentication](authentication.md)
- Config reference: `config/authorization.php`
