# Application Blueprint: Institutional Foundation (ARC01-INST-01)

**Series Code**: `ARC01-INST` | **Status**: `Done`

---

## 1. Strategic Context

- **Spec Alignment**: This blueprint authorizes the creation of the institutional hierarchy and the
  headless design system required to satisfy **[SYRS-F-101]** (Student Profiles) and
  **[SYRS-NF-401]** (Mobile-First).
- **Objective**: Establish the institutional context (School/Department) and formalize the system's
  visual identity through a standardized UI module.

---

## 2. Logic & Architecture (Systemic View)

### 2.1 Capabilities

- **Institutional Identity**: Management of school branding and academic specialization structures.
- **Systemic State Management**: Centralization of entity lifecycle tracking using PHP 8.1+ Enums.
- **Birth of Core Components**:
    - **`School`**: Implementation of institutional metadata and branding (Logo).
    - **`Department`**: Implementation of academic specializations and organizational grouping.
    - **`UI`**: Implementation of the headless Design System and the **Slot Injection** mechanism.
    - **`Status`**: Implementation of the auditable state transition engine and Status Enums.

### 2.2 Service Contracts

- **`SchoolService`**: Contract for managing authoritative institutional data.
- **`LocalizationService`**: Contract for orchestrating the application's multi-language state.
- **`SlotManager`**: Contract for cross-module UI fragment injection.

### 2.3 Data Architecture

- **State Invariant**: Use of the `HasStatus` concern to track lifecycle transitions ("Active",
  "Inactive", "Pending") with an automated audit trail.
- **Logo Management**: Integration with the `Media` module for secure branding asset storage.

---

## 3. Presentation Strategy (User Experience View)

### 3.1 UX Workflow

- **Headless UI Orchestration**: Implementation of the `SlotRegistry` to allow modules to inject
  fragments into global layouts without coupling.
- **Language Switching**: Interactive UI for toggling between Indonesian and English baselines.

### 3.2 Interface Design

- **Tiered Layout System**: Formalization of the Base, Page, and Component layout hierarchy.
- **Branding Integration**: Dynamic application of institutional colors and logos across the
  dashboard.

### 3.3 Invariants

- **Accessibility Baseline**: Enforcement of semantic HTML and ARIA attributes in all UI components.

---

## 4. Documentation Strategy (Knowledge View)

### 4.1 Engineering Record

- **UI/UX Standards**: Formalization of the **Slot Injection** pattern and Component naming rules.
- **Module Catalog**: Initialization of the authoritative module registry.

### 4.2 Release Narration

- **Institutional Message**: Highlighting the platform's readiness to represent educational
  institutions with professional branding.

---

## 5. Exit Criteria & Quality Gates

- **Acceptance Criteria**: School and Department management operational; Slot injection verified;
  Status transitions auditable.
- **Verification Protocols**: 100% pass rate in UI unit tests and integration suites.
- **Quality Gate**: Zero violations of the Mobile-First design philosophy.
