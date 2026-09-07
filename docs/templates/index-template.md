# Index Template — Catalog / Hub Skeleton

## Description

The structure for every index document in the repo: the hub that catalogs a set of sibling
documents and gives readers the orientation to navigate them. Live indexes include
`docs/specs/index.md`, `docs/templates/index.md`, `docs/guides/arch/index.md`,
`docs/refs/modules/index.md`, and `docs/refs/deps/index.md`.

## The Skeleton

```markdown
# {Collection} — `{dir path}`

## Description

{One short paragraph: what this collection holds and how it fits the system. The governing
principles this collection serves (e.g., specs are the authoritative source for implementation).}

---

## {Grouping Section}

{Group the catalog by the dimension that matters most (lifecycle phase, area, tier). Each group
gets a table with at least the ID, the document, and its subject. Example:

| ID | Document | Subject | Depends On |
|----|----------|---------|------------|
| ...}

Include a legend/status column where documents carry a lifecycle state (Planned / Partial /
Shipped), and a short legend table explaining the meaning of each value.
```

## Rules

- **One index per collection.** New documents register here under the matching group; remove
  entries when a document is deleted. An index that lags the directory is doc drift.
- **Groups order by dependency.** Where build/reading order matters, sequence groups
  (phases first, then modules in dependency depth order). The index is the entry point, so the
  order it presents is the order readers follow.
- **Every row is a link.** Index rows link to the sibling document with a relative path — no
  unlinked plain text where a link belongs, no absolute paths.
- **Legends match the docs they describe.** A status legend uses the same values the documents
  carry; inventing legend values the docs don't use creates drift.
- **Keep prose minimal.** The index is a catalog, not a chapter — descriptions stay to one
  sentence per entry.

## Quick References

- [`doc-template.md`](doc-template.md) — shared documentation standards
- [`index.md`](index.md) — the live catalog of all templates