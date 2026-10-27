# Integrations UI (Mail / Auth / Slack)

Integration settings are managed in the **admin dashboard** as the source of truth. Environment variables are optional bootstrap until you save in the UI.

| Dashboard page | Purpose |
| --- | --- |
| **Integrations → Authentication** | Local, OIDC, LDAP |
| **Integrations → Mail** | Outbound SMTP mailer + test email |
| **Integrations → Slack notifications** | Native Slack delivery |

## Mail

- Prefer saving a mailer here over `MAIL_*` once you are past first boot.
- Leaving mailer empty keeps env bootstrap.
- Redis / AWS remain Laravel infrastructure env — not on this page.
- Subscriber *toggles* stay under **Settings → Notifications**.

## Secrets

Secret fields (client secret, bind password, SMTP password, webhook URL, bot token) are:

- Stored encrypted / securely in settings
- Never re-shown after save (leave blank to keep the current value)
- Never printed in status panels or delivery failure messages

## Related

- [Authentication](authentication.md)
- [Slack notifications](slack-notifications.md)
- `.env.example` comments for bootstrap keys
