# UI/UX Development: Human-Centered Design Standards

This document formalizes the **User Interface (UI)** and **User Experience (UX)** standards for the
Internara project, adhering to **ISO 9241-210** (Human-Centered Design), **ISO/IEC 40500** (WCAG 2.1
Accessibility), and **ISO/IEC 29148** (Requirements Engineering).

> **Governance Mandate:** All UI/UX implementation must strictly adhere to the visual design,
> typography, and thematic invariants defined in the authoritative **[Internara Specs](software-requirements.md)**.

---

## 1. Design Philosophy: Aesthetic-Natural

Internara prioritizes cognitive efficiency through a minimalist aesthetic that reduces visual noise
and focuses on task completion.

### 1.1 Visual Hierarchy & Contrast

- **Hierarchy Levels**: Implementation must demonstrate 4 distinct priority levels:
    - **Primary**: Bold, high contrast (Emerald accent) for core actions (e.g., Save, Check-in).
    - **Secondary**: Clear but muted (Outline/Gray) for alternative actions (e.g., Cancel, Back).
    - **Tertiary**: Subtle links or ghost buttons for optional utilities.
    - **Metadata**: Small, low-contrast text for timestamps, UUIDs, or secondary labels.
- **Contrast (WCAG Compliance)**: Text-to-background contrast must maintain a ratio of **> 4.5:1**
  for normal text and **> 3:1** for large text/icons.

### 1.2 Layout & Gestalt Grouping

- **Proximity**: Related elements (e.g., input labels and fields) must be grouped closely.
- **Alignment & Grid**: Adherence to a **4px (base-4) grid**. Every element must align to a
  consistent axis.
- **Figure-Ground**: Modals and dropdowns must utilize shadows and overlays to separate active tasks
  from the background.
- **White Space**: Utilize negative space to prevent information overload and improve "Chunking."

---

## 2. Interaction & Cognitive Efficiency

### 2.1 Affordance & Feedback

- **Signifiers**: Icons and labels must explicitly indicate function (e.g., "trash" icon for
  deletion).
- **Interactive States**: Elements must have distinct `:hover`, `:focus-visible`, and `:active`
  states.
- **Immediate Feedback**: Dispatched via the `Notifier` service; systems must show loading states
  (spinners) during async operations.

### 2.2 Design Heuristics

- **Hick’s Law**: Limit choices in primary navigation to reduce decision-making time.
- **Fitts’s Law**: Primary targets (buttons) must be at least **44x44px** and positioned for
  accuracy.
- **Recognition over Recall**: Show familiar choices/icons instead of requiring users to remember
  flows.
- **Progressive Disclosure**: Hide detailed information until it is actually needed by the user.

---

## 3. Accessibility (A11y) Invariants (ISO/IEC 40500)

Accessibility is a foundational engineering requirement, not an optional feature.

### 3.1 Operability & Navigation

- **Keyboard First**: Every interactive element must be reachable and operable via **Tab** and
  **Enter/Space**.
- **Focus Ring**: Focus states must be clearly visible and high-contrast (`focus-visible`).
- **Skip Links**: Layouts must provide a "Skip to Content" link for keyboard/screen-reader users.

### 3.2 Screen Reader Support (ARIA)

- **Semantic HTML**: Mandatory use of `<main>`, `<nav>`, `<header>`, `<footer>`, and `<button>`.
- **ARIA Attributes**: Use `aria-label` for icon-only buttons and `aria-expanded` for
  toggles/dropdowns.
- **Alt Text**: Every non-decorative image must provide a descriptive `alt` attribute.

---

## 4. Internationalization (i18n) Invariants

Internara is a multi-language system. Hard-coding of user-facing text is a **Critical Quality
Violation**.

### 4.1 Translation Standards

- **Zero Hard-coding**: All text must be resolved via `__('module::file.key')`.
- **Dynamic Content**: Use placeholders for dynamic data (e.g.,
  `__('shared::messages.deleted', ['record' => $name])`).
- **Contextual Microcopy**: Labels and tooltips must be localized to reduce ambiguity.

### 4.2 Locale-Aware Formatting

- **Dates & Times**: Must be formatted according to the active locale (`id` or `en`) via standard
  Laravel/Carbon helpers.
- **Numbers & Currency**: Utilize `Number::format()` or equivalent for localized separators (e.g.,
  `.` for ID, `,` for EN).

---

## 5. Layouting Standards: Tiered Structural Hierarchy

Internara utilizes a three-tiered layouting strategy to ensure separation of concerns, visual
consistency, and modular reuse.

### 5.1 Tier 1: Base Layout (`x-ui::layouts.base`)

The **Base Layout** is the absolute technical foundation of the system.

- **Scope**: Entire application.
- **Role**:
    - Manages global HTML structure (`<!DOCTYPE html>`, `<head>`, `<body>`).
    - Orchestrates asset loading (Vite, Fonts).
    - Initializes global JavaScript libraries (AlpineJS, AOS).
    - Provides a standard "Skip to Content" mechanism for accessibility.
- **Constraint**: Must remain agnostic of module-specific logic or business rules.

### 5.2 Tier 2: Page Layouts (Structural Orchestrators)

**Page Layouts** define the high-level structural arrangement for specific environments or modules.

- **Scope**: Multi-page areas or specific domain modules.
- **Types**:
    - **Global Dashboard (`x-ui::layouts.dashboard`)**: The standard internal layout featuring a
      persistent Navbar and a dynamic Sidebar (via Slot Injection).
    - **Specialized Layouts**: Module-specific structures like `x-auth::layouts.auth` (centered
      forms) or `x-setup::layouts.setup` (wizard-style navigation).
