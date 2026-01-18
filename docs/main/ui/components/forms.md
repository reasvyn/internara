# UI Components: Forms

Internara uses a combination of MaryUI wrappers and custom Alpine.js components to handle user
input.

## Standard Wrappers (MaryUI)

The following components are thin wrappers around **MaryUI**. They support all standard attributes
and Livewire `wire:model` binding.

### `button`

Wrapper for `<x-mary-button>`.

- **Usage:** `<x-ui::button primary label="Save" spinner />`

### `input`

Wrapper for `<x-mary-input>`.

- **Usage:** `<x-ui::input label="Username" wire:model="username" />`

### `checkbox`

Wrapper for `<x-mary-checkbox>`.

- **Usage:** `<x-ui::checkbox label="Remember Me" wire:model="remember" />`

### `textarea`

Wrapper for `<x-mary-textarea>`.

- **Usage:** `<x-ui::textarea label="Bio" wire:model="bio" />`

### `select`

Wrapper for `<x-mary-select>`.

- **Usage:** `<x-ui::select label="Option" :options="$options" wire:model="selected" />`

### `radio`

Wrapper for `<x-mary-radio>`.

- **Usage:** `<x-ui::radio label="Choice" :options="$options" wire:model="choice" />`

### `choices`

Advanced multi-select and tagging component.

- **Usage:** `<x-ui::choices label="Tags" wire:model="tags" :options="$options" />`

---

## `form`

A wrapper for standard HTML forms, integrated with MaryUI features like `wire:submit` and
validation.

- **Usage:**

```blade
<x-ui::form wire:submit="save">
    <x-ui::input label="Name" wire:model="name" />
    <x-slot:actions>
        <x-ui::button label="Save" type="submit" class="btn-primary" />
    </x-slot>
</x-ui::form>
```

---

## Custom Components

### File Upload

Standardized file upload component with drag-and-drop, preview, and cropping support.

```blade
<x-ui::file
    label="Logo"
    wire:model="logo"
    accept="image/*"
    crop-after-change
    :preview="$logo_url"
/>
```

For multiple files:

```blade
<x-ui::file
    label="Attachments"
    wire:model="attachments"
    multiple
    accept="image/*,application/pdf"
    :preview="$attachment_urls"
/>
```

### Custom Previews (Slots)

The `file` component supports a default slot to render custom previews or placeholders (e.g., for
profile avatars).

```blade
<x-ui::file wire:model="avatar" accept="image/*" crop-after-change>
    <img src="{{ $avatar_url }}" class="h-40 w-40 rounded-full object-cover" />
</x-ui::file>
```

---

**Navigation**

[← Layouts](layouts.md) | [Next: Navigation →](navigation.md)
