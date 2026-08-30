# Two-Tier Model — Conceptual vs Reference Docs

## Intent

Every module has **exactly two** documents with a strict separation of concerns: a **conceptual**
doc and a **reference** doc. The separation is not advisory — it is enforced because mixing the two
makes docs useless to their two different audiences.

| Tier | File | Content | Must NOT contain |
|------|------|---------|-----------------|
| **Conceptual** | `docs/refs/modules/{module}.md` | Purpose, design principles, business rules, module boundary | File paths, class names, schemas, Actions tables, Routes tables |
| **Reference** | `docs/refs/modules/{module}-reference.md` | File paths, class names, table schemas, Actions/Routes tables, dependency graphs | Design rationale, "why" explanations |

## Rationale

The two audiences read for different reasons and at different times:

- A **conceptual** doc answers *"why does this module exist, and what rules govern it?"* — read by
  humans (and agents) deciding *whether* and *how* to change behavior. File paths and schema columns
  are noise to this reader and go stale constantly, poisoning the "why" with implementation trivia.
- A **reference** doc answers *"what exists and where?"* — read to locate files, sign a method, or
  check a schema. Design rationale in a reference doc is noise to this reader and, worse, reads like
  a second, competing narrative that drifts from the conceptual doc (content duplication).

The hard split keeps each doc single-purpose, so each stays accurate and cheap to maintain.

## How to Apply

When creating or editing a module doc, ask: **"Is this design intent or implementation detail?"**

- Design intent → conceptual (`docs/refs/modules/{module}.md`)
- Implementation detail → reference (`docs/refs/modules/{module}-reference.md`)

**Non-module docs** (architecture patterns, infrastructure, foundation) follow the same principle:
conceptual docs explain *why*, reference docs explain *what*.

**Concretely:**
- File paths, class names, Actions tables, Routes tables, enum cases, schemas → reference doc.
- Purpose, design principles, business rules, boundary statements, trade-offs → conceptual doc.
- When both docs need the same fact (e.g. an overview of the module), keep the overview in the
  conceptual doc and link to it from the reference doc — never copy it.

## Examples

```markdown
# docs/refs/modules/enrollment.md  (conceptual)
## Description
Enrollment converts approved student applications into active internship placements.
## Business Rules
- A student may hold only one active placement per academic year.
- Placement requires an approved account application and a valid internship.

# docs/refs/modules/enrollment-reference.md  (reference)
## Description
Locator for the Enrollment module's implementation.
## Actions
| Action | File | execute() |
|--------|------|-----------|
| CreatePlacementAction | `app/Modules/Enrollment/Placement/Actions/CreatePlacementAction.php` | `CreatePlacementData` |
```

## Anti-Patterns & Pitfalls

- **Schema leakage:** a conceptual doc listing `internships.registration_start` as a column. The
  schema belongs in the reference doc; the *rule* about when registration opens belongs in
  conceptual.
- **Rationale in reference:** a reference doc explaining *why* Actions exist. The Action Triad's
  rationale lives in `docs/guides/arch/action-pattern.md`; the reference doc lists *which* Actions
  exist.
- **A third doc creeping in:** a `docs/refs/modules/{module}-notes.md`. If a fact doesn't fit the two
  existing docs, it is either misplaced (move it) or not worth documenting (drop it).
- **Duplicated overviews:** the same module summary pasted into both tiers. Authoritative in one
  place, link from the other.

## Verification / Detection

- Every module has exactly one `{module}.md` and one `{module}-reference.md` — no more, no fewer.
- Grep the conceptual doc for `app/`, `::class`, `.php`, `## Actions`, `## Routes` — any hit is a
  tier violation.
- Grep the reference doc for "because", "why", "purpose", "intent" — any hit is a rationale leak.
- `python3 tools/scan_doc_links/cli.py` — catches the file-listing drift that indicates tier mixing.
