# Settings Domain
> Last updated: 2026-05-31
> **Status:** ✅ **Fully Implemented** — all 21 files in [reference](settings-reference.md) exist


## Purpose

Settings manages the application's runtime configuration — the knobs and dials that can be
adjusted without a code deployment or server restart. Unlike environment variables (deployment-
specific) and config files (code-managed), settings are stored in the database and modified at
runtime through the admin interface.

Settings controls:
- **Appearance** — name, logo, colors, footer text
- **Behavior** — feature flags, default values, operational thresholds
- **Regional presentation** — language, timezone, date/number formats
- **Operational defaults** — pagination size, session lifetime, notification retention
- **Mail configuration** — SMTP host, credentials, encryption

---

## Design Principles

### 1. Multi-Tier Resolution with Caching

Every setting value resolves through a five-tier chain, ensuring the system always returns
the most specific value available:

```
1. Runtime overrides (ephemeral — for testing only)
2. AppInfo (composer.json — SSOT for app name, version, author)
3. Database (cached forever, invalidated on write)
4. Laravel config file (code defaults)
5. Default parameter (caller-provided fallback)
```

Tiers 1–2 are bypassed when not applicable. The database tier is cached with
`Cache::rememberForever()` and invalidated atomically on every write — no manual cache
clearing required.

### 2. Single Source of Truth for App Metadata

`AppInfo` reads from `composer.json` (cached 24h) and is the authoritative source for
application name, version, author, license, and support URLs. The `setting()` helper's
resolution chain maps several setting keys directly to `AppInfo` fields — so `app_name`
always returns the canonical name regardless of whether it exists in the database.

### 3. Type-Enforced Storage

Every setting has an explicit type. The `SettingValueCast` handles serialization and
deserialization automatically:

| Type | Stored As | Retrieved As |
|---|---|---|
| `string` | Plain string | `string` |
| `integer` | String representation | `int` |
| `float` | String representation | `float` |
| `boolean` | `0` or `1` | `bool` |
| `json` / `array` | JSON-encoded string | `array` |
| `encrypted` | Encrypted string | Decrypted `string` |
| `null` | `NULL` | `null` |

Type mismatches on write are rejected with validation errors. Encrypted values are
transparently decrypted on read.

### 4. Immediate Propagation via Cache Invalidation

Every setting change triggers atomic cache invalidation:
1. The specific key's cache entry is forgotten
2. The group cache is forgotten
3. The "all settings" cache is forgotten
4. If the key relates to colors or branding, the theme CSS variables cache is also forgotten

This guarantees that configuration changes take effect on the next page load — no restart,
no deployment, no manual cache clear.

### 5. Audit Trail on Every Write

All setting changes (create, update, delete) are logged via SmartLogger with before/after
values, acting user, timestamp, and IP address. This provides a complete change history for
compliance and troubleshooting.

---

## Domain Boundary

The Settings domain owns runtime application configuration — values stored in the database that can be changed through the admin interface without a code deployment or server restart. It manages appearance settings (application name, logo, favicon, colors, custom CSS), behavioral configuration (feature flags, operational thresholds, default values), regional presentation (language, timezone, date and number formats), operational defaults (pagination size, session lifetime, notification retention), and mail configuration (SMTP host, credentials, encryption). Every setting has an explicit type that enforces storage and retrieval behavior, and every change triggers automatic cache invalidation so updates take effect on the next page load.

Settings does not own user data, school profiles, program definitions, partnership records, or any operational domain data. It does not manage authentication or authorization (Auth), nor does it handle user identity persistence (User). It provides the configurable knobs that other domains read at runtime but does not own the business logic that consumes those values.

The domain relies on Core for caching infrastructure and logging, and is consumed by Shared (theme color resolution) and virtually every other domain that needs configurable behavior. It references no foreign keys to other domains — settings are stored as self-contained key-value pairs — but its values influence behavior across the entire application.

---

## Key Features

- Store and retrieve configuration values with enforced types including string, integer, float, boolean, JSON, encrypted, and null.
- Configure application branding including name, logo, favicon, primary and accent colors, and custom CSS.
- Toggle feature flags at runtime to enable or disable system capabilities without deployment.
- Configure SMTP mail settings and send a test email to verify the configuration works.
- Automatically invalidate all relevant cache entries whenever any setting value is changed.
- Maintain a complete audit trail of every setting change with before-and-after values, acting user, and timestamp.
- Pick brand colors using a color picker with a live preview of the application theme updating in real time.
- Upload a logo and favicon with an instant visual refresh of the application header and browser tab.
- Send a test email and receive an immediate success or failure notification.
- Toggle feature flags on or off with simple switch controls and instant feedback.
