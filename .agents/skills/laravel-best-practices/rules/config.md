# Configuration

## What It Enforces

`env()` is called only inside `config/*.php` files. Application code uses `config()` helper.
Environment checks use `App::environment()` or `app()->isProduction()`. Class constants or Enums
replace magic strings. Config validation at boot catches misconfiguration early.

## Why It Matters

When config is cached (`php artisan config:cache`), `env()` calls return `null` because the `.env`
file is no longer loaded. All `env()` calls must be in config files so that config caching works
correctly. `App::environment()` is a helper that reads from config, making it cache-safe.

## When It Applies

- `env()`: only in `config/*.php` files, never in application code
- `config()`: everywhere else, with fallback defaults: `config('app.name', 'Internara')`
- Environment checks: `app()->isProduction()` or `App::environment('production')`
- Magic strings: use class constants or Enums instead of raw strings
- Config validation: use `Config::validate()` in AppServiceProvider::boot() for critical values
- Typed config: cast env values with `(int)`, `(bool)` in config files
- `.env.example`: include comments explaining where to get values

Use `.env.encrypted` for production secrets or the platform's native secret store.

Exceptions: None. These are universal Laravel security best practices.
