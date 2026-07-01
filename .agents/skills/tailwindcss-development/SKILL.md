---
name: tailwindcss-development
description: SDLC Phase: IMPLEMENTATION (Sub-skill). Specialized UI/styling development — Blade templates, responsive layouts, dark mode, daisyUI, maryUI, Tailwind CSS v4.
upstream:
  - feature-building
  - livewire-development
downstream:
  - sync-docs
---

> **⚠️ Context Awareness Required:** Before following any instruction in this skill,
> read [context-awareness.md](context-awareness.md). Do NOT trust numbers, paths,
> class names, or method signatures without verifying them in the actual codebase.
> The codebase evolves independently of this document — verify, don't assume.
> **Rule:** If the skill says a number/path/name, verify it in the code first.


# Tailwind CSS Development Skill

## When to Activate

Apply this skill whenever styling Blade templates, building responsive layouts, implementing dark mode, fixing spacing or typography, or working with daisyUI and maryUI components.

## SDLC Context

| Role | Skill |
|------|-------|
| **Upstream (input)** | `feature-building` — roadmap task requiring UI work |
| | `livewire-development` — component needing styling |
| **This skill** | **IMPLEMENTATION (UI/Styling)** — produces styled Blade templates |
| **Downstream (output)** | `sync-docs` — documentation after UI changes |
| **Phase** | [Planning] → [Analysis] → [Design] → Implementation → [Testing] → [Maintenance] |

## Key References

- **CSS entrypoint**: `resources/css/app.css` — Tailwind v4 config with `@import`, `@theme`, `@plugin`
- **Architecture**: `docs/architecture.md#4-layer-architecture` (Layer 4 — Presentation/UI)
- **Livewire Pattern**: `docs/architecture/livewire-pattern.md`
- **Conventions**: `docs/conventions.md` (§3 Security, §6 Performance)
- **UI Patterns**: `references/ui-patterns.md` (form, table, card, modal, navigation, states, animations, maryUI, layout)

## Tech Stack

| Technology | Role |
|-----------|------|
| Tailwind CSS v4 | Utility framework (CSS-first config, no `tailwind.config.js`) |
| daisyUI v5 | Component classes (`btn`, `card`, `modal`, `table`) |
| maryUI | Livewire component library (`x-mary-button`, `x-mary-card`, `x-mary-table`, `x-mary-modal`) |
| Flatpickr (via maryUI) | Date pickers (`x-mary-datepicker`) |
| Blade Tabler Icons | `o-` prefix for icons |
| Alpine.js | Client-side interactivity, `x-transition`, `x-data` |

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
- `@source` directives tell Tailwind where to scan for class usage — forgetting them breaks class scanning in dynamic content
- No `tailwind.config.js` file — all configuration in CSS
- v3 utilities like `bg-opacity-*` replaced with `bg-black/50` syntax
- `@custom-variant dark` enables `dark:` prefix in templates (class-based on `<html>`)

## Dark Mode

Class-based: `.dark` on `<html>` element toggled by `ThemeSwitcher` component.

```blade
<html lang="{{ app()->getLocale() }}" class="{{ theme() }}">
```

Custom variant: `@custom-variant dark (&:where(.dark, .dark *));` — use `dark:` prefix in templates.

```blade
<div class="bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 p-4 rounded-box">
    <p>{{ __('common.content') }}</p>
</div>

{{-- daisyUI handles dark via themes automatically --}}
<div class="card bg-base-100 text-base-content">
    {{-- Automatically adapts to light/dark --}}
</div>
```

### DaisyUI Themes

Configured in `app.css` via `@plugin "daisyui/theme"` blocks for `light` and `dark`. The `<html>` class toggles between them: `class="light"` or `class="dark"`.

### Color Transition on Theme Switch

```css
/* Already applied via daisyUI's --animation-btn and --animation-input */
* {
    transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
}
```

### Per-Element Dark Override

```blade
<span class="text-gray-500 dark:text-gray-400">{{ $meta }}</span>
```

## Typography & Spacing

### Consistent Spacing

