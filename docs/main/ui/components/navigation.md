# UI Components: Navigation

These components handle application navigation, branding, and modular UI injection.

## `navbar`

The primary navigation bar. It is designed to be extensible via the `SlotManager`.

- **Prefix:** `ui::navbar`

### Usage

```blade
<x-ui::navbar />
```

### Extension Points (Slots)

The navbar uses `@slotRender` to allow other modules to inject content:

1. `navbar.brand`: Where the logo or app name is rendered.
2. `navbar.actions`: Where user menus, theme togglers, or notifications are rendered.

---

## `nav`

A utility navigation component often used for smaller, secondary navigation sets or within layouts.

- **Usage:**

```blade
<x-ui::nav>
    <x-slot:actions>
        <x-ui::button label="Login" />
    </x-slot>
</x-ui::nav>
```

---

## `footer`

The global application footer.

- **Prefix:** `ui::footer`

### Usage

```blade
<x-ui::footer />
```

### Extension Points (Slots)

1. `footer.app-credit`: Allows modules to add additional credits or links to the footer.

---

## `brand`

Renders the application name from the system settings.

- **Usage:** `<x-ui::brand />`
- **Output:** A link to `/` containing the `brand_name` setting.

---

## `dropdown`

A dropdown menu component.

- **Usage:**

```blade
<x-ui::dropdown label="Actions">
    <x-ui::menu-item title="Edit" icon="tabler.edit" />
    <x-ui::menu-item title="Delete" icon="tabler.trash" />
</x-ui::dropdown>
```

---

## `tabs` & `tab`

Navigation tabs for switching between views.

- **Usage:**

```blade
<x-ui::tabs wire:model="selectedTab">
    <x-ui::tab name="tab1" label="Overview" icon="tabler.home">Content 1</x-ui::tab>
    <x-ui::tab name="tab2" label="Settings" icon="tabler.settings">Content 2</x-ui::tab>
</x-ui::tabs>
```

---

## `sidebar`

_(Currently placeholder)_ - Intended for vertical navigation layouts.

---

**Navigation**

[← Forms](forms.md) | [Next: Display →](display.md)
