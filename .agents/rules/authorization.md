# Pulse Authorization — Who May See the Dashboard

> **Last updated:** 2026-08-17 **Changes:** extracted from SKILL.md — comprehensive rewrite

Pulse exposes application internals: request times, query latencies, cache ratios, sessions. Access
is controlled by a single Gate, `viewPulse`, and only users with the `admin` or `superadmin` role may
see it. Authorization is enforced once in a provider and must never be duplicated or bypassed in
routes.

---

## Intent

Define `Gate::define('viewPulse', ...)` in `app/Providers/AppServiceProvider.php` so only `admin` and
`superadmin` roles pass. Keep Pulse's middleware on the dashboard route so the Gate is actually
evaluated (see the Dashboard & Configuration rule for the middleware group).

## Rationale — What Fails Without It

- **No Gate** means anyone with the network path can render the dashboard — monitoring surfaces are
  treasure maps for attackers (what's slow, where the sessions are, cache layout).
- **Only checking the Role in the route middleware** (e.g., a `role:admin` middleware that misses the
  role model) under-blocks: `superadmin` is a distinct role and must be included; a role check that
  lists one and not the other locks out legitimate ops users or admits the wrong ones.
- **Enforcing inline in the controller/component** duplicates the rule and drifts — one path updated,
  another forgotten. A single Gate definition is testable and consistent.
- **Graceful-degradation errors** — without a test, a future refactor could remove
  `Gate::define('viewPulse')` and silently open the dashboard (Laravel then allows anyone).

## How to Apply

```php
// app/Providers/AppServiceProvider.php
use Illuminate\Support\Facades\Gate;

Gate::define('viewPulse', function ($user) {
    return $user->hasRole(['admin', 'superadmin']);
});
```

- Apply the middleware group on the Pulse route so the Gate runs: the `middleware` entry in
  `config/pulse.php` includes auth + authorization (see Dashboard & Configuration rule).
- Keep **both roles** in the Gate — `superadmin` exists alongside `admin` in this project.

### Test dashboard access for correct roles

- Test that `admin` and `superadmin` reach the dashboard and a student/intern gets denied
  (`assertForbidden()` or a redirect). A custom gate regression test documents this contract.
- Cover the negative case explicitly: no role → no view. (See `pest-testing` for spec-traceable tests.)

## Anti-Patterns & Pitfalls

- Adding `viewPulse` to the Gate but forgetting the route middleware — the Gate never runs, dashboard
  stays open.
- `return $user->is_admin;` on a column that doesn't exist in this schema — uses a false assumption;
  role checks live on the roles/permissions system.
- Duplicating the role check in a Livewire card and the route — two sources of truth.
- Not testing the negative case — the "open dashboard" regression slips in silently.

## Verification

- `Gate::define('viewPulse', ...)` exists in `AppServiceProvider` with both `admin` and `superadmin`.
- Dashboard accessible for `admin` / `superadmin`; denied for other roles in the feature test.
- `python3 tools/scan_security.py` reports no unguarded auth pattern on the pulse route.