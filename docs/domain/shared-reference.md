# Shared — API Reference

Total: 9 files

## Enums

| File | Class | Implements | Description |
|---|---|---|---|
| `Shared/Enums/CsvRowResult.php` | `CsvRowResult` | — | CSV import row result: CREATED or SKIPPED |

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
| `Shared/Support/LangChecker.php` | `LangChecker` | Extended Translator — logs warning on missing translation keys (dev only) |
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
