# UI Components: Layouts

Layout components provide the structural foundation for all pages in Internara. They ensure
consistent HTML boilerplate, head meta-tags, and global asset loading.

## `layouts.base`

The root layout component. Every page in the application ultimately extends this.

- **File Location:** `modules/UI/resources/views/components/layouts/base.blade.php`
- **Prefix:** `ui::layouts.base`

### Usage

```blade
<x-ui::layouts.base title="Dashboard">
    <div>Your content here</div>
</x-ui::layouts.base>
```

### Props

| Prop    | Type     | Default | Description                                  |
| :------ | :------- | :------ | :------------------------------------------- |
| `title` | `string` | `null`  | The page title displayed in the browser tab. |

### Internal Structure

- Includes `<x-ui::layouts.base.head>` for scripts, styles, and meta-tags.
- Includes `<x-ui::toast />` for global notification support.
- Provides a `@stack('scripts')` for page-specific JS.

---

## `main`

The primary content orchestrator. Handles sidebars, actions, and footer slots.

- **Usage:**
```blade
<x-ui::main>
    <x-slot:actions>
        <x-ui::button label="Action" />
    </x-slot:actions>
    Content...
</x-ui::main>
```

---

## `header`

Page header component with support for title, subtitle, middle, and actions slots.

- **Usage:** `<x-ui::header title="Users" subtitle="Manage your users" />`

---

## `toast`

Global notification component.

- **Usage:** `<x-ui::toast />` (Included automatically in `layouts.base`).

---

## `layouts.base.with-navbar`

A specialized layout that includes the global navigation bar and a responsive main content area.

- **File Location:** `modules/UI/resources/views/components/layouts/base/with-navbar.blade.php`

### Usage

```blade
<x-ui::layouts.base.with-navbar title="Settings">
    <div class="p-6">Settings Content</div>
</x-ui::layouts.base.with-navbar>
```

### Slots

- `default`: The main content of the page, wrapped in a `<main>` tag with appropriate spacing.

---

**Navigation**

[← Back to TOC](../table-of-contents.md) | [Next: Forms →](forms.md)
