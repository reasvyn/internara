# Session
> Last updated: 2026-06-01
> Changes: docs: comprehensive infrastructure, architecture, and conventions overhaul; fixed core.php reference to auth.php for password confirmation routes


## Purpose

The session layer manages user authentication state across HTTP requests. It remembers who a
user is after login without requiring credentials on every request, and provides per-user
ephemeral storage for flash messages, locale preference, and multi-step form progress.

---

## Driver Strategy by Tier

| Aspect | Tier 1 (Entry) | Tier 2 (Standard) | Tier 3 (HA) |
|---|---|---|---|
| **Driver** | `database` | `redis` | `redis` (cluster) |
| **Setup** | Auto-migrated table | Redis server required | Redis cluster |
| **Persistence** | Durable (database) | Memory + persistence | Replicated |
| **Multi-server** | ✅ (shared DB) | ✅ (shared Redis) | ✅ (Redis cluster) |

Default for new installations: `database` driver. The sessions table is created by
migration and requires no external service.

```env
# Tier 1 (default)
SESSION_DRIVER=database

# Tier 2+
SESSION_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

---

## How Sessions Work

1. **Unauthenticated request arrives** — Laravel creates a session with a cryptographically
   random identifier stored in an HTTP-only cookie.
2. **User logs in** — the session is regenerated (new ID, old discarded) to prevent session
   fixation. The user ID is stored in the session.
3. **Subsequent requests** — the browser sends the session cookie; the server looks up
   session data and restores user state.
4. **User logs out** — session is regenerated again. Session data is cleared from the store.

### Database Schema (default driver)

```
sessions
├── id              VARCHAR(255)   PRIMARY KEY  — session ID (hash)
├── user_id         VARCHAR(36)    NULLABLE      — authenticated user UUID
├── ip_address      VARCHAR(45)    NULLABLE      — client IP
├── user_agent      TEXT           NULLABLE      — browser User-Agent
├── payload         TEXT           NOT NULL      — encrypted session data
└── last_activity   INTEGER        NOT NULL      — UNIX timestamp
    └── INDEX
```

The `last_activity` column is indexed because garbage collection queries against it.
Garbage collection runs probabilistically (not on a cron) — each request has a small
chance of sweeping expired sessions.

---

## Session Lifetime

The session lifetime defaults to **120 minutes of inactivity**. This balances security
and convenience:

| Duration | Impact |
|---|---|
| < 30 min | Secure — forces re-auth often, disrupts long work sessions |
| **120 min** | Standard — reasonable for a school day, auto-logout on idle |
| > 480 min | Risky — forgotten sessions on shared computers stay active |

After expiry, the user is redirected to login. In-flight form data is lost unless saved
as a draft.

### Remember Me

The "remember me" option on login creates a longer-lived remember token (via `recaller`
cookie) separate from the session. This allows the session to persist across browser
restarts within the token's lifetime (default: 5 years, hashed).

---

## Security

### Session Fixation Prevention

The session ID is regenerated on every authentication state change:

```php
// Called automatically on login/logout by Laravel's authentication system
session()->regenerate();

// Also called on privilege changes
session()->regenerate();
```

### Password Confirmation

Sensitive operations (email change, password change, account deletion) require
re-authentication within a configurable timeout (default: 15 minutes via
`auth.password_timeout`). This prevents a stolen session from modifying credentials.

### CSRF Protection

Every mutating request validates a CSRF token stored in the session. Livewire handles
this transparently; Blade forms include `@csrf`. The token is regenerated on logout and
periodically refreshed during active sessions.

### Cookie Security Flags

```php
// config/session.php
'http_only' => true,      // Inaccessible to JavaScript
'secure' => env('APP_ENV') === 'production',  // HTTPS only in production
'same_site' => 'lax',     // Prevent CSRF from external origins
```

These flags make it significantly harder for attackers to steal or misuse session cookies.

---

## Garbage Collection

Expired sessions are cleaned probabilistically:

| Setting | Default | Purpose |
|---|---|---|
| `lottery` | `[2, 100]` | 2% chance of GC per request |
| `expire_on_close` | `false` | Don't expire on browser close |

With `[2, 100]`, garbage collection runs on approximately 2% of requests. For a site
with 10,000 requests/day, that is ~200 GC runs/day — sufficient to keep the sessions
table lean without a separate cron job.

In production with Redis (`SESSION_DRIVER=redis`), garbage collection is handled by
Redis's key expiry — no application-level GC needed.

---

## Locale Persistence

The user's language preference is stored in the session by `SetLocaleMiddleware`:

```php
// app/Domain/Settings/Http/Middleware/SetLocaleMiddleware.php
$locale = session('locale', config('app.locale'));
app()->setLocale($locale);
```

The preference is set by the `LanguageSwitcher` Livewire component and persists across
requests. When Redis is the session driver, locale preference survives application
restarts.

---

## Where to Find It

- `config/session.php` — session driver, lifetime, cookie settings
- `database/migrations/` — sessions table migration
- `app/Domain/Settings/Http/Middleware/SetLocaleMiddleware.php` — locale persistence
- `routes/web/auth.php` — password confirmation routes (`/user/confirm-password`)
- `bootstrap/app.php` — middleware configuration
- `docs/infrastructure.md` — tier-based infrastructure design
