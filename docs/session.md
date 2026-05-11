# Session

| Setting | Value |
|---|---|
| Driver | `database` (configurable via `SESSION_DRIVER`) |
| Lifetime | 120 minutes (configurable via `SESSION_LIFETIME`) |
| Table | `sessions` (UUID `user_id` foreign key) |
| Cookie | Auto-named based on app name, HTTP-only, SameSite=Lax |
| Test driver | `array` (via `phpunit.xml`) |

Configuration is in `config/session.php`.

## Usage

- **Setup wizard** — form data persists across steps via `session()->put('setup.form_data', [...])`
- **Authentication** — session is regenerated on login to prevent fixation; intended URL is preserved for redirect
- **Localization** — user locale is stored per-session, applied by `SetLocaleMiddleware`
- **Logout** — session is invalidated and CSRF token is regenerated

## Security

- `SESSION_SECURE_COOKIE` enforces HTTPS in production
- `SESSION_HTTPONLY=true` prevents JavaScript from accessing the cookie
- SameSite is set to `Lax` by default
