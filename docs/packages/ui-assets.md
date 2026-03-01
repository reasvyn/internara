# UI Assets & Visualization: Supporting Package Integration

This document formalizes the integration of UI assets and visualization libraries within the
Internara project, adhering to **ISO 9241-210** (Human-Centered Design) and **ISO/IEC 25010**
(Usability). It defines the technical standards for utilizing icons and QR codes to enhance the user
experience.

---

## 1. Iconography Orchestration

Internara uses a dual-library strategy to ensure semantic clarity and visual consistency across all
domain modules.

### 1.1 `postare/blade-mdi` (Material Design Icons)

- **Purpose**: Primary library for domain-specific actions and institutional metaphors (e.g.,
  `mdi-account-school`, `mdi-briefcase`).
- **Implementation**: Utilized as Blade components: `<x-mdi-icon-name />`.
- **Standard**: Icons should maintain a default size of **24px** for primary actions and **16px**
  for inline metadata.

### 1.2 `secondnetwork/blade-tabler-icons` (Tabler Icons)

- **Purpose**: Secondary library for UI utilities and abstract metaphors (e.g., `tabler-search`,
  `tabler-settings`).
- **Implementation**: Utilized as Blade components: `<x-tabler-icon-name />`.
- **Rationale**: Provides a lighter, more geometric aesthetic suitable for technical dashboards.

---

## 2. Dynamic Visualization (QR Codes)

Internara uses QR codes to facilitate **Mobile-First** verification and physical-to-digital
onboarding.

### 2.1 `simplesoftwareio/simple-qrcode`

- **Purpose**: Generate dynamic QR codes for student IDs, internship placements, and verified report
  validation.
- **Implementation**: Integrated via the `QrCode` facade in Blade templates or Service Layer
  orchestration.
- **Standard**:
    ```php
    QrCode::size(200)->generate('https://internara.id/verify/' . $uuid);
    ```

---

## 3. Formatting & Design System Invariants

### 3.1 Color & Branding Consistency

All UI assets must respect the **Emerald** accent theme defined in the
**[UI/UX Guidelines](../user-interface-design.md)**.

- **Standard**: Apply the `text-emerald-600` or `bg-emerald-500` classes to icon wrappers to
  maintain the institutional aesthetic.

### 3.2 Accessibility (A11y)

Icon-only buttons must include an `aria-label` or `title` attribute to ensure screen-reader
compatibility.

- **Requirement**: Use the `<x-ui::button icon="mdi-..." label="..." />` component from the `UI`
  module to automatically handle accessibility.

---

_By strictly governing UI assets, Internara ensures a visually consistent, professional, and
accessible experience for all stakeholders._
