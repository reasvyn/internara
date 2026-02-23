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
  (`composer test`, `composer lint`, and **PHPStan Level 8**).
- **Privacy Protocol**: Storage or processing of PII is strictly prohibited. Use masking and
  encryption mandates.

---

## 🔄 Standardized Engineering Workflows

Gemini is mandated to operate within the specialized engineering lifecycles defined in the
**[Lifecycle Processes](../docs/developers/lifecycle.md)**. These processes ensure a disciplined,
traceable, and ISO-compliant engineering environment:

1.  **Requirements Analysis**: Traceability check against the SSoT.
2.  **Architectural Design**: Blueprinting and contract formalization.
3.  **Construction**: TDD-First implementation (Services, Models, Livewire).
4.  **Verification & Validation**: Automated testing and manual quality gates.
5.  **Artifact Synchronization**: Documentation and repository closure.

---

## 🛠 Technical Invariants (The Systemic Record)

### 1. Construction Baseline

- **Strict Typing**: Every PHP file MUST declare `declare(strict_types=1);`.
- **Identity**: Mandatory use of **UUID v4** (via `Shared\Models\Concerns\HasUuid`) for all domain
  entities. System-level tables (e.g., `Setting`, jobs) may utilize string keys.
- **Versioning**: Adherence to the **Clean Versioning Standard** (Current: `v0.13.1`).
- **Isolation**: Physical foreign keys across module boundaries are **STRICTLY PROHIBITED**.
- **Logic**: No `env()` calls; utilize `config()` for static values and the `setting()` registry for
  dynamic ones.
- **Helper Organization**: Global functions must reside in individual files within `src/Functions/`
  (one function per file).
- **Service Layer**: Business logic resides in Services extending `EloquentQuery`. Inter-module
  communication MUST use **Service Contracts** (Auto-bound via `BindServiceProvider`).
- **UI**: Implementation utilizes **Tailwind CSS v4**, **maryUI**, and **Livewire v3**. Components
  must be **Thin Components**; UI integration via **Slot Injection**.
- **Branding**: Utilize **Instrument Sans** typography with an **Emerald** accent theme.

### 2. Namespace & Semantic Omission

- **The `src` Omission**: All namespaces MUST omit the `src` segment.
- **Domain Exception**: If Domain Name == Module Name, the domain folder is omitted.
- **Contracts**: Interfaces must NOT have an `Interface` suffix.

### 3. Resilience & Security

- **Logging Discipline**: All `Log::debug()` calls MUST be wrapped in an `is_debug_mode()` check.
- **PII Redaction**: Personally Identifiable Information MUST be masked in all logging sinks.
- **Sanitization**: External input must be validated at the system boundary (PEP).

---

## ⚡ Automated Tooling & Orchestration

The following commands are the authoritative mechanisms for system interaction:

- **Initialization**: `composer setup` (Full environment bootstrapping).
- **Development**: `composer dev` (Concurrent server, queue, logs, and vite).
- **Verification**: `composer test` (Mandatory **Pest v4** suite).
- **Quality**: `composer lint` (Static analysis check) and `composer format` (Auto-fix).
- **Audit**: `php artisan app:info` (Metadata and stack verification).

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

- **[System Specs](../docs/developers/specs.md)** |
  **[Architecture](../docs/developers/architecture.md)**
- **[Conventions](../docs/developers/conventions.md)** |
  **[Lifecycle](../docs/developers/lifecycle.md)**
- **[Testing Guide](../docs/developers/testing.md)** |
  **[Module Catalog](../docs/wiki/modules.md)**
- **[Lifecycle Tutorial](../docs/wiki/README.md)**

---

## 🧠 Gemini Added Memories

- Always exclude ignored directories (e.g. `node_modules/` and `vendor/`) when scanning or searching the codebase. Use flags like `--exclude-dir` or ensure patterns naturally ignore these paths. Also, double-check any decision to use a `force` flag.
- **Sync or Sink**: Code changes without documentation updates are considered incomplete.
- A software engineering baseline consists of three core pillars: (1) Engineering Baseline (including git log, technical standards, architecture, and conventions), (2) Requirements Baseline (authoritative functional and non-functional specifications), and (3) Codebase Baseline (the current stable and verified state of the system used as a reference for future development).
- The user uses the terms "pushing" or "push it" to mean "commit and push".

---

_This document constitutes the operational identity of Gemini within Internara. Non-compliance is an
architectural defect._
