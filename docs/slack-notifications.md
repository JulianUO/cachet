# Slack notifications

Native Slack posting is the preferred path. The legacy **`/slack.rss`** feed remains for compatibility.

## Configure (dashboard)

1. Open **Integrations → Slack notifications**.
2. Enable Slack notifications.
3. Choose a delivery method:
   - **Incoming Webhook** (preferred) — paste the webhook URL from Slack
   - **Bot token + channel** — `xoxb-…` token and channel ID (or `#name`); app needs `chat:write`
4. Optionally set display name / icon emoji.
5. Toggle events: incidents, component status changes, maintenance/schedules.
6. Save. The side panel shows readiness and last success/failure (secrets are never shown).

No Slack secrets are required in `.env`. An optional `SLACK_HTTP_TIMEOUT` may tune HTTP timeout only.

## What gets posted

| Event | When |
| --- | --- |
| Incidents | Create / update when the incident’s notify flag allows |
| Components | Status changes |
| Maintenance | Schedule publish / updates / completion |

Messages include title, status, affected components, and a link back to the status page. Delivery failures are logged for admins (no webhook URL or bot token in logs).

## Related

- [Integrations UI](integrations-ui.md)
- [Status page](status-page.md)
