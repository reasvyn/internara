# Code Quality Standardization: Engineering & Quality Models

This document formalizes the **Code Quality Standardization (CQS)** for the Internara project,
standardized according to **ISO/IEC 25010** (Product Quality Model) and **ISO/IEC 5055** (Software
Quality Measurement). It establishes the rigorous technical gates required to ensure system
maintainability, reliability, and security from **Conceptual Design** to **Artifact Delivery**.

---

## 1. Product Quality Model (ISO/IEC 25010 Alignment)

Internara prioritizes the following quality sub-characteristics to manage systemic complexity and
ensure architectural integrity.

### 1.1 Maintainability (Structural Integrity)

- **Modularity**: Modules must maintain strict functional cohesion and logical isolation.
  Cross-module interaction is restricted to **Service Contracts**. This applies to production logic
  and **Verification Tests**.
- **Zero-Coupling**: Absolute isolation between domain modules. Interaction is strictly permitted
  only via Service Contracts (Interfaces) or **Public Infrastructure Classes** (Stateless Helpers,
  Utilities). Direct usage of **Domain-Specific Concrete Classes** (Models, Factories, Repositories)
  from other modules is a critical quality violation.
- **Analyzability**: Source code must utilize explicit typing (PHP 8.4+), professional PHPDoc, and
  semantic naming that reflects domain concepts as defined in the
  **[System Requirements Specification](software-requirements.md)**.
- **Testability**: Every capability must have an accompanying behavioral verification suite (Pest
  v4) to ensure deterministic outcomes.

### 1.2 Reliability (Operational Fault Tolerance)

- **Maturity**: Service logic must demonstrate predictable behavior across all identified state
  transitions.
- **Recoverability**: All multi-entity persistence operations must be encapsulated within database
  transactions to ensure atomicity and consistency.

### 1.3 Usability & Accessibility (A11y Aware)

- **Accessibility**: Compliance with **WCAG 2.1 (Level AA)** standards. Interfaces must be navigable
  via keyboard and compatible with assistive technologies (A11y Aware).
- **Inclusivity**: UI elements must utilize semantic HTML and provide sufficient color contrast to
  ensure usability for all user cohorts.

---

## 2. End-to-End Protection Protocols (ISO/IEC 27034)

Protection is integrated into the engineering process to ensure information integrity and
confidentiality.

### 2.1 Boundary Protection (Entry/Exit Validation)

- **Input Validation Invariant**: All external input must be validated at the system boundary
  utilizing strict type-hinting and Laravel's validation subsystem.
- **Output Sanitization**: Data rendered to the presentation layer must be sanitized to prevent
  injection vulnerabilities (XSS).

### 2.2 Data Integrity & Privacy

- **Identity Invariant**: Mandatory use of **UUID v4** for all entity identifiers to prevent
  unauthorized enumeration attacks.
- **Privacy Invariant**: Sensitive data (PII) must be encrypted at rest and masked within the
  logging subsystem to comply with privacy mandates in the System Requirements Specification.
- **Referential Integrity**: Managed exclusively at the **Service Layer** to preserve modular
  portability (no physical foreign keys across modules).

---

## 3. Quantitative Quality Gates (ISO/IEC 5055)

Internara utilizes the following metrics as mandatory automated quality gates:

| Characteristic      | Metric (ISO/IEC 5055)      | Limit/Target            |
| :------------------ | :------------------------- | :---------------------- |
| **Maintainability** | Cyclomatic Complexity      | < 10 per method         |
| **Cognitive Load**  | Cognitive Complexity       | < 15 per class          |
| **Control Flow**    | N-Path Complexity          | < 200                   |
| **V&V Coverage**    | Behavioral Coverage (Pest) | > 90% per Domain Module |
| **Security Risk**   | Vulnerability Count (SAST) | 0 Critical / 0 High     |

---

## 4. Construction Protocols (Root-to-Delivery)

### 4.1 Design-to-Construction Traceability

- **Blueprint Alignment**: All implementation must demonstrate traceability to an approved
  Architectural Blueprint.
- **Architectural Compliance**: Every modification is validated against the
  **[Architecture Description](system-architecture.md)**.

### 4.2 Automated Verification Gates

- **Static Analysis**: All commits must pass **PHPStan Level 8** analysis and **Laravel Pint**
  linting.
- **Contract Fulfillment**: Services must strictly implement their designated Service Contracts.

### 4.3 Delivery Synchronization

- **Artifact Consistency**: The code, verification tests, and documentation must be promoted as a
  single, cohesive configuration baseline.
- **Environment Parity**: Application behavior is managed via the `setting()` helper to ensure
  uniformity across development and production environments.

---

## 5. The Mandatory 3S Audit Protocol (V&V Phase)

Prior to any artifact delivery, all modules must undergo a formal **3S Audit** to ensure alignment
with the system's foundational principles and resource efficiency.

### 5.1 Stage 1: Security Verification (S1)

- **Authorization Enforcement (RBAC)**: Ensure every entry point (Livewire, API) is protected by a
  Laravel Policy or Gate. Every state-changing method MUST invoke `Gate::authorize()`.
- **Identity Invariant (UUID v4)**: Verify usage of UUID v4 (via `Shared\Models\Concerns\HasUuid`)
  on all Domain Models. No auto-increment integers exposed.
- **Zero-Trust Boundary Validation**: Input from outside the module must be strictly validated at
  the PEP (Livewire rules/Form Requests).
- **PII Masking & Encryption**: Sensitive data (email, NIK, phone) must be encrypted at rest and
  redacted in all logging sinks.
- **Credential Protection**: No `env()` calls within application logic. Use `config()` or
  `setting()`.

### 5.2 Stage 2: Sustainability Verification (S2)

- **PHP 8.4 Compliance**: Verify use of explicit typing and modern features.
- **Strict Typing Invariant**: Every PHP file MUST declare `declare(strict_types=1);`.
- **Clean Code & i18n**: Adhere to SOLID/DRY/KISS. Zero hard-coded user-facing text (`__('key')`).
- **Professional Documentation**: All public methods must have English DocBlocks explaining intent.
- **Verification Mirroring**: The `tests/` directory MUST mirror the `src/` hierarchy with **>90%
  behavioral coverage**.

### 5.3 Stage 3: Scalability Verification (S3)

- **Physical Isolation**: **Zero physical foreign keys** across module boundaries. Referential
  integrity is managed at the Service Layer.
- **Domain Isolation**: Zero direct model instantiation or usage from other modules.
- **Contract-First Interaction**: Cross-module communication must exclusively use **Service
  Contracts** (Interfaces).
- **Logic Dualism (CQRS)**: Business logic MUST reside in the Service Layer (`EloquentQuery` for
  queries, `BaseService` for commands/orchestration).
- **Asynchronous Processing**: Cross-domain side-effects must utilize **Domain Events** with
  lightweight (UUID-only) payloads.

### 5.4 Performance & Accessibility Overrides

- **Performance Audit**: Detect and eliminate N+1 queries. Mandate `paginate()`, `chunk()`, or
  `cursor()`.
- **A11y Audit**: UI MUST comply with **WCAG 2.1 AA** (Semantic HTML, ARIA, Keyboard Nav).
- **Bottleneck Identification**: Identify heavy synchronous logic during page renders. Ensure proper
  indexing on `searchable` and `sortable` columns.
