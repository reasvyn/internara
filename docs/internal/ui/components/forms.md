# UI Components: Input & Form Handling

Internara provides a library of standardized form components that wrap **MaryUI** and **DaisyUI**,
ensuring that user inputs are accessible, validated, and consistently styled across modules.

---

## 1. Standard Input Wrappers

These components are optimized for Livewire's `wire:model` and provide automatic error handling.

### 1.1 `x-ui::input`

A standard text input field.

- **Example**: `<x-ui::input label="Full Name" wire:model="name" icon="tabler.user" inline />`
- **Feature**: Displays validation errors automatically below the input.

### 1.2 `x-ui::select`

A dropdown selector.

- **Example**: `<x-ui::select label="Department" :options="$depts" wire:model="dept_id" />`

### 1.3 `x-ui::choices`

An advanced multi-select and tagging component.

- **Usage**: Use this for selecting multiple roles or filter tags.

---

## 2. Interactive Components

### 2.1 `x-ui::button`

Our primary action trigger.

- **Usage**: `<x-ui::button label="Save Changes" spinner="save" primary />`
- **Feature**: The `spinner` attribute automatically shows a loading state during Livewire requests.

### 2.2 `x-ui::file` (File Management)

A robust file upload component with built-in preview and cropping.

- **Usage**:

```blade
<x-ui::file
    label="Profile Picture"
    wire:model="photo"
    accept="image/*"
    crop-after-change
    :preview="$photo_url"
/>
```

- **Customization**: Supports a default slot for custom placeholders (e.g., Avatars).

---

## 3. Structural Form Components

### 3.1 `x-ui::form`

Wraps the HTML `<form>` tag and handles the submission event.

- **Usage**:

```blade
<x-ui::form wire:submit="save">
    <x-ui::input ... />

    <x-slot:actions>
        <x-ui::button label="Cancel" />
        <x-ui::button label="Submit" type="submit" primary />
    </x-slot>
</x-ui::form>
```

---

## 4. Best Practices for Forms

1.  **Always use `wire:model`**: Connect your UI components directly to your Livewire component
    state.
2.  **Explicit Labels**: Never skip the `label` prop. It is crucial for accessibility (screen
    readers).
3.  **Inline Validation**: Use the `inline` attribute for cleaner side-by-side labels in complex
    forms.
4.  **No `env()`**: If your form needs configuration (e.g., max upload size), retrieve it via
    `config()` or `setting()`.

---

_Form components in Internara are designed to be "Thin." If you need to add custom validation logic,
place it in the corresponding **Service** class, not in the UI component._
