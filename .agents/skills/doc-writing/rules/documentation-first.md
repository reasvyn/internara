# Documentation-First (SSOT) — Docs Win Over Code

> **Last updated:** 2026-08-24 **Changes:** git-history window clarified as minimum 7 days — extend longer when drift suspected

## Intent

Documentation is the **single source of truth (SSOT)** for intent and behavior. When a doc and the
code disagree, the doc wins — and the code is aligned to it, not the other way around. Every change
starts with documentation, before code is written, and a change is not complete until the relevant
docs are updated.

## Rationale

Code is an *implementation* of a documented decision; it records *how* something works but not
*why* it works that way. The `why` — design intent, business rules, accepted trade-offs — lives in
docs and specs. If agents treat code as authoritative whenever docs lag, the docs stop being
maintained, drift accelerates, and the project loses its decision history. The SSOT rule breaks that
feedback loop: the doc is the contract, and code that violates it is a bug to fix (or an intentional
behavior change that must be recorded in the doc first).

This is why the **Spec-First Doctrine** (AGENTS.md) matters here: no behavior without a requirement
ID. A doc that describes behavior no spec defines is drifting; a behavior with no doc is
undocumented intent.

## How to Apply

- **Write or update the doc FIRST, then write the code to match.** For a new feature: draft the
  relevant doc/spec, then implement.
- **A change is not done until its docs are done.** Include doc updates in the same commit as the
  code, or the immediately preceding one.
- **When docs and code disagree:** if the code reflects an intentional behavior change, update the
  doc to match and record the decision. If the change was unintentional, fix the code to match the
  doc. Never silently pick the code as "truth" because it runs.
- **Update metadata** on every touched doc: `> **Last updated:** YYYY-MM-DD **Changes:** ...`
  (see `rules/metadata-structure.md`).

## Edit, Don't Rewrite — The Companion Discipline

Full rewrites risk silently dropping details — naming conventions, edge-case notes,
cross-references, or nuance that took effort to capture. **Always prefer targeted edits over full
rewrites.**

| Scenario | Approach |
|----------|----------|
| Update a section | Edit that section only — leave everything else untouched |
| Rename a term across a doc | Use a `replaceAll` edit — never rewrite the file |
| Add new content | Insert at the right position — don't reconstruct the file |
| Restructure headings | Move sections individually — preserve all content |
| Fix a typo | Edit the line — not the whole paragraph |

**When a full rewrite seems necessary:** read the entire file first, confirm no details will be
lost, then proceed. But ask yourself — can this be done with 2-3 targeted edits instead?

## Examples

```markdown
# BAD — rewriting the whole file to fix one paragraph
# (risk: silently losing an edge-case note or a cross-reference)

# GOOD — targeted edit of just the changed section
> **Last updated:** 2026-08-17 **Changes:** fix — corrected enrollment deadline rule
```

Before/after every doc edit, run `git diff` on that file to prove only the intended lines changed
(Edit Policy, AGENTS.md).

## Anti-Patterns & Pitfalls

- **Code-as-truth:** when a doc is stale, "fixing the doc to match the code" without checking git
  history. Both can be stale — check `git log -p` to see which side changed last and why before
  deciding.
- **Rewrite-on-any-change:** recreating a file to make a small edit, dropping undocumented nuance
  in the process. This is the #1 source of silent doc regressions.
- **Docs deferred to "later":** landing code without its doc update. If it's not in this change, it
  won't happen — a change without a doc update is an incomplete change.
- **Silent behavior change:** changing code behavior while leaving the doc stale instead of
  recording the decision.

## Verification / Detection

- `git diff` on touched doc files — only intended lines changed, nothing dropped.
- `python3 scripts/scan_doc_links.py` — flags files whose `Last updated` metadata is missing or
  stale, a first-order signal of docs not updated alongside code.
- `git log --since="7 days ago" --stat` (minimum window; extend to 14/30 days if needed) — cross-check code commits against doc commits; a code
  commit with no doc commit is a candidate SSOT violation.
