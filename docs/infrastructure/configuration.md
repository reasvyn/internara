# Configuration
> Last updated: 2026-05-27
> Changes: docs: comprehensive infrastructure, architecture, and conventions overhaul


## Config File Organization

Configuration files live in the `config/` directory, one file per subsystem. Each file returns
a PHP array of default values. Environment variables (`.env`) override these defaults at
runtime without modifying the files.

Configuration files are organized into three tiers matching the infrastructure design
(see [Infrastructure](infrastructure.md)):

| Tier | Storage | Precedence | Changed By | Effect |
|---|---|---|---|---|
| **Environment** | `.env` + `config/*.php` `env()` calls | Highest | Operations, deployment | Requires `config:cache` rebuild |
| **Code defaults** | `config/*.php` hardcoded fallbacks | Medium | Developers, deployment | Deployment required |
| **Runtime settings** | Database `settings` table | Lowest (overrides code) | Admins via web UI | Immediate (cache invalidated) |

---

## Environment Variable Conventions

Variables follow a naming convention that makes their origin clear:

| Prefix | Module | Example |
|---|---|---|
| `APP_*` | Application | `APP_NAME`, `APP_ENV`, `APP_KEY`, `APP_DEBUG`, `APP_URL` |
| `DB_*` | Database | `DB_CONNECTION`, `DB_HOST`, `DB_DATABASE` |
| `MAIL_*` | Mail | `MAIL_MAILER`, `MAIL_HOST`, `MAIL_USERNAME` |
| `SESSION_*` | Session | `SESSION_DRIVER`, `SESSION_LIFETIME` |
| `CACHE_*` | Cache | `CACHE_STORE`, `CACHE_PREFIX` |
| `QUEUE_*` | Queue | `QUEUE_CONNECTION` |
| `BROADCAST_*` | Broadcasting | `BROADCAST_CONNECTION` |
| `REDIS_*` | Redis | `REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD` |
| `AWS_*` | Cloud storage | `AWS_ACCESS_KEY_ID`, `AWS_BUCKET` |
| `LOG_*` | Logging | `LOG_CHANNEL`, `LOG_LEVEL`, `LOG_STACK` |
| `PULSE_*` | Monitoring | `PULSE_ENABLED` |
| `VITE_*` | Frontend | various Vite env vars |

---

## Three Configuration Tiers in Detail

### 1. Environment Tier (`.env` + `config/*.php`)

The `.env` file is the highest-priority configuration. It stores environment-specific values
that MUST NOT be committed to version control:

```env
# .env — DO NOT COMMIT
APP_KEY=base64:abc123...
DB_PASSWORD=supersecret
MAIL_PASSWORD=mailpass
```

Configuration files read environment variables via `env()`:

```php
// config/database.php
'default' => env('DB_CONNECTION', 'sqlite'),
```

Values from `config/*.php` are cached by `php artisan config:cache`, which merges all
config files into a single cached file. After caching, `env()` calls inside config files
become inert — only the cached values are used. This means `.env` changes require
`php artisan config:cache` to take effect.

### 2. Code Defaults Tier (`config/*.php` fallbacks)

The second argument to `env()` provides a hardcoded default:

```php
'default' => env('DB_CONNECTION', 'sqlite'),  // sqlite is the code default
```

These defaults are version-controlled and change only with deployments. They ensure the
application works with zero configuration in a fresh installation.

### 3. Runtime Settings Tier (Database `settings` table)

The `setting()` helper reads from the `settings` database table, which stores runtime-
configurable values:

```php
$value = setting('app_name', 'Internara');          // with default fallback
$value = setting('primary_color');                    // returns null if missing
setting(['app_name' => 'Internara Baru'], $cacheTtl); // write (optional TTL override)
```

| Feature | Behavior |
|---|---|
| Storage | `settings` table — key, value, type, group |
| Caching | `Cache::rememberForever()` — indefinite, invalidated on write |
| Types | string, integer, float, boolean, JSON, encrypted |
| Access | Admins via web UI; any code via `setting()` helper |
| Scope | Brand colors, app name, feature flags, operational thresholds |

Settings take effect immediately — no deployment, no cache clear, no server restart.

---

## Development vs Production

