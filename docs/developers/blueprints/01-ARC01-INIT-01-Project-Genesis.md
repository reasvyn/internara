# Application Blueprint: Project Genesis (ARC01-INIT-01)

**Series Code**: `ARC01-INIT` | **Status**: `Done`

---

## 1. Strategic Context

- **Spec Alignment**: This blueprint authorizes the creation of the foundational modular monolith
  infrastructure required to satisfy **[SYRS-NF-601]** (Isolation) and **[SYRS-NF-602]** (TALL
  Stack).
- **Objective**: Establish the "Engine Room" of the Internara ecosystem, defining the core
  behavioral protocols and technical utilities used by all future modules.

---

## 2. Logic & Architecture (Systemic View)

### 2.1 Capabilities

- **Modular Monolith Foundation**: Implementation of the autonomous module loader and domain
  isolation protocols.
- **Birth of Core Infrastructure**:
    - **`Shared`**: Implementation of universal technical utilities, UUID generation, and the base
      `EloquentQuery` pattern.
    - **`Core`**: Implementation of the global metadata service and academic year scoping logic.
    - **`Exception`**: Implementation of standardized, localized fault management and production
      error abstraction.
    - **`Log`**: Implementation of user activity audit trails and automated PII masking.

### 2.2 Service Contracts

- **`EloquentQuery`**: Abstract contract for standardized CRUD and filtering orchestration.
- **`MetadataService`**: Authoritative contract for application identity and author verification.

### 2.3 Data Architecture

- **Identity Invariant**: Mandatory utilization of **UUID v4** for all entities to prevent ID
  enumeration.
- **Isolation Protocol**: Prohibition of physical foreign keys across module boundaries; referential
  integrity maintained via indexed UUID columns.

---

## 3. Presentation Strategy (User Experience View)

### 3.1 UX Workflow

- **Foundational Layouts**: Implementation of the basic Blade layout hierarchy to support reactive
  components.

### 3.2 Interface Design

- **TALL Stack Integration**: Baseline configuration for Tailwind CSS v4, Alpine.js, and Livewire
  v3.
- **Standardized UI Typography**: Enforcement of **Instrument Sans** as the primary font
  (**[SYRS-NF-402]**).

### 3.3 Invariants

- **Multi-Language Baseline**: Implementation of the translation bridge for Indonesian and English
  locales (**[SYRS-NF-403]**).

---

## 4. Documentation Strategy (Knowledge View)

### 4.1 Engineering Record

- **Formalization of Standards**: Creation of `specs.md` (SSoT) and `architecture.md` to govern
  system evolution.
- **Coding Conventions**: Establishment of `conventions.md` detailing the Service Layer and Omission
  naming rules.

### 4.2 Release Narration

- **Genesis Message**: Formalizing the conceptual birth of Internara and its commitment to modular
  excellence.

---

## 5. Exit Criteria & Quality Gates

- **Acceptance Criteria**: Modular loader verified; Shared utilities (UUID, Formatter) operational;
  AppException handling active.
- **Verification Protocols**: 100% pass rate in the foundational verification suite via Pest v4.
- **Quality Gate**: Zero static analysis violations in the initial module baseline.
