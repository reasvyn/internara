# Settings — Documentation Overview

> Last updated: 2026-06-06
> Changes: Extracted from SysAdmin into standalone module; created dedicated documentation.

Manages system-wide configuration including brand identity, color schemes, localization preferences, SMTP mail services, and global feature toggles. Acts as the single source of truth for all runtime settings consumed across every module.

For complete technical reference including API, models, actions, and components, see [settings-reference.md](settings-reference.md).

---

## Key Principles

- **Centralized Configuration**: All system settings are stored as key-value pairs with type enforcement, cached forever for performance.
- **Multiple Resolution Layers**: Values resolve through a chain — runtime overrides → AppInfo (composer.json) → DB (cached) → config fallback → default.
- **Automatic Cache Invalidation**: Setting changes invalidate relevant cache keys immediately, ensuring next reads are fresh.
- **Type Safety**: Every setting has an explicit type (`string`, `integer`, `float`, `boolean`, `json`, `encrypted`, `null`) enforced at the cast layer.
- **Encrypted Secrets**: Sensitive values (e.g., SMTP passwords) are stored encrypted via Laravel's `Crypt` facade.
- **Superadmin-Only Mutations**: Only superadmin users can create, update, or delete settings. All admin users can view them.
- **Dynamic Branding**: Brand colors, logo, favicon, and site title are configurable at runtime without redeployment.

---

## Context Boundary

Owns the `settings` database table and all supporting infrastructure (caching, casting, validation). Provides the `Settings` static facade and `AppMetadata` / `AppInfo` helpers consumed globally via `setting()`, `brand()`, and `app_info()` helper functions. Handles brand asset uploads (logo/favicon) through Spatie Media Library.

**Dependencies**: Core (base classes, SmartLogger), Academics (academic year data), User (notifications for mail tests)
**Used By**: Every module (via `Settings::get()` / `setting()` / `brand()` / `app_info()`)

---

## Module Rules

- **Key Format**: Setting keys must match `/^[a-z][a-z0-9_.]*$/` — lowercase alphanumeric with underscores or dots.
- **Superadmin Mutations**: Only users with the `super_admin` role may create, update, or delete settings.
- **Cached Forever**: All setting reads are cached forever; cache is manually flushed on every write/update.
- **Encrypted Storage**: SMTP passwords and other secrets are stored with `type=encrypted` using Laravel Crypt.
- **Brand Asset Limits**: Logo upload max 1 MB, favicon upload max 512 KB. Supported formats: PNG, JPEG, WebP, ICO.
- **Color Validation**: All color values must be valid 6-digit hex codes (e.g., `#059669`).
- **Fallback Chain**: resolution order: runtime overrides → AppInfo metadata → DB cache → config → provided default.

---

## Error Handling & Failure Modes

- **Database Unavailable**: All `Settings` methods gracefully fall back to empty defaults and log warnings via `SmartLogger`.
- **Cache Stampede**: Cache keys use `rememberForever`, so concurrent writes only cause redundant DB fetches, not data corruption.
- **Invalid Key Format**: `SetSettingAction` throws `ValidationException` on malformed keys.
- **Encryption Failure**: `SettingValueCast` throws `RuntimeException` if encryption fails during write, gracefully returns raw value on decryption failure.
- **Missing Migration Before Setup**: `AppMetadata::isInstalled()` guards all DB reads until the system is fully installed.

---

For complete technical reference, see [settings-reference.md](settings-reference.md).
