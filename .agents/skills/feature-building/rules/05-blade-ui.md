# Blade & UI

## Stack

| Layer | Technology |
|---|---|
| CSS | TailwindCSS v4 (CSS-first) |
| Components | daisyUI v5 + maryUI 2 (`x-mary-*`) |
| Interactivity | Livewire 4 + Alpine.js |
| Typography | Instrument Sans (self-hosted) |
| Icons | Blade Tabler Icons (`o-*` prefix) |
| Theming | DaisyUI light/dark themes, `data-theme` attribute |
| Brand colors | Dynamic via `BrandColors::cssVariables()` |
| Dark mode | Class-based: `.dark` on `<html>` |
| Localization | `__()` for all user-facing strings |

## Component Namespacing

```blade
<!-- maryUI components -->
<x-mary-input wire:model="name" />
<x-mary-table :headers="$headers" :rows="$rows" />
<x-mary-button label="Save" class="btn-primary" wire:click="save" />
<x-mary-badge :value="$status" />
<x-mary-modal wire:model="showModal">
<x-mary-avatar :image="$url" placeholder="JD" />

<!-- Layout components -->
<x-layouts::app :$title>
<x-layouts::auth :$title>
<x-layouts::base :$title>

<!-- UI components -->
<x-ui::brand size="md" />
<x-ui::theme-switcher />
<x-ui::lang-switcher />
```

## Page Layout

Every Livewire component defines its layout via attribute:
```php
#[Layout('layouts::app', ['title' => 'Page Title'])]
```

The title automatically formats as `{site_title} - {title}`.

## Dark Mode

```html
<html data-theme="light|dark|system">
```
- System mode reads `prefers-color-scheme`
- Toggle via `ThemeSwitcher` component
- Custom CSS variant: `@custom-variant dark (&:where(.dark, .dark *));`

## Notifications

Use PHPFlasher for all flash messages:
```php
flash()->success(__('message'));
flash()->error(__('message'));
flash()->warning(__('message'));
```

Never use maryUI Toast methods (`$this->success()`, `$this->error()`, etc.).

## File Uploads

Use maryUI's built-in file upload via Livewire `WithFileUploads`:
```php
use Livewire\WithFileUploads;
public $avatar;
// View: <input type="file" wire:model="avatar">
```
