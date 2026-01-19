# UI Components: System Utilities

Utility components handle technical UI concerns like theme persistence, dialog modals, and the
dynamic injection of content from various modules.

---

## 1. `x-ui::slot-render` (Modular Injection)

This is the core of our "Pluggable UI" system. It renders content that has been registered via the
`SlotManager`.

- **Usage**: `<x-ui::slot-render name="navbar.actions" />`
- **Why?**: It allows the `Notification` module to add a bell icon to the `UI` module's navbar
  without any hard dependencies.

---

## 2. `x-ui::modal` (Focused Interaction)

A high-level wrapper for DaisyUI dialogs.

- **Example**:

```blade
<x-ui::modal wire:model="showModal" title="Confirm Delete" separator>
    <div>Are you sure? This action is permanent.</div>
    <x-slot:actions>
        <x-ui::button label="Cancel" @click="$wire.showModal = false" />
        <x-ui::button label="Confirm" primary spinner="delete" />
    </x-slot>
</x-ui::modal>
```

---

## 3. `x-ui::theme-toggle` (User Preference)

A simple button that toggles between light and dark themes.

- **Persistence**: Preferences are stored in the browser's local storage and synchronized via
  Alpine.js.

---

## 4. `x-ui::user-menu` (Account Access)

A dropdown component typically placed in the navbar.

- **Features**: Links to Profile settings and the Logout trigger.
- **Note**: Handled via the `Auth` and `Profile` modules through the `navbar.actions` slot.

---

_Utilities are the "Glue" of our design system. They enable complex technical patterns while keeping
the template code clean and semantic._
