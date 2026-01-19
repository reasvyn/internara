# UI/UX Development Guide: Designing Internara

Internara follows a **Minimalist & Accessible** design philosophy. We utilize the TALL Stack and
standardized UI components to ensure that the user experience is fast, reactive, and visually
consistent across all modules.

---

## 1. The Design System: MaryUI + DaisyUI

We do not write custom CSS for individual components. Instead, we leverage two powerful libraries
built on top of Tailwind CSS.

- **DaisyUI (v5)**: Provides semantic CSS class names (e.g., `btn-primary`, `card`, `input`) that
  follow Material Design and modern web principles.
- **MaryUI (v2)**: Offers complex, interactive Blade components for Livewire, such as data tables,
  modals, and multi-selectors.

---

## 2. Shared UI Module (`modules/UI`)

All custom or customized components reside in the `UI` module. **Do not use direct MaryUI classes in
your feature modules if a shared component exists.**

### 2.1 Core Components

- **Layouts**: Standard dashboard and auth layouts (`x-ui::layouts.dashboard`).
- **Cards**: Semantic wrappers for grouping content (`x-ui::card`).
- **Forms**: Standardized inputs, file uploaders, and date pickers.
- **Feedback**: Success/Error toasts and modal confirmations.

---

## 3. Styling Principles

### 3.1 Utility-First (Tailwind)

Use Tailwind classes for layout, spacing, and minor adjustments.

- **Spacing**: Follow the 4-px grid (e.g., `p-4`, `m-2`, `gap-6`).
- **Responsive**: Use standard prefixes (`md:`, `lg:`) to ensure tablet and desktop compatibility.

### 3.2 Consistency

- **Icons**: Always use **Tabler Icons** via the `tabler.name` format.
- **Colors**: Stick to the theme variables (`primary`, `secondary`, `accent`, `error`, `success`).
  Do not use arbitrary hex codes.

---

## 4. Multi-Language & Locales

The UI must support real-time switching between English (`en`) and Indonesian (`id`).

- **Standard**: Always wrap user-facing text in `__('module::file.key')`.
- **Validation**: Ensure that error messages are properly translated in the module's `lang/` folder.

---

_A great UI is invisible. Follow these guidelines to build an interface that helps users complete
their tasks with zero cognitive friction._
