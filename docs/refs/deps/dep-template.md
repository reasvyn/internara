# Dep Template — Dependency Reference Skeleton

> **Last updated:** 2026-08-25 **Changes:** feat — created as the deps-directory skeleton, mirroring the established laravel.md anatomy

## Description

The structure for every `docs/refs/deps/{package}.md`: one conceptual reference per runtime
dependency. Filename is kebab-case of the real package name (`spatie-laravel-permission.md`,
not `permissions`). Tightly-coupled families share one doc (e.g., `prettier.md` covers the
prettier plugin family; `marked.md` pairs marked with dompurify).

## The Skeleton

```markdown
# {Package} — Dependency Reference

> **Last updated:** YYYY-MM-DD **Changes:** feat — initial dependency reference for {package} {version}

## Description

Conceptual reference for **{Package}** ({exact installed version}) — {one-line role in Internara}.
Component-specific operations live in guides/infra; this file documents the library itself.

---

## Installed & Role

| | |
|---|---|
| Installed | `{version}` (`composer.json`/`package.json`: constraint) |
| Role | {What the package does inside this app} |

## What It Delivers / Core Concepts

{For releases: notable capabilities of the current major. For libraries: concept table.}

| Concept | What it is |
|---------|-----------|

## How Internara Uses It

{Concrete usage with REAL file paths — verified via grep before writing.
Map framework capability to project conventions/invariants where applicable.}

## Quick References

- Official docs URL (markdown link in the real doc)
- Related internal docs (relative links)
```

## Writing Discipline

- Versions come from lock files only — never from memory or docs.
- Every claim in "How Internara Uses It" must be verifiable in the codebase (grep first).
- Support-lifecycle rows only where the vendor publishes them (e.g., Laravel); otherwise omit.
- Register new docs in [index.md](index.md) under the matching category.

## Quick References

- [../module-template.md](../modules/module-template.md) — sibling template style for modules
- [`../../doc-template.md`](../../doc-template.md) — shared documentation standards
