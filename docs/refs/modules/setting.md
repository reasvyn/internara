# Setting — Config, Branding & Feature Flags

## Description

System-wide configuration management: key-value store with type enforcement, brand identity (colors,
logo, favicon, site title), localization preferences, SMTP mail configuration, and global feature
toggles.

## Purpose & Boundary

Setting is the single source of truth for all runtime configuration consumed across every module.
It provides the `setting` database table, the `Setting` static facade, caching infrastructure,
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

The `Brand` class (in `App\Setting\Support`) resolves dynamic branding values directly from the
setting database, bypassing AppInfo's static resolution. This allows users to customize `name`,
`title`, `logo`, and `favicon` without conflicting with AppInfo's reserved keys. Fallback chain:
setting model → config → AppInfo → hardcoded default.

### Cache Strategy

All setting reads are cached forever. Cache invalidation happens via `SettingObserver`, which
responds to Eloquent model events (`created`, `updated`, `deleted`) and clears affected cache keys
synchronously. Brand color cache (`brand.colors`) and theme CSS variable cache
(`theme.css_variables`) are invalidated when relevant setting change, driven by
`config('setting.theme_cache_keys')`.

### Superadmin-Only Mutations

Only users with `super_admin` role can create, update, or delete setting. All admin users have read
access. This prevents accidental or unauthorized configuration changes.

## Dependencies

- Core (base classes, SmartLogger, AppInfo, AppIntegrity)
- Academic (academic year reference data)

## Used By

Every module (via `setting()` and `brand()` helpers).

## Design Principles

- **Setting is the single runtime configuration store** — every module reads from `setting()` and `brand()` helpers, never from `.env` at runtime. Environment-specific overrides belong in `.env`; all runtime config belongs in the database-backed setting store. This keeps config accessible without code deployment.
- **Caching is aggressive but invalidation is automatic** — all reads use `rememberForever`. Writes trigger `SettingObserver` to clear the affected cache key synchronously. Brand color and theme CSS variable caches are driven by `config('setting.theme_cache_keys')`. There is no stale-read risk because invalidation is event-driven, not TTL-based.
- **Encrypted values stay encrypted at rest and in transit** — SMTP passwords, API keys, and any value with `encrypted` type are stored via `Crypt::encryptString()` and never exposed in logs, errors, or API responses. Decryption happens only at the point of use.
- **Mutations are superadmin-gated and audit-logged** — only `super_admin` can create, update, or delete setting. All mutations are logged via SmartLogger. Even read access is auditable via SmartLogger if configured; the design prioritises traceability for compliance.

## How It Works

Setting is the one module every other module reads from, which shapes everything about its design.
Values are typed — a school administrator who stores `true` where a string is expected is told so
rather than silently getting a coerced value — and reads are served from a cache that is
invalidated on write, so a page that reads a dozen settings still costs one lookup.

The resolution chain is what makes this usable offline. A value may come from an override, from the
stored record, from configuration, or from a compiled-in default, and callers ask for a setting by
name without knowing or caring which of those answered. That is why changing the school name or the
primary colour does not require touching code, and why a fresh install still renders a complete
interface before anyone has configured anything.

Branding is the most visible consumer: colours, logos, and the site identity are read from the same
store, so a school re-skins the whole application by uploading assets rather than by editing
templates. Sensitive values — mail credentials, keys — are stored encrypted rather than in plain
configuration, and mail settings can be tested from the settings screen before the school depends
on them, so a misconfigured server is discovered by the administrator rather than by a student
who never received their activation mail.

---

## Global Helpers in this Module

Two global functions are defined by this module:

- `setting($key, $default, $skipCache)` — Runtime configuration access
- `brand($key, $default)` — Dynamic branding values (name, title, logo, favicon, colors)
