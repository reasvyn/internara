# Module Template — Conceptual Overview Skeleton

> **Last updated:** 2026-08-25 **Changes:** feat — extracted from doc-template.md as the modules-directory home of the conceptual-doc template

## Description

The structure for `docs/refs/modules/{module}.md` — the **conceptual** tier of module documentation.
Pure design intent: purpose, boundary, business concepts. No file paths, no class names, no
schemas — those belong in `{module}-reference.md`.

## The Skeleton

```markdown
# {Module} — {One-line Purpose}

> **Last updated:** YYYY-MM-DD **Changes:** {latest change}

## Description

{1–3 sentences: what this module owns and why it exists.}

## Boundary

{What this module handles / explicitly does not handle.}

## Key Concepts

{Domain terms and business rules a newcomer must know.}

## Design Principles

{Decisions shaping the module, each with a one-line rationale.}

## How It Works

{Conceptual flow — Livewire → Action → Entity → Model narrative, no class names.}

## Quick References

- `{module}-reference.md` — full API reference (markdown link in the real doc)
- `../../specs/{ID}-{feature}.md` — governing spec (markdown link in the real doc)
```

## What Belongs Here vs. the Reference Doc

| Question | Conceptual (`{module}.md`) | Reference (`{module}-reference.md`) |
|----------|----------------------------|-------------------------------------|
| Why does this module exist? | Yes | No |
| Which Actions exist and their signatures? | No | Yes |
| What business rule governs X? | Yes (prose) | No |
| What table does Model Y use? | No | Yes |

## Quick References

- [`doc-template.md`](../../doc-template.md) — shared documentation standards
- [`module-reference-template.md`](module-reference-template.md) — companion reference-tier template
