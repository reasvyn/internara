# Shared Domain

## Purpose

Shared is a collection of cross-domain utility code that doesn't belong to any single business
domain or to Core. When two or more domains need the same function — environment detection,
locale management, CSV handling, theme resolution — the logic lives here.

Shared has no Models, Controllers, Routes, Views, or database migrations. It contains only
pure utility classes, support traits, and a minimal set of Livewire components for cross-domain
UI features (language and theme switching).

---

## Design Principles

### 1. Used by at Least Two Domains

Code belongs in Shared only when consumed by at least two different business domains.
Single-domain utilities stay in their owning domain. This prevents Shared from becoming
a dumping ground for "something I don't know where to put."

### 2. Stateless and Immutable

Every class in Shared is either:

- A **final class with static methods** — no mutable state, no constructor injection
- A **final readonly object** — immutable after construction
- A **trait** — provides behavior without state
- An **enum** — self-contained constants

This guarantees that Shared utilities have no side effects and are safe to call from
anywhere in the application.

### 3. No Business Logic

Shared utilities operate on primitive types, framework abstractions, or Core contracts.
They never encode business rules — no status checks, no permission gating, no domain
concepts. Business logic belongs in business domains.

### 4. Backward Compatibility Matters

Every domain depends on Shared. A breaking change in a Shared utility cascades to every
consumer. Add new methods rather than changing existing signatures. Deprecate gradually
rather than removing.

### 5. Framework Dependencies Are Explicit

Shared may depend on Laravel's facades, helpers, and service container — unlike entities
in business domains which must be framework-free. This is acceptable because Shared
utilities are pure infrastructure code, not domain rules.

---

## Layer Structure

Shared has three subdirectories, each with a distinct role:

```
app/Domain/Shared/
├── Enums/        → Self-contained enumerations
├── Livewire/     → Cross-domain UI components
└── Support/      → Utility classes, traits, helpers
```

---

## Enums

### CsvRowResult

```php
enum CsvRowResult: string
{
    case CREATED = 'created';
    case SKIPPED = 'skipped';
}
```

Tracks the result of importing a single CSV row. Returned by the row processor callback
in `CsvHandler::import()` to indicate whether the row was successfully created or skipped.

Design rationale: A dedicated enum rather than a boolean or magic string makes CSV import
results self-documenting in both the handler and the caller.

---

## Livewire Components

### ThemeSwitcher

A Livewire component that toggles between light, dark, and system-preferred themes.

```php
class ThemeSwitcher extends Component
{
    public string $theme = 'system';
}
```

| Method | Trigger | Behavior |
|---|---|---|
| `mount()` | Component load | Reads theme preference from `theme` cookie (default: `system`) |
| `setTheme(light\|dark\|system)` | User click | Sets cookie, dispatches `theme-changed` event |

The preference is stored in a cookie (not session) so it persists across browser restarts
and is accessible to both Livewire and the Blade layout (which reads the cookie to set
the `data-theme` attribute on `<html>`).

### LangSwitcher

A Livewire component that toggles between supported interface languages.

```php
class LangSwitcher extends Component
{
    public string $locale = 'en';
}
```

| Method | Trigger | Behavior |
|---|---|---|
| `mount()` | Component load | Reads current locale from `app()->getLocale()` |
| `setLocale(locale)` | User click | Sets `locale` cookie, calls `app()->setLocale()`, dispatches `language-changed` event |

Design rationale: Language preference is stored in a cookie (consistent with ThemeSwitcher
pattern) rather than session. This makes the locale available at the HTTP middleware level
before session is started, and survives browser restarts. The `SetLocaleMiddleware` reads
from the cookie first, then falls back to session, then `APP_LOCALE`.

---

## Support Utilities

### Environment

Centralized environment detection that replaces scattered `app()->environment()` calls
throughout the codebase.

```php
final class Environment
{
    public static function isDebugMode(): bool
    public static function isDevelopment(): bool   // local, dev
    public static function isStaging(): bool        // staging
    public static function isTesting(): bool        // PHPUnit
    public static function isMaintenance(): bool    // down for maintenance
    public static function isProduction(): bool     // production
}
```

Design rationale: Semantic method names are clearer than `app()->environment('production')`
and centralize environment logic. If a new environment needs to be supported (e.g.,
`acceptance`), only this file changes.