- **Role**:
    - Arranges major UI blocks: Navbar, Sidebar, Footer, and Main Content area.
    - Handles page-level metadata and breadcrumbs.

### 5.3 Tier 3: Component Layouts (Internal Organization)

**Component Layouts** are structural building blocks used to organize information _within_ a page.

- **Scope**: Individual views or components.
- **Examples**: `x-ui::card`, `x-ui::modal`, `x-ui::form`, `x-ui::tabs`.
- **Role**:
    - Enforces consistent padding, borders, and shadows (Design System).
    - Group related information into "chunks" to improve readability.
    - Encapsulate complex UI patterns (e.g., Modal state management) to simplify developer
      experience.

---

## 6. Mobile-First & Safety Strategy

### 6.1 Responsive Adaptation

- **Default Viewport**: Implementation must default to mobile layouts (single-column).
- **Adaptive Data**: Tables must utilize horizontal scrolling or card-stacking on mobile.

### 6.2 Error Prevention

- **Destructive Confirmation**: High-impact actions must require explicit user confirmation.
- **Real-time Validation**: Provide instant feedback on form errors before submission.

---

## 7. Engineering the UI (TALL Stack)

### 7.1 The UI Module & Component Architecture

The **UI Module** is the central authority for the project's design system. All reusable components
must reside here to ensure visual consistency across the modular monolith.

- **Thin Components**: Livewire components must focus exclusively on UI state and user interaction,
  delegating all business logic to the **Service Layer**.

### 7.2 Decoupled UI: Slot Injection Pattern

To maintain zero-coupling between domain modules, Internara uses a **Slot Injection** mechanism as
detailed in the **[Presentation Isolation](engineering-standards.md#174-presentation-isolation-slot-pattern)**
section.

- **The Registrar**: Modules register their UI elements into named slots via `viewSlots()`.
- **The Renderer**: Layouts use `<x-ui::slot-render name="slot.name" />` as a dynamic placeholder.
- **Benefit**: Allows Module A to add content to Module B's layout without direct coupling.

### 7.3 Dynamic Configuration: Settings vs. Config

To avoid hardcoded "Magic Values" and ensure system flexibility, developers must follow these
standards:

- **`config('key')`**: Use for **Environment-Static** values. These are values defined in code that
  rarely change and are tied to the environment (e.g., API keys, driver names).
- **`setting('key')`**: Use for **Runtime-Dynamic** values. These are business-level configurations
  that administrators can modify via the UI.
- **Identity Usage**:
    - Use `setting('app_name')` for product-level identity (e.g., technical footers, system logs).
    - Use `setting('brand_name', setting('app_name'))` for user-facing branding (e.g., Navbar
      titles, email senders, report headers).
- **Hardcoding Prohibited**: Never hardcode values like brand names, version numbers, or status
  identifiers directly in Blade templates.

---

## 8. Motion & Animation (AOS Integration)

Internara uses **AOS (Animate On Scroll)** to provide subtle, meaningful motion that enhances the
storytelling and flow of the application without causing cognitive overload.

### 8.1 Principles of Motion

- **Subtlety**: Animations must be brief and non-distracting.
- **Directional Logic**: Elements should enter from their logical point of origin (e.g., sidebars
  from the side).
- **Performance**: Avoid animating complex layouts that may cause layout shifts or frame drops.

### 8.2 Implementation Standards

- **Global Config**: Default duration is set to **800ms** with `ease-out-cubic` easing.
- **Usage**: Apply motion via the `data-aos` attribute on Blade elements.
    - Example: `<div data-aos="fade-up">...</div>`.
- **SPA Compatibility**: AOS is automatically re-initialized on the `livewire:navigated` event to
  ensure consistent behavior during SPA transitions.

---

## 9. UI Evolution Strategy: The Path to TALL Core

Internara adopts an **Evolutionary UI Strategy** designed to balance rapid prototyping with
long-term architectural independence.

### 9.1 Library Minimization Principle

As the application matures, the system shall systematically minimize reliance on third-party UI
libraries (e.g., **MaryUI**, **DaisyUI**) to reduce the "Black Box" effect and ensure absolute
design control.

- **Phase 1: Bootstrapping (Library-Heavy)**: Utilize UI libraries for rapid scaffolding of complex
  components (Modals, Dropdowns, Navbars) during initial domain construction.
- **Phase 2: Refinement (Native Transition)**: Incrementally refactor library-based components into
  **Native TALL** implementations (Tailwind CSS, AlpineJS, Livewire) during feature updates or UI
  audits.
- **Phase 3: Mature Core (Zero-Dependency UI)**: The ultimate goal is a system that depends
  exclusively on the core TALL stack, where all UI components are authored locally within the
  `modules/UI` foundation.

### 9.2 Migration Mandate

- **Incrementalism**: Migration to native TALL should occur naturally during the development
  lifecycle, not as a separate, high-risk "Big Bang" refactor.
- **Maintainability**: Custom native components must prioritize readability and utilize standard
  Tailwind v4 utility patterns to ensure they remain accessible to future maintainers.
- **Performance**: Native components must demonstrate superior performance (smaller bundle size,
  fewer DOM nodes) compared to their library-based predecessors.

---

_By adhering to these standards, Internara provides a professional, accessible, and high-performance
experience._
