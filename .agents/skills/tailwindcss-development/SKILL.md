---
name: tailwindcss-development
description: SDLC Phase: IMPLEMENTATION (Sub-skill). Specialized UI/styling development — Blade templates, responsive layouts, dark mode, daisyUI, maryUI, Tailwind CSS v4.
upstream:
  - feature-building
  - livewire-development
downstream:
  - sync-docs
---

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

## 1. Form Patterns

### Input Group with Label and Error

```blade
<x-mary-input
    wire:model="form.name"
    label="{{ __('users.name') }}"
    placeholder="{{ __('users.name_placeholder') }}"
    icon="o-user"
/>
```

### Select Menu (daisyUI / maryUI)

```blade
<x-mary-select
    wire:model="form.department_id"
    label="{{ __('users.department') }}"
    :options="$departments"
    option-label="name"
    option-value="id"
    placeholder="{{ __('common.select_placeholder') }}"
    icon="o-building-office"
/>
```

### Checkbox / Toggle

```blade
<x-mary-checkbox
    wire:model="form.is_active"
    label="{{ __('users.is_active') }}"
    class="gap-4"
/>

{{-- daisyUI toggle (raw) --}}
<label class="label cursor-pointer gap-2">
    <span class="label-text">{{ __('users.is_active') }}</span>
    <input type="checkbox" class="toggle toggle-primary" wire:model="form.is_active" />
</label>
```

### Radio Group

```blade
<x-mary-radio
    wire:model="form.gender"
    :options="[
        ['id' => 'male', 'name' => __('users.male')],
        ['id' => 'female', 'name' => __('users.female')],
    ]"
/>
```

### Date Picker (Flatpickr via maryUI)

```blade
<x-mary-datepicker
    wire:model="form.start_date"
    label="{{ __('internship.start_date') }}"
    icon="o-calendar"
    class="rounded-xl border-base-300"
/>
```

### File Upload with Preview

```blade
<x-mary-file
    wire:model="photo"
    label="{{ __('users.photo') }}"
    accept="image/png, image/jpeg"
    max-size="{{ 2 * 1024 }}"
>
    <x-slot:preview>
        @if ($photo)
            <img src="{{ $photo->temporaryUrl() }}" class="w-24 h-24 rounded-box object-cover" />
        @endif
    </x-slot:preview>
</x-mary-file>
```

### @error Handling

```blade
<x-mary-input wire:model="form.email" label="{{ __('users.email') }}" />
@error('form.email')
    <p class="text-error text-sm mt-1">{{ $message }}</p>
@enderror
```

## 2. Responsive Table Patterns

### Horizontal Scroll on Mobile

```blade
<div class="overflow-x-auto">
    <table class="table table-zebra w-full">
        <thead>
            <tr>
                <th>{{ __('users.name') }}</th>
                <th class="hidden md:table-cell">{{ __('users.email') }}</th>
                <th>{{ __('common.actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $user)
                <tr wire:key="{{ $user->id }}">
                    <td>{{ $user->name }}</td>
                    <td class="hidden md:table-cell">{{ $user->email }}</td>
                    <td>@include('users.actions-dropdown')</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
```

### Stack Cards on Small Screens

```blade
{{-- Mobile: cards; md+: table --}}
<div class="block md:hidden space-y-3">
    @foreach($users as $user)
        <div class="card bg-base-100 shadow-sm p-4" wire:key="{{ $user->id }}">
            <p class="font-semibold">{{ $user->name }}</p>
            <p class="text-sm text-base-content/70">{{ $user->email }}</p>
            <div class="mt-2 flex gap-2">@include('users.actions-dropdown')</div>
        </div>
    @endforeach
</div>
<div class="hidden md:block overflow-x-auto">
    <table class="table table-zebra w-full">...</table>
</div>
```

### Sticky Header

```blade
<div class="overflow-y-auto max-h-96">
    <table class="table table-zebra w-full">
        <thead class="sticky top-0 bg-base-100 z-10">
            ...
        </thead>
    </table>
</div>
```

### Sort Indicators

