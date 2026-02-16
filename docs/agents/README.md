# The Agent Operational Guidelines: Internara Project

This document establishes the authoritative **Operational Baseline** for the AI assistant (“The
Agent”) within the Internara ecosystem. It ensures that all AI-driven interventions demonstrate 100%
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

### 2. Documentation Integrity (The Non-Truncation Law)

It is **STRICTLY PROHIBITED** to simplify, truncate, or reduce the technical depth of any
documentation. Architectural details, rigorous definitions, and technical nuances MUST be preserved
in their entirety. Every update MUST maintain or increase clarity without sacrificing depth.

### 3. Analytical Accountability

- **Traceability**: Every action must trace back to a SyRS requirement or architectural mandate,
  documented via **GitHub Issues**.
- **Verification Gate**: No implementation is complete without a formal verification pass
  (`composer test` and `composer lint`) and a **Pull Request** review.
- **Privacy Protocol**: Storage or processing of PII is strictly prohibited. Use masking and
  encryption mandates.

---

## 🛠 Repository & Tooling Protocols

The Agent must utilize standard free OSS tools for repository management:

- **Branching Strategy**: Strictly adhere to the **Git Flow** (`feature/*`, `release/*`, `hotfix/*`)
  defined in `docs/developers/git.md`.
- **Commit Standards**: Mandatory use of **Conventional Commits** (`feat`, `fix`, `refactor`, etc.)
  with reference to Issue IDs.
- **GitHub Orchestration**: Utilize the **GitHub CLI (`gh`)** for issue tracking, milestone
  alignment, and PR management.

---

## 🔄 Standardized Engineering Workflows

The Agent is mandated to operate within the following specialized lifecycles, hosted in the
universal **[docs/agents/workflows/](./workflows/README.md)** repository:

1.  **[Planning](./workflows/planning.md)**: Requirement elicitation and strategy.
2.  **[Designing](./workflows/designing.md)**: Architecture and contract design.
3.  **[Developing](./workflows/developing.md)**: Feature construction and TDD.
4.  **[Refactoring](./workflows/refactoring.md)**: Structural realignment and hardening.
5.  **[Testing](./workflows/testing.md)**: Verification and V-Model validation.
6.  **[Documenting](./workflows/documenting.md)**: Engineering record synchronization.
7.  **[Publicating](./workflows/publicating.md)**: Release baseline and versioning.
8.  **[Deploying](./workflows/deploying.md)**: Library distribution and registries.
9.  **[Maintenancing](./workflows/maintenancing.md)**: Evolution and technical debt.

---

## 🛠 Technical Invariants (The Systemic Record)

### 1. Construction Baseline

- **Identity**: Mandatory use of **UUID v4** for all entities.
- **Isolation**: Physical foreign keys across module boundaries are **PROHIBITED**.
- **Logic**: No `env()` calls; utilize the `setting()` registry.
- **UI**: Livewire components must be **Thin Components**; UI integration via **Slot Injection**.

### 2. Namespace & Semantic Omission

- **The `src` Omission**: All namespaces MUST omit the `src` segment.
- **Domain Exception**: If Domain Name == Module Name, the domain folder is omitted.

---

## 📊 Verification & Validation (V&V) Standards

Artifacts must be verified against the **[Testing Guide](../docs/developers/testing.md)**.

- **Mirroring Invariant**: The `tests/` directory MUST exactly mirror the `src/` hierarchy.
- **Coverage**: Minimum **90% behavioral coverage** per domain module.
- **Complexity**: Cyclomatic Complexity **< 10** per method.
- **Localization**: Zero hard-coded user-facing text; verify in **ID** and **EN**.

---

## 🔗 Authoritative References

- **[Laravel Boost Guidelines](./BOOST.md)**
- **[System Specs](../developers/specs.md)** | **[Architecture](../developers/architecture.md)**
- **[Conventions](../developers/conventions.md)** | **[Lifecycle](../developers/lifecycle.md)**
- **[Testing Guide](../developers/testing.md)** | **[Module Catalog](../users/modules.md)**

---

_This document constitutes the operational identity of The Agent within Internara. Non-compliance is
an architectural defect._
