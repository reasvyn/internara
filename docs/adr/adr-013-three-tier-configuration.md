# ADR-013: Three-Tier Configuration

## Status
Accepted

## Context
The application needs configuration at three distinct levels of persistence and precedence:

1. **Environment-specific secrets**: Database passwords, API keys, deployment URLs — must
   never be committed to version control, differ per environment (dev/staging/production).
2. **Code-managed defaults**: Application behavior (session lifetime, cache driver, mail
   driver) — defined in code, version-controlled, change with deployments.
3. **Runtime-adjustable settings**: Branding colors, application name, feature flags,
   localization preferences — change frequently, must take effect immediately without
   deployment.

Using `.env` for everything is insecure (commits secrets) and requires server restarts.
Using `config/*.php` for everything requires deployments for trivial changes (e.g., changing
the application name). Using the database for everything is slow (database query on every
config read) and scatters code defaults across migrations.

## Decision
Three tiers with explicit precedence:

| Tier | Storage | Precedence | Changed By | Effect |
|---|---|---|---|---|
| **Environment** | `.env` + `config/*.php` | Highest | Operations team, deployment | Server restart |
| **Code defaults** | `config/*.php` fallbacks | Medium | Developers, deployment | Deployment |
| **Runtime settings** | Database `settings` table | Lowest (overrides code) | Admins via UI | Immediate (cache invalidated) |

The `setting()` helper reads from the database settings table (cached in memory, auto-
invalidated on write). `config()` reads from Laravel's config files (environment overrides
applied). `app_info()` provides computed metadata (version, environment name).

Feature flags are boolean settings in the database — toggling them enables/disables features
at runtime with zero deployment.

## Consequences
- **Positive**: Environment secrets stay out of version control. Code defaults are always
  defined. Runtime changes are instant — no restart, no deployment.
- **Positive**: Cache invalidation on setting change ensures runtime updates take effect
  immediately across all application instances.
- **Positive**: Feature flags in settings enable gradual rollout and emergency disable
  without code changes.
- **Negative**: Three tiers means three places to look when debugging a configuration issue.
  "Is this value coming from `.env`, `config/`, or the database?"
- **Negative**: Settings cache must be invalidated on write — a cache stampede on setting
  change could temporarily spike database load. Mitigated by atomic cache invalidation.
- **Negative**: Not all settings should be overridable at runtime — security-critical
  settings (e.g., `app.key`, `database.default`) are excluded from the settings store.

## References
- `app/Domain/Settings/Models/Setting.php`
- `app/Domain/Settings/Support/Settings.php` — cached read layer
- `app/Domain/Settings/Livewire/SystemSetting.php` — admin UI
- `docs/configuration.md`
- `config/` — 20+ configuration files
