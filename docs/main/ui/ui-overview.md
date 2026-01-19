# UI Module: The Internara Design System

The `UI` module is the central repository for all shared frontend assets, global Blade components,
and Livewire interface elements. It acts as the project's internal design system, ensuring that
whether you are in the `Admin` or `Student` module, the experience remains cohesive and
high-quality.

---

## 1. Our UI Architecture

We follow a "Layered Design" approach, where each tool has a specific role:

- **Tailwind CSS 4**: The utility-first engine for layout, spacing, and colors.
- **DaisyUI 5**: Provides the semantic "base" components (e.g., `.btn`, `.modal`, `.card`).
- **MaryUI 2**: Supplies interactive Livewire components like data tables and form selectors.
- **UI Module**: Provides the "Internara-flavored" wrappers that unify these tools.

---

## 2. The Component Hierarchy

When building an interface, you should choose components in this order of priority:

1.  **Internara Components (`x-ui::`)**: These are our project-specific components (e.g.,
    `x-ui::button`). They wrap MaryUI/DaisyUI with our specific styles and defaults.
2.  **MaryUI Components (`x-mary-`)**: Use these for complex Livewire interactions that haven't been
    wrapped yet.
3.  **DaisyUI Classes**: Use raw DaisyUI classes for simple, static HTML elements.

---

## 3. Core Directory Layout

For developers, understanding where assets reside is crucial:

- `resources/css/app.css`: The main entry point for Tailwind. Custom theme variables are here.
- `resources/views/layouts/`:
    - `dashboard.blade.php`: The primary layout for all authenticated workspaces.
    - `guest.blade.php`: Used for login, registration, and public verification pages.
- `resources/views/components/`: Individual components categorized by purpose (Forms, Display,
  etc.).

---

## 4. Branding & Visual Standards

### 4.1 Icons

We have standardized on **Tabler Icons**. Always use the `tabler.name` format. Example:
`<x-ui::icon name="tabler.user" />`.

### 4.2 Colors

Use theme-aware classes instead of hardcoded hex values:

- `primary`: For main actions.
- `secondary`: For supporting elements.
- `error`: For destructive actions (Delete buttons).
- `base-100` to `base-300`: For background layering.

---

_The UI module is designed to be "Developer-First." By leveraging these components, you can build
beautiful, responsive modules in minutes without writing a single line of custom CSS._
