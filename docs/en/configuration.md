# Configuration

## Config File Organization

Configuration files live in the `config/` directory, one file per subsystem.
Each file returns a PHP array of default values. This organization makes it
obvious where to find configuration for any given concern — database settings
in `config/database.php`, mail settings in `config/mail.php`, caching in
`config/cache.php`, and so on.

There are approximately 20 configuration files covering application settings,
database connections, cache stores, session behavior, queue connections, mail
drivers, broadcasting, filesystem disks, logging channels, security headers,
CORS, localization, third-party packages (media library, permissions,
activity log), Livewire, the menu structure, and the setup wizard.

## Environment Variable Conventions

Configuration files read environment variables via the `env()` helper. This
creates a two-tier system: defaults are defined in the config files and can
be overridden by `.env` values. Environment variables follow a naming
convention that makes their origin clear: `APP_*` for application settings,
`DB_*` for database, `MAIL_*` for mail, `SESSION_*` for sessions,
`CACHE_*` for cache, `QUEUE_*` for queue, `BROADCAST_*` for broadcasting,
`REDIS_*` for Redis, `AWS_*` for cloud services, `LOG_*` for logging,
`PULSE_*` for monitoring.

Variables prefixed with `VITE_` are explicitly exposed to the frontend
build pipeline for use in JavaScript.

## Three Configuration Tiers

The application has three distinct configuration systems, each serving a
different purpose.

The `config()` tier stores infrastructure defaults: database credentials,
queue drivers, mail settings, cache store selection. These values are
typically set once per environment and rarely change. They can be cached
via `php artisan config:cache`, which merges all config files into a single
file for faster loading.

The `setting()` tier stores dynamic business rules: site name, brand colors,
operational thresholds, attendance policies. These values are editable at
runtime through the admin panel, stored in the `settings` database table, and
cached indefinitely with automatic invalidation on update.

The `app_info()` tier reads metadata from `composer.json`: application name,
version, author, license. This is the single source of truth for static
metadata that changes only between releases.

## Settings Key-Value Store

The settings system differs from configuration files in a fundamental way:
config files are code that is version-controlled and changes require a deploy.
Settings are data that are stored in the database and can be changed at
runtime by authorized administrators. Settings are cached with
`Cache::rememberForever()` and each key is invalidated individually when its
value changes. This means changing a brand color in the admin panel takes
effect immediately without a deploy or cache clear.

Settings support typed values: string, integer, float, boolean, JSON, and
encrypted (for sensitive values like mail passwords). Keys use lowercase
dot-notation and each key belongs to a logical group (general, system,
operational). The group is used for organization in the admin panel and for
selective cache invalidation.

## Where to Find It

All configuration files are in `config/`. The settings model is at
`app/Domain/Settings/Models/Setting.php`. The settings resolver (with
multi-tier caching) is at `app/Domain/Settings/Support/Settings.php`. The
`AppMetadata` class that powers the `brand()` helper is at
`app/Domain/Settings/Support/AppMetadata.php`. The `AppInfo` class that
reads `composer.json` is at `app/Domain/Settings/Support/AppInfo.php`.
