# Spec Indexing & Lifecycle — IDs, Phases, Dependencies, Splits & Updates

`docs/specs/index.md` is the canonical registry of every spec. It orders specs by **lifecycle phase**
and **dependency depth**, assigns each a unique 5-character ID, tracks dependencies, and records
splits. This rule covers indexing mechanics (IDs, phases, dependencies, add/split) and the spec
lifecycle (how a spec moves from draft to update).

---

## Spec IDs — the 5-Character Registry Key

Every spec file carries a **unique 5-character alphanumeric spec ID** (`XXXXX`, uppercase `A-Z0-9`).
IDs replace the old sequential `#N` numbering.

| Where the ID lives | Format |
|--------------------|--------|
| Filename | `docs/specs/3UOZP-dummy-data.md` |
| File metadata (first metadata line) | `> **Spec ID:** 3UOZP` |
| Index `ID` column | `| 3UOZP | [Dummy Data](3UOZP-dummy-data.md) | …` |
| Link reference | `[Dummy Data](3UOZP-dummy-data.md)` |
| Prose short reference | `(3UOZP)` |

**Rules:**

- **Generate** a fresh random 5-char ID at spec creation — **never reuse a retired ID**.
- **Persist** it in the file's `> **Spec ID:**` metadata line and register the row in
  `docs/specs/index.md` **before committing** — the index is the canonical ID registry.
- **Requirement IDs inside the spec are unchanged** — `FR-*`, `NFR-*`, `UC-*`, `DD-*` stay as-is.
- **Tests** reference specs by ID: test descriptions use the `{SpecID}-{ReqID}: Test description...`
  format, grouped under `describe("{SpecID}: Test description...")`.
- When **renaming** a spec file, keep the ID stable — the filename becomes `{ID}-{new-description}.md`.

**Why random 5-char IDs:** sequential numbers don't survive splits and renames (a renumbered spec
breaks every test and cross-reference); the random ID is stable and collision-proof. "Never reuse a
retired ID" keeps test names and cross-references unambiguous — an old test referring to `3UOZP`
points at exactly one historical feature.

---

## Lifecycle Phases

```
Foundation → Configuration → Identity & Auth → Institutional → Partnerships → Programs → Enrollment → Daily Ops → Assessment → Certification → Reporting → Maintenance
```

Each spec belongs to **exactly one phase**. Assign phase by asking:
**"At what point in the build order does this feature first become usable?"** — not when it's used in
the PKL lifecycle.

| Phase | What goes here | Key Dependencies |
|-------|---------------|-----------------|
| Foundation | PHP/Laravel stack, base classes, utilities, event system, RBAC, middleware | None (everything depends on this) |
| Configuration | Installation, setup wizard, settings, branding, school profile | Foundation |
| Identity & Auth | Authentication, notifications, announcements, dashboards | Foundation + Configuration |
| Institutional | Academic structure (departments, academic years) | Configuration (school profile) |
| Partnerships | External partners (companies, formal partnerships) | Institutional |
| Programs | Internship program definition, grouping | Institutional + Partnerships |
| Enrollment | Student registration, placement, user CRUD, CSV utilities | Programs |
| Daily Operations | Logbook, attendance, supervision, incidents | Enrollment |
| Assessment | Rubrics, scoring frameworks, evaluations, assignments | Enrollment |
| Certification | Templates, credentials, handbooks, media, PDF | Daily Ops + Assessment |
| Reporting | Grade cards, archived snapshots, final lifecycle records | Certification |
| Maintenance | Backup, GDPR, job queues, archiving, system cleanup | Runs continuously after Reporting |

**Why "first becomes usable":** "Assessment" logic is *used* during the PKL lifecycle, but the build
order makes it *usable* only after Enrollment exists — so it belongs in the Assessment phase.
Assigning by usage instead of build order creates dependency cycles in the index.

---

## Dependency Tracking

The index table has a `Depends On` column with spec-ID references to earlier specs. Rules:

- **Every spec must declare its dependencies** — even if it's "none".
- Dependencies are **spec IDs**, not module names.
- A spec may only depend on specs **earlier in the build order** (acyclic).
- If spec A is split from spec B, the new spec **inherits B's dependencies**.
- Cross-module dependencies are explicit (e.g., `T4B26, C8F0D`).

**Why it matters:** the dependency column drives §9 Roadmap (prerequisites + next steps), the index
ordering (dependency depth within a phase), and `spec-audit` cross-reference checks. A missing
dependency hides the build order; a cyclic dependency breaks the phase ordering entirely.

---

## Adding a New Spec

1. **Determine lifecycle phase** (table above)
2. **Determine dependencies** — which earlier specs must be built first?
3. **Generate a unique 5-char alphanumeric ID** and record it in the file's metadata line and
   `docs/specs/index.md`
4. **Add row** to the correct phase table in `docs/specs/index.md`
5. **Update the ASCII flow diagram** if adding a new phase
6. **Update the total count** in the index's metadata line
7. **Set §9 Roadmap**: identify prerequisites (with specific artifacts) and next steps from the
   dependency graph

---

## Splitting a Spec

1. The new spec gets its own spec ID and table row
2. The old spec's entry is **removed** from the index
3. Each new spec declares which original dependencies it inherits
4. Cross-reference related split specs in both files' Quick References
5. Non-Goals in each new spec explicitly list capabilities that moved to siblings

---

## Cross-Reference Conventions

- Split specs must cross-reference siblings in their **Quick References** section
- Use **relative links**: `[other-spec.md]({ID}-other-spec.md)` (same directory)
- Non-Goals should cite the sibling spec that covers the excluded capability
- The Description block must state the split provenance: `"split from {ID}-{original}.md"`

**Why relative links:** the specs directory is self-contained; a relative link survives the repo being
cloned anywhere and keeps `scan_doc_links.py` / `spec-audit` cross-ref checks simple.

---

## Spec Lifecycle

| Phase | Action |
| ----- | ------ |
| Draft | Write initial spec with all 11 sections |
| Review | Verify against code, check completeness |
| Approve | User confirms spec before implementation begins |
| Implement | `feature-building` implements against spec |
| Verify | Tests trace back to FR/NFR IDs |
| Update | If requirements change during implementation, update spec first |

**Documentation-first:** the spec is written BEFORE implementation. Code matches the spec, not the
other way around.

**Why this order matters:** the Implement/Verify steps depend on an approved spec; the Update step is
spec-first so that when implementation discovers a requirement change, the spec is amended first and
code/tests follow — never code first with the spec playing catch-up (that is the drift
`spec-audit` exists to detect).

---

## Verification / Detection

- Every file in `docs/specs/` has a `> **Spec ID:**` metadata line matching its filename prefix.
- Every spec has a row in `docs/specs/index.md`; the index total count matches the actual file count.
- Every spec's dependencies reference earlier-phase spec IDs only (acyclic).
- Tests use `{SpecID}-{ReqID}:` naming against registered IDs.
- After any split/rename, no dangling references to the old filename/ID remain in specs, tests, or
  agent guides (`spec-audit` Area 5/8 verify this).
