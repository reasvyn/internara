# Settings — Config, Branding & Feature Flags

> **Last updated:** 2026-07-21 **Changes:** sync — update cache strategy to reflect
> SettingObserver-based invalidation

## Description

System-wide configuration management: key-value store with type enforcement, brand identity (colors,
logo, favicon, site title), localization preferences, SMTP mail configuration, and global feature
toggles.

## Purpose & Boundary

Settings is the single source of truth for all runtime configuration consumed across every module.
It provides the `settings` database table, the `Settings` static facade, caching infrastructure,
type casting, brand asset management via Spatie Media Library, and the global helper functions
(`setting()`, `brand()`).

Out of scope: environment-specific config (`.env`), user preferences (User profile),
feature-specific logic. Static application metadata (name, version, author) belongs to Core's
`AppInfo`.

## Submodules

### SettingStore

Core key-value store with explicit type enforcement (`string`, `integer`, `float`, `boolean`,
`json`, `encrypted`, `null`). Values cached forever via `rememberForever` with automatic
invalidation on write. Sensitive values (SMTP passwords, API keys) stored encrypted via Laravel's
`Crypt` facade.

### Branding

Dynamic brand identity management: site title, tagline, primary/secondary/accent colors (validated
6-digit hex), logo upload (max 1 MB, PNG/JPEG/WebP), favicon upload (max 512 KB, PNG/JPEG/WebP/ICO).
All assets render immediately without redeployment.

### Locale

Language switching between English (EN) and Indonesian (ID). Locale preference is stored in session
and applied via `SetLocaleMiddleware`. Uses Laravel's built-in localization with published language
files.

### Theme

Dark/light mode and CSS variable generation. Theme preference is stored in session. CSS variables
are generated from the active color palette and cached.

## Key Concepts

### Resolution Chain

Setting values resolve through a multi-layer fallback: runtime overrides → `AppInfo` (composer.json)
→ database (cached) → config file fallback → provided default. This enables environment-specific
overrides while maintaining a consistent API.

### Brand Resolution

The `Brand` class (in `App\Settings\Support`) resolves dynamic branding values directly from the
settings database, bypassing AppInfo's static resolution. This allows users to customize `name`,
`title`, `logo`, and `favicon` without conflicting with AppInfo's reserved keys. Fallback chain:
setting model → config → AppInfo → hardcoded default.

### Cache Strategy

All setting reads are cached forever. Cache invalidation happens via `SettingObserver`, which
responds to Eloquent model events (`created`, `updated`, `deleted`) and clears affected cache keys
synchronously. Brand color cache (`brand.colors`) and theme CSS variable cache
(`theme.css_variables`) are invalidated when relevant settings change, driven by
`config('settings.theme_cache_keys')`.

### Superadmin-Only Mutations

Only users with `super_admin` role can create, update, or delete settings. All admin users have read
access. This prevents accidental or unauthorized configuration changes.

## Dependencies

- Core (base classes, SmartLogger, AppInfo, AppIntegrity)
- Academics (academic year reference data)

## Used By

Every module (via `setting()` and `brand()` helpers).

---

## Global Helpers in this Module

The file `app/Settings/Support/helpers.php` defines two global functions:

- `setting($key, $default, $skipCache)` — Runtime configuration access
- `brand($key, $default)` — Dynamic branding values (name, title, logo, favicon, colors)
