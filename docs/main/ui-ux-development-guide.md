# UI/UX Development Guide

This guide provides the core UI/UX principles, standards, and detailed technical specifications for
implementing UI components and systems in the Internara application. It is intended for developers
who are actively building or modifying the user interface, ensuring a consistent, modern, and
high-quality user experience.

---

## 1. Core Principles & Philosophy

The development of Internara's UI/UX is rooted in a strong philosophy to create an experience that
is not only functional but also embodies the following core characteristics. Every design and
implementation choice must align with these principles.

- **Minimalist, Elegant, and Clean:** We prioritize a modern aesthetic that is clean, uncluttered,
  and visually calming. The design should feel intentional, with a focus on essential functionality
  and generous use of whitespace to reduce cognitive load.

- **User-Friendly and Professional:** The interface must be intuitive, predictable, and easy to
  navigate for all user roles. Professionalism is maintained through consistency in design,
  typography, and interaction patterns as defined in this guide.

- **Empathetic and Story-Driven:** We build with empathy for our users' challenges. This is
  reflected in a friendly tone and a narrative-driven approach to copywriting. The UI should tell a
  story that resonates with the user's journey, transforming complex tasks into a guided, meaningful
  experience.

- **Consistent and Cohesive:** Adherence to the guidelines, components, and patterns defined here is
  mandatory. This ensures a cohesive look, feel, and behavior across the entire application, making
  it a reliable and trustworthy tool.

---

## 2. The `UI` Module: The Center of Frontend

The `UI` module (`modules/UI`) is the definitive source of truth for all frontend assets and
standards within the Internara application. It encapsulates all core styling, JavaScript, and view
components. All UI-related development and configuration, including the principles in this guide,
are implemented within this module.

- **Asset Entry Points:** The root-level files at `resources/js/app.js` and `resources/css/app.css`
  simply import the actual assets from `modules/UI/resources/`.
- **Configuration:** All frontend tooling, including Tailwind CSS, DaisyUI, and Vite, is configured
  to process and bundle assets originating from this module.
- **Component Storage:** Core Blade and Livewire components that form the foundation of the user
  interface are stored within `modules/UI/resources/views/`.

All UI development should be done with the `UI` module as the central context.

---

## 3. UI Framework: DaisyUI + Tailwind CSS

