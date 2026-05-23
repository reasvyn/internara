# Shared — API Reference

Total: 13 files

## Livewire Components

| File | Class | Extends | Description |
|---|---|---|---|
| `Shared/Livewire/ThemeSwitcher.php` | `ThemeSwitcher` | `Component` | Light/dark/system theme toggle with cookie persistence |
| `Shared/Livewire/LangSwitcher.php` | `LangSwitcher` | `Component` | EN/ID language toggle with cookie persistence |

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
| `shared/ui/page-header.blade.php` | Page header with title and actions |
| `shared/ui/markdown-editor.blade.php` | Markdown editor component |
| `shared/ui/record-manager.blade.php` | Data table with bulk actions |
| `shared/ui/selection-bar.blade.php` | Selection bar for bulk operations |

## Blade Widgets (read-only, `x-shared::widgets.*`)

| File | Description |
|---|---|
| `shared/widgets/stat-card.blade.php` | Displays a numeric statistic with icon and color |
| `shared/widgets/profile-summary.blade.php` | User avatar, name, role with optional edit button |
| `shared/widgets/quick-link.blade.php` | Navigation link with icon and chevron |
| `shared/widgets/action-button.blade.php` | Full-width action button for navigation |
| `shared/widgets/empty-state.blade.php` | Empty state placeholder with icon and text |

## Support

| File | Class | Description |
|---|---|---|
| `Shared/Support/CsvHandler.php` | `CsvHandler` | Utility for generating CSV downloads and templates |
| `Shared/Support/Environment.php` | `Environment` | Utility for checking environment configs |
| `Shared/Support/HasModelStatuses.php` | `HasModelStatuses` | Trait adding status management to Eloquent models |
| `Shared/Support/LangChecker.php` | `LangChecker` | Extended translator for checking language existence |
| `Shared/Support/Locale.php` | `Locale` | Utility for managing application locale |
| `Shared/Support/Theme.php` | `Theme` | Theme configuration from settings |