Usage in domain code:

```php
if (Environment::isProduction()) {
    // Production-only behavior
}
```

### Locale

Bilingual locale management with support for English and Indonesian, designed to be
extensible for additional languages.

```php
final class Locale
{
    public const string DEFAULT_LOCALE = 'en';

    public const array SUPPORTED_LOCALES = [
        'en' => ['name' => 'English', 'native' => 'English'],
        'id' => ['name' => 'Indonesian', 'native' => 'Bahasa Indonesia'],
    ];

    public static function set(string $locale): bool
    public static function current(): string
    public static function all(): array
    public static function keys(): array
    public static function isSupported(string $locale): bool
    public static function metadata(string $locale): ?array
}
```

| Method | Purpose |
|---|---|
| `set()` | Sets the locale in session and updates the application locale |
| `current()` | Returns the active locale from session or config default |
| `all()` | Returns all supported locales with metadata |
| `keys()` | Returns just the locale codes (`['en', 'id']`) |
| `isSupported()` | Checks if a locale code is valid |
| `metadata()` | Returns display name and native name for a locale |

Design rationale: The default locale is English (`en`) as the primary interface language,
with Indonesian (`id`) as the localized market variant. Locale data is centralized here
rather than duplicated in the Livewire component, middleware, and config files.

### Theme

Color and theme resolution system that transforms database-styled brand colors into CSS
custom properties for both light and dark modes.

```php
final class Theme
{
    public static function defaults(): array
    public static function presets(): array
    public static function all(): array
    public static function get(string $key): string
    public static function base(): string
    public static function cssVariables(): array
}
```

| Method | Purpose |
|---|---|
| `defaults()` | Hardcoded fallback colors if settings are empty |
| `presets()` | Predefined color palettes from config |
| `all()` | Resolved colors from settings (or defaults) |
| `get(key)` | Single resolved color value |
| `base()` | Base page background color |
| `cssVariables()` | Computed light + dark CSS custom properties (cached) |

Design rationale: `cssVariables()` is cached via `CacheKeys::THEME_CSS_VARIABLES` (1 hour
TTL) because it iterates over all colors and computes contrast shades. The cache is
invalidated whenever brand colors change in settings. This computation is done server-side
rather than in CSS to support runtime branding changes without a rebuild.

The `all()` method reads colors from the Settings domain's key-value store via
`Settings::get()`. This is a documented exception to the "no business domain imports"
rule — Theme is a cross-cutting concern that must read settings, and duplicating the
color storage in Shared would violate DRY.

### CsvHandler

CSV export, import, and template download with optional header validation.

```php
final class CsvHandler
{
    public function export(Collection $items, array $headers, callable $rowMapper, string $filename): StreamedResponse
    public function downloadTemplate(array $headers, array $exampleRow, string $filename): StreamedResponse
    public function import(string $filePath, callable $rowProcessor, ?array $expectedHeaders): array
}
```

| Method | Purpose |
|---|---|
| `export()` | Streams a CSV response from a collection with a row mapping callback |
| `downloadTemplate()` | Streams a CSV template with headers and one example row |
| `import()` | Reads a CSV file, processes each row, returns `{created, skipped, invalid}` |

Design rationale: `export()` and `downloadTemplate()` return `StreamedResponse` to handle
large datasets without loading everything into memory. `import()` uses `CsvRowResult` enum
for row processing results. Header validation is optional — pass `null` to skip.

### LangChecker

Development-only translator extension that logs warnings for missing translation keys.

```php
final class LangChecker extends Translator
{
    public function get($key, array $replace = [], $locale = null, $fallback = true): string|array
}
```

Activated in `AppServiceProvider::register()` when `APP_DEBUG=true`. When a translation
key is not found and the result equals the key string, it logs a warning via SmartLogger
with the caller file and line number.

Design rationale: Catches untranslated keys during development before they reach
production. The caller detection uses `debug_backtrace` filtered to skip vendor and
framework frames. Only active in debug mode — zero overhead in production.

### HasModelStatuses (Heritage)

A trait that bridges Spatie's legacy `HasStatuses` (deprecated in the package) with the
application's typed `StatusEnum` contract.

