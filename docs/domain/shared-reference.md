# Shared — API Reference
> Last updated: 2026-05-31
> Changes: docs: audit — all items Implemented

> **Legend:** ✅ Implemented = code exists | ⏳ Planned = not yet implemented

Total: 9 files — ✅ 9 Implemented (+ 17 Blade UI components)

## Enums

| File | Class | Implements | Description |
|---|---|---|---|
| `Shared/Enums/CsvRowResult.php` | `CsvRowResult` | `LabelEnum` | CSV import row result: CREATED or SKIPPED |

## Livewire Components

| File | Class | Extends | Description |
|---|---|---|---|
| `Shared/Livewire/ThemeSwitcher.php` | `ThemeSwitcher` | `Component` | Light/dark/system theme toggle with cookie persistence |
| `Shared/Livewire/LangSwitcher.php` | `LangSwitcher` | `Component` | Language toggle — delegates to `Locale::set()` for cookie persistence |

## Support

| File | Class | Description |
|---|---|---|
| `Shared/Support/CsvHandler.php` | `CsvHandler` | CSV export (streamed), import with header validation, template download |
| `Shared/Support/Environment.php` | `Environment` | Centralized environment detection — `isDebugMode()`, `isDevelopment()`, `isProduction()`, etc. |
| `Shared/Support/HasModelStatuses.php` | `HasModelStatuses` | Trait — bridges legacy Spatie HasStatuses with typed StatusEnum. @deprecated |
| `Shared/Support/LangChecker.php` | `LangChecker` | Implements Translator contract — logs warning on missing translation keys (dev only) |
| `Shared/Support/Locale.php` | `Locale` | Locale management — set, current, all, keys, isSupported, metadata. Cookie-based persistence |
| `Shared/Support/Theme.php` | `Theme` | Theme/color resolution — defaults, presets, all, cssVariables (cached) |

## Blade UI Components (`x-shared::ui.*`)

| File | Description |
|---|---|
| `shared/ui/brand.blade.php` | Brand logo with name and optional tagline |
| `shared/ui/logo.blade.php` | Brand logo image only |
| `shared/ui/credits.blade.php` | Footer credits with app signature |
| `shared/ui/theme-switcher.blade.php` | Theme toggle wrapper (light/dark/system) |
| `shared/ui/lang-switcher.blade.php` | Language toggle wrapper (EN/ID) |
| `shared/ui/navbar-actions.blade.php` | Navbar action items (theme, lang, notifications, user) |
| `shared/ui/confirm.blade.php` | Confirmation dialog |
| `shared/ui/display-field.blade.php` | Read-only display field with label and optional icon |
| `shared/ui/page-header.blade.php` | Page header with title and actions |
| `shared/ui/avatar.blade.php` | User avatar with fallback initials |
| `shared/ui/credit.blade.php` | Footer credit line |
| `shared/ui/markdown-editor.blade.php` | Markdown editor component |
| `shared/ui/record-manager.blade.php` | Data table with bulk actions |
| `shared/ui/selection-bar.blade.php` | Selection bar for bulk operations |

## Blade Widgets (`x-shared::widgets.*`)

| File | Description |
|---|---|
| `shared/widgets/stat-card.blade.php` | Displays a numeric statistic with icon and color |
| `shared/widgets/profile-summary.blade.php` | User avatar, name, role with optional edit button |
| `shared/widgets/quick-link.blade.php` | Navigation link with icon and chevron |
| `shared/widgets/action-button.blade.php` | Full-width action button for navigation |
| `shared/widgets/empty-state.blade.php` | Empty state placeholder with icon and text |

## Where to Find It

- `app/Domain/Shared/Support/Environment.php` — environment detection
- `app/Domain/Shared/Support/Locale.php` — locale management
- `app/Domain/Shared/Support/Theme.php` — theme/color resolution
- `app/Domain/Shared/Support/CsvHandler.php` — CSV handler
- `app/Domain/Shared/Support/LangChecker.php` — translation key checker (deprecated — use Laravel's built-in trans())
- `app/Domain/Shared/Support/HasModelStatuses.php` — legacy Spatie bridge trait
- `app/Domain/Shared/Enums/CsvRowResult.php` — CSV import result enum
- `app/Domain/Shared/Livewire/LangSwitcher.php` — language switcher component
- `app/Domain/Shared/Livewire/ThemeSwitcher.php` — theme switcher component
- `resources/views/shared/` — Blade views for Livewire components and layouts

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
