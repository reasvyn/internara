# Application Blueprint: Project Genesis (ARC01-INIT-01)

**Series Code**: `ARC01-INIT` | **Scope**: `Infrastructure & Foundational Engine` | **Compliance**:
`ISO/IEC 12207`

---

## 1. Context & Strategic Intent

This blueprint authorizes the construction of the authoritative **Modular Monolith** infrastructure.
It establishes the "Engine Room" of Internara, defining the behavioral protocols and technical
utilities required to satisfy the foundational system requirements.

- **SyRS Traceability**:
    - **[SYRS-NF-601]**: Modular Monolith & Domain Isolation.
    - **[SYRS-NF-602]**: TALL Stack Baseline (Laravel 12, Livewire 3, Tailwind v4).
    - **[SYRS-NF-504]**: Identity Integrity (UUID v4).
    - **[SYRS-C-004]**: Product Identity vs. Institutional Branding.

---

## 2. User Roles & Stakeholders

While this phase is primarily technical, it addresses the requirements of:

- **[STRS-05] System Administrator**: Initial system initialization, metadata configuration, and
  institutional branding setup.
- **Engineering Team**: Provides a disciplined, predictable, and isolated environment for feature
  construction.

---

## 3. Modular Impact Assessment

The Genesis phase establishes the **Foundational Layer**, consisting of four primary modules:

1.  **Shared**: Business-agnostic technical utilities (UUIDs, Base Services, Masking).
2.  **Core**: System orchestration (Module Discovery, Academic Year, Metadata SSoT).
3.  **UI**: Headless Design System and the Slot Injection mechanism.
4.  **Support**: Automated scaffolding and testing orchestration.

---

## 4. Contract & Interface Definition

To ensure **Strict Modular Isolation**, inter-module communication is governed by interfaces.

### 4.1 Persistence Orchestration

- **`Modules\Shared\Services\EloquentQuery`**: The abstract base for all domain services.
    - `find(string $uuid)`: Safe retrieval via UUID.
    - `search(array $criteria)`: Standardized filtering.
    - `paginate(int $perPage)`: Mandatory memory-efficient listing.

### 4.2 Module Orchestration

- **`Modules\Core\Services\Contracts\MetadataService`**: Single source of truth for `app_name`,
  `brand_name`, and versioning.
- **`Modules\Core\Providers\ModuleServiceProvider`**: Autonomous loader for modular discovery,
  service binding, and namespace omission.
- **Autonomous Service Binding**: Implementation of a global \`BindServiceProvider\` that 
  automatically maps Interfaces to Implementations based on folder signatures (\`src/Services/Contracts\`), 
  enabling Zero-Config cross-module dependency injection.

---

## 5. Data Persistence Strategy

### 5.1 Identity & State (The Uuid Invariant)

- **Mandate**: Every domain model MUST implement the `Shared\Models\Concerns\HasUuid` trait.
- **Protocol**:
    - UUIDs are generated during the `creating` Eloquent event.
    - Primary keys are binary/string UUIDs (Non-sequential).
- **SLRI (Software-Level Referential Integrity)**: No physical foreign keys across modules.
  Integrity is verified in the Service Layer using UUID lookups.

### 5.2 System Metadata

- **Identity Scoping**: The system distinguishes between the **Product Identity** (Internara) and
  the **Instance Identity** (e.g., SMKN 1 Jakarta).
- **Scoping**: All domain data must be scoped by the **Active Academic Year** managed by the `Core`
  module.

---

## 6. Authorization Governance

### 6.1 Policy-First Security

- **Mandate**: Access is denied by default.
- **Mechanism**: Every domain model must have a corresponding **Laravel Policy**.
- **Governance**: Policies must verify both functional permission (RBAC) and data ownership (IDOR
  protection).

### 6.2 Privacy Redaction

- **Masking**: The `Shared\Utils\PiiMasker` must be active in all logging sinks.
- **Encryption**: Sensitive PII fields (phone, national IDs) must be encrypted at rest using
  Eloquent `encrypted` casts.

---

## 7. Verification Plan (V&V Strategy)

### 7.1 The Architecture Police

- **Tool**: Pest Arch.
- **Standard**:
    - Domain modules MUST NOT depend on other domain modules directly (Interface only).
    - Domain modules MUST NOT use Models from other modules.
    - All logic MUST reside in Services (Thin Component rule).

### 7.2 The Mirroring Invariant

- **Standard**: `tests/Unit/Services/` must mirror `src/Services/` 1:1.
- **Threshold**: Minimum **90% behavioral coverage** for all foundational services.

---

_This blueprint constitutes the authoritative engineering record for the Project Genesis phase. Any
deviation is considered an architectural defect._