```php
trait HasModelStatuses
{
    public function setStatusEnum(StatusEnum $status): static
    public function hasStatusEnum(StatusEnum $status): bool
    public function currentStatus(): ?StatusEnum
}
```

**Status:** Heritage — maintained for existing models but not used for new development.
New stateful models should use plain `StatusEnum` columns with a `status` string column
casting to the enum type, avoiding the Spatie package entirely.

Migration path:
1. Add `status` string column casting to `StatusEnum` on the model
2. Replace `setStatusEnum($x)` calls with `$model->status = $x`
3. Replace `hasStatusEnum($x)` calls with `$model->status === $x`
4. Replace `currentStatus()` calls with `$model->status`
5. Remove the `HasStatuses` import and the trait usage

---

## Dependency Graph

```
                  ┌─────────────────────────┐
                  │  Shared Domain           │
                  │  ┌──────┬──────┬──────┐  │
                  │  │Enums │Livewr│Support│  │
                  │  └──┬───┴──────┴──┬───┘  │
                  └────┼──────────────┼──────┘
                       │              │
          ┌────────────┘              └────────────┐
          ▼                                        ▼
┌──────────────────┐                  ┌──────────────────────┐
│   Core Domain    │                  │  Settings Domain     │
│  (Contracts)     │                  │  (Color, Settings)   │
└──────────────────┘                  │  — Theme only, doc'd │
                                      │    exception         │
                                      └──────────────────────┘
```

---

## What Shared Does NOT Contain

| Excluded | Reason | Belongs In |
|---|---|---|
| Models | Shared has no database tables | Business domains |
| Routes | Shared has no HTTP endpoints | `routes/web/{domain}.php` |
| Controllers | Shared has no HTTP handling | Business domains |
| Views (except Livewire) | UI belongs to consuming domains | Domain-specific view directories |
| Migrations | Shared has no schema | `database/migrations/` |
| Business logic | Domain rules belong in business domains | Respective business domains |
| Feature-specific utilities | Single-domain utilities stay in their domain | The consuming domain |

---

## Requirements

### Rules

- Shared MUST NOT import any business domain (exception: Theme imports Settings for color
  resolution — documented in architecture.md)
- Shared MUST NOT have Models, Controllers, Routes, Views (except Livewire components), or
  database migrations
- Code belongs in Shared only when used by at least 2 different domains
- Utilities must be stateless: final classes with static methods, final readonly objects,
  or traits
- Backward compatibility: add new methods rather than changing existing signatures;
  deprecate gradually
- All locale and theme data is centralized in Shared — not duplicated in middleware,
  Livewire components, and config files

### Utility Inventory

| Utility | Type | Consumed By | Purpose |
|---|---|---|---|
| `Environment` | Final static | All domains | Centralized environment detection |
| `Locale` | Final class | All views, middleware | Locale management and metadata |
| `Theme` | Final static | Layout views, admin settings | Color/theme resolution into CSS |
| `CsvHandler` | Final class | School, Partnership, Internship | CSV export, import, template |
| `LangChecker` | Class (extends Translator) | Development | Warning on missing translation keys |
| `HasModelStatuses` | Trait | Registration, Internship (heritage) | Legacy Spatie bridge (deprecated) |
| `LangSwitcher` | Livewire | All layouts | Language toggle UI |
| `ThemeSwitcher` | Livewire | All layouts | Theme toggle UI (light/dark/system) |
| `CsvRowResult` | Enum | School, Partnership, Internship | CSV import row result |

## Where to Find It

- `app/Domain/Shared/Support/Environment.php` — environment detection
- `app/Domain/Shared/Support/Locale.php` — locale management
- `app/Domain/Shared/Support/Theme.php` — theme/color resolution
- `app/Domain/Shared/Support/CsvHandler.php` — CSV handler
- `app/Domain/Shared/Support/LangChecker.php` — translation key checker
- `app/Domain/Shared/Support/HasModelStatuses.php` — legacy Spatie bridge trait
- `app/Domain/Shared/Enums/CsvRowResult.php` — CSV import result enum
- `app/Domain/Shared/Livewire/LangSwitcher.php` — language switcher component
- `app/Domain/Shared/Livewire/ThemeSwitcher.php` — theme switcher component
- `resources/views/shared/` — Blade views for Livewire components and layouts
