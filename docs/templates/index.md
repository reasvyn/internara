# Documentation Templates — `docs/templates/`

## Description

Every document type in Internara has a self-contained template in this directory: the skeleton
**plus** the rules governing that type (structure contract, writing discipline, verification gate).
Templates are the SSOT for doc standards — when a task reaches a document's concern, load the
matching template rather than searching for conventions elsewhere. AGENTS.md points here; it does
not duplicate these rules.

## Register Your Doc

- **Create a new document** → copy the matching skeleton, fill it per that template's rules.
- **Create a new template** → add it to this index, and reference it from
  [`../templates/spec-template.md`](spec-template.md) / [`../conventions.md`](../conventions.md) /
  [`../index.md`](../index.md) where doc types are listed.

## Template Catalog

| Template | Applies to | What it governs |
|----------|-----------|-----------------|
| [`doc-template.md`](doc-template.md) | Any general markdown doc | Shared doc standards: Description block, skeleton rule, writing discipline, Quick References footer |
| [`spec-template.md`](spec-template.md) | Feature / module specs in `docs/specs/` | Spec IDs (FR/NFR/UC), problem statement, user stories, data contracts, design decisions, success metrics |
| [`adr-template.md`](adr-template.md) | Architecture Decision Records | Decision context, options, chosen path, consequences; ADR registry linkage |
| [`guide-template.md`](guide-template.md) | How-to guides | Prerequisites, step-by-step, verification of the outcome |
| [`pattern-template.md`](pattern-template.md) | Architecture patterns in `docs/guides/arch/` | Pattern problem, structure, contract, invariants (C1-C8, D1-D6) |
| [`dep-template.md`](dep-template.md) | Dependency references in `docs/refs/deps/` | Installed version, role, usage with verifiable code paths |
| [`module-template.md`](module-template.md) | Module conceptual docs in `docs/refs/modules/` | Two-tier model: business purpose, rules, state, health |
| [`module-reference-template.md`](module-reference-template.md) | Module reference docs | Implementation reference: files, schema, actions, routes, config |
| [`index-template.md`](index-template.md) | Catalog / hub documents | Index structure, grouping by dependency, link integrity |
| [`issue-template.md`](issue-template.md) | GitHub issues | Issue skeleton + quality rules, types, mandatory labels (Type/Severity/Priority), spec tracing |

## Quick References

- [`spec-template.md`](spec-template.md) — most-referenced sibling; specs drive implementation
- [`issue-template.md`](issue-template.md) — pairing for issue-driven docs → spec → code
- [`../index.md`](../index.md) — docs home
- [`../conventions.md`](../conventions.md) — coding and documentation conventions