# Module Template — Conceptual Overview Skeleton

## Description

The structure for `docs/refs/modules/{module}.md` — the **conceptual** tier of module documentation.
Pure design intent: purpose, boundary, business concepts. No file paths, no class names, no
schemas — those belong in `{module}-reference.md`.

## The Skeleton

```markdown
# {Module} — {One-line Purpose}

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

## Two-Tier Model — Conceptual vs Reference

Every module has **exactly two** documents with a strict separation of concerns:
`{module}.md` (conceptual) and `{module}-reference.md` (reference). The split is enforced — mixing
the two makes docs useless to their two audiences.

| Tier | File | Content | Must NOT contain |
|------|------|---------|-----------------|
| **Conceptual** | `docs/refs/modules/{module}.md` | Purpose, design principles, business rules, module boundary | File paths, class names, schemas, Actions tables, Routes tables |
| **Reference** | `docs/refs/modules/{module}-reference.md` | File paths, class names, table schemas, Actions/Routes tables, dependency graphs | Design rationale, "why" explanations |

When writing or editing a module doc, ask: **"Is this design intent or implementation detail?"**
Design intent → conceptual; implementation detail → reference. Non-module docs (architecture
patterns, infra, foundation) follow the same principle.

Anti-patterns (avoid): schema leakage in a conceptual doc; rationale in a reference doc; a third
doc creeping in (`{module}-notes.md`); duplicated overviews pasted into both tiers.

## Quick References

- [`doc-template.md`](doc-template.md) — shared documentation standards
- [`module-reference-template.md`](module-reference-template.md) — companion reference-tier template