- **Primary Library:** Internara uses **[DaisyUI](https://daisyui.com/)**, a component library for
  **[Tailwind CSS](https://tailwindcss.com/)**.
- **Secondary Library (MaryUI):** We also leverage **[MaryUI](https://mary-ui.com/)** for
  specialized Laravel/Livewire components that build upon DaisyUI.
- **Mandatory Use:** All new UI development **must** use DaisyUI components and utility classes. Do
  not introduce custom one-off styles or new component libraries.
- **Configuration:** The DaisyUI and MaryUI libraries are configured within the `UI` module's
  primary stylesheet at `modules/UI/resources/css/app.css`.

---

## 4. Theming & Color Palette

We use DaisyUI's theme system to manage colors for both light and dark modes.

### 4.1. Semantic Colors

Prioritize the use of DaisyUI's semantic color classes (e.g., `primary`, `secondary`, `success`,
`error`) over hard-coded Tailwind color utilities. Using semantic classes is **highly recommended**
as it helps elements adapt correctly to the theming system, ensuring overall consistency.

However, given that Internara currently utilizes a limited set of themes and color schemes, the use
of semantic classes is **not strictly mandatory**. If a specific UI need is better served by
standard Tailwind color utilities (e.g., `text-gray-500` for minor details that do not require theme
adaptation), it is permissible. Nonetheless, exercise careful consideration before deviating from
semantic classes.

| Class Name  | Role                   | Example Usage                                 |
| :---------- | :--------------------- | :-------------------------------------------- |
| `primary`   | Primary actions, links | `<button class="btn btn-primary">`            |
| `secondary` | Secondary actions      | `<button class="btn btn-secondary">`          |
| `accent`    | Emphasis, highlights   | `<progress class="progress progress-accent">` |
| `success`   | Success states, alerts | `<div class="alert alert-success">`           |
| `warning`   | Warning states         | `<div class="alert alert-warning">`           |
| `error`     | Error states, danger   | `<div class="alert alert-error">`             |
| `info`      | Informational messages | `<div class="alert alert-info">`              |

### 4.2. Theme Configuration Summary

All theme configurations are managed directly within the `UI` module's primary CSS file
(`modules/UI/resources/css/app.css`). The application defines a default "light" theme and a "dark"
theme. The following table summarizes and compares their key-value pairs as configured via the
`@plugin "daisyui/theme"` directive.

| Variable Name               | Light Theme Value            | Dark Theme Value               |
| :-------------------------- | :--------------------------- | :----------------------------- |
| `name`                      | `"light"`                    | `"dark"`                       |
| `default`                   | `true`                       | `false`                        |
| `prefersdark`               | `false`                      | `true`                         |
| `color-scheme`              | `"light"`                    | `"dark"`                       |
| `--color-base-100`          | `oklch(100% 0 0)`            | `oklch(0% 0 0)`                |
| `--color-base-200`          | `oklch(97% 0 0)`             | `oklch(10% 0 0)`               |
| `--color-base-300`          | `oklch(94% 0 0)`             | `oklch(15% 0 0)`               |
| `--color-base-content`      | `oklch(10% 0 0)`             | `oklch(95% 0 0)`               |
| `--color-primary`           | `oklch(0% 0 0)`              | `oklch(100% 0 0)`              |
| `--color-primary-content`   | `oklch(100% 0 0)`            | `oklch(0% 0 0)`                |
| `--color-secondary`         | `oklch(20% 0 0)`             | `oklch(85% 0 0)`               |
| `--color-secondary-content` | `oklch(100% 0 0)`            | `oklch(0% 0 0)`                |
| `--color-accent`            | `oklch(69% 0.17 162.48)`     | `oklch(69% 0.17 162.48)`       |
| `--color-accent-content`    | `oklch(100% 0 0)`            | `oklch(100% 0 0)`              |
| `--color-neutral`           | `oklch(0% 0 0)`              | `oklch(26% 0 0)`               |
| `--color-neutral-content`   | `oklch(100% 0 0)`            | `oklch(98% 0 0)`               |
| `--color-info`              | `oklch(79.54% 0.103 205.9)`  | `oklch(54% 0.245 262.881)`     |
| `--color-info-content`      | `oklch(15.908% 0.02 205.9)`  | `oklch(89.04% 0.062 264.052)`  |
| `--color-success`           | `oklch(90.13% 0.153 164.14)` | `oklch(59% 0.145 163.225)`     |
| `--color-success-content`   | `oklch(18.026% 0.03 164.14)` | `oklch(90.395% 0.035 142.495)` |
| `--color-warning`           | `oklch(88.37% 0.135 79.94)`  | `oklch(82% 0.189 84.429)`      |
| `--color-warning-content`   | `oklch(17.674% 0.027 79.94)` | `oklch(19.359% 0.042 109.769)` |
| `--color-error`             | `oklch(78.66% 0.15 28.47)`   | `oklch(63% 0.237 25.331)`      |
| `--color-error-content`     | `oklch(15.732% 0.03 28.47)`  | `oklch(12.559% 0.051 29.233)`  |
| `--radius-selector`         | `0.5rem`                     | `0.5rem`                       |
| `--radius-field`            | `0.5rem`                     | `0.5rem`                       |
| `--radius-box`              | `0.75rem`                    | `0.75rem`                      |
| `--size-selector`           | `0.25rem`                    | `0.25rem`                      |
| `--size-field`              | `0.25rem`                    | `0.25rem`                      |
| `--border`                  | `1px`                        | `1px`                          |
| `--depth`                   | `1`                          | `1`                            |
| `--noise`                   | `0`                          | `0`                            |

For other configurations like font (`@theme`) or plugin sources (`@source`), please refer directly
to the `modules/UI/resources/css/app.css` file.

### 4.3. Light & Dark Mode Support

All UI components **must** support both light and dark modes to provide a consistent user
experience.

- **DaisyUI & MaryUI Components:** Components from these libraries typically adapt to the current
  theme automatically. Semantic colors (e.g., `bg-primary`, `text-base-content`) will adjust without
  extra work.
- **Custom Tailwind Components:** When building custom components or applying specific styles with
  Tailwind utilities, you **must** provide styles for dark mode using the `dark:` variant.

> **DO:** Use the `dark:` prefix for custom styles:
> `<div class="bg-gray-100 dark:bg-gray-800 border-gray-200 dark:border-gray-700">`
>
> **DON'T:** Create styles that only work in one mode:
> `<div class="bg-white border-gray-200"> <!-- This will look wrong in dark mode -->`

---

## 5. Layout & Spacing

- **Grid System:** Use Tailwind's built-in grid (`grid`, `grid-cols-*`) or flexbox (`flex`)
  utilities for all layouts.
- **Spacing Scale:** Adhere strictly to Tailwind’s default 4px-based spacing scale (`p-4`, `m-8`,
  `gap-2`).
- **Whitespace:** Embrace whitespace to improve focus and reduce cognitive load. Don't crowd
  elements.

> **DO:** Use spacing scale classes: `<div class="p-4 m-8">` **DON'T:** Use arbitrary values:
> `<div style="padding: 15px; margin: 30px;">`

### 5.1. Mobile-First & Responsiveness

All UI components **must** be developed using a **mobile-first** approach, which is the default
methodology for Tailwind CSS. Styles applied without a screen-size prefix (e.g., `w-full`) target
all screen sizes, starting with mobile. Use responsive prefixes to adapt layouts for larger screens.

- **Required Breakpoints:** At a minimum, ensure components are responsive and well-adapted for `md`
  (medium) and `lg` (large) viewports.
- **Example:** A component might have a single-column layout on mobile and switch to a two-column
  layout on medium screens and up.

```html
<!-- Mobile: Single-column stack -->
<div class="grid grid-cols-1 gap-4 md:grid-cols-2 md:gap-8 lg:grid-cols-3">
    <!-- Items -->
</div>
```

---

## 6. Component Strategy & Interactivity

To maintain consistency and accelerate development, a clear component hierarchy is enforced.

### 6.1. Component Choice Policy

When new UI functionality is required, developers must follow this order of preference:

1.  **Use `UI` Module Core Components (Mandatory):** Always use the standardized wrapper components
    provided by the `UI` module (e.g., `<x-ui::button>`, `<x-ui::input>`). **Direct use of `x-mary-`
    or `x-daisy-` components in feature modules is strictly forbidden.** These wrappers ensure
    global consistency and theming support.
2.  **Use Existing `UI` Module Composites:** Check if a suitable higher-level component (like a
    Navbar or Sidebar) already exists in `modules/UI/resources/views/components`.
3.  **Use `MaryUI` Components (Via Wrapper):** If a required MaryUI component is not yet wrapped in
    the `UI` module, **you must first create a wrapper for it** in
    `modules/UI/resources/views/components/` before using it in your feature module.
4.  **Create a New `UI` Module Component:** Only if neither of the above options provides the
    necessary functionality should a new, custom component be created. All new components must be
    placed within the `UI` module.

### 6.2. Common Components

Use DaisyUI components for all standard UI elements. Below are examples for common components.

- **Buttons:** Use `btn` and semantic color classes.
- **Primary:** `<button class="btn btn-primary">Action</button>`
- **Secondary:** `<button class="btn btn-secondary">Action</button>`
- **[View Docs](https://daisyui.com/components/button/)**

### 6.3. Icon Standardization

Internara has standardized on **Tabler Icons** for all UI elements to ensure visual consistency and
professional aesthetic.

- **Library:** [Tabler Icons](https://tabler-icons.io/) via `secondnetwork/blade-tabler-icons`.
- **Convention:** When using icons in MaryUI components or the `x-ui::icon` wrapper, always use the
  dot-notation format: `tabler.{icon-name}` (e.g., `tabler.plus`, `tabler.search`).
- **Mandatory Prefix:** The `tabler.` prefix is mandatory to ensure the correct icon set is loaded.
- **Direct Usage:** For non-MaryUI contexts, use the provided wrapper:
    ```blade
    <x-ui::icon name="tabler.settings" class="w-5 h-5" />
    ```

### 6.4. MaryUI Components

For complex UI interactions within Livewire, prefer MaryUI wrappers where available.

- **Primary Component Choice:** When a required component is not already provided by the `UI`
  module, developers **must first use a component from MaryUI** before attempting to build a custom
  one. This policy ensures maximum consistency and development speed.
- **Examples:** `x-mary-header`, `x-mary-list`, `x-mary-form`, etc.
- Refer to the official [MaryUI Documentation](https://mary-ui.com/) for usage details.

### 6.4. Module-Specific Components & Communication

To maintain the project's modular architecture, components that are specific to a single module
(e.g., a "Create User" form in the `User` module) must be fully **isolated and portable**. They
should not have direct dependencies on other modules.

### 6.5. UI Module Component Conventions

The `UI` module serves as the central library for all global, application-wide UI components. To
simplify usage, it follows a flatter structure.

- **Location:** All generic, reusable components (like navbars, buttons, cards, etc.) should be
  placed directly in: `modules/UI/resources/views/components/`

- **Usage:** Since these components are registered under the `ui` namespace, they can be called
  directly without any subdirectory prefix. ```blade {{-- CORRECT --}} <x-ui::navbar />
  <x-ui::button />

                                    {{-- INCORRECT --}}
                                    <x-ui::ui.navbar />
                                    ```

    This convention keeps component tags clean and acknowledges that the `UI` module itself is the
    designated "ui" library for the project.

---

## 7. Cross-Module UI Communication

Directly coupling modules at the UI layer is prohibited. To ensure components remain isolated and
portable, all interactions between modules must use one of the two approved methods: Slot Injection
or Event-Based Rendering.

### 7.1. Slot Injection (`@slotRender`)

The `@slotRender` directive is the primary method for a module to inject Blade or Livewire
components into a layout defined in another module. This method is ideal for server-rendered content
that doesn't require complex client-side reactivity between the two components.

#### When to Use the Slot System (The Philosophy)

The Slot System is a powerful tool for decoupling, but it should be used judiciously. Its primary
purpose is to create **extension points** in a component where other modules might need to inject UI
elements.

> **Rule of Thumb:** Use `@slotRender` only when you expect or want to allow another module to
> provide a sub-component for a specific part of the UI.

- **GOOD Use Case:** A slot in the main navbar for user actions (`@slotRender('navbar.actions')`).
  The `User` module can inject a profile dropdown, and a future `Notification` module could inject a
  notification bell, all without the `UI` module needing to know about them.
- **BAD Use Case:** Rendering a static logo within the navbar. The logo is an integral part of the
  navbar component and is not expected to be replaced by other modules. In this case, a standard
  `<img ...>` tag or a simple Blade component (`<x-ui::logo />`) is more appropriate.

For static, non-changeable parts of a component, always prefer standard Blade `@include`s or direct
component tags over the slot system.

#### How to Use

##### 1. Registering Components to a Slot

You can register various types of renderable content using the `SlotRegistry` facade. This typically
happens within a module's Service Provider (`boot()` method) or any other bootstrapping location.

- **Blade View:** Register a Blade view by its name.

    ```php
    // In your Service Provider's boot() method
    \Modules\UI\Facades\SlotRegistry::register('header.actions', 'partials.user-profile-dropdown', [
        'user' => auth()->user(),
    ]);
    ```

- **Livewire Component:** Register a Livewire component by its tag name (`livewire:component-name`).

    ```php
    // In your Service Provider's boot() method
    \Modules\UI\Facades\SlotRegistry::register('sidebar.menu.top', 'livewire:user-card');
    ```

- **Custom Renderer (Closure):** Use a closure for more complex logic or conditional rendering.
    ```php
    // In your Service Provider's boot() method
    \Modules\UI\Facades\SlotRegistry::register('footer.links', function () {
        if (auth()->check()) {
            return view('partials.authenticated-footer-links');
        }
        return view('partials.guest-footer-links');
    });
    ```
    _Note: The `$data` parameter can be passed as the second argument to the closure._

##### 2. Rendering Slots in your Layouts

Once components are registered, you can render them in your Blade layouts. There are two ways to do
this: using the custom `@slotRender` directive or the `<x-ui::slot-render>` component tag.

**Method 1: Using the `@slotRender` Directive (Recommended)**

This is the primary and cleanest way to render a slot. It is powered by a custom Blade directive for
simplicity.

```blade
<header>
    <div class="container">
        <!-- Other header content -->
        @slotRender('header.actions')
        {{-- Renders all components registered to 'header.actions' --}}
    </div>
</header>
```

**Method 2: Using the Component Tag**

Alternatively, you can use the standard Blade component syntax. This is functionally equivalent to
the directive but is more verbose.

```blade
<header>
    <div class="container">
        <!-- Other header content -->
        <x-ui::slot-render name="header.actions" />
    </div>
</header>
```

Both methods achieve the same result, rendering all UI components that have been registered to the
specified slot name.

### 7.2. Event-Based Rendering (Browser Events)

For dynamic client-side communication between components (especially Livewire components) across
different modules, use browser events. This creates a fully decoupled system.

**Example:**

1.  **Module A (`Edit-Post` component)** dispatches an event after saving a post.

    ```php
    // In a Livewire component in Module A
    class EditPost extends Component
    {
        public function save()
        {
            // ... save logic ...

            // Dispatch a browser event to notify other components
            $this->dispatch('post-updated', postId: $this->post->id);
        }
    }
    ```

2.  **Module B (`Post-List` component)** listens for this event to refresh itself.

    ```php
    // In a Livewire component in Module B
    use Livewire\Attributes\On;

    class PostList extends Component
    {
        #[On('post-updated')]
        public function refreshPostList($postId)
        {
            // The component will automatically re-render.
            // You can add specific logic here if needed, like showing a notification.
        }

        // ... rest of the component ...
    }
    ```

This approach ensures that `Module A` does not need to know that `Module B` exists, and vice-versa.

---

## 8. Typography

- **Font Family:** The primary font is `Instrument Sans`, configured in
  `modules/UI/resources/css/app.css`. It falls back to Tailwind's default sans-serif stack
  (`font-sans`).
- **Hierarchy:** Use Tailwind's responsive font size utilities to create a clear hierarchy.
- `h1`: `text-3xl font-bold`
- `h2`: `text-2xl font-bold`
- `h3`: `text-xl font-semibold`
- **Body:** `text-base`
- **Labels/Captions:** `text-sm`
- **Line Height:** Use Tailwind's leading utilities (e.g., `leading-relaxed`) for readable text
  blocks.

---

## 9. Important Note on Inertia

The slot system is designed for server-rendered HTML (Blade and Livewire components). It is **not**
suitable for dynamically injecting Inertia components. Inertia operates on a client-side rendering
paradigm where dynamic components should be managed using Inertia's own features (e.g.,
`<component :is="dynamicComponentName" />` in your client-side framework) in conjunction with props
passed from your Laravel controllers.

---

**Navigation**

[← Previous: Exception Handling Guidelines](exception-handling-guidelines.md) |
[Next: Testing Guide →](testing-guide.md)
