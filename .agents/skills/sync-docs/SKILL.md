---
name: sync-docs
description: Synchronize ALL markdown documentation against actual code implementation. Thin process layer — delegates all content rules to their authoritative source docs. Never hardcode rules that already exist in docs/.
---

# Documentation Sync Skill

## When to Activate

Apply this skill when asked to synchronize, update, refresh, or align documentation with implementation. Covers **every `.md` file in the repository** (excl. `node_modules/`, `vendor/`, `.git/`, `storage/`).

## Core Principle

**Every rule, convention, or pattern belongs in exactly one place: its authoritative doc in `docs/`.** This skill is a thin process layer — it references those docs but never duplicates them. If a rule needs updating, update the authoritative doc, not this skill.

## Workflow

### Step 0 — Load Authoritative References

Read these FIRST; they define what "correct" means:

| # | Document | What it defines |
|---|----------|-----------------|
| 1 | `docs/architecture.md` | Action Triad, 12 layers, Base Class Mandate, exception hierarchy, validation strategy, caching strategy, module invariants |
| 2 | `docs/conventions.md` | PHP rules, naming conventions, migration/factory/seeder rules, cross-cutting protocols |
| 3 | `docs/architecture/action-pattern.md` | What a Command/Read/Process Action looks like, contract rules |
| 4 | `docs/architecture/entity-pattern.md` | Entity purity, fromModel bridge, final readonly contract |
| 5 | `docs/architecture/model-pattern.md` | Model conventions, UUID PKs, fillable, casts |
| 6 | `docs/architecture/livewire-pattern.md` | Thin component rule, Form Objects, BaseRecordManager |
| 7 | `docs/architecture/policy-pattern.md` | Flat RBAC, BasePolicy, three-layer auth |
| 8 | `docs/architecture/enum-pattern.md` | LabelEnum/StatusEnum, state machine patterns |
| 9 | `docs/architecture/event-pattern.md` | BaseEvent contract, dispatch patterns, logging integration |
| 10 | `docs/architecture/testing-pattern.md` | Scope isolation, layer strategies, assertion preferences |
| 11 | `docs/architecture/logging-pattern.md` | SmartLogger dual-channel, PII masking |
| 12 | `docs/modules/module-index.md` | Module boundaries, dependencies, layer mapping |
| 13 | `docs/foundation/product-definition.md` | Product scope, personas, system boundary |
| 14 | `docs/foundation/rbac.md` | Role hierarchy, functional roles, permissions model |
| 15 | `docs/infrastructure/testing.md` | TDD workflow, feature vs unit, LazilyRefreshDatabase |

### Step 1 — Identify Files to Sync

```bash
find . -name "*.md" -not -path "*/node_modules/*" -not -path "*/vendor/*" -not -path "*/.git/*" -not -path "*/storage/*" | sort
```

Group into:

- **SSOT**: Everything under `docs/` — these define what's correct
- **Derivative**: `README.md`, `AGENTS.md`, `GEMINI.md`, `.agents/skills/**/*.md` — these summarize/reference SSOT and must be verified against it

### Step 2 — For Each File, Verify Against Authoritative Docs

For every substantive claim in a `.md` file, trace it back to its authoritative document. If none exists, trace to actual code.

**General approach — do NOT hardcode checks; reference the authoritative doc instead:**

| If the claim is about... | Verify against... |
|--------------------------|-------------------|
| Which base class something extends | `docs/architecture.md` §Base Class Mandate |
| Action contract rules | `docs/architecture/action-pattern.md` |
| Entity rules (purity, fromModel, readonly) | `docs/architecture/entity-pattern.md` |
| Model conventions | `docs/architecture/model-pattern.md` |
| Livewire component patterns | `docs/architecture/livewire-pattern.md` |
| Authorization / policy patterns | `docs/architecture/policy-pattern.md` |
| Enum / state machine patterns | `docs/architecture/enum-pattern.md` |
| Event / notification patterns | `docs/architecture/event-pattern.md` |
| Testing conventions | `docs/architecture/testing-pattern.md` |
| Logging / PII masking | `docs/architecture/logging-pattern.md` |
| Naming / file structure | `docs/conventions.md` |
| Module dependencies | `docs/modules/module-index.md` |
| Class path or file location | Actual file in `app/` or `resources/` |
| Method signature or behavior | Actual method in code |

**Then cross-check against actual code** — does the code actually follow the rules in those docs? If yes, the doc is correct. If no, the doc or the code needs fixing (see Step 4).

### Step 3 — Derivative File Special Audit

`AGENTS.md`, `GEMINI.md`, `README.md` must be checked against **both** the SSOT docs AND actual code. Common pitfalls:

| Derivative file | Likely stale items | Verify against |
|----------------|-------------------|----------------|
| `AGENTS.md` | Stack versions, skill list, quick rules, invariants | `docs/architecture.md`, `docs/conventions.md`, `.agents/skills/` directory, `config/setup.php` |
| `GEMINI.md` | Same as AGENTS | Same as AGENTS |
| `README.md` | Module list, tech stack table, quick start commands | `app/` directories, `composer.json`, `package.json`, actual commands |
| `.agents/skills/*/SKILL.md` | File paths, code examples, class references | Actual files and classes |

### Step 4 — Fix Pattern

| Finding | Action |
|---------|--------|
| Doc claim contradicts authoritative doc | Fix the claim to match the authoritative doc |
| Authoritative doc claim contradicts actual code | Fix the claim in the authoritative doc; add code gap to `known-issues.md` if the code should change |
| Derivative doc contradicts authoritative doc | Fix derivative, authoritative doc wins |
| Broken link | Fix or remove |
| Stale exact count | Replace with generic (e.g. "40+") — counts are not the focus |
| Missing documentation for implemented feature | Add documentation |

### Step 5 — Wrap Up

1. Update `> **Last updated:**` on every edited file
2. Update `docs/doc-index.md` date + changes summary
3. Update `docs/known-issues.md` with new gaps found
4. Run `vendor/bin/pint --format agent`

## Quality Checklist

- [ ] AGENTS.md verified against authoritative docs + code — no stale claims
- [ ] GEMINI.md verified against authoritative docs + code — no stale claims
- [ ] README.md commands work; module listing matches `app/`
- [ ] Skill file paths and class references verified
- [ ] No broken links in docs tree
- [ ] `doc-index.md` up to date
- [ ] `known-issues.md` updated
- [ ] `vendor/bin/pint --format agent` passes