```blade
<th wire:click="sortBy('name')" class="cursor-pointer select-none">
    {{ __('users.name') }}
    @if ($sortBy['column'] === 'name')
        <span class="inline-block ml-1">
            {{ $sortBy['direction'] === 'asc' ? '↑' : '↓' }}
        </span>
    @endif
</th>
```

### Row Actions Dropdown

```blade
<div class="dropdown dropdown-end">
    <label tabindex="0" class="btn btn-ghost btn-sm">
        <x-icon name="o-ellipsis-vertical" class="w-4 h-4" />
    </label>
    <ul tabindex="0" class="dropdown-content menu p-2 shadow bg-base-100 rounded-box w-40 z-20">
        <li><a wire:click="edit('{{ $row->id }}')">{{ __('common.edit') }}</a></li>
        <li><a wire:click="askDelete('{{ $row->id }}')" class="text-error">{{ __('common.delete') }}</a></li>
    </ul>
</div>
```

### Bulk Selection Header (maryUI)

```blade
<x-mary-table
    :headers="$headers"
    :rows="$this->rows()"
    :sort-by="$this->sortBy"
    selectable
    wire:model="selectedIds"
>
    @scope('actions', $row)
        ...
    @endscope
</x-mary-table>
```

## 3. Card & Grid Layout Patterns

### Dashboard Card Grid

```blade
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
    @foreach($stats as $stat)
        <x-mary-card>
            ...
        </x-mary-card>
    @endforeach
</div>
```

### Stat Card with Icon + Number + Trend

```blade
<x-mary-card class="p-4">
    <div class="flex items-center gap-3">
        <div class="p-3 rounded-box bg-primary/10 text-primary">
            <x-icon name="o-users" class="w-6 h-6" />
        </div>
        <div>
            <p class="text-sm text-base-content/60">{{ __('dashboard.active_students') }}</p>
            <p class="text-2xl font-bold">{{ $activeCount }}</p>
            <p class="text-xs {{ $trend > 0 ? 'text-success' : 'text-error' }}">
                {{ $trend > 0 ? '+' : '' }}{{ $trend }}%
            </p>
        </div>
    </div>
</x-mary-card>
```

### Content Card with Header/Footer

```blade
<x-mary-card title="{{ __('internship.details') }}" subtitle="{{ $internship->name }}">
    <x-slot:menu>
        <x-mary-button icon="o-pencil" wire:click="edit" class="btn-ghost btn-sm" />
    </x-slot:menu>

    <p>{{ $internship->description }}</p>

    <x-slot:actions>
        <x-mary-button label="{{ __('common.save') }}" wire:click="save" class="btn-primary" />
    </x-slot:actions>
</x-mary-card>
```

### Card Hover Effects

```blade
<div class="card bg-base-100 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 cursor-pointer">
    ...
</div>
```

## 4. Modal & Dialog Patterns

### DaisyUI Modal with Backdrop Blur

```blade
<dialog id="my-modal" class="modal backdrop-blur-sm">
    <div class="modal-box">
        <h3 class="font-bold text-lg">{{ __('common.confirm') }}</h3>
        <p class="py-4">{{ __('common.confirm_message') }}</p>
        <div class="modal-action">
            <form method="dialog">
                <button class="btn">{{ __('common.cancel') }}</button>
            </form>
            <button wire:click="confirm" class="btn btn-primary">{{ __('common.confirm') }}</button>
        </div>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>
```

### Modal Sizes

```blade
{{-- sm --}}
<div class="modal-box max-w-sm">...</div>

{{-- default (md) --}}
<div class="modal-box">...</div>

{{-- lg --}}
<div class="modal-box max-w-3xl">...</div>

{{-- xl --}}
<div class="modal-box max-w-5xl">...</div>
```

### maryUI Modal

```blade
<x-mary-modal wire:model="showModal" title="{{ __('users.create') }}" class="backdrop-blur-sm">
    <x-mary-input wire:model="form.name" label="{{ __('users.name') }}" />
    <x-mary-input wire:model="form.email" label="{{ __('users.email') }}" />

    <x-slot:actions>
        <x-mary-button label="{{ __('common.cancel') }}" wire:click="$set('showModal', false)" />
        <x-mary-button label="{{ __('common.save') }}" wire:click="save" class="btn-primary" />
    </x-slot:actions>
</x-mary-modal>
```

