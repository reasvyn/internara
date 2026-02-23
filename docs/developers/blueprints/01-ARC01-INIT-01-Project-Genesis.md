# Application Blueprint: Project Genesis (ARC01-INIT-01)

**Series Code**: `ARC01-INIT` | **Scope**: `Infrastructure & Foundation`

---

## 1. Strategic Context

- **Spec Alignment**: This blueprint authorizes the creation of the foundational modular monolith
  infrastructure required to satisfy **[SYRS-NF-601]** (Isolation), **[SYRS-NF-602]** (TALL Stack),
  and **[SYRS-NF-403]** (Localization).
- **Objective**: Establish the "Engine Room" and architectural DNA of Internara. This phase defines
  the behavioral protocols, technical utilities, and systemic invariants that will govern every
  subsequent domain module.
- **Rationale**: A system is only as strong as its foundation. By formalizing infrastructure before
  domain logic, we prevent architectural drift and ensure a predictable engineering environment for
  future developers.

---

## 2. Logic & Architecture (Systemic View)

### 2.1 The Modular Engine

- **Autonomous Loader**: Development of a custom bootstrapping mechanism to discover and initialize
  modules located in the `modules/` directory.
- **Domain Isolation**: Implementation of namespaces and service providers that prevent leakage
  between business domains.

### 2.2 Service Contract Specifications

- **`Modules\Shared\Services\Contracts\EloquentQuery`**: Standardized API for query, persistence,
  and filtering, allowing for cross-module referential integrity checks.
- **`Modules\Core\Metadata\Services\Contracts\MetadataService`**: Authoritative source for system
  identity, versioning, and attribution protection.
- **`Modules\Core\Academic\Contracts\AcademicYearManager`**: Providing temporal context for data
  scoping based on institutional cycles.

### 2.3 Data Architecture

- **Identity Invariant**: Mandatory use of **UUID v4** for all entities via the foundational
  `HasUuid` concern.
- **Referential Integrity**: SLRI (Service Layer Referential Integrity) pattern using indexed UUID
  columns. Physical foreign keys across module boundaries are strictly forbidden.
- **State Audit**: Implementation of `HasStatus` to track and audit entity lifecycle transitions.

---

## 3. Presentation Strategy (User Experience View)

### 3.1 UX Workflow

- **Layout Hierarchy**: Definition of the base administrative shell (`AppLayout`) and minimalist
  entry shell (`AuthLayout`).
- **Mobile-First Navigation**: Prioritizing bottom-heavy actions and responsive menus for one-handed
  mobile operability.

### 3.2 Interface Design

- **Slot Injection Pattern**: Implementation of the `UI` module's slot registry to facilitate
  cross-module UI integration without tight coupling.
- **Design Baseline**: Enforcement of **Instrument Sans** typography and the institutional emerald
  theme via Tailwind v4 and MaryUI.

---

## 4. Verification Strategy (V&V View)

### 4.1 Unit Verification

- **Shared Integrity**: 100% behavioral coverage for technical formatters and UUID generation logic.
- **Contract Adherence**: Unit tests verifying that all foundational services strictly adhere to
  their defined contracts.

### 4.2 Feature Validation

- **Module Discovery**: Verification that the autonomous loader correctly discovers and initializes
  modules from the `modules/` directory.
- **Layout Rendering**: Tests verifying the correct rendering of base layouts across different
  viewports.

### 4.3 Architecture Verification

- **Isolation Enforcement**: Pest Arch tests ensuring foundational modules (Shared, Core) remain
  strictly agnostic of domain modules.
- **Constraint Audit**: Verification of the `src` omission rule in namespaces and the `final`
  keyword on all utility classes.

---

## 5. Compliance & Standardization (Integrity View)

### 5.1 i18n & Localization

- **Multi-Locale Support**: Implementation of the localization bridge supporting Indonesian (`id`)
  and English (`en`) with zero hard-coded user-facing strings.

### 5.2 Security & Privacy

- **Least Privilege**: Adherence to the least privilege principle in early-boot middleware.
- **PII Hardening**: Standardizing the `encrypted` cast for foundational identity data.

### 5.3 Zero-Coupling

- **Service-Based Interaction**: Ensuring foundational modules provide capabilities exclusively via
  Service Contracts to prevent concrete coupling.

---

## 6. Documentation Strategy (Knowledge View)

### 6.1 Engineering Record

- **Standards Publication**: Formalization of `specs.md`, `architecture.md`, `governance.md`, and
  `testing.md`.
- **Glossary Definition**: Establishment of the technical terminology used throughout the project.

### 6.2 Stakeholder Manuals

- **Wiki Genesis**: Creation of the initial Wiki structure covering system overview and
  installation.

### 6.3 Release Narration

- **Genesis Message**: Highlighting the architectural birth of Internara and its commitment to
  modular stability and Indonesian vocational excellence.

### 6.4 Strategic GitHub Integration

- **Issue #1**: Creation of the custom Module Bootstrapper.
- **Issue #2**: Implementation of the `Shared` utility tier (UUID, Formatter).
- **Issue #3**: Construction of the base `App` and `Auth` layouts.
- **Milestone**: ARC01-INIT (Project Genesis Baseline).

---

## 7. Exit Criteria & Quality Gates

- **Acceptance Criteria**: Foundational infrastructure operational; autonomous loader active;
  standardized CRUD patterns verified.
- **Verification Protocols**: 100% pass rate in **`composer test`** across all foundational modules.
- **Quality Gate**: Zero violations in static analysis (`composer lint`) and architectural
  invariants verified via Pest Arch.

---

_Application Blueprints prevent architectural decay and ensure continuous alignment with the
foundational specifications._
