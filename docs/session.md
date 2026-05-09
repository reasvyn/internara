# Session

| Setting | Value |
|---|---|
| Driver | `database` (env: `SESSION_DRIVER`) |
| Lifetime | 120 min (env: `SESSION_LIFETIME`) |
| Table | `sessions` (uuid `user_id` FK) |
| Cookie | `internara_session`, HTTP-only, SameSite=Lax |
| Test driver | `array` (via `phpunit.xml`) |

Config: `config/session.php`

## Usage

- **Setup Wizard** — form data persists across steps via `session()->put('setup.form_data', [...])`
- **Authentication** — session regenerated on login to prevent fixation; intended URL preserved for redirect
- **Localization** — user locale stored per-session, applied by `SetLocaleMiddleware`
- **Logout** — session invalidated, CSRF token regenerated

## Security

`SESSION_SECURE_COOKIE` enforces HTTPS in production. `SESSION_HTTPONLY=true` prevents JS cookie access.