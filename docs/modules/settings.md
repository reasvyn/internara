# Settings

> **Last updated:** 2026-06-08

System-wide configuration management: key-value store with type enforcement, brand identity (colors, logo, favicon, site title), localization preferences, SMTP mail configuration, and global feature toggles.

## Purpose & Boundary

Settings is the single source of truth for all runtime configuration consumed across every module. It provides the `settings` database table, the `Settings` static facade, caching infrastructure, type casting, brand asset management via Spatie Media Library, and the global helper functions (`setting()`, `brand()`, `app_info()`).

Out of scope: environment-specific config (`.env`), user preferences (User profile), feature-specific logic.

## Submodules

### SettingStore
Core key-value store with explicit type enforcement (`string`, `integer`, `float`, `boolean`, `json`, `encrypted`, `null`). Values cached forever via `rememberForever` with automatic invalidation on write. Sensitive values (SMTP passwords, API keys) stored encrypted via Laravel's `Crypt` facade.

### Branding
Dynamic brand identity management: site title, tagline, primary/secondary/accent colors (validated 6-digit hex), logo upload (max 1 MB, PNG/JPEG/WebP), favicon upload (max 512 KB, PNG/JPEG/WebP/ICO). All assets render immediately without redeployment.

### AppMetadata
Read-only system metadata derived from `composer.json` and environment: application name, version, description, environment name, debug mode status, and installation state. Accessed via `app_info()` helper.

### MailConfiguration
SMTP mail driver configuration stored as encrypted settings. Consumed by the notification system for transactional email delivery. Includes host, port, encryption, username, password, from address, and from name.

## Key Concepts

### Resolution Chain

Setting values resolve through a multi-layer fallback: runtime overrides → `AppInfo` (composer.json) → database (cached) → config file fallback → provided default. This enables environment-specific overrides while maintaining a consistent API.

### Cache Strategy

All setting reads are cached forever. Cache invalidation happens synchronously on every write operation. This eliminates cache stampede risk while ensuring stale data is never served longer than one request cycle.

### Superadmin-Only Mutations

Only users with `super_admin` role can create, update, or delete settings. All admin users have read access. This prevents accidental or unauthorized configuration changes.

## Dependencies

- Core (base classes, SmartLogger)
- Academics (academic year reference data)

## Used By

Every module (via `setting()`, `brand()`, `app_info()` helpers).
