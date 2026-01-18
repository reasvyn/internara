# UI Components: Utilities

Utility components provide specialized functionality like theme switching and dynamic slot
rendering.

## `slot-render`

A class-based component used to render dynamic UI content registered by modules.

- **Component Tag:** `<x-ui::slot-render name="slot.name" />`
- **Blade Directive:** `@slotRender('slot.name')`

---

## `theme-toggle`

A button that allows users to toggle between Light and Dark themes.

- **Usage:** `<x-ui::theme-toggle />`
- **Technical Note:** Integrated with DaisyUI themes and persists user preference.

---

## `modal`

A dialog component for focused user interaction.

- **Usage:**

```blade
<x-ui::modal wire:model="myModal" title="Confirm Action">
    <p>Are you sure?</p>
    <x-slot:actions>
        <x-ui::button label="Cancel" wire:click="$set('myModal', false)" />
        <x-ui::button label="Confirm" class="btn-primary" />
    </x-slot>
</x-ui::modal>
```

---

## `user-menu`

A dropdown component for user-specific actions (Profile, Logout).

- **Usage:** `<x-ui::user-menu />`
- **Slot Registration:** Typically registered to the `navbar.actions` slot.

---

**Navigation**

[← Display](display.md) | [Back to TOC](../table-of-contents.md)