```blade
{{-- Inside cards --}}
<div class="p-4 space-y-4">
    <h3 class="text-lg font-semibold">{{ __('users.details') }}</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        ...
    </div>
</div>

{{-- Between sections --}}
<div class="space-y-6">
    <section>...</section>
    <section>...</section>
</div>
```

### Heading Sizes

```blade
<h1 class="text-2xl font-bold">{{ __('dashboard.title') }}</h1>
<h2 class="text-xl font-semibold">{{ __('users.list') }}</h2>
<h3 class="text-lg font-medium">{{ __('users.details') }}</h3>
<p class="text-sm text-base-content/60">{{ __('common.meta') }}</p>
```

### Line Heights

```blade
<p class="leading-relaxed">{{ $description }}</p>
<p class="leading-tight text-xs">{{ $compact }}</p>
```

## Common Mistakes

### Forgetting `@source` Directives

Without `@source '../views'` and `@source` for maryUI, Tailwind v4 cannot scan Blade/component files for class names, breaking purging.

### Using v3 Syntax Instead of v4

```blade
{{-- ❌ Wrong (v3) --}}
<div class="bg-opacity-50 bg-black"></div>
<div class="flex-shrink-0"></div>

{{-- ✅ Correct (v4) --}}
<div class="bg-black/50"></div>
<div class="shrink-0"></div>
```

### Missing Responsive Prefixes

```blade
{{-- ❌ Always full-width on mobile --}}
<div class="grid grid-cols-3 gap-4">

{{-- ✅ Responsive --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
```

### Hardcoded Colors Instead of Theme Variables

```blade
{{-- ❌ Broken in dark mode --}}
<div class="bg-white text-black">

{{-- ✅ Adapts to theme --}}
<div class="bg-base-100 text-base-content">
```

### Overusing Margins Instead of Gap

```blade
{{-- ❌ Inconsistent spacing --}}
<div class="flex flex-col">
    <div class="mb-4">...</div>
    <div class="mb-4">...</div>
</div>

{{-- ✅ Consistent --}}
<div class="flex flex-col space-y-4">
    <div>...</div>
    <div>...</div>
</div>
```

### Skipping `wire:key` in Loops

```blade
{{-- ❌ Causes Livewire rendering bugs --}}
@foreach($items as $item)
    <tr>...</tr>
@endforeach

{{-- ✅ Stable DOM diffing --}}
@foreach($items as $item)
    <tr wire:key="{{ $item->id }}">...</tr>
@endforeach
```

### Direct Model Mutations in Livewire

```blade
{{-- ❌ Violates architecture --}}
<button wire:click="$emit('delete', {{ $id }})">

{{-- ✅ Delegates to Action --}}
<button wire:click="askDelete('{{ $id }}')">
```

### Forgetting PHPFlasher Over maryUI Toast

```blade
{{-- ❌ Not allowed --}}
$this->success(__('users.created'));

{{-- ✅ Mandated by conventions --}}
flash()->success(__('users.created_success'));
```

## Verification

- `@import "tailwindcss"` and `@theme` used instead of `@tailwind` + `tailwind.config.js`?
- `@source` directives present for all dynamic content paths?
- Deprecated v3 utilities avoided (no `bg-opacity-*`, `flex-shrink-0`)?
- `wire:key` on all `@foreach` loops?
- Translations (`__()`) instead of hardcoded strings?
- `gap` or `space-y` on parent containers instead of margins on children?
- Responsive at target breakpoints (mobile-first)?
- Dark mode (`dark:` prefix) where needed for custom overrides?
- maryUI components used with correct prefixes (`x-mary-`)?
- No inline DB mutations in Livewire — always delegated to Actions?
- PHPFlasher `flash()->success()` used instead of maryUI `$this->success()`?
- Form `@error` handling present for required fields?
- Empty/loading/error states handled for async content?
- Skeleton or spinner shown during loading (`wire:loading`)?
- N+1 queries avoided in Blade loops (eager loading verified)?
- Color uses theme variables (`bg-base-100`, `text-base-content`) not hardcoded values?
- Modal uses `backdrop-blur-sm` for overlay consistency?
- Sidebar uses drawer pattern for mobile responsiveness?
