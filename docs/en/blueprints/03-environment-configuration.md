# Blueprint 03: Environment Configuration

## Three-Tier Configuration Model

Internara uses a three-tier configuration system (see ADR-013):

| Tier | Storage | Precedence | Changed By | Effect |
|---|---|---|---|---|
| **Environment** | `.env` + `config/*.php` | Highest | Operations, deployment | Server restart required |
| **Code defaults** | `config/*.php` fallbacks | Medium | Developers, deployment | Deployment required |
| **Runtime settings** | Database `settings` table | Lowest (overrides code) | Admins via UI | Immediate (cache invalidated) |

## Development vs Production

| Aspect | Development | Production |
|---|---|---|
| **Database** | SQLite (file-based) | MySQL 8+ / PostgreSQL 14+ |
| **Cache driver** | `file` or `database` | `redis` |
| **Queue driver** | `sync` (jobs run immediately) | `redis` or `database` |
| **Queue worker** | `php artisan queue:work` in terminal | Supervisor-managed daemon |
| **Mail driver** | `log` (writes to storage/logs) | SMTP, Mailgun, SES, etc. |
| **Debug mode** | `APP_DEBUG=true` | `APP_DEBUG=false` |
| **OPcache** | Disabled | Enabled |
| **Composer** | Full install | `--optimize-autoloader --no-dev` |
| **Asset compilation** | `npm run dev` (Vite HMR) | `npm run build` (static) |

## Environment File (.env)

Copy `.env.example` to `.env` and configure:

```env
APP_NAME=Internara
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_TIMEZONE=UTC
APP_URL=http://localhost
APP_LOCALE=en
APP_FALLBACK_LOCALE=en

DB_CONNECTION=sqlite

SESSION_DRIVER=database
SESSION_LIFETIME=120

QUEUE_CONNECTION=database

CACHE_STORE=database

MAIL_MAILER=log

BROADCAST_CONNECTION=reverb
REVERB_APP_ID=
REVERB_APP_KEY=
REVERB_APP_SECRET=
VITE_REVERB_APP_KEY=
```

## Runtime Settings (Database)

Settings managed via the admin UI are stored in the `settings` table and
cached in memory. Changes take effect immediately without deployment:

- Application name and branding colors
- Feature flags
- Localization preferences
- System configuration

These settings override code defaults but are themselves overridden by
environment variables (tier precedence).

## Security

| Setting | Production Value |
|---|---|
| `APP_DEBUG` | `false` — do not expose stack traces to users |
| `APP_KEY` | Must be a random 32-character base64 string |
| `HONEYPOT_ENABLED` | `true` (if the honeypot package is reinstalled) |
| `SESSION_DRIVER` | `redis` or `database` — not `file` on multi-server |
| `CSP_ENABLED` | `true` — Content Security Policy header |

Verify production security:

```bash
php artisan system:health
```

## References

- `.env.example` — template with all configurable variables
- `config/app.php` — application configuration
- `config/database.php` — database connections
- `config/cache.php` — cache stores
- `config/queue.php` — queue connections
- `config/broadcasting.php` — broadcasting (Reverb/Pusher)
- `docs/en/adr/adr-013-three-tier-configuration.md` — ADR for this model
