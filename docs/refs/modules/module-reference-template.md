# Module Reference Template — API Reference Skeleton

## Description

The structure for `docs/refs/modules/{module}-reference.md` — the **reference** tier of module
documentation: complete, dry, factual inventory of the module's public surface. No design
rationale — that lives in `{module}.md`.

## The Skeleton

```markdown
# {Module} Reference — API & Structure

## Description

Complete API reference for the {Module} module: Models, Actions, Routes, Policies, Livewire,
Events. Design rationale lives in `{module}.md`.

## Models

| Model | Table | Purpose |
|-------|-------|---------|

## Actions

| Action | Type | Signature | Notes |
|--------|------|-----------|-------|

## Routes

| Method | URI | Name | Middleware |
|--------|-----|------|------------|

## Policies & Permissions

| Permission | Role(s) | Gate |
|------------|---------|------|

## Events

| Event | Fired by | Listeners |
|-------|----------|-----------|

## Quick References

- `{module}.md` — conceptual overview (markdown link in the real doc)
```

## Writing Discipline

- Every table row must match real code — run `python3 tools/scan_architecture.py` to reconcile
  component counts before publishing.
- Reference docs go stale fastest; update them in the same PR as any Action/Route/Model change.
- Dry tone: state facts, no "why" paragraphs, no usage tutorials.

## Quick References

- [`doc-template.md`](../../doc-template.md) — shared documentation standards
- [`module-template.md`](module-template.md) — companion conceptual-tier template
