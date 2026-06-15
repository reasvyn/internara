---
name: tailwindcss-development
description: Apply this skill whenever styling Blade templates, building responsive layouts, implementing dark mode, fixing spacing or typography, or working with daisyUI and maryUI components. Always invoke when the task involves Tailwind utility classes in HTML, Blade, or JSX templates.
---

# Tailwind CSS Development Skill

## When to Activate

Apply this skill whenever styling Blade templates, building responsive layouts, implementing dark mode, fixing spacing or typography, or working with daisyUI and maryUI components.

## Key References

- **CSS entrypoint**: `resources/css/app.css` — Tailwind v4 config with `@import`, `@theme`, `@plugin`
- **Architecture**: `docs/architecture.md#layered-architecture` (Layer 11 — UI/Presentation)
- **Livewire Pattern**: `docs/architecture/livewire-pattern.md`

## Tech Stack

| Technology | Role |
|-----------|------|
| Tailwind CSS v4 | Utility framework (CSS-first config, no `tailwind.config.js`) |
| daisyUI v5 | Component classes (`btn`, `card`, `modal`, `table`) |
| maryUI | Livewire component library (`x-mary-button`, `x-mary-card`, `x-mary-table`, `x-mary-modal`) |
| Blade Tabler Icons | `o-` prefix for icons |
| Alpine.js | Client-side interactivity |

## Tailwind v4 Configuration

All configuration is CSS-first in `resources/css/app.css`:

```css
@import "tailwindcss";
@plugin "daisyui" { exclude: properties; }
@source '../views';
@source "../../vendor/robsontenorio/mary/src/View/Components/**/*.php";

@custom-variant dark (&:where(.dark, .dark *));

@theme {
    --font-sans: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif, ...;
}
```

### Key Points

- `@import "tailwindcss"` replaces `@tailwind` directives
- `@theme` block replaces `tailwind.config.js` theme extend
- `@plugin "daisyui"` replaces daisyUI plugin registration
- `@source` directives tell Tailwind where to scan for class usage
- No `tailwind.config.js` file — all configuration in CSS
- v3 utilities like `bg-opacity-*` replaced with `bg-black/50` syntax

## Component Patterns

### Page Layout

```
flex header (title + action button) → search/filter bar → selection bar → content (table/grid)
```

### CRUD Table (maryUI)

```blade
<x-mary-card>
    <x-mary-table
        :headers="$headers"
        :rows="$this->rows()"
        :sort-by="$this->sortBy"
        selectable
        wire:model="selectedIds"
    >
        @scope('actions', $row)
            <x-mary-button icon="o-pencil" wire:click="edit('{{ $row->id }}')" spinner class="btn-ghost btn-sm" />
        @endscope
    </x-mary-table>
</x-mary-card>
```

### Modal Form (maryUI)

```blade
<x-mary-modal wire:model="showModal" title="{{ __('users.create') }}">
    <x-mary-input wire:model="form.name" label="{{ __('users.name') }}" />
    <x-mary-input wire:model="form.email" label="{{ __('users.email') }}" />
    <x-mary-button wire:click="save" label="{{ __('common.save') }}" class="btn-primary" />
</x-mary-modal>
```

### Button Variants

| Class | Purpose |
|-------|---------|
| `btn-primary` | Primary action |
| `btn-ghost` | Subtle/secondary |
| `btn-error` | Destructive action |
| `btn-sm` / `btn-xs` | Compact sizing |
| `btn-outline` | Outlined variant |

## Dark Mode

Class-based: `.dark` on `<html>` element toggled by `ThemeSwitcher` component.

```blade
<html lang="{{ app()->getLocale() }}" class="{{ theme() }}">
```

Custom variant: `@custom-variant dark (&:where(.dark, .dark *));` — use `dark:` prefix in templates.

## Responsive Design

Standard breakpoints: `sm` (640px), `md` (768px), `lg` (1024px), `xl` (1280px), `2xl` (1536px). Use `gap` on parent flex/grid containers rather than margins on children.

## Translation

```blade
{{ __('users.name') }}       {{-- ✅ --}}
{{ 'Name' }}                 {{-- ❌ hardcoded --}}
```

All user-facing strings must use `__()`.

## Locale-Aware Direction

The `.dark` class and the Arabic locale (`ar`) should both be checked for RTL-specific styling when implemented. Currently, LTR is the default.

## Verification

- `@import "tailwindcss"` and `@theme` used instead of `@tailwind` + `tailwind.config.js`?
- Deprecated v3 utilities avoided (no `bg-opacity-*`, `flex-shrink-0`)?
- `wire:key` on all `@foreach` loops?
- Translations instead of hardcoded strings?
- `gap` on parent containers instead of margins on children?
- Responsive at target breakpoints?
- Dark mode (`dark:` prefix) where needed?
- maryUI components used with correct prefixes (`x-mary-`)?
