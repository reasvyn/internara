# UI Components: Display

Components used for data presentation and visual decoration.

## `card`

A standard container for grouping related content. Standardized with default borders, rounded
corners, and shadow.

- **Usage:**

```blade
<x-ui::card title="User Stats" subtitle="Updated hourly">
    <p>Stats content...</p>
</x-ui::card>
```

---

## `badge`

Small status or label indicator.

- **Usage:** `<x-ui::badge label="Active" class="badge-primary" />`

---

## `avatar`

Displays a user profile image or placeholder.

- **Usage:** `<x-ui::avatar image="/path/to/img.jpg" class="!w-10" />`

---

## `table`

Data grid component with support for pagination and custom slots.

- **Usage:**

```blade
<x-ui::table :headers="$headers" :rows="$rows" with-pagination>
    @scope('cell_name', $user)
        <strong>{{ $user->name }}</strong>
    @endscope
</x-ui::table>
```

---

## `icon`

A helper component to render SVG icons from the **Iconify** library via Blade Icons.

- **Props:**
    - `name`: The icon name (e.g., `tabler-home`, `tabler-user`).
- **Usage:**

```blade
<x-ui::icon name="tabler-settings" class="w-5 h-5 text-primary" />
```

---

## `alert`

Displays important feedback or messages to the user. Supports DaisyUI status classes.

- **Usage:**

```blade
<x-ui::alert icon="tabler.alert-triangle" class="alert-warning">
    Please check your input.
</x-ui::alert>
```

---

## `sidebar`

A vertical navigation container. Designed to be used within the `sidebar` slot of `x-ui::main`.

- **Usage:**

```blade
<x-ui::sidebar drawer="my-drawer" collapsible>
    <x-ui::menu-item title="Home" icon="tabler.home" link="/" />
</x-ui::sidebar>
```

---

## `app-credit`

Renders a small credit tag, typically used in the footer.

---

**Navigation**

[← Navigation](navigation.md) | [Next: Utilities →](utilities.md)
