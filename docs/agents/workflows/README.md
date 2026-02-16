# Internara Engineering Protocols

This directory contains the authoritative operational protocols for the Internara project. Every
contribution must adhere to the corresponding protocol to maintain architectural integrity and
documentation synchronization.

---

## 📚 Protocol Catalog

### 1. [Planning](./planning.md)

**Inception Phase**: Focuses on requirement elicitation, SyRS alignment, and feasibility analysis.

### 2. [Designing](./designing.md)

**Architecture Phase**: Focuses on Architectural Blueprinting, Service Contracts, and Data Schema
design.

### 3. [Developing](./developing.md)

**Construction Phase**: Focuses on TDD-First implementation, Modular DDD, and coding invariants.

### 4. [Testing](./testing.md)

**Verification Phase**: Focuses on the V-Model, Architecture tests, and coverage validation.

### 5. [Auditing](./auditing.md)

**Security Phase**: Focuses on identifying vulnerabilities, privacy violations, and compliance
audits.

### 6. [Refactoring](./refactoring.md)

**Realignment Phase**: Focuses on structural integrity, logic hardening, and realignment of existing
code.

### 7. [Documenting](./documenting.md)

**Synchronization Phase**: Focuses on continuous engineering records and Docs-as-Code
synchronization.

### 8. [Publicating](./publicating.md)

**Publication Phase**: Focuses on generating version baseline records and Publication Notes.

### 9. [Deploying](./deploying.md)

**Distribution Phase**: Focuses on library distribution (Packagist/NPM) and registry integrity.

### 10. [Maintenancing](./maintenancing.md)

**Evolution Phase**: Focuses on bug fixes, technical debt management, and software evolution.

---

## ⚖️ General Invariants

- **Laravel Boost**: Adhere to the core rules defined in **[BOOST.md](../BOOST.md)**.
- **Git & GitHub CLI**: Utilize `git` for baseline management and `gh` for tracked work items
  (Issues, PRs, Releases). All tools MUST be free/OSS compatible.
- **Non-Truncation Policy**: It is **STRICTLY PROHIBITED** to simplify, truncate, or reduce
  technical depth in any documentation.
- **Phase Progression**: Every task should ideally follow the **Phase 0 to Phase 6** progression.
- **English Language**: All internal documentation and commit messages MUST be in professional
  English.
- **Zero-Coupling**: Strictly enforce domain isolation across all lifecycle stages.

_These protocols ensure Internara remains a high-fidelity, traceable, and reliable Open-Source
system._
