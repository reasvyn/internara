# UI/UX Development Guide: Designing Internara

Internara follows a **Minimalist, Accessible, and Mobile-First** design philosophy. We utilize the
TALL Stack and standardized UI components to ensure that the user experience is fast, reactive, and
visually consistent across all modules and devices.

> **Governance Mandate:** The UI/UX implementation must adhere to the visual design, typography, and
> theming standards defined in the **[Internara Specs](../internal/internara-specs.md)**.
> Deviation from the approved design system (colors, fonts, layout behavior) is prohibited.

---

## 1. The Design System: TALL Stack + MaryUI

We leverage a robust component ecosystem built on top of **Tailwind CSS v4**.

- **DaisyUI:** Provides semantic, theme-aware CSS components (e.g., `btn-primary`, `card`).
- **MaryUI:** Provides rich Blade components for Livewire (Tables, Modals, Drawers).
- **Tailwind CSS v4:** The utility engine for layout and spacing.

### 1.1 Visual Identity (from Specs)

- **Typography:** Primary Font is **Instrument Sans**.
- **Theme:**
    - **Light Mode:** White/Soft Gray background, Deep Black primary elements.
    - **Dark Mode:** Deep Black/Dark Gray background, White primary elements.
    - **Accent:** **Emerald Green** (Buttons, Links, Highlights).
- **Shapes:** Rounded corners (`0.25` – `0.75 rem`).

---

## 2. Mobile-First Strategy

Per the **Internara Specs**, the interface must be designed for mobile devices first, then progressively enhanced for larger screens.

### 2.1 Responsive Principles

- **Default Layout:** All grids and flex containers must default to a single-column (mobile) layout.
- **Breakpoints:** Use `md:` (Tablet) and `lg:` (Desktop) prefixes to expand layouts.
- **Touch Targets:** Buttons and interactive elements must have a minimum touch target size (approx. 44x44px).
- **Navigation:**
    - **Mobile:** Collapsible Sidebar (Drawer) or Bottom Navigation.
    - **Desktop:** Persistent Sidebar or Top Navigation.

### 2.2 Component Behavior

- **Tables:** On mobile, tables should stack or scroll horizontally without breaking layout. Consider using "Card Views" for data on small screens.
- **Modals:** Must occupy full width/height or be sheet-based on mobile devices.

---

## 3. Shared UI Module (`modules/UI`)

All custom or customized components reside in the `UI` module. **Do not duplicate component logic in feature modules.**

### 3.1 Core Components

- **Layouts**:
    - `x-ui::layouts.app` (Main Dashboard with Responsive Navigation).
    - `x-ui::layouts.auth` (Authentication Pages).
- **Wrappers**:
    - `x-ui::card`: Standard content container with responsive padding.
    - `x-ui::container`: Max-width wrapper for page content.
- **Interactions**:
    - `x-ui::button`: Standardized button styles (Primary, Ghost, Error).
    - `x-ui::modal`: Responsive dialog wrapper.

---

## 4. Styling & Theming

### 4.1 Utility-First (Tailwind)

- **Spacing:** Adhere to the 4px grid (e.g., `p-4`, `gap-3`).
- **Colors:** **NEVER** use hardcoded hex values (e.g., `#000000`). Always use semantic theme variables:
    - `bg-base-100`, `text-base-content` (Background/Text).
    - `btn-primary`, `text-primary` (Emerald Green).
    - `text-error`, `alert-warning` (Feedback).

### 4.2 Icons

- **Library:** **Tabler Icons**.
- **Usage:** Use the `x-icon` component or generic svg helpers. Ensure consistent stroke width.

---

## 5. Multi-Language (i11n) Support

The UI must support seamless switching between English (`en`) and Indonesian (`id`).

- **Hardcoding Prohibited:** Never write raw text in Blade views.
- **Usage:**
    - Blade: `{{ __('module::file.key') }}`.
    - Class/JS: Use the global translation helper.
- **RTL:** Not required (both EN and ID are LTR), but layout directionality should use logical properties (`ms-`, `me-`) where possible for future proofing.

---

## 6. Feedback & Accessibility

- **Global Notification Bridge:** Feedback (Toasts) must work across all device sizes.
- **Accessibility (a11y):**
    - Semantic HTML (Use `<button>`, not `<div>`).
    - Focus states must be visible.
    - Color contrast must meet WCAG AA standards (enforced by the Theme).

---

_A great UI is invisible. Follow these guidelines to build an interface that helps Instructors, Staff, and Students complete their tasks with zero cognitive friction, regardless of the device they use._