### Nested Modal Limitation

DaisyUI modals stack correctly but nested modals are discouraged for UX. Use a single modal and swap content based on state (`$step` or `$subAction`).

### Programmatic Open/Close

```php
// Livewire
$this->showModal = true;  // open
$this->showModal = false; // close
```

```blade
{{-- DaisyUI native toggle via <label> --}}
<label for="my-modal" class="btn btn-primary">{{ __('common.open') }}</label>
<input type="checkbox" id="my-modal" class="modal-toggle" />
<label for="my-modal" class="modal cursor-pointer">...</label>
```

## 5. Navigation Patterns

### DaisyUI Menu

```blade
<ul class="menu bg-base-200 rounded-box w-56">
    <li class="menu-title"><span>{{ __('common.navigation') }}</span></li>
    <li><a wire:click="goTo('dashboard')" class="active">{{ __('common.dashboard') }}</a></li>
    <li><a wire:click="goTo('users')">{{ __('common.users') }}</a></li>
    <li><a wire:click="goTo('settings')">{{ __('common.settings') }}</a></li>
</ul>
```

### Tabs

```blade
<div role="tablist" class="tabs tabs-bordered">
    <a role="tab" class="tab tab-active" wire:click="$set('tab', 'details')">
        {{ __('common.details') }}
    </a>
    <a role="tab" class="tab" wire:click="$set('tab', 'history')">
        {{ __('common.history') }}
    </a>
    <a role="tab" class="tab" wire:click="$set('tab', 'logs')">
        {{ __('common.logs') }}
    </a>
</div>
```

### Breadcrumbs

```blade
<div class="breadcrumbs text-sm">
    <ul>
        <li><a wire:navigate href="{{ route('dashboard') }}">{{ __('common.dashboard') }}</a></li>
        <li><a wire:navigate href="{{ route('users.index') }}">{{ __('users.title') }}</a></li>
        <li>{{ $user->name }}</li>
    </ul>
</div>
```

### Dropdown (daisyUI)

```blade
<div class="dropdown dropdown-end">
    <label tabindex="0" class="btn btn-ghost btn-circle">
        <x-icon name="o-bell" class="w-5 h-5" />
    </label>
    <div tabindex="0" class="dropdown-content card card-sm bg-base-100 shadow-lg mt-3 w-80 z-30">
        <div class="card-body p-3 space-y-2">
            @foreach($notifications as $n)
                <p class="text-sm">{{ $n->message }}</p>
            @endforeach
        </div>
    </div>
</div>
```

### Pagination

```blade
{{-- maryUI table handles this automatically --}}
<x-mary-table :rows="$rows" ... />

{{-- DaisyUI manual --}}
<div class="join">
    <button class="join-item btn" wire:click="previousPage">«</button>
    <button class="join-item btn btn-active">{{ $page }}</button>
    <button class="join-item btn" wire:click="nextPage">»</button>
</div>
```

## 6. State & Feedback Patterns

### Loading Spinner

```blade
{{-- Full-page --}}
<div class="flex justify-center items-center py-12">
    <span class="loading loading-spinner loading-lg text-primary"></span>
</div>

{{-- Inline --}}
<button wire:click="save" wire:loading.attr="disabled" class="btn btn-primary">
    <span wire:loading wire:target="save" class="loading loading-spinner"></span>
    {{ __('common.save') }}
</button>

{{-- Loading dots --}}
<span class="loading loading-dots loading-md text-primary"></span>
```

### Empty State

```blade
<div class="flex flex-col items-center justify-center py-16 text-center">
    <x-icon name="o-inbox" class="w-16 h-16 text-base-content/30" />
    <h3 class="mt-4 text-lg font-semibold">{{ __('common.no_data') }}</h3>
    <p class="text-sm text-base-content/60 mt-1">{{ __('common.no_data_description') }}</p>
    <x-mary-button label="{{ __('common.create_first') }}" wire:click="create" class="btn-primary mt-4" />
</div>
```

