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

A wrapper for standard HTML forms, integrated with MaryUI features like `wire:submit` and validation.

- **Usage:**
```blade
<x-ui::form wire:submit="save">
    <x-ui::input label="Name" wire:model="name" />
    <x-slot:actions>
        <x-ui::button label="Save" type="submit" class="btn-primary" />
    </x-slot:actions>
</x-ui::form>
```

---

## Custom Components

### `file-upload`

A feature-rich file uploader with drag-and-drop and real-time previews.

- **File Location:** `modules/UI/resources/views/components/file-upload.blade.php`

### Props

| Prop       | Type     | Default | Description                                         |
| :--------- | :------- | :------ | :-------------------------------------------------- |
| `label`    | `string` | `null`  | Label displayed above the dropzone.                 |
| `name`     | `string` | `file`  | The HTML name of the input.                         |
| `multiple` | `bool`   | `false` | Whether to allow multiple file uploads.             |
| `accept`   | `string` | `*`     | Mime-types or extensions (e.g., `image/*`, `.pdf`). |
| `preview`  | `array`  | `[]`    | Initial URLs to display as existing files.          |
| `hint`     | `string` | `null`  | Helper text shown in the empty state.               |

### Usage

```blade
<x-ui::file-upload
    label="Profile Picture"
    wire:model="avatar"
    accept="image/*"
    hint="Max size 2MB"
/>
```

### Technical Notes

- **Alpine.js:** Manages local state for previews and drag-over effects.
- **DataTransfer API:** Syncs selected files back to the hidden input for Livewire compatibility.
- **Blob URLs:** Uses `URL.createObjectURL` for zero-latency image previews.

---

**Navigation**

[← Layouts](layouts.md) | [Next: Navigation →](navigation.md)
