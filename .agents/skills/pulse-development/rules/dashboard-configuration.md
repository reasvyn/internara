# Pulse Dashboard & Configuration — Setup Contract

> **Last updated:** 2026-08-17 **Changes:** extracted from SKILL.md — comprehensive rewrite

Laravel Pulse is configured centrally in `config/pulse.php`. The dashboard's domain, middleware
stack, enabled recorders, and ingest driver are all set there — and every one of those settings has a
deployment-tier consequence. Configuring Pulse is easy; configuring it *consistently with the
deployment tier* is the actual task.

---

## Intent

Pulse configuration lives in `config/pulse.php`. Key settings are `domain` (restrict dashboard by
subdomain), `middleware` (auth + authorization middleware group), `recorders` (which recorders are
enabled), and `ingest` (`redis` in production, `file` in development). Before configuring, read
`docs/guides/infra/deployment.md` to know which tier you are targeting.

## Rationale — What Fails Without It

- **No `domain` restriction** means the dashboard is reachable on every host of the app; a
  monitoring surface should be minimized to a deliberate host, especially in production where the app
  host is public.
- **Weak `middleware`** leaves the dashboard open to unauthored access. The middleware list is the
  enforcement point for "only signed-in admins see metrics" — see the Authorization rule.
- **Recorders enabled blindly** (or disabled by accident during refactor) silently stops capturing a
  metric — `SlowQueries` turned off is how a regression in query performance goes unnoticed.
- **Wrong `ingest` driver** is the classic failure: `file` ingest in production loses metrics under
  concurrency and single-node assumption; `redis` in development adds an unneeded dependency. The two
  violate the deployment tier the config claims to support.

## How to Apply

Cluster the settings in `config/pulse.php`:

| Setting      | Purpose                                        | Guidance                                        |
| ------------ | ---------------------------------------------- | ----------------------------------------------- |
| `domain`     | Dashboard domain (restrict by subdomain)       | Set a dashboard-only host in production         |
| `middleware` | Auth + authorization middleware group          | `auth` + the `viewPulse` Gate (see auth rule)   |
| `recorders`  | Which recorders are enabled                    | Enable the set the spec/observability doc names |
| `ingest`     | `redis` (production) or `file` (development)   | Must match the deployment tier                  |

- Read `docs/guides/system-observability.md` first — it declares which metrics Pulse must expose.
  Configure exactly that recorder set; don't enable recorders no one consumes.
- After edits, run the Verification Checklist below so config changes don't ship in a half-broken
  state.

## Verification Checklist

- [ ] Pulse dashboard accessible only by authorized roles (route + Gate — see Authorization rule)
- [ ] Recorders configured for production ingest (Redis)
- [ ] Custom cards have proper authorization
- [ ] Ingest configured appropriately for the deployment tier
- [ ] Pulse data retention set in config (`purge`/storage pruning as documented)

## Anti-Patterns & Pitfalls

- Shipping `ingest` = `file` to production "because it works locally" — metrics silently drop under
  real concurrency.
- Enabling every recorder "to be safe" — noise in the dashboard, wasted ingest, and data retention
  cost; enable only what `system-observability.md` names.
- Setting `domain` to the app's public host — defeats the restrict-by-subdomain intent.
- Editing `config/pulse.php` without checking the deployment tier — config and docs diverge and the
  next deploy surprises.

## Verification

- `config/pulse.php` values match `docs/guides/infra/deployment.md` for the target tier.
- `python3 scripts/scan_doc_links.py` clean (config changes rereference docs correctly).
- Manual: dashboard loads for an `admin`, 403/abort for others (see Authorization rule).