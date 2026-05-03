# UI/UX Design System

## Architecture

### Stack

- **CSS:** TailwindCSS v4 + DaisyUI (theme engine) + maryUI (Blade components)
- **JS:** Alpine.js (instant interactions) + Livewire (server-state sync)
- **Font:** Instrument Sans (400, 500, 600) — self-hosted via `resources/fonts/`

### Layer Hierarchy

```
base.blade.php          → DOCTYPE, <html>, <head>, <body>, @stack('scripts')
  └─ app.blade.php      → header slot, <main> content, footer (signature default)
  └─ auth.blade.php     → centered auth page (TODO: extend base.blade.php)
  └─ header.blade.php   → sticky navbar, role-based nav, user dropdown
```

## Theming

### Design Tokens (DaisyUI OKLCH)

| Token          | Light                        | Dark                          | Purpose                              |
| -------------- | ---------------------------- | ----------------------------- | ------------------------------------ |
| `base-100`     | `oklch(100% 0 0)` White      | `oklch(15% 0 0)` Dark         | Card backgrounds                     |
| `base-200`     | `oklch(97% 0 0)` Light Gray  | `oklch(10% 0 0)` Darker       | Subtle backgrounds, table headers    |
| `base-300`     | `oklch(94% 0 0)` Gray        | `oklch(7% 0 0)` Near Black    | Page background                      |
| `base-content` | `oklch(10% 0 0)` Near Black  | `oklch(95% 0 0)` Near White   | Primary text                         |
| `primary`      | `oklch(0% 0 0)` Pure Black   | `oklch(90% 0 0)` Light Gray   | Primary actions (inverted per theme) |
| `secondary`    | `oklch(45% 0 0)` Medium Gray | `oklch(55% 0 0)` Medium Gray  | Secondary actions                    |
| `accent`       | `oklch(25% 0 0)` Darker Gray | `oklch(75% 0 0)` Lighter Gray | Accent emphasis                      |
| `neutral`      | `oklch(45% 0 0)` Gray        | `oklch(30% 0 0)` Dark Gray    | Neutral elements                     |

### Semantic Colors

| State     | Light                            | Dark                                     | Use                         |
| --------- | -------------------------------- | ---------------------------------------- | --------------------------- |
| `info`    | `oklch(60% 0.55 250)` Bold Blue  | `oklch(70% 0.38 250)` Eye-catching Blue  | Informational messages      |
| `success` | `oklch(55% 0.58 150)` Bold Green | `oklch(65% 0.42 150)` Eye-catching Green | Success states              |
| `warning` | `oklch(70% 0.6 80)` Bold Amber   | `oklch(75% 0.45 80)` Eye-catching Amber  | Warnings, pending states    |
| `error`   | `oklch(55% 0.62 25)` Bold Red    | `oklch(65% 0.48 25)` Eye-catching Red    | Errors, destructive actions |

### Radii

- `--radius-selector`: `0.5rem` (dropdowns, selects)
- `--radius-field`: `0.5rem` (inputs, buttons)
- `--radius-box`: `0.75rem` (cards, containers)

### Theme Switching

- Cookie-based (`theme` cookie)
- Three modes: `light`, `dark`, `system` (follows OS preference)
- Dark variant: `@custom-variant dark (&:where(.dark, .dark *))`
- Applied via `html` class toggle — no page reload (Alpine.js)

## Component Patterns

### Enterprise Component Classes

| Class               | Purpose           | Key Styles                                                                |
| ------------------- | ----------------- | ------------------------------------------------------------------------- |
| `.card-enterprise`  | Card containers   | `rounded-3xl`, `shadow-xl`, `overflow-hidden`, `transition-all 300ms`     |
| `.table-enterprise` | Data tables       | `rounded-2xl`, `border base-content/5`, uppercase tracking-widest headers |
| `.stat-enterprise`  | Statistic widgets | `rounded-2xl`, `hover:scale-[1.01]`, `hover:shadow-md`                    |

### maryUI Component Usage

- **Tables:** `x-mary-table` with `:headers`, `:rows`, `with-pagination`, `@scope` for cell
  customization
- **Forms:** `x-mary-input`, `x-mary-select`, `x-mary-textarea`, `x-mary-checkbox` with `wire:model`
- **Feedback:** `x-mary-badge` (status), `x-mary-alert` (messages), `x-mary-toast` (notifications)
- **Navigation:** `x-mary-dropdown`, `x-mary-menu`, `x-mary-button` (with `icon`, `label`,
  `spinner`)
- **Layout:** `x-mary-header` (page headers with `title`, `subtitle`, `separator`,
  `progress-indicator`), `x-mary-card`, `x-mary-modal`

### Plain HTML Workaround (Temporary)

Some scaffolded views use plain Tailwind HTML instead of maryUI due to `$this` context errors. These
must be migrated back to maryUI components once the root cause is resolved. Affected views are
flagged in `known-issues.md`.

## Layout System

### Page Structure

```
┌─────────────────────────────────────────┐
│  header (sticky, z-50)                  │
│  ├─ Logo/Brand                          │
│  ├─ Role-based navigation               │
│  └─ User menu (theme, language, profile)│
├─────────────────────────────────────────┤
│  main (flex-1, container mx-auto)       │
│  ├─ x-mary-header (page title + actions)│
│  └─ Content area                        │
├─────────────────────────────────────────┤
│  footer (mt-auto)                       │
│  └─ App signature (author credit)       │
└─────────────────────────────────────────┘
```

### Responsive Breakpoints

- Mobile-first design
- `px-4 md:px-6 lg:px-8` — container padding scales with viewport
- `hidden md:flex` — desktop nav hidden on mobile, visible from `md`
- Mobile menu triggered via `@click="$dispatch('toggle-sidebar')"` (Alpine.js event)

### Z-Index Hierarchy

| Layer     | Z-Index    | Element                               |
| --------- | ---------- | ------------------------------------- |
| Header    | `z-50`     | Sticky navbar                         |
| Drawer    | `z-40`     | Mobile sidebar                        |
| Dropdowns | `z-[1000]` | Shared dropdown menus, choices panels |

## Interaction Patterns

### Alpine.js — Instant Interactions

Alpine.js handles all client-side interactions without server round-trips:

| Pattern           | Usage                         | Example                                   |
| ----------------- | ----------------------------- | ----------------------------------------- |
| `@click`          | Toggle state, trigger actions | `@click="switchTheme('dark')"`            |
| `x-model`         | Two-way binding               | `x-model="searchQuery"`                   |
| `x-show` / `x-if` | Conditional rendering         | `x-show="isOpen"`                         |
| `x-transition`    | Enter/leave animations        | `x-transition.duration.300ms`             |
| `x-cloak`         | Hide uncompiled content       | `[x-cloak] { display: none !important; }` |
| `$dispatch`       | Custom events                 | `$dispatch('toggle-sidebar')`             |
| `$wire`           | Livewire proxy                | `$wire.generateModal = false`             |

### Livewire — Server-State Sync

- `wire:model` — form input binding
- `wire:model.live` — reactive updates on every keystroke
- `wire:model.live.debounce.300ms` — debounced search/filter inputs
- `wire:click` — server action triggers
- `wire:loading` — loading state indicators
- `spinner="actionName"` — button spinner during action
- `wire:navigate` — instant page transitions (Livewire SPA mode)

### Form Submission Pattern

```blade
<x-mary-button label="Save" class="btn-primary" wire:click="save" spinner="save" />
```

### Modal Pattern

```blade
<x-mary-modal wire:model="modalOpen" title="Title" separator>
    {{-- content --}}
    <x-slot:actions>
        <x-mary-button label="Cancel" @click="$wire.modalOpen = false" />
        <x-mary-button label="Submit" class="btn-primary" wire:click="submit" spinner="submit" />
    </x-slot>
</x-mary-modal>
```

### Table Pattern

```blade
<x-mary-table :headers="$headers" :rows="$data" with-pagination>
    @scope('cell_status', $row)
        <x-mary-badge :value="$row->status" class="badge-success" />
    @endscope

    @scope('actions', $row)
        <x-mary-button icon="o-pencil" class="btn-ghost btn-sm" wire:click="edit({{ $row->id }})" />
    @endscope
</x-mary-table>
```

## Accessibility

### Requirements

- Mobile-first responsive design (touch targets ≥ 44px)
- Color contrast meets WCAG AA (OKLCH tokens designed for contrast)
- Keyboard navigation supported (DaisyUI components include focus states)
- `x-cloak` prevents flash of unstyled content during Alpine.js initialization
- Skip-to-content link (planned, not yet implemented)
- Preloader animation (planned, not yet implemented)

## Conventions

### Naming

- View files: `kebab-case.blade.php`
- Livewire components: `PascalCase.php` → `kebab-case.blade.php`
- Layout slots: `$slot`, `$header`, `$footer`, `$title`
- Component props: `@props(['title' => null])`

### Styling

- Use DaisyUI semantic classes (`btn-primary`, `badge-success`, `bg-base-100`) — not raw color
  values
- Use enterprise component classes (`.card-enterprise`, `.table-enterprise`, `.stat-enterprise`) for
  consistent styling
- Hover transitions: `300ms` enforced globally via `@layer base`
- Borders: `border-base-content/5` for subtle separation
- Shadows: `shadow-xl shadow-base-content/5` for card depth

### File Organization

```
resources/
├── css/
│   └── app.css              → TailwindCSS, DaisyUI themes, enterprise components
├── fonts/
│   └── instrument-sans-*.woff2  → Self-hosted font files
├── views/
│   ├── components/
│   │   ├── layouts/         → base, app, auth, header, base/head
│   │   └── ui/              → credits (reusable UI fragments)
│   ├── livewire/
│   │   ├── admin/           → Admin domain components
│   │   ├── student/         → Student domain components
│   │   ├── teacher/         → Teacher domain components (academic supervision)
│   │   ├── supervisor/      → Supervisor domain components (industry evaluation)
│   │   ├── dashboard/       → Dashboard widgets
│   │   ├── setup/           → Installation wizard
│   │   ├── layout/          → Global layout components (signature)
│   │   ├── theme-switcher   → Theme toggle
│   │   └── language-switcher→ Language toggle
│   ├── auth/                → Auth pages (login, reset, forgot password)
│   ├── emails/              → Email templates
│   └── errors/              → Error pages (403, etc.)
```
