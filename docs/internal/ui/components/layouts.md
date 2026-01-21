# UI Components: Structural Layouts

Layout components are the scaffolding of the Internara application. They manage the global HTML
structure, meta-tags, responsive containers, and modular UI injection points.

---

## 1. `x-ui::layouts.base` (The Root)

Every page in Internara ultimately resides within this component.

- **Role**: Manages the `<head>` section, global styles, and system-wide toasts.
- **Usage**:

```blade
<x-ui::layouts.base title="Your Page Title">
    {{ $slot }}
</x-ui::layouts.base>
```

### Key Features:

- **Automatic Head Management**: Includes SEO meta-tags and Vite asset loading.
- **Flash Feedback**: Automatically includes the `<x-ui::toast />` component for system alerts.
- **Script Stacking**: Supports `@stack('scripts')` for page-specific JavaScript.

---

## 2. `x-ui::main` (Content Orchestrator)

This component defines the layout of an internal workspace page.

- **Role**: Handles responsive sidebar behavior and main content positioning.
- **Usage**:

```blade
<x-ui::main>
    <x-slot:sidebar>
        <!-- Navigation menu here -->
    </x-slot>

    <x-slot:actions>
        <x-ui::button label="Create Record" icon="tabler.plus" />
    </x-slot>

    {{ $slot }}
</x-ui::main>
```

### Supported Slots:

- `sidebar`: The left-side navigation area (hidden on mobile, accessible via burger menu).
- `actions`: A top-right area for primary page buttons.
- `middle`: A central area in the header for search bars or tabs.

---

## 3. `x-ui::header` (Page Identity)

Displays the title and context of the current view.

- **Usage**: `<x-ui::header title="Student List" subtitle="Manage internship registrations" />`
- **Logic**: Integrates with the `x-ui::main` component to ensure titles are responsive.

---

## 4. `x-ui::toast` (Feedback Layer)

A non-blocking notification system.

- **Usage**: Handled automatically via `layouts.base`.
- **Trigger**: In Livewire, use `$this->success('Message')` or `$this->error('Message')`.

---

## 5. `x-ui::layouts.guest` (Public Context)

Used for non-authenticated pages like Login, Registration, or Certificate Verification.

- **Role**: Provides a clean, focused container without navigation sidebars.

---

_Layouts are designed to be consistent across all modules. If you need to add a global element (like
a help button), modify it in the `UI` module layouts to ensure it propagates everywhere._
