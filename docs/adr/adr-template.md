# ADR Template — Architecture Decision Record Skeleton

> **Last updated:** 2026-08-25 **Changes:** feat — created as the adr-directory skeleton, mirroring the anatomy of existing records (ADR-001…014)

## Description

The structure for every `docs/adr/adr-{slug}.md`. ADRs record a single architectural decision with
its context and consequences so future contributors understand *why* the system looks the way it
does. Numbering is sequential (`ADR-{NNN}`); the slug is kebab-case of the title.

## The Skeleton

```markdown
# ADR-{NNN}: {Title}

> **Last updated:** YYYY-MM-DD **Changes:** {latest change}

## Description

{2–3 sentences: what was decided and the essence of why.}

## Context

{The problem and forces at play — constraints, requirements, prior state.
Cite specs/modules that create the pressure.}

## Decision

{The choice made, stated concretely — what is now true of the system.
Include the mechanism/pattern adopted.}

## Consequences

{What becomes easier, what becomes harder, what neutral trade-offs are accepted.}

## References

- `related-spec-or-module.md` — relevance (markdown link in the real record)
```

Optional sections used by existing records when warranted: `Replaces`, `Comparison: X vs Y`,
domain-specific coverage maps. Keep them after Consequences.

## Writing Discipline

- One decision per record — if two choices were made together, either one ADR covering both
  explicitly or two ADRs cross-referencing.
- Context must be written as if the outcome were unknown — no justifying backwards.
- Superseded ADRs are never deleted; mark them in the body and link the successor, then update
  [index.md](index.md).
- Register every new record in [index.md](index.md) and mention it in the relevant module/spec
  docs.

## Quick References

- [index.md](index.md) — ADR registry
- [pattern-template.md](../guides/arch/pattern-template.md) — sibling skeleton for pattern docs
- [`../doc-template.md`](../doc-template.md) — shared documentation standards
