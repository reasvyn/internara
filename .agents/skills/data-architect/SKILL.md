---
name: data-architect
description: "SDLC Phase: DESIGN & IMPLEMENTATION. Data architecture — schema (migrations, indexes, FK, UUID), flow (ERD, CQRS, Action triad, lineage), security (PII, encryption, masking, audit), contracts (interfaces, structs, types, enums, DTOs), mapping, and formatting. Single source for any data-related task."
upstream:
  - spec-writing
downstream:
  - code-writing
  - laravel-development
  - arch-guard
  - sync-docs
---

# Data Architect — Schema, Flow, Security & Contracts

> **Prerequisite:** Read `AGENTS.md §Agent Workflow` (mandatory first) and `AGENTS.md §Context Awareness` (orientation). This skill assumes spec-first doctrine and module-first Action architecture.

## When to Activate

Use this skill for **any task that touches data** — not only migrations:

- **Schema:** table design, migrations, FK `onDelete/onUpdate` (D6), indexes, UUID v7 PK, composite indexes
- **Flow:** ERD, lineage, CQRS-inspired Action triad (Command/Read/Process), event flow, cache invalidation flow
- **Security:** PII identification, encryption, masking, `#[Fillable]` (D4), audit trail, RBAC field-level visibility
- **Contract:** interfaces, structs, types, enums (LabelEnum/StatusEnum), DTOs (`BaseData`, `final readonly`), Data Pattern
- **Mapping:** Entity ↔ Model ↔ DTO ↔ Action ↔ Livewire/Blade, request ↔ DTO via FormRequest, external CSV ↔ internal model
- **Formatting:** presentation formatting (dates, numbers, currency via `__()` + `Carbon::locale()`), storage formatting (JSON, decimal precision, timezone UTC)

If the task mentions `schema`, `flow`, `security`, `contract`, `interface`, `struct`, `type`, `DTO`, `data mapping`, `data formatting`, or `data` in any form — load this skill.

## Workflow

Follow `AGENTS.md §Agent Workflow` canonical pipeline. This skill adds data-specific design gates on top of it; it does not replace the pipeline.

### 1. Understand — Governing Spec & Data Impact

- Locate governing spec in `docs/specs/` via FR/NFR/UC IDs; map affected data entities (tables, enums, DTOs).
- Classify data sensitivity (public / internal / PII / restricted) and downstream consumers (Blade, PDF, export, external CSV).

### 2. Plan — Contracts Before Code

- Design contracts first: **Interface → Struct/Type → Enum → Entity → DTO → Model → Migration** — in that order. No model without an entity; no DTO without a `BaseData` type.
- Sketch schema (FK + index + onDelete), flow (Action triad + cache keys in `config/cache-keys.php`), and security (visibility, masking, audit volume) before writing code.
- Consider 2+ schema alternatives; choose normalized vs denormalized with recorded tradeoff (ADR if cross-module).

### 3. Implement — Clean Data Layer

- Implement `final readonly` Entities and `BaseData` DTOs (C5/C6 — no forbidden imports), `#[Fillable]` Models (D4), and migrations with explicit FK behavior (D6).
- Map explicitly — no raw `$request->all()` to create/update (D5); use DTO `fromArray()` / FormRequest.
- Format at the boundary: store UTC + precise type; format for display in Livewire/Alpine/Blade via `__()` and locale-aware `Carbon`/`Number`.

### 4. Verify — Data Quality Gates

- `php artisan migrate --pretend` or `migrate:fresh --seed` (isolated), and arch-guard scans (`scan_violations.py` for C3/C4/D4/D6, `scan_class_contracts.py`, `scan_security.py`, `scan_conventions.py`).
- Verify PII coverage (no plaintext PII in logs via `scan_security.py`), FK/index existence (`scan_conventions.py`), and DTO contract compliance.

### 5. Summarize — Record Decisions

- Add ADR entry if schema/flow/security tradeoff is cross-module, and note mapping/formatting decisions in module reference docs; history via `git log --follow -- <file>` and commit messages.

## Skill Rules

| Rule | Asset | Applies when |
|------|-------|--------------|
| Schema & migrations (UUID v7, FK with onDelete/onUpdate, indexes, composite indexes) | `.agents/rules/schema-migrations.md` | Any table, migration, index, or FK change |
| Data flow & lineage (ERD, CQRS/Action triad, cache invalidation flow, event lineage) | `.agents/rules/data-flow-lineage.md` | Any flow, ERD, or cross-module data movement |
| Data security & privacy (PII, encryption/masking, audit, RBAC field visibility) | `.agents/rules/data-security-privacy.md` | Any PII, sensitive field, or audit requirement |
| Contracts — interfaces, structs, types, enums, Entities | `.agents/rules/contracts-entities-enums.md` | Any interface, struct, type, or enum |
| DTOs & Data objects (BaseData, fromArray, validation, readonly) | `.agents/rules/dtos-data-objects.md` | Any DTO or Data transfer |
| Data mapping (Entity↔Model↔DTO↔Action, request↔DTO, CSV↔model) | `.agents/rules/data-mapping.md` | Any mapping or transformation layer |
| Data formatting (storage vs presentation, i18n dates/numbers, JSON/decimal/UTC) | `.agents/rules/data-formatting.md` | Any formatting, export, or display of data |

## References

| Topic | Doc |
|-------|-----|
| Entity pattern (`final readonly`, fromModel) | `docs/guides/arch/entity-pattern.md` |
| Data/DTO pattern (BaseData, C6) | `docs/guides/arch/data-pattern.md` |
| Model pattern (#[Fillable], D4, D6) | `docs/guides/arch/model-pattern.md` |
| Enum pattern (LabelEnum/StatusEnum) | `docs/guides/arch/enum-pattern.md` |
| Action pattern (triad, DTO for 3+ params C7) | `docs/guides/arch/action-pattern.md` |
| Cache keys (no inline C4) | `docs/guides/arch/cache-pattern.md` · `config/cache-keys.php` |
| Logging & PII masking | `docs/guides/arch/logging-pattern.md` |
| Conventions (D4/D5/D6, security §3, performance §6) | `docs/conventions.md` |
| ADRs for data decisions | `docs/adr/*.md` |
