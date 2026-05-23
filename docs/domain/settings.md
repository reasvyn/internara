# Settings Domain

## Purpose

Settings manages the application's runtime configuration — the knobs and dials that can be 
adjusted without a code deployment or server restart. Unlike environment variables (which are 
deployment-specific and change only when the server is reconfigured) and config files (which are 
code-managed and change only when new code is deployed), settings are stored in the database and 
can be modified at runtime through the admin interface. This domain controls the application's 
appearance (name, logo, colors, footer text), behavior (feature flags, default values, timeouts), 
regional presentation (language, timezone, date and number formats), and operational defaults 
(pagination sizes, notification preferences). Settings enables administrators to customize the 
application to their institution without writing code.

## Boundary

**In scope:** Key-value configuration store (CRUD for application settings with type 
enforcement), setting groups and categories (branding, localization, system, features, 
notifications) for admin UI organization, setting type enforcement and validation (boolean 
switches, text fields, numeric thresholds, JSON configuration blocks, image uploads, color 
pickers), branding configuration (application name, logo, favicon, primary/secondary/accent 
colors, footer text, custom CSS), localization settings (default language, available languages, 
timezone, date format, time format, number format, first day of week, currency symbol), feature 
flag management (enable or disable features at runtime with no deployment), cache invalidation on 
any setting change (ensuring immediate effect), complete setting change audit trail.

**Out of scope:** Environment-level configuration (Laravel .env file and config/ directory files 
— these are deployment-level concerns managed by the operations team), user-level individual 
preferences (User domain Profile stores personal user preferences like notification preferences 
and UI density), school-level configuration (School domain manages institution-specific settings 
like department structure and academic years), security-headers configuration (Core/Auth domain 
manages HTTP security headers), notification channel configuration per domain (each domain 
manages its own notification channel preferences).

## Key Concepts

**Configuration Store.** A key-value storage system purpose-built for runtime configuration. Each 
setting is a record with: a unique string key (e.g., "app.name", "branding.primary_color", 
"localization.timezone"), a value stored as a string (cast to the appropriate PHP type at read 
time — boolean, integer, float, string, array from JSON, or media URL for images), a 
human-readable display name, a description explaining the setting's purpose, a group assignment 
for admin UI organization, a type definition that determines the form control used in the admin 
UI (text input, toggle switch, color picker, image upload, select dropdown, code editor for 
JSON), and optional validation rules. Settings are cached in memory after the first read — 
subsequent reads hit the cache rather than querying the database. The entire settings cache is 
automatically invalidated whenever any setting is created, updated, or deleted, ensuring the 
application always sees current values without manual cache clearing.

**Setting Groups.** Settings are organized into groups for conceptual clarity and admin UI 
navigation. BRANDING: application name, logo, favicon, primary/secondary/accent colors, footer 
text, custom CSS — controls the application's visual identity across all pages and emails. 
LOCALIZATION: default language, available languages list, timezone, date format (short, medium, 
long), time format (12h/24h), number format (decimal and thousands separators), first day of 
week, currency symbol — controls how dates, times, numbers, and currencies are displayed. 
SYSTEM: operational defaults like default pagination size, session lifetime, upload maximum file 
size, cache and queue driver selection (read-only display), maintenance mode toggle. FEATURES: 
feature flags — boolean settings that enable or disable specific application features at 
runtime. NOTIFICATIONS: default notification delivery preferences, digest timing defaults, and 
notification retention settings.

**Branding Configuration.** Controls the application's visual identity, making each installation 
feel unique to its institution. The APPLICATION NAME appears in page titles (browser tab), email 
subjects, notification messages, and the navigation bar. The LOGO is an uploaded image displayed 
in the navigation bar, login page, and email headers (with automatic responsive image variants 
via media library conversions). The FAVICON is a small icon for browser tabs and bookmarks. The 
COLOR SCHEME (primary, secondary, accent) is injected into the Tailwind CSS configuration at 
runtime, changing buttons, links, badges, headers, and accent elements across all pages. FOOTER 
TEXT appears in the page footer (commonly used for copyright notices or institutional 
disclaimers). CUSTOM CSS allows installations to override specific styles without modifying core 
CSS files. Branding changes take effect immediately upon save — no build step, no deployment.

