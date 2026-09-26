# UI Integration — How Everything Works Together

## Description

This guide explains how all UI technologies in the TALL stack (Tailwind, Alpine, Laravel, Livewire)
work together with TallStackUI to create Internara's comprehensive UI system. It covers patterns,
conventions, and best practices for building cohesive interfaces.

---


## Prerequisites

See [Installation](../installation.md#prerequisites) for full server requirements and verification commands.

## Steps

This document is reference-oriented. For installation and setup procedures, see:

1. [Installation](../installation.md) — server preparation and CLI provisioning
2. [Setup Wizard](../setup-wizard.md) — browser-based initial configuration
3. [Post-Setup](../post-setup.md) — initial data population after wizard completion

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Component Layering](#component-layering)
3. [Data Flow Patterns](#data-flow-patterns)
4. [Common UI Patterns](#common-ui-patterns)
5. [Form Patterns](#form-patterns)
6. [Table Patterns](#table-patterns)
7. [Modal Patterns](#modal-patterns)
8. [Notification Patterns](#notification-patterns)
9. [Theme & Dark Mode](#theme--dark-mode)
10. [Performance Considerations](#performance-considerations)

---

## Architecture Overview

### Technology Stack

```
┌─────────────────────────────────────────────────────────────┐
│                        Browser                               │
│  ┌─────────────────────────────────────────────────────────┐ │
│  │                    Blade Templates                       │ │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐     │ │
│  │  │  TallStackUI │  │  Alpine.js  │  │   Tailwind  │     │ │
│  │  │ Components   │  │  Dropdowns  │  │   Utilities │     │ │
│  │  │ (x-modal,    │  │  Toggles    │  │   (flex,    │     │ │
│  │  │  x-input)    │  │  Tooltips   │  │    grid)    │     │ │
│  │  └─────────────┘  └─────────────┘  └─────────────┘     │ │
│  └─────────────────────────────────────────────────────────┘ │
│                            │ AJAX                             │
└────────────────────────────┼─────────────────────────────────┘
                             │
┌────────────────────────────┼─────────────────────────────────┐
│                     Laravel Backend                          │
│  ┌─────────────────────────────────────────────────────────┐ │
│  │              Livewire Components (PHP)                   │ │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐     │ │
│  │  │  Properties  │  │   Actions   │  │   Events    │     │ │
│  │  │  (state)     │  │  (methods)  │  │  (dispatch) │     │ │
│  │  └─────────────┘  └─────────────┘  └─────────────┘     │ │
│  └─────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘

```

### File Locations

```
resources/
├── css/
│   └── app.css                    # Tailwind imports + @theme configuration
├── js/
│   └── app.js                     # Alpine.js + Livewire initialization
└── views/
    ├── components/                # Blade components (TallStackUI + custom)
    │   └── ui/                    # Custom UI components
    ├── layouts/                   # App layouts
    │   └── app.blade.php          # Main layout
    └── {module}/                  # Module-specific views

app/Modules/{Module}/Livewire/     # Livewire components per module

```

---

## Component Layering

### Layer 1: Tailwind Utilities

Foundation layer — utility classes for layout, spacing, typography, colors:

```blade
<div class="flex items-center justify-between p-4 bg-base-100 rounded-xl border border-base-content/10 shadow-sm">
    <h2 class="text-lg font-semibold text-base-content">Page Title</h2>
    <x-ts-button sm wire:click="create">New Record</x-ts-button>
</div>

```

### Layer 2: TallStackUI Components

Pre-built components for common patterns:

```blade
<x-ts-card header="User Details" footer>
    <x-ts-input label="Name" wire:model="name" />
    <x-ts-input label="Email" wire:model="email" email />

    <x-slot:footer between>
        <x-ts-button color="red" outline>Cancel</x-ts-button>
        <x-ts-button color="green" wire:click="save">Save</x-ts-button>
    </x-slot:footer>
</x-ts-card>

```

### Layer 3: Alpine.js Interactivity

Lightweight interactivity for dropdowns, toggles, tooltips:

```blade
<div x-data="{ open: false }" class="relative">
    <button @click="open = !open" class="…">
        Toggle Dropdown
    </button>

    <div x-show="open"
         x-transition
         @click.outside="open = false"
         class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg">
        <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Item</a>
    </div>
</div>

```

### Layer 4: Livewire Reactivity

Server-side state management with AJAX-powered updates:

```php
class UserList extends Component
{
    public string $search = '';

    #[Computed]
    public function users()
    {
        return User::where('name', 'like', "%{$this->search}%")->paginate(15);
    }
}

```

```blade
<div>
    <x-ts-input label="Search" wire:model.live.debounce.300ms="search" />

    <x-ts-table :headers="['Name', 'Email', 'Actions']" :rows="$this->users" />
</div>

```

---

## Data Flow Patterns

### Two-Way Data Binding (Livewire + TallStackUI)

```blade
<x-ts-input label="Title" wire:model="title" />
<x-ts-textarea label="Description" wire:model="description" />
<x-ts-select.styled label="Status" wire:model="status" :options="$statuses" />
<x-ts-checkbox label="Active" wire:model="active" />
<x-ts-toggle label="Notifications" wire:model="notifications" />

```

### One-Way Data Display

```blade
<x-ts-badge :text="$user->status->label()" :color="$user->status->color()" />
<x-ts-avatar :model="$user" />

```

### Action Dispatch

```blade
<x-ts-button wire:click="save">Save</x-ts-button>
<x-ts-button wire:click="edit({{ $id }})">Edit</x-ts-button>
<x-ts-button color="red"
    x-on:click="$tsui.interaction('dialog')?.confirm('Delete?', 'Are you sure?', {
        accept: { label: 'Delete', method: 'delete', params: {{ $id }} },
        reject: { label: 'Cancel' },
    })">
    Delete
</x-ts-button>

```

### Loading States

```blade
<x-ts-button wire:click="save">
    <span wire:loading.remove wire:target="save">Save</span>
    <span wire:loading wire:target="save">
        <x-ts-loading xs /> Saving…
    </span>
</x-ts-button>

<x-ts-table :headers="$headers" :rows="$rows" loading wire:loading.delay.shortest />

```

### Error Display

```blade
<x-ts-error :errors="$errors" />

<x-ts-input label="Name" wire:model="name" :errors="$errors" />

```

---

## Common UI Patterns

### Card with Header and Footer

```blade
<x-ts-card header="User Information">
    <div class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
            <x-ts-input label="First Name" wire:model="firstName" />
            <x-ts-input label="Last Name" wire:model="lastName" />
        </div>
        <x-ts-input label="Email" wire:model="email" email />
    </div>

    <x-slot:footer between>
        <x-ts-button color="red" outline wire:click="cancel">Cancel</x-ts-button>
        <x-ts-button color="green" wire:click="save">Save Changes</x-ts-button>
    </x-slot:footer>
</x-ts-card>

```

### Data Table with Actions

```blade
<x-ts-card :paddingless="true">
    <x-ts-table :headers="[
        ['index' => 'name', 'label' => 'Name'],
        ['index' => 'email', 'label' => 'Email'],
        ['index' => 'role', 'label' => 'Role'],
        ['index' => 'actions', 'label' => 'Actions', 'align' => 'right'],
    ]" :rows="$this->users" searchable striped>
        <x-slot:actions>
            <div class="flex items-center justify-end gap-2">
                <x-ts-button sm outline wire:click="edit({{ $user->id }})">Edit</x-ts-button>
                <x-ts-button sm color="red" outline
                    x-on:click="$tsui.interaction('dialog')?.confirm('Delete?', 'Are you sure?', {
                        accept: { label: 'Delete', method: 'delete', params: {{ $user->id }} },
                        reject: { label: 'Cancel' },
                    })">
                    Delete
                </x-ts-button>
            </div>
        </x-slot:actions>
    </x-ts-table>
</x-ts-card>

```

### Search and Filter Bar

```blade
<div class="flex items-center gap-4 mb-6">
    <div class="flex-1">
        <x-ts-input placeholder="Search records…"
            wire:model.live.debounce.300ms="search"
            icon="magnifying-glass" />
    </div>

    <x-ts-select.styled wire:model="status" :options="$statuses" placeholder="All Statuses" />

    <x-ts-button wire:click="resetFilters" outline>
        <x-ts-icon name="arrow-path" class="w-4 h-4" />
        Reset
    </x-ts-button>
</div>

```

---

## Form Patterns

### Simple Form

```blade
<form wire:submit="save" class="space-y-6">
    <x-ts-card header="Create User">
        <div class="grid grid-cols-2 gap-4">
            <x-ts-input label="Name" wire:model="name" required />
            <x-ts-input label="Email" wire:model="email" email required />
            <x-ts-input label="Password" wire:model="password" password required />
            <x-ts-select.styled label="Role" wire:model="role" :options="$roles" required />
        </div>

        <div class="mt-4">
            <x-ts-checkbox label="Send welcome email" wire:model="sendEmail" />
        </div>
    </x-ts-card>

    <div class="flex justify-end gap-3">
        <x-ts-button color="gray" outline wire:click="cancel">Cancel</x-ts-button>
        <x-ts-button color="green" type="submit">
            <span wire:loading.remove wire:target="save">Create User</span>
            <span wire:loading wire:target="save">Creating…</span>
        </x-ts-button>
    </div>
</form>

```

### Multi-Step Wizard

```blade
<div>
    {{-- Step Indicator --}}
    <div class="mb-8">
        <div class="flex items-center justify-between">
            @foreach ($steps as $index => $step)
                <div class="flex items-center">
                    <div class="flex items-center justify-center w-8 h-8 rounded-full
                        {{ $currentStep > $index ? 'bg-green-500 text-white' : ($currentStep === $index ? 'bg-blue-500 text-white' : 'bg-gray-200') }}">
                        {{ $index + 1 }}
                    </div>
                    @if (!$loop->last)
                        <div class="w-16 h-1 mx-2 {{ $currentStep > $index ? 'bg-green-500' : 'bg-gray-200' }}"></div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- Step Content --}}
    <x-ts-card>
        @switch($currentStep)
            @case(0)
                <x-ts-input label="First Name" wire:model="firstName" />
                <x-ts-input label="Last Name" wire:model="lastName" />
                @break
            @case(1)
                <x-ts-input label="Email" wire:model="email" email />
                <x-ts-input label="Phone" wire:model="phone" />
                @break
            @case(2)
                <x-ts-textarea label="Bio" wire:model="bio" />
                <x-ts-checkbox label="Agree to terms" wire:model="agreed" />
                @break
        @endswitch
    </x-ts-card>

    {{-- Navigation --}}
    <div class="flex justify-between mt-6">
        <x-ts-button wire:click="prevStep" outline :disabled="$currentStep === 0">
            Previous
        </x-ts-button>

        @if ($currentStep < count($steps) - 1)
            <x-ts-button wire:click="nextStep">Next</x-ts-button>
        @else
            <x-ts-button color="green" wire:click="save">Complete</x-ts-button>
        @endif
    </div>
</div>

```

### File Upload with Preview

```blade
<div>
    <x-ts-upload label="Profile Photo"
        wire:model="photo"
        accept="image/*"
        hint="Max 2MB, JPG or PNG" />

    @if ($photo)
        <div class="mt-4">
            <img src="{{ $photo->temporaryUrl() }}" class="w-32 h-32 rounded-full object-cover" />
        </div>
    @elseif ($user->avatar)
        <div class="mt-4">
            <img src="{{ $user->avatar_url }}" class="w-32 h-32 rounded-full object-cover" />
        </div>
    @endif
</div>

```

---

## Table Patterns

### Basic Table

```blade
<x-ts-table :headers="[
    ['index' => 'id', 'label' => '#'],
    ['index' => 'name', 'label' => 'Name'],
    ['index' => 'email', 'label' => 'Email'],
    ['index' => 'created_at', 'label' => 'Created'],
]" :rows="$this->users" striped />

```

### Searchable Table with Pagination

```blade
<x-ts-table :headers="$headers" :rows="$this->users"
    searchable
    :search="$search"
    @search="$set('search', $event.detail)" />

<x-ts-pagination :paginator="$this->users" />

```

### Table with Loading State

```blade
<x-ts-table :headers="$headers" :rows="$this->users"
    loading wire:loading
    wire:target="search,filter,sort" />

```

### Table with Actions

```blade
<x-ts-table :headers="$headers" :rows="$this->users">
    <x-slot:actions>
        <div class="flex items-center gap-2">
            <x-ts-button sm outline wire:click="view({{ $row->id }})">View</x-ts-button>
            <x-ts-button sm outline wire:click="edit({{ $row->id }})">Edit</x-ts-button>
            <x-ts-button sm color="red" outline wire:click="delete({{ $row->id }})">Delete</x-ts-button>
        </div>
    </x-slot:actions>
</x-ts-table>

```

---

## Modal Patterns

### Simple Modal

```blade
<x-ts-modal id="create-modal" title="Create New Record">
    <x-ts-input label="Name" wire:model="name" />
    <x-ts-input label="Description" wire:model="description" />

    <x-slot:footer>
        <x-ts-button color="green" wire:click="save">Save</x-ts-button>
    </x-slot:footer>
</x-ts-modal>

<x-ts-button x-on:click="$tsui.open.modal('create-modal')">Create</x-ts-button>

```

### Confirmation Modal

```blade
<x-ts-dialog>
    <x-ts-dialog.button color="red">
        Delete Record
    </x-ts-dialog.button>

    <x-ts-dialog.content title="Delete Confirmation">
        <p>Are you sure you want to delete this record? This action cannot be undp>

        <x-slot:footer>
            <x-ts-button outline Cancel</x-ts-button>
            <x-ts-button color="red" wire:click="delete({{ $id }})">Delete</x-ts-button>
        </x-slot:footer>
    </x-ts-dialog.content>
</x-ts-dialog>

```

### Slide Panel

```blade
<x-ts-slide id="edit-slide" title="Edit Record">
    <x-ts-input label="Name" wire:model="name" />
    <x-ts-input label="Description" wire:model="description" />
</x-ts-slide>

<x-ts-button wire:click="loadEdit({{ $id }})" x-on:click="$tsui.open.slide('edit-slide')">
    Edit
</x-ts-button>

```

---

## Notification Patterns

### Toast Notifications

```blade
{{-- In layout --}}
<div x-data="{ showToast(message, type = 'success') {
    $tsui.interaction('toast')[type](message)
}}"
x-on:notify.window="showToast($event.detail.message, $event.detail.type)"
<x-ts-toast position="top-right" />

```

```php
// In Livewire component
public function save()
{
    // …
    $this->dispatch('notify', type: 'success', message: 'Record created successfully');
}

public function delete()
{
    // …
    $this->dispatch('notify', type: 'success', message: 'Record deleted');
}

```

### Inline Error Messages

```blade
<x-ts-error :errors="$errors" />

@error('name')
    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
@enderror

```

### Alert Component

```blade
@if (session('success'))
    <x-ts-alert color="green" close>{{ session('success') }}</x-ts-alert>
@endif

@if (session('error'))
    <x-ts-alert color="red" close>{{ session('error') }}</x-ts-alert>
@endif

```

---

## Theme & Dark Mode

### CSS Configuration

```css
/* resources/css/app.css */
@import 'tailwindcss';
@import '../../vendor/tallstackui/tallstackui/css/v4.css';

@source '../views';
@source '../../vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php';
@source '../../vendor/tallstackui/tallstackui/**/*.php';

/* Dark mode via class */
@custom-variant dark (&:where(.dark, .dark *));

/* Self-hosted Instrument Sans & Semantic Palette */
@theme {
  --font-sans: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
}

```

### Theme Toggle Component

```blade
<div x-data="{
    dark: localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches),
    toggle() {
        this.dark = !this.dark;
        document.documentElement.classList.toggle('dark', this.dark);
        localStorage.theme = this.dark ? 'dark' : 'light';
    }
}" x-init="$watch('dark', val => document.documentElement.classList.toggle('dark', val))">
    <x-ts-toggle x-model="dark" @change="toggle()" label="Dark Mode" />
</div>

```

### Semantic Color Usage

```blade
<!-- Recommended: Semantic palette tokens (auto dark-mode aware) -->
<div class="bg-base-100 text-base-content border border-base-content/10 rounded-xl p-4">
    Themed content with automatic dark mode contrast
</div>

<!-- Surface hierarchy (base-200 container -> base-100 card) -->
<div class="bg-base-200 p-6 rounded-2xl">
    <div class="bg-base-100 p-4 rounded-xl border border-base-content/10 shadow-sm">
        Elevated card surface
    </div>
</div>

```

---

## Performance Considerations

### Lazy Loading Components

```blade
{{-- Only load when visible --}}
<div x-intersect="$wire.loadData()">
    <x-ts-card skeleton header footer />
</div>

```

### Deferring Component Load

```blade
{{-- Load after page is interactive --}}
<div wire:init="loadChart">
    <x-ts-card loading wire:loading>
        <div class="h-64"></div>
    </x-ts-card>
</div>

```

### Debouncing Search Inputs

```blade
{{-- Debounce to reduce server requests --}}
<x-ts-input wire:model.live.debounce.300ms="search" placeholder="Search…" />

```

### Pagination

```blade
{{-- Always paginate large datasets --}}
<x-ts-table :headers="$headers" :rows="$this->records" />
<x-ts-pagination :paginator="$this->records" />

```

### Minimizing Re-renders

```blade
{{-- Use wire:target to scope loading states --}}
<x-ts-button wire:click="save">
    <span wire:loading.remove wire:target="save">Save</span>
    <span wire:loading wire:target="save">Saving…</span>
</x-ts-button>

```

---

## Testing UI Components

### Testing Livewire + TallStackUI

```php
it('renders form with TallStackUI components', function () {
    Livewire::test(UserCreate::class)
        ->assertSeeHtml('Create User')
        ->assertSeeHtml('wire:model="name"')
        ->set('name', 'John Doe')
        ->set('email', 'john@example.com')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('users.index'));
});

```

### Testing Validation

```php
it('shows validation errors', function () {
    Livewire::test(UserCreate::class)
        ->set('name', '')
        ->set('email', 'invalid')
        ->call('save')
        ->assertHasErrors(['name' => 'required', 'email' => 'email']);
});

```

### Testing Interactions

```php
it('dispatches notification on save', function () {
    Livewire::test(UserCreate::class)
        ->set('name', 'John Doe')
        ->set('email', 'john@example.com')
        ->call('save')
        ->assertDispatched('notify');
});

```

---

## Common Pitfalls & Solutions

### 1. Flash of Unstyled Content (FOUC)

**Problem:** Content appears unstyled before Alpine initializes.

**Solution:**

```html
<style>
    [x-cloak] { display: none !important; }
</style>

<div x-data x-cloak>
    <!-- Content hidden until Alpine initializes -->
</div>

```

### 2. TallStackUI Script Not Loaded

**Problem:** Components don't work, JavaScript errors in console.

**Solution:**

```blade
<head>
    <!-- Correct order -->
    <tallstackui:script />
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

```

### 3. Tailwind Classes Not Generated

**Problem:** Custom classes don't appear in output.

**Solution:**

```css
/* Add source paths for all files using Tailwind classes */
@source '../views';
@source '../../vendor/tallstackui/tallstackui/**/*.php';

```

### 4. Dark Mode Not Toggling

**Problem:** Dark mode class not applied to `<html>`.

**Solution:**

```js
// Ensure class is on <html>, not <body>
document.documentElement.classList.toggle('dark', isDark);

```

### 5. Livewire Events Not Firing

**Problem:** `wire:click` doesn't trigger action.

**Solution:**
- Ensure component extends `Livewire\Component`
- Check for JavaScript errors in console
- Verify method is public

---

## Related Documentation

- [UI/UX Index](./index.md) — Complete UI system overview
- [Livewire Guide](./livewire.md) — Reactive components
- [Tailwind CSS Guide](./tailwindcss.md) — Styling with Tailwind
- [TallStackUI Guide](./tallstackui.md) — Pre-built components
- [Conventions](../../conventions.md) — Code rules and best practices
- [UI Pattern](../arch/ui-pattern.md) — Project-specific UI patterns
- [Testing Pattern](../arch/testing-pattern.md) — Testing UI components

---

## External Resources

| Resource | URL |
|----------|-----|
| TALL Stack | https://tallstack.dev/ |
| Livewire + Tailwind | https://livewire.laravel.com/docs/styling |
| TallStackUI + Livewire | https://tallstackui.com/docs/livewire |
| Alpine.js + Livewire | https://livewire.laravel.com/docs/javascript |
---

## Troubleshooting

| Symptom | Cause | Fix |
|---------|-------|-----|
