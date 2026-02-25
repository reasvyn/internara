# Gemini Operational Guidelines: Internara Project

This document establishes the authoritative **Operational Baseline** for the AI assistant (“Gemini”)
within the Internara ecosystem. It ensures that all AI-driven interventions demonstrate 100%
alignment with the system's engineering records and international standards.

---

## 🏛️ Strategic Context & Architecture

**Internara** is an internship management ecosystem engineered as a **Modular Monolith**. It
prioritizes systemic integrity, domain isolation, and long-term maintainability.

- **Authoritative Specification (SSoT)**:
  **[`docs/developers/specs.md`](../docs/developers/specs.md)**.
- **Architectural Pattern**: **Modular DDD** with a **Service-Oriented Logic Layer**.
- **Domain Mapping**: `modules/{Module}/src/({Domain}/){Layer}/{Class}.php`.

---

## ⚖️ Interaction & Operational Principles

### 1. Aesthetic-Natural Interaction
Maintain a calm, structured, and adaptive tone. Responses must be structurally minimalist,
cognitively non-aggressive, and oriented toward actionable engineering outcomes.

### 2. Analytical Accountability
- **Traceability**: Every action must trace back to a SyRS requirement ID (e.g., `[SYRS-F-101]`).
- **Verification Gate**: No implementation is complete without a formal verification pass
  (`composer test`, `composer lint`).
- **Privacy Protocol**: Storage or processing of PII is strictly prohibited. Use masking and
  encryption mandates.

### 3. Core Construction Philosophy (S3)
- **Secure (S1)**: Engineered for resilience. Zero-trust, sanitized boundaries, RBAC via Policies.
- **Sustain (S2)**: Maintainable and clear. Strict typing, translation keys, professional PHPDoc.
- **Scalable (S3)**: Designed for growth. Modular isolation, Service-oriented, asynchronous side-effects.

---

## 🔄 Agentic Workflow Discipline (ISO/IEC 12207)

Gemini MUST adhere to the **8-Step Engineering Workflow** defined in 
**[`docs/developers/workflow.md`](../docs/developers/workflow.md)**:

1.  **Step 1: Analysis**: Audit SyRS IDs and technical impacts.
2.  **Step 2: Design**: Open with an **Application Blueprint** update.
3.  **Step 3: TDD RED**: Write failing Pest v4 tests mirroring the `src` structure.
4.  **Step 4: Construction**: Implement logic using the **S3 Doctrine**.
5.  **Step 5: Validation**: Ensure green pass, zero lint violations, and performance audit.
6.  **Step 6: Sync**: Close with **Release Notes** and Documentation updates (**Sync or Sink**).
7.  **Step 7: Commit**: Atomic commits using **Conventional Commits**.
8.  **Step 8: Push**: Final gate check before remote synchronization.

---

## 🛠️ Technical Invariants (The Systemic Record)

### 1. Construction Baseline
- **Strict Typing**: Every PHP file MUST declare `declare(strict_types=1);`.
- **Identity**: Mandatory use of **UUID v4** (via `Shared\Models\Concerns\HasUuid`) for all domain
  entities. 
- **Isolation**: Physical foreign keys across module boundaries are **STRICTLY PROHIBITED**.
- **Logic**: No `env()` calls; utilize `config()` for static values and the `setting()` registry.
- **Service Layer**: Logic resides in Services extending `EloquentQuery`. Communication MUST use 
  **Service Contracts** (Interfaces).
- **UI**: Tailwind CSS v4, maryUI, and Livewire v3. Components MUST be **Thin Components**.

### 2. Namespace & Semantic Omission
- **The `src` Omission**: All namespaces MUST omit the `src` segment.
- **Domain Exception**: If Domain Name == Module Name, the domain folder is omitted.
- **Contracts**: Interfaces must NOT have an `Interface` suffix.

### 3. Resilience & Security
- **Logging**: All `Log::debug()` MUST be wrapped in an `is_debug_mode()` check.
- **PII Redaction**: Personally Identifiable Information MUST be masked in all logging sinks.
- **Sanitization**: External input must be validated at the system boundary (PEP).

---

## ⚡ Automated Tooling & Orchestration

The following commands are the authoritative mechanisms for system interaction:

- **Initialization**: `composer setup` (Bootstrapping).
- **Development**: `composer dev` (Concurrent server, queue, logs, vite).
- **Verification**: `composer test` (Pest v4 + Arch audit).
- **Quality**: `composer lint` (Pint + Prettier + PHPStan).
- **Formatting**: `composer format` (Pint + Prettier auto-fix).
- **Orchestration**: `php artisan app:test` (Sequential module testing).

---

## 📊 Verification & Validation (V&V) Standards

Artifacts must be verified against the **[`Testing Guide`](../docs/developers/tests/verification-index.md)**.

- **Mirroring Invariant**: The `tests/` directory MUST exactly mirror the `src/` hierarchy.
- **Coverage**: Minimum **90% behavioral coverage** per domain module.
- **Complexity**: Cyclomatic Complexity **< 10** per method.
- **Localization**: Zero hard-coded user-facing text; use `__('module::file.key')`.
- **Documentation**: All public methods MUST have professional **English PHPDoc**.

---

## 🔗 Authoritative References

- **[System Specs](../docs/developers/specs.md)** | **[Architecture](../docs/developers/architecture.md)**
- **[Conventions](../docs/developers/conventions.md)** | **[Engineering Lifecycle](../docs/developers/workflow.md)**
- **[Testing Guide](../docs/developers/tests/verification-index.md)** | **[Workflow Guide](../docs/developers/workflow.md)**

---

_This document constitutes the operational identity of Gemini within Internara. Non-compliance is an
architectural defect._
