# Build Order — Canonical Implementation Sequence

> **Last updated:** 2026-08-17 **Changes:** extracted from SKILL.md — comprehensive rewrite

Feature-building implements in a fixed, dependency-respecting order: **Docs → Migration → Model →
Enum → Entity → DTO → Action → Event/Listener → Policy → Livewire → Blade → Route → Tests →
Translations.** This ordering is the single source of truth for the implementation sequence, used by
both the workflow phase and the Design phase. Deviating from it inverts dependencies and produces
compilation breaks, missed registrations, and unverified slices.

---

## The Order Is a Dependency Topology, Not a Suggestion

**What it enforces:** The 14 build steps are executed in the given order. Each step produces an
artifact that the next step depends on (a Model needs the Migration's table; an Entity needs the
Model; a Livewire component needs the Action).

**Why it matters:** The order is bottom-up through the dependency graph of the 4-layer model —
Data/Framework first, Business next, Presentation last, verification and i18n last. Reordering
(e.g., writing the Livewire component before the Action) forces the agent to write code against
signatures that do not exist yet, guarantees multiple rework passes, and makes the diff impossible
to review per slice.

**How to apply:** Treat the numbered list as a checklist with a fixed sequence:

1. **Docs** — documentation-first: draft/update module docs alongside the build, not after.
2. **Migration** — the database table with `onDelete`/`onUpdate` constraints (D6).
3. **Model** — extends `BaseModel`, `#[Fillable]`, relationships, entity bridge.
4. **Enum** — `implements LabelEnum` (+ `StatusEnum` for state machines).
5. **Entity** — `final readonly`, `fromModel()`, business rules.
6. **DTO** — `final readonly`, `BaseData`, `fromArray()`.
7. **Action** — correct triad base, single `execute()`, DTO input, ActionResponse output.
8. **Event + Listener** — only if an async side effect is needed.
9. **Policy** — `BasePolicy`, CRUD methods.
10. **Livewire component** — thin, delegates to Actions.
11. **Blade view** — follows existing view patterns.
12. **Route** — in the correct `routes/web/{module}.php` (or `{submodule}.php` for split
    submodules).
13. **Tests** — one per spec requirement (FR/NFR/UC ID) this feature introduces; never padded.
14. **Translations** — `__()` keys in both `lang/en/` and `lang/id/`.

**Examples:**

A new "internship placement" feature follows: write `create_internships` migration → `Internship`
Model with `#[Fillable]` → `InternshipStatus` enum → `Internship` Entity → `PlacementData` DTO →
`CreatePlacementAction` → `PlacementCreated` event (only if a listener reacts) → `PlacementPolicy` →
`CreatePlacement` Livewire component → Blade view → route `admin/internships/placements` → Pest
tests traced to the spec IDs → `lang/en/internship.php` + `lang/id/internship.php`.

**Pitfalls to avoid:**

- Jumping to the Livewire/Blade slice first "to see something on screen" — this is the most common
  reorder and the most costly to unwind.
- Writing the route before the component it names — the route imports the component class, so the
  class must exist.
- Writing translations before knowing the user-facing strings the views/Actions emit — you will
  rewrite keys twice.

**Verification:** The commits/diffs can be replayed slice by slice in the order above; `git diff`
shows dependencies appearing before dependents.

---

## Design Follows the Same Order

**What it enforces:** The Design phase (§2) plans the solution using the identical build order as
the implementation phase (§4). Design and implementation never use two different sequences.

**Why it matters:** If design plans in a different order than implementation executes, the plan
cannot serve as the implementation's blueprint, and the two phases drift apart — the plan describes
files in one arrangement, the code lands in another. One order, used twice, keeps the plan and the
build verifiable against each other.

**How to apply:** During Design, walk the same 14 steps at planning depth: decide the 4-layer
placement (UI → Business → Data → Framework), the Action Triad role (Command/Read/Process), the DTO
input boundary (3+ params, C7) and ActionResponse output, and which business rules the Entity owns.
Record the intended file structure in build order so Implementation §4 simply executes the plan.

**Pitfalls to avoid:**

- Designing "by feature surface" (screen-first) and implementing bottom-up — the plan and the code
  stop matching halfway through.
- Leaving the Action triad type undecided at design time and picking it during implementation.

**Verification:** The design section's file list is ordered identically to the implemented build
order; no Action in the final code uses a different triad base than the design stated.

---

## Stage Verification for M/L Features

**What it enforces:** For **M/L** tasks, each build-order slice is a stage: verify with `git diff`
plus a targeted check before moving to the next slice. If the feature spans multiple modules or more
than 10 files, split the slices across sessions per Size Triage (inform the user, session plan, then
execute).

**Why it matters:** Unverified slices compound silently. A wrong Entity contract discovered at the
Blade stage means rework across five previous slices. Verifying per slice confines each failure to
its stage, keeps the diff reviewable, and matches the batched-verification strategy (verify once per
stage, not per file, and not all-at-the-end).

**How to apply:** After completing each build slice, run the change-type verification that matches it
(see `agent-workflow` verification matrix) and review `git status` + `git diff`. Classify the feature
S/M/L up front: **L** (>10 files or multi-module) MUST be split into sessions — tell the user in one
short paragraph and list the sessions; never attempt L in a single pass.

**Pitfalls to avoid:**

- Waiting until all 14 slices are written before running any check.
- Verifying every file individually instead of per slice (batching is the rule).
- Treating an L-size feature as one marathon session "because the code is related".

**Verification:** Each slice ends with a `git diff` review and its targeted check before the next
slice begins; L-size features have a documented session plan agreed with the user.

---

## References

| Topic                       | Asset                                        |
| --------------------------- | -------------------------------------------- |
| Size Triage (S/M/L)         | `AGENTS.md §Agent Workflow`     |
| Verification strategy       | `AGENTS.md` §Verification Strategy           |
| 4-layer model & Action triad | `docs/architecture.md`                      |
| Action triad contracts      | `docs/guides/arch/action-pattern.md`        |
