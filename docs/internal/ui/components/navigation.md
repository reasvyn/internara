# UI Components: Navigation & Branding

These components handle the user's journey through the Internara platform, including modular
injection of menu items and visual branding.

---

## 1. `x-ui::navbar` (Global Access)

The top bar found in all dashboard layouts.

- **Dynamic Content**: The navbar is extensible via the **`SlotManager`**. Modules can inject
  actions or branding without modifying the core UI module.
- **Slots**:
    - `navbar.brand`: Displays the application logo and title.
    - `navbar.actions`: Contains user menus, notifications, and theme togglers.

---

## 2. `x-ui::sidebar` (Workspace Menu)

The vertical menu used for role-specific navigation.

- **Usage**:

```blade
<x-ui::sidebar drawer="main-drawer" collapsible>
    <x-ui::menu-item title="Dashboard" icon="tabler.home" link="/admin" />
    <x-ui::menu-sub title="Management" icon="tabler.users">
        <x-ui::menu-item title="Users" link="/admin/users" />
    </x-ui::menu-sub>
</x-ui::sidebar>
```

---

## 3. `x-ui::brand` (Application Identity)

Renders the "Internara" logo and title linked to the homepage.

- **Source**: Pulls the application name from `setting('brand_name')`.

---

## 4. `x-ui::tabs` (In-Page Navigation)

Used for switching between views within a single component.

- **Example**:

```blade
<x-ui::tabs wire:model="activeTab">
    <x-ui::tab name="info" label="General Info" icon="tabler.info-circle" />
    <x-ui::tab name="history" label="Log History" icon="tabler.history" />
</x-ui::tabs>
```

---

_Navigation components are the only parts of the UI that interact directly with modular boundaries
via slots. Refer to **[Module Provider Concerns](../module-provider-concerns.md)** to learn how to
inject menu items from your module._
