# Pattern Template — Architecture Pattern Doc Skeleton

## Description

The structure for `docs/guides/arch/{pattern}-pattern.md` — explanation-first documentation of an
architecture pattern: why it exists, then how to apply it correctly, then how violations look.

## The Skeleton

```markdown
# {Pattern} Pattern

## Description

{What problem this pattern solves and when it applies.}

## Non-Negotiable

{Hard rules. Violations are architecture violations.}

## How to Apply

{Concrete application with a minimal example.}

## Anti-Patterns

| You see… | It should be… | Violation |
|----------|---------------|-----------|

## Quick References

- `../conventions.md` — related invariants (markdown link in the real doc)
```

## Writing Discipline

- Lead with the problem, not the mechanism — a reader who doesn't recognize the problem won't
  recognize the solution.
- Anti-pattern tables mirror the Codebase Senses format used across the project
  ("You see… → It should be…") so agents can pattern-match mechanically.
- Cross-link the invariant IDs (C1–C8, D1–D6) this pattern enforces where applicable.
- Register the pattern in [`architecture/index.md`](index.md).

## Quick References

- [`doc-template.md`](../../doc-template.md) — shared documentation standards
- [`../conventions.md`](../../conventions.md) — architecture invariants C1–C8, D1–D6
