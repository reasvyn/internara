# Authentication & Authorization — Identity and Access Control

> **Last updated:** 2026-08-17 **Changes:** extracted from SKILL.md — comprehensive rewrite

## Intent

Authentication proves *who* a user is; authorization decides *what* that identity may do. This rule
defines how to audit both: password storage and recovery flows on the identity side, and Policy-based
decisions with a guaranteed super-admin bypass on the access side. Every mutation must be authorized,
and no code path may bypass the Policy layer.

## Rationale

Authentication and authorization are the two highest-value attack surfaces in a web application, and
their failures compound: a broken login flow invites brute force and credential stuffing; a
privilege-escalation hole turns any authenticated user into an admin. The concrete failures this
rule prevents:

- **Plaintext / weak hashes** — credentials are stored with MD5/SHA1 or a non-Laravel scheme, so a
  database leak becomes a usable-credential leak.
- **Unlimited login attempts** — no throttling means the attacker wins by persistence.
- **No `Gate::before` super-admin bypass** — the one mechanism that guarantees the platform owner
  always retains access; if it is removable, a misconfiguration can lock out the last admin.
- **Policies that return non-boolean** — a `null` or an Eloquent result from a policy breaks the
  authorization contract and can silently deny or allow.
- **Inline authorization** — `$this->authorize()` scattered in components instead of the Policy layer
  diverges the access rules from the single source of truth.
- **Magic-string permissions** — `'can:delete-users'` literals in code that diverge from
  `config/permission.php` silently change what a role can do.

## How to Apply

### Authentication

- **Password hashing** uses bcrypt/argon2 via Laravel defaults (`Hash::make` / `Hash::check`).
  Verify no `md5()`, `sha1()`, or plaintext assignment exists for credentials
  (`rg -n "md5\(|sha1\(|password\s*=\s*\$req" app/`).
- **Login rate limiting** applied — check `bootstrap/app.php` for a `RateLimiter::for('login', ...)`
  and that the login route carries the `throttle:` middleware.
- **Account recovery rate limited** — recovery slip and password reset flows are throttled, not just
  login.
- **Session management** follows Laravel best practices (regeneration on login, invalidation on
  logout — see `session` docs).
- **MFA readiness** — note current status (future) but flag the absence of rate-limited gates that
  would otherwise be abused.

### Authorization

- **Super admin bypass via `Gate::before`** — verify it exists in a service provider and *is not
  removable* (its removal would break recovery). The `Super Admin` always has full access.
- **Policy methods return boolean** — check every method returns `bool`; a loose return type is a
  finding.
- **No inline `Gate::authorize()` bypassing the Policy layer** — components should call the Policy
  (`$this->authorize('delete', $model)` / `$user->can(...)`), not duplicate the check inline.
- **Permissions defined in `config/permission.php`** — no magic strings in code. Every string used in
  `->can('...')` / `->hasPermissionTo('...')` must resolve to a configured permission.
- **5 flat roles enforced** — no role inheritance; roles map directly to the five personas.

## Examples

```php
// GOOD — hashing via Laravel defaults
$user->password = Hash::make($validated['password']);

// GOOD — login rate limiting registered
RateLimiter::for('login', function (Request $request) {
    return Limit::perMinute(5)->by($request->input('identifier') . '|' . $request->ip());
});

// GOOD — super admin bypass guaranteed (service provider)
Gate::before(fn ($user) => $user->username === 'superadmin' ? true : null);

// GOOD — policy decision stays boolean and is delegated
public function delete(User $user, InternDocument $document): bool
{
    return $user->id === $document->owner_id;
}
```

## Anti-Patterns & Pitfalls

- `Hash::make` replaced by a custom algorithm "for speed".
- Login throttling scoped only to IP, so a botnet bypasses it — bind to identifier + IP.
- A super-admin check done per-route instead of a single `Gate::before`, so one route forgets it.
- Policy methods typed to return a model or `null` instead of `bool`.
- Authorization repeated inline in Livewire components while the Policy drifts.
- Permission names typed in code that no longer exist in `config/permission.php`.

## Verification & Detection

```bash
# Weak or missing hashing for credentials
rg -n "md5\(|sha1\(|password\s*=\s*" app/ --include="*.php"

# Login routes and their middleware
rg -n "Route::post.*login|throttle" routes/ bootstrap/app.php

# The super admin gate bypass
rg -n "Gate::before|superadmin" app/

# Policy methods that do not return bool
rg -n "function (view|create|update|delete)[A-Za-z]*\(" app/ --include="*Policy.php"
```