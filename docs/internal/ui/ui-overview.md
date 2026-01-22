# UI Module: The Internara Design System

The `UI` module is the central repository for all shared frontend assets, global Blade components,
and Livewire interface elements. It acts as the project's internal design system.

> **Governance Mandate:** The UI module is the technical implementation of the visual identity
> defined in the **[Internara Specs](../../internara-specs.md)**. All components must enforce
> **Mobile-First** responsiveness and **Multi-Language** support.

---

## 1. Visual Identity (from Specs)

All components in this module are pre-configured with Internara's branding:

- **Typography:** Uses **Instrument Sans** as the primary font.
- **Theming:** Supports Light/Dark modes with **Emerald Green** as the primary accent color.
- **Shapes:** Standardized rounded corners (`0.25` – `0.75 rem`).

---

## 2. Our UI Architecture

We follow a "Layered Design" approach:

- **Tailwind CSS v4:** The utility engine for layout and spacing.
- **DaisyUI:** Semantic CSS components (Theme-aware).
- **MaryUI:** Rich Livewire components.
- **UI Module:** The "Internara-flavored" wrappers (e.g., `x-ui::button`).

---

## 3. Mobile-First & i11n Strategy

### 3.1 Responsiveness
- **Principle:** Default to mobile layouts. Use `md:` and `lg:` for desktop enhancement.
- **Components:** Containers and grids in the `UI` module are responsive by default.

### 3.2 Localization
- **Principle:** No hardcoded strings in components.
- **Usage:** All labels, placeholders, and tooltips must use `__('key')` or allow for slot injection.

---

## 4. The Component Hierarchy

Priority order for building interfaces:

1.  **Internara Components (`x-ui::`)**: Project-specific wrappers with built-in accessibility.
2.  **MaryUI Components (`x-mary-`)**: Complex interactions not yet wrapped.
3.  **DaisyUI Classes**: Raw CSS components for static elements.

---

## 5. Standardized Assets

### 5.1 Icons
Standard: **Tabler Icons**. Use the `tabler.name` format.

### 5.2 Colors
Always use semantic variables:
- `primary`: Emerald Green actions.
- `base-100`: Main background.
- `error`: Destructive actions.

---

_The UI module ensures that Instructors, Staff, and Students experience a cohesive and professional interface across all devices._