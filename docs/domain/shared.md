# Shared Domain

## Purpose

Shared is a collection of cross-domain utility code that doesn't belong to any single business domain or to Core. When two or more domains need the same function (environment detection, locale management, CSV handling), the logic lives here — not in Core (which handles infrastructure patterns) and not in any business domain.

Shared has no Models, Controllers, Routes, or Views. Pure support code with minimal Livewire components for cross-domain UI utilities.

## Support Utilities

| Class | Type | Purpose |
|---|---|---|
| `Environment` | Final static | `isDebugMode()`, `isDevelopment()`, `isStaging()`, `isTesting()`, `isMaintenance()`, `isProduction()` — centralized environment detection instead of scattered `app()->environment()` calls. |
| `Locale` | Final class | Bilingual locale management (Indonesian default, English). `set()`, `current()`, `all()`, `keys()`, `isSupported()`, `metadata()`. Stores preference in session. Provides locale metadata (name, native name, flag icon). |
| `Theme` | Final static | Color/theme resolution system. `defaults()`, `presets()`, `all()`, `get(key)`, `cssVariables()` — resolves colors from the settings key-value store into CSS custom properties for both light and dark modes. |
| `CsvHandler` | Final class | `export(Collection, headers, rowMapper, filename)`, `downloadTemplate(headers, exampleRow, filename)`, `import(filePath, rowProcessor, expectedHeaders)` — CSV export/import with optional header validation. |
| `LangChecker` | Class (extends Translator) | Extends Laravel's `Translator`. Logs a warning when a translation key is not found — helps detect untranslated strings during development. Includes caller location in the warning. |
| `HasModelStatuses` | Trait | Bridges Spatie's generic `HasStatuses` (deprecated) with the application's typed `StatusEnum`. `setStatusEnum(StatusEnum)`, `hasStatusEnum(StatusEnum)`, `currentStatus(): ?StatusEnum` — type-safe status management for stateful models. Being migrated to plain `StatusEnum` columns. |

## Key Concepts

**Environment detection** (`Environment`) provides semantic methods that are clearer than raw config checks and centralizes environment logic. If a new environment needs to be supported, only this file changes.

**Locale management** (`Locale`) supports bilingual Indonesian/English with Indonesian as default. Locale preference is stored in session. Each locale carries metadata including display name, native name, and a flag icon reference.

**Theme system** (`Theme`) resolves colors from the database settings store into CSS custom properties using the `Color` utility from the Settings domain. It generates light and dark mode variables from a single color configuration — shades, content colors, and contrast colors are computed automatically.

**Model traits** provide reusable behavior: `HasModelStatuses` for models with state machine lifecycles (registrations, internships), bridging Spatie's legacy `HasStatuses` with typed `StatusEnum`. New models use plain `StatusEnum` columns directly.

## Requirements

### Purpose (Developer-Facing)

Shared provides cross-domain utility code. It has no end-user stories — the consumers are developers across all business domains.

### Utility Inventory

| Utility | Type | Used By | Purpose |
|---------|------|---------|---------|
| `CsvHandler` | Final class | School, Partnership, Internship | CSV export, import, template download with optional header validation |
| `Environment` | Final static | All domains | Centralized environment detection (`isDebugMode()`, `isDevelopment()`, `isProduction()`) |
| `Locale` | Final class | All views | Bilingual locale management (Indonesian default, English), session-based preference |
| `Theme` | Final static | Layout views | Color/theme resolution from settings into CSS custom properties for light/dark modes |
| `LangChecker` | Class | Development | Extends Laravel Translator — logs warnings for missing translation keys |
| `HasModelStatuses` | Trait | Registration, Internship (deprecated) | Type-safe bridge between Spatie statuses and `StatusEnum`. Being migrated to plain columns. |
| `LangSwitcher` | Livewire | All views | Bilingual language switcher component |
| `ThemeSwitcher` | Livewire | Layout views | Theme toggle component (light/dark mode) |
| `CsvRowResult` | Enum | School, Partnership | CSV import row result tracking |

### Rules

- Shared MUST NOT import any business domain (exception: Theme imports Settings)
- Shared MUST NOT have Models, Controllers, Routes, Views, or migrations (Livewire UI utilities are the sole exception)
- Code belongs in Shared only when used by at least 2 different domains
- Utilities must be stateless: static methods or immutable objects

### User Stories & Rules
- Shared MUST NOT import any business domain (exception: Theme imports Settings for color resolution).
- Shared MUST NOT have Models, Controllers, Routes, Views, or migrations (Livewire UI utilities are the sole exception).
- Utilities must be stateless: static methods or immutable readonly objects.
- Code belongs in Shared only when used by at least 2 different domains; single-domain utilities stay in their domain.
- Changes to Shared affect every consuming domain — backward compatibility matters.

## Dependencies

| Dependency | Reason |
|---|---|
| Core | Contract interfaces (`LabelEnum`, `StatusEnum`) used by `HasModelStatuses` bridge (legacy). |
| Settings | Theme resolves colors from the settings key-value store (documented exception). |


