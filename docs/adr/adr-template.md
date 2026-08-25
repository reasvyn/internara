# ADR Template — Architecture Decision Record Skeleton

> **Last updated:** 2026-08-25 **Changes:** rewritten to MADR-lite skeleton — sequential numbering dropped per owner decision, ADRs identified by slug only

## Description

The structure for every `docs/adr/adr-{slug}.md`. ADRs record a single architectural decision with
its context and consequences so future contributors understand *why* the system looks the way it
does. ADRs are **not numbered** — numbers imply a fixed ordering, fight reordering flexibility,
and drift in practice; identity is the slug, chronology lives in the `Date` field and git history.

## The Skeleton

```markdown
# {Title}

> **Last updated:** YYYY-MM-DD **Changes:** {latest change}

| Field | Value |
|-------|-------|
| Status | Accepted |
| Deciders | {name} |
| Date | {original decision date, else YYYY-MM-DD} |
| Technical Story | {spec or issue reference} |

## Context and Problem Statement

{Problem and forces at play — constraints, requirements, prior state.
Ends with an explicit Decision Drivers list.}

## Considered Options

- **{Option A}**
- **{Option B}**
- …

## Decision Outcome

**Chosen option: {X}** — {justification paragraph}

### Positive Consequences

{What becomes easier.}

### Negative Consequences

{What becomes harder; accepted trade-offs.}

## Links

- `related-spec-or-module.md` — relevance (markdown link in the real record)
```

Optional sections used by existing records when warranted: `Replaces`, `Comparison: X vs Y`,
domain-specific coverage maps. Keep them after Links.

## Writing Discipline

- One decision per record — if two choices were made together, either one ADR covering both
  explicitly or two ADRs cross-referencing.
- Context must be written as if the outcome were unknown — no justifying backwards.
- Every Considered Options entry earns its place from real deliberation recorded in prose or git
  history — never invented alternatives.
- Superseded ADRs are never deleted; flip `Status` to `Superseded by …` and link the successor,
  then update [index.md](index.md).
- Register every new record in [index.md](index.md) and mention it in the relevant module/spec
  docs.

## Quick References

- [index.md](index.md) — ADR registry
- [pattern-template.md](../guides/arch/pattern-template.md) — sibling skeleton for pattern docs
- [`../doc-template.md`](../doc-template.md) — shared documentation standards