| Aspect | Development | Production (Tier 1) | Production (Tier 2+) |
|---|---|---|---|
| **Database** | SQLite (file) | MySQL / MariaDB | MySQL / PostgreSQL |
| **Cache driver** | `file` | `file` or `database` | `redis` |
| **Queue driver** | `sync` | `sync` | `redis` |
| **Session driver** | `file` | `database` | `redis` |
| **Mail driver** | `log` | SMTP | SES / SMTP |
| **Debug mode** | `APP_DEBUG=true` | `false` | `false` |
| **OpCache** | Disabled | Enabled | Enabled |
| **Composer** | Full install | `--optimize-autoloader --no-dev` | Same |
| **Asset build** | `npm run dev` (HMR) | `npm run build` | `npm run build` |

See [Infrastructure → Three Deployment Tiers](infrastructure.md#1-three-deployment-tiers)
for the complete tier breakdown.

---

## Security Hardening

| Setting | Tier 1 | Tier 2+ | Why |
|---|---|---|---|
| `APP_DEBUG` | `false` | `false` | Never expose stack traces in production |
| `APP_ENV` | `production` | `production` | Enables production-only behavior |
| `APP_KEY` | Random 32-char base64 | Same | Session encryption, signed URLs |
| `SESSION_DRIVER` | `database` | `redis` | `file` breaks on multi-server |
| `SESSION_SAME_SITE` | `lax` | `lax` | CSRF prevention |
| `SESSION_SECURE_COOKIE` | `true` | `true` | HTTPS only |
| `CSP_ENABLED` | `true` | `true` | Content Security Policy |
| `HONEYPOT_ENABLED` | `true` | `true` | Bot prevention |

```bash
# Verify all security settings
php artisan system:health
```

---

## Localization Configuration

Internara ships with two language packs:

| Locale | Language | Status |
|---|---|---|
| `en` | English | Complete (default) |
| `id` | Indonesian | Complete |

Locale resolution order:
1. User preference (stored in session by `LanguageSwitcher`)
2. `APP_LOCALE` environment variable
3. Browser `Accept-Language` header

```env
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
```

See [Localization](localization.md) for adding new languages.

---

## Complete Config File Reference

| File | Contents | Environment Variable |
|---|---|---|
| `config/app.php` | Application name, env, debug, URL, timezone, locale | `APP_*` |
| `config/database.php` | Database connections, Redis config | `DB_*`, `REDIS_*` |
| `config/cache.php` | Cache stores, per-store configuration | `CACHE_*` |
| `config/session.php` | Session driver, lifetime, cookie settings | `SESSION_*` |
| `config/queue.php` | Queue connections, retry limits | `QUEUE_*` |
| `config/mail.php` | Mail driver, SMTP credentials, from address | `MAIL_*` |
| `config/broadcasting.php` | Broadcast driver, Reverb/Pusher config | `BROADCAST_*` |
| `config/filesystems.php` | Disk definitions, cloud storage | `FILESYSTEM_DISK`, `AWS_*` |
| `config/logging.php` | Log channels, rotation, levels | `LOG_*` |
| `config/security-headers.php` | CSP, X-Frame-Options, Referrer-Policy | `CSP_*` |
| `config/setup.php` | Setup wizard steps, requirements, defaults | — |
| `config/menu.php` | Sidebar navigation structure | — |
| `config/permission.php` | Spatie permission caching | — |
| `config/activitylog.php` | Activity log retention, model | — |
| `config/media-library.php` | Media library paths, queue, conversions | — |
| `config/flasher.php` | Flash message style, timeout, position | — |
| `config/localization.php` | Supported locales, fallback rules | — |
| `config/pulse.php` | Pulse recorders, ingestion, pruning | `PULSE_*` |
| `config/mary.php` | maryUI component configuration | — |

---

## The `brand()` Helper

The companion `brand()` helper reads brand-specific settings (colors, logo, favicon) and
returns structured data for use in Blade templates:

```php
brand('name');          // Application name from settings
brand('logo');          // Logo URL (uploaded or default)
brand('colors');        // Array of primary, secondary, accent, base
```

Values resolve through a fallback chain: runtime settings → config defaults → hardcoded
defaults. See [Branding](../branding.md) for details.

---

## Where to Find It

- All configuration files: `config/`
- Environment template: `.env.example`
- Settings model: `app/Settings/Models/Setting.php`
- Settings resolver: `app/Settings/Support/Settings.php`
- Brand resolver: `app/Settings/Support/AppMetadata.php`
- App info (composer.json): `app/Settings/Support/AppInfo.php`
- Infrastructure tiers: [Infrastructure](infrastructure.md)
