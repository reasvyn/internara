# Session

## Purpose

The session layer manages user authentication state across HTTP requests. It
is the mechanism by which the application remembers who a user is after they
log in, without requiring credentials on every request. It also provides a
per-user ephemeral storage for flash messages, locale preference, and
multi-step form progress.

## How Sessions Work

When an unauthenticated request arrives, Laravel creates a session with a
cryptographically random identifier. This identifier is stored in a cookie on
the browser. On subsequent requests, the browser sends this cookie, the server
looks up the corresponding session data, and the user's state is restored.

On successful login, the session is regenerated — a new ID is issued and the
old one is discarded. This prevents session fixation attacks, where an
attacker tricks a user into using a known session ID before authentication.
The same regeneration happens on logout.

## Database Driver

Sessions use the database driver by default. Session records are stored in a
`sessions` table with columns for the session ID, the authenticated user's
UUID, IP address, user agent, the encrypted payload, and a last-activity
timestamp. This approach requires no external service (no Redis, no
filesystem permissions) and works across multiple web servers without shared
storage configuration.

The last-activity timestamp is indexed because it is used by garbage
collection. Session garbage collection runs probabilistically — on each
request, there is a small chance that old sessions will be swept. This avoids
the need for a separate cron job while keeping the table from accumulating
stale records.

## Why Session Lifetime Matters

The session lifetime defaults to 120 minutes of inactivity. This balances
user convenience against security. A short lifetime forces frequent
re-authentication, which is secure but disruptive. A long lifetime risks
unauthorized access if a device is left unattended. The 120-minute default
is the standard compromise.

When the session expires, the user is redirected to the login page. Any
in-flight operation is lost. Users can choose to extend the session by
checking a "remember me" option on login, which creates a longer-lived
remember token separate from the session itself.

## Security Considerations

Session fixation is prevented by regenerating the session ID on every
authentication state change — login, logout, and privilege escalation. The
regeneration uses Laravel's built-in `session()->regenerate()` which issues a
new ID while preserving session data.

Password confirmation adds an additional layer for sensitive operations.
When a user changes their email, password, or other critical settings, they
must re-enter their password within a configurable timeout window. This
prevents a stolen session from being used to modify account credentials.

Cross-Site Request Forgery (CSRF) protection is built into every mutating
request. The session stores a CSRF token that is embedded in forms and
validated by middleware. Livewire handles this automatically; Blade forms
include it via a directive. The token is regenerated on logout and
periodically refreshed during active sessions.

Cookie security flags are configured to prevent JavaScript access
(HTTP-only), restrict cross-origin requests (SameSite), and require HTTPS in
production (Secure). These flags make it significantly harder for attackers
to steal or misuse session cookies.

## Where to Find It

Session configuration is in `config/session.php` with environment overrides
via `SESSION_*` variables. The sessions table migration is in
`database/migrations/`. Session-based locale handling is in
`app/Domain/Settings/Http/Middleware/SetLocaleMiddleware.php`.
Password confirmation middleware is configured in `bootstrap/app.php`.
