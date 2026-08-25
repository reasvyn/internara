# Guide Template — Operational How-To Skeleton

> **Last updated:** 2026-08-25 **Changes:** feat — extracted from doc-template.md as the foundation-directory home of the operational-guide template

## Description

The structure for how-to guides under `docs/foundation/` (and ops guides in
`docs/infrastructure/`): goal-oriented steps for operators performing a specific task. Assumed
knowledge goes in Prerequisites, not in the body.

## The Skeleton

```markdown
# {Operation}

> **Last updated:** YYYY-MM-DD **Changes:** {latest change}

## Description

{What this achieves and when an operator needs it.}

## Prerequisites

{Access, versions, prior steps.}

## Steps

1. {Action with exact command}
2. …

## Verification

{How to confirm success — expected output, health check.}

## Troubleshooting

| Symptom | Cause | Fix |
|---------|-------|-----|

## Quick References

- `other-operation.md` — related operation (markdown link in the real doc)
```

## Writing Discipline

- One guide = one goal. If steps fork into unrelated goals, split into separate guides.
- Every step ends in an observable result; if a step can silently fail, add a verification line.
- Commands must be copy-pasteable and verified — no pseudoshell placeholders.
- A guide with no troubleshooting table usually means it was never actually followed end-to-end.

## Quick References

- [`doc-template.md`](../doc-template.md) — shared documentation standards
- [`infrastructure/tools.md`](../infrastructure/tools.md) — scanner toolkit referenced by guides