### Error State

```blade
<div role="alert" class="alert alert-error">
    <x-icon name="o-exclamation-triangle" class="w-5 h-5" />
    <span>{{ $errorMessage }}</span>
    <div>
        <button wire:click="retry" class="btn btn-sm btn-ghost">{{ __('common.retry') }}</button>
    </div>
</div>
```

### Success Toast

```blade
{{-- PHPFlasher (mandated by conventions) --}}
flash()->success(__('users.created_success'));
```

### Skeleton Loading

```blade
<div class="space-y-3 p-4">
    <div class="skeleton h-4 w-3/4"></div>
    <div class="skeleton h-4 w-1/2"></div>
    <div class="skeleton h-32 w-full"></div>
</div>
```

## 7. Dark Mode

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

## 8. Typography & Spacing

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

## 9. Animation & Transition

### DaisyUI Animation Classes

```blade
{{-- Buttons animate by default (--animation-btn: 0.3s) --}}
<button class="btn btn-primary active:scale-95">{{ __('common.save') }}</button>

{{-- Inputs animate on focus --}}
<input class="input input-bordered transition-all duration-200 focus:scale-[1.02]" />
```

### Transition All

```blade
<div class="transition-all duration-300 hover:scale-105 hover:shadow-lg cursor-pointer">
    ...
</div>
```

### Alpine.js x-transition

```blade
<div x-data="{ open: false }">
    <button @click="open = !open" class="btn">{{ __('common.toggle') }}</button>
    <div x-show="open" x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2">
        {{ __('common.content') }}
    </div>
</div>
```

## 10. maryUI Component Patterns

### x-mary-card

```blade
<x-mary-card
    title="{{ __('users.profile') }}"
    subtitle="{{ $user->name }}"
    icon="o-user"
>
    <x-slot:menu>
        <x-mary-button icon="o-ellipsis-vertical" class="btn-ghost btn-sm" />
    </x-slot:menu>

    <div class="space-y-2">
        <p>{{ $user->email }}</p>
        <p>{{ $user->role }}</p>
    </div>

    <x-slot:actions>
        <x-mary-button label="{{ __('common.edit') }}" wire:click="edit" class="btn-primary" />
    </x-slot:actions>
</x-mary-card>
```

### x-mary-table

```blade
<x-mary-table
    :headers="$headers"
    :rows="$this->rows()"
    :sort-by="$this->sortBy"
    selectable
    wire:model="selectedIds"
    link="users/{id}/edit"
>
    @scope('actions', $row)
        <x-mary-button icon="o-pencil" wire:click="edit('{{ $row->id }}')" spinner class="btn-ghost btn-sm" />
        <x-mary-button icon="o-trash" wire:click="askDelete('{{ $row->id }}')" spinner class="btn-ghost btn-sm text-error" />
    @endscope

    @scope('cell_status', $row)
        <x-mary-badge :value="$row->status" class="badge-{{ $row->status === 'active' ? 'success' : 'ghost' }}" />
    @endscope
</x-mary-table>
```

### x-mary-modal

```blade
<x-mary-modal wire:model="showModal" title="{{ __('users.create') }}" class="backdrop-blur-sm">
    <x-mary-input wire:model="form.name" label="{{ __('users.name') }}" />
    <x-mary-input wire:model="form.email" label="{{ __('users.email') }}" />

    <x-slot:actions>
        <x-mary-button label="{{ __('common.cancel') }}" wire:click="$set('showModal', false)" />
        <x-mary-button label="{{ __('common.save') }}" wire:click="save" class="btn-primary" />
    </x-slot:actions>
</x-mary-modal>
```

### x-mary-button Variants

```blade
<x-mary-button label="{{ __('common.save') }}" class="btn-primary" />
<x-mary-button label="{{ __('common.cancel') }}" class="btn-ghost" />
<x-mary-button label="{{ __('common.delete') }}" class="btn-error" />
<x-mary-button label="{{ __('common.edit') }}" icon="o-pencil" class="btn-ghost btn-sm" spinner />
<x-mary-button label="{{ __('common.submit') }}" class="btn-primary btn-outline" />
```