**Feature Flags.** Boolean settings that enable or disable features at runtime without any code 
deployment. Each feature flag has: a unique key, a display name, a description explaining what 
disabling the feature affects, and the current state (ENABLED or DISABLED). Feature flags serve 
multiple purposes: GRADUAL ROLLOUT — enable a feature for internal users first before opening 
to all users; EMERGENCY DISABLE — instantly turn off a problematic feature in production 
without a rollback deployment; A/B TESTING — enable a feature for a subset of users to compare 
behavior; SEASONAL FEATURES — enable registration features only during enrollment periods. 
Feature flag checks are evaluated at both the UI level (hide/show elements) and the backend level 
(gate/ungate operations), ensuring disabled features cannot be accessed even by direct URL entry.

**Audit Trail and Cache Invalidation.** Every setting change is audited with full 
before-and-after details: who changed it, what the previous value was, what the new value is, the 
timestamp, and the IP address of the changer. This provides a complete change history for 
compliance, troubleshooting, and rollback reference. When any setting is changed, the settings 
cache is immediately and atomically invalidated. The next read of any setting (which happens on 
nearly every page load — branding colors, locale, pagination size) will re-fetch from the 
database and rebuild the cache. This ensures that configuration changes take effect instantly 
across the entire application.

## Requirements

### User Stories & Rules

**Branding & Identity**
- **Admin:** As an admin, I want to change the application name and branding colors so that the system reflects my institution's identity
- **Admin:** As an admin, I want to upload a logo and favicon so that the application looks professional
- Branding setting changes trigger immediate cache invalidation — visual changes take effect on the next page load, no restart needed
- Image-type settings (logo, favicon) store media library references; the uploaded file is versioned and optimized automatically

**General Configuration**
- **Admin:** As an admin, I want to configure localization (language, timezone, date format) so that the system matches regional preferences
- **Admin:** As an admin, I want to modify system defaults (pagination, session lifetime) so that operational behavior is tuned
- **Admin:** As an admin, I want to toggle feature flags so that I can enable or disable functionality without deployment
- Feature flags that are referenced by code cannot be deleted — they can only be disabled; deletion is blocked at the database level
- Critical settings (affecting security, core functionality, or data integrity) require a confirmation dialog before the change is applied

**Mail Configuration**
- **Admin:** As an admin, I want to test email configuration so that I can verify notifications are delivered
- `TestMailNotification` provides a simple mail message for testing SMTP configuration

**System Integrity**
- **System:** As the system, I want to invalidate the settings cache on every change so that updates take effect immediately
- Setting keys must be unique across the entire application — key collisions are rejected at write time with a clear error message
- Setting values are validated against their declared type on every write; type mismatches are rejected with validation errors
- All setting changes (create, update, delete) are audited with before and after values, acting user, and timestamp
- The settings cache is invalidated on every write — there is no manual "clear cache" action needed
- Default values for all settings are defined in code (config files); database settings override code defaults for the current runtime values
- All logging uses SmartLogger; empty catch blocks from preview URL generation have been replaced with SmartLogger warnings

### Key Operations

| Action | Description |
|--------|-------------|
| `SetSettingAction` | Sets a single setting value |
| `BatchSetSettingAction` | Sets multiple settings in one operation |
| `UploadBrandAssetAction` | Uploads a branding asset (logo, favicon) |
| `TestMailSettingsAction` | Sends a test email to verify mail configuration |

### Technical Reference

| Layer | Artifacts |
|-------|-----------|
| **Models** | `Setting` (key-value store with type enforcement) |
| **Livewire** | `SystemSetting`, `AppSignature` |
| **Support** | `Color` (color computation), `Settings` (cached setting retrieval), `AppInfo`, `AppMetadata` |
| **Middleware** | `SetLocaleMiddleware` |
| **Rules** | `ValidSettingKey` |

## Dependencies

| Dependency | Reason |
|---|---|---|
| Core | BaseModel, BaseAction, SmartLogger |
| User | TestMailNotification (for email testing) |


