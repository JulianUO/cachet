# History & calendar

Phase 1 status UX: Atlassian-style **90-day bars** on the status page, plus a public **month calendar**.

## 90-day uptime bars

When system status display is enabled, each component (and group summary) shows:

- A status badge (operational, partial/major outage, maintenance, …)
- Full-width day bars for the last 90 days (usable on mobile)
- Tooltips with date and outage/maintenance summary
- Links into calendar detail when events exist for that day

Labels and copy use the `status_history` language file (English + `es_AR`).

## Calendar hub (`/history`)

Public route: **`/history`** (month grid).

- Navigate previous / next month
- Days with incidents or maintenance are highlighted
- Select a day to list that day’s incidents and schedules
- Link back to the chronological incident timeline on the status page

## Operator notes

- Calendar and bars read the same incident + schedule data already in Cachet — no separate history store.
- Empty days vs disrupted days are visually distinct in the legend.
- Existing API and timeline behavior are unchanged.

## Related

- [Status page](status-page.md)
- [Localization](localization.md)