### x-mary-input with Icons and Prefixes

```blade
<x-mary-input
    wire:model="form.price"
    label="{{ __('products.price') }}"
    icon="o-currency-dollar"
    prefix="Rp"
    money
/>
```

### x-mary-choices / x-mary-select

```blade
<x-mary-choices
    wire:model="form.roles"
    label="{{ __('users.roles') }}"
    :options="$roles"
    option-label="name"
    option-value="id"
    icon="o-shield-check"
    multiple
    searchable
/>
```

### x-mary-stat

```blade
<x-mary-stat
    title="{{ __('dashboard.active_users') }}"
    value="{{ $activeCount }}"
    icon="o-users"
    color="text-primary"
/>
```

## 11. Layout Structure

### Standard Page Layout

```blade
{{-- Header --}}
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-2xl font-bold">{{ __('users.title') }}</h1>
        <p class="text-sm text-base-content/60">{{ __('users.description') }}</p>
    </div>
    <x-mary-button label="{{ __('users.create') }}" wire:click="create" class="btn-primary" icon="o-plus" />
</div>

{{-- Search/Filters --}}
<div class="flex flex-col sm:flex-row gap-3 mb-4">
    <x-mary-input wire:model.live.debounce="search" placeholder="{{ __('common.search') }}" class="input-sm" />
    <x-mary-select wire:model="filters.status" :options="$statusOptions" placeholder="{{ __('common.all_status') }}" class="select-sm" />
</div>

{{-- Content --}}
<x-mary-card>
    <x-mary-table :headers="$headers" :rows="$this->rows()" :sort-by="$this->sortBy" selectable wire:model="selectedIds">
        @scope('actions', $row) ... @endscope
    </x-mary-table>
</x-mary-card>
```

### Responsive Sidebar (Off-canvas on Mobile)

```blade
{{-- Mobile: off-canvas drawer; lg+: sidebar --}}
<div class="drawer lg:drawer-open">
    <input id="sidebar-drawer" type="checkbox" class="drawer-toggle" />
    <div class="drawer-content p-4">
        <label for="sidebar-drawer" class="btn btn-ghost lg:hidden">
            <x-icon name="o-bars-3" class="w-5 h-5" />
        </label>
        @yield('content')
    </div>
    <div class="drawer-side z-40">
        <label for="sidebar-drawer" class="drawer-overlay"></label>
        <aside class="menu p-4 w-64 min-h-full bg-base-200 text-base-content">
            @include('layouts.sidebar-items')
        </aside>
    </div>
</div>
```

### Form Layout with Sections

```blade
<div class="space-y-6 max-w-3xl">
    <x-mary-card title="{{ __('users.personal_info') }}">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-mary-input wire:model="form.first_name" label="{{ __('users.first_name') }}" />
            <x-mary-input wire:model="form.last_name" label="{{ __('users.last_name') }}" />
        </div>
        <x-mary-input wire:model="form.email" label="{{ __('users.email') }}" class="mt-4" />
    </x-mary-card>

    <x-mary-card title="{{ __('users.account_settings') }}">
        <x-mary-select wire:model="form.role" label="{{ __('users.role') }}" :options="$roles" />
        <x-mary-checkbox wire:model="form.is_active" label="{{ __('users.is_active') }}" class="mt-2" />
    </x-mary-card>

    <div class="flex justify-end gap-3">
        <x-mary-button label="{{ __('common.cancel') }}" wire:click="cancel" class="btn-ghost" />
        <x-mary-button label="{{ __('common.save') }}" wire:click="save" class="btn-primary" />
    </div>
</div>
```

## 12. Common Mistakes

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
- Skeleton or spinner shown during loading (`wire:loading`)？
- N+1 queries avoided in Blade loops (eager loading verified)?
- Color uses theme variables (`bg-base-100`, `text-base-content`) not hardcoded values?
- Modal uses `backdrop-blur-sm` for overlay consistency?
- Sidebar uses drawer pattern for mobile responsiveness?
