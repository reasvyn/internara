# Gemini Operational Guidelines: Internara Project

This document establishes the authoritative **Operational Baseline** for the AI assistant (“Gemini”)
within the Internara ecosystem. It ensures that all AI-driven interventions demonstrate 100%
alignment with the system's engineering records and international standards.

---

## 🏛 Strategic Context & Architecture

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

- **Traceability**: Every action must trace back to a SyRS requirement or architectural mandate.
- **Verification Gate**: No implementation is complete without a formal verification pass
  (`composer test` and `composer lint`).
- **Privacy Protocol**: Storage or processing of PII is strictly prohibited. Use masking and
  encryption mandates.

---

## 🔄 Standardized Engineering Workflows

Gemini is mandated to operate within the following specialized lifecycles, hosted in the universal
**[docs/agents/workflows/](../docs/agents/workflows/README.md)** repository:

1.  **[Planning](../docs/agents/workflows/planning.md)**: Requirement elicitation and strategy.
2.  **[Designing](../docs/agents/workflows/designing.md)**: Architecture and contract design.
3.  **[Developing](../docs/agents/workflows/developing.md)**: Feature construction and TDD.
4.  **[Testing](../docs/agents/workflows/testing.md)**: Verification and V-Model validation.
5.  **[Auditing](../docs/agents/workflows/auditing.md)**: Security and privacy compliance.
6.  **[Refactoring](../docs/agents/workflows/refactoring.md)**: Structural realignment and
    hardening.
7.  **[Documenting](../docs/agents/workflows/documenting.md)**: Engineering record synchronization.
8.  **[Publicating](../docs/agents/workflows/publicating.md)**: Release baseline and versioning.
9.  **[Deploying](../docs/agents/workflows/deploying.md)**: Library distribution and registries.
10. **[Maintenancing](../docs/agents/workflows/maintenancing.md)**: Evolution and technical debt.

---

## 🛠 Technical Invariants (The Systemic Record)

### 1. Construction Baseline

- **Strict Typing**: Every PHP file MUST declare `declare(strict_types=1);`.
- **Identity**: Mandatory use of **UUID v4** (via `Shared\Models\Concerns\HasUuid`) for all
  entities.
- **Versioning**: Adherence to the **Clean Versioning Standard** (e.g., `v0.13.0`). Pre-release
  suffixes (`-alpha`, `-beta`) are optional and reserved for major release tracks.
- **Isolation**: Physical foreign keys across module boundaries are **STRICTLY PROHIBITED**.
- **Logic**: No `env()` calls; utilize `config()` for static values and the `setting()` registry for
  dynamic ones.
- **Service Layer**: Business logic resides in Services extending `EloquentQuery`.
- **UI**: Livewire components must be **Thin Components**; UI integration via **Slot Injection**.
- **Typography**: Utilize **Instrument Sans** for all UI-driven artifacts.

### 2. Namespace & Semantic Omission

- **The `src` Omission**: All namespaces MUST omit the `src` segment.
- **Domain Exception**: If Domain Name == Module Name, the domain folder is omitted.
- **Contracts**: Interfaces must NOT have an `Interface` suffix.

---

## ⚡ Automated Tooling & Orchestration

The following commands are the authoritative mechanisms for system interaction:

- **Initialization**: `composer setup` (Full environment bootstrapping).
- **Development**: `composer dev` (Concurrent server, queue, logs, and vite).
- **Verification**: `composer test` (Mandatory Pest v4 suite).
- **Quality**: `composer lint` (Static analysis check) and `composer format` (Auto-fix).

---

## 📊 Verification & Validation (V&V) Standards

Artifacts must be verified against the **[Testing Guide](../docs/developers/testing.md)**.

- **Mirroring Invariant**: The `tests/` directory MUST exactly mirror the `src/` hierarchy.
- **Coverage**: Minimum **90% behavioral coverage** per domain module.
- **Complexity**: Cyclomatic Complexity **< 10** per method.
- **Localization**: Zero hard-coded user-facing text; use `__('module::file.key')`.
- **Documentation**: All public methods MUST have professional **English PHPDoc**.

---

## 🔗 Authoritative References

- **[Laravel Boost Guidelines](../docs/agents/BOOST.md)**
- **[System Specs](../docs/developers/specs.md)** |
  **[Architecture](../docs/developers/architecture.md)**
- **[Conventions](../docs/developers/conventions.md)** |
  **[Lifecycle](../docs/developers/lifecycle.md)**
- **[Testing Guide](../docs/developers/testing.md)** |
  **[Module Catalog](../docs/users/modules.md)**

---

_This document constitutes the operational identity of Gemini within Internara. Non-compliance is an
architectural defect._
