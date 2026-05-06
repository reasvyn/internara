# Session Management

## Configuration

| Setting | Value |
|---|---|
| Driver | `database` |
| Lifetime | 120 minutes |
| Cookie name | `internara_session` |
| Test driver | `array` |

Config: `config/session.php`. Environment: `SESSION_DRIVER`, `SESSION_LIFETIME`.

## Session Table

Stored in the `sessions` table, created during initial migration.

| Column | Type | Description |
|---|---|---|
| `id` | string | Primary key |
| `user_id` | uuid (nullable) | FK to `users` |
| `ip_address` | string(45) | Client IP |
| `user_agent` | text | Browser agent |
| `payload` | longText | Serialized session data |
| `last_activity` | integer | Unix timestamp |

## Usage Patterns

### Setup Wizard

Form data persists across wizard steps via `session()->put('setup.form_data', [...])`. The setup token is stored at `setup.token_input` and authorization flag at `setup.authorized`.

### Authentication

Session is regenerated on login to prevent fixation. The intended URL is preserved for post-login redirect.

### Localization

User locale preference stored per-session and applied by middleware.

### Logout

Session invalidated and CSRF token regenerated.

## Security

- Session ID regenerated after authentication
- `SESSION_SECURE_COOKIE=true` enforces HTTPS in production
- `SESSION_HTTPONLY=true` prevents JavaScript cookie access
- `SESSION_SAME_SITE=lax` provides CSRF protection
