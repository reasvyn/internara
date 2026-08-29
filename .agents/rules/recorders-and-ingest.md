# Pulse Recorders & Ingest — What Is Captured and Where

Recorders define what Pulse captures; the ingest driver defines where captured samples go. The two
must be chosen together against the deployment tier: the recorder set names the observable metrics,
and the ingest driver determines whether those metrics survive production traffic.

---

## Intent

Enable recorders in `config/pulse.php`. Ingest is `redis` for production and `file` for development.
The recorder set below is the standard Internara collection; enable exactly the recorders the
observability contract calls for.

## Rationale — What Fails Without It

- **Recorders disabled/changed ad hoc** break the observability contract (`docs/guides/
  system-observability.md`) — a recorded metric vanishes and a performance regression goes dark.
- **`file` ingest in production** is single-process; concurrent worker/queue processes overwrite or
  lose samples, so counts/histograms silently under-report.
- **`redis` ingest in development** imposes a Redis dependency nobody intended for local coding.
- **Recording everything always** bloats the ingest stream and retention; a focused set matches what
  ops can actually act on.

## How to Apply

Enable in `config/pulse.php` under `recorders`:

| Recorder               | What It Captures             |
| ---------------------- | ---------------------------- |
| `SlowRequests`         | Requests exceeding threshold |
| `SlowJobs`             | Slow queue jobs              |
| `SlowQueries`          | Slow database queries        |
| `SlowOutgoingRequests` | Slow HTTP calls              |
| `Exceptions`           | Exception frequency          |
| `Cache`                | Cache hit/miss ratio         |
| `UserSessions`         | Active user count            |

- Start from the full set above (it is the Internara default contract) and prune only where the
  spec/deployment doc explicitly excludes a recorder.
- Set ingest by tier:

```php
// config/pulse.php
'ingest' => [
    'driver' => env('PULSE_INGEST_DRIVER', 'file'),
    // production .env: PULSE_INGEST_DRIVER=redis
],
```

- Match `PULSE_INGEST_DRIVER` to deployment (`docs/guides/infra/deployment.md`): Redis in
  production, file in dev.

## Anti-Patterns & Pitfalls

- `PULSE_INGEST_DRIVER=file` committed into the production `.env.example` — a default that silently
  loses metrics in prod.
- Enabling `SlowQueries`, `UserSessions`, etc. but never consuming them — ingest noise with no
  stakeholder.
- Editing the recorder list while `system-observability.md` still lists the old set — docs drift and
  future agents misread expected observability.
- Ignoring queue/Redis availability: choosing `redis` ingest while Redis is down is livelier than
  `file` — verify `Redis` connection on the target tier.

## Verification

- `config/pulse.php` recorder list matches `docs/guides/system-observability.md`.
- Ingest driver equals the deployment tier's documented choice.
- Dashboard shows populated samples after load (requests appear); a load test against a dummy request
  confirms `SlowRequests`/`UserSessions` stream in.
