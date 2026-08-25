# Sub-Skill Delegation — Orchestrator Coordination

> **Last updated:** 2026-08-25 **Changes:** sync — maryUI/DaisyUI → TallstackUI v4 (FB792 0.15.0)

feature-building is an **orchestrator**, not a do-everything skill. It owns the build order, the
spec anchors, and the quality gates; the specialized work is delegated to sub-skills that each own
one concern. This rule defines when to delegate, what to load before touching each concern, and how
to hand off upstream and downstream without duplicating work.

---

## Delegate Each Concern to Its Specialized Sub-Skill

**What it enforces:** Before writing code for a specialized concern, load the matching sub-skill from
the Skill Map — never implement that concern from general knowledge. The rule fires per concern
group, not once per feature.

**Why it matters:** Each sub-skill encodes project-specific contracts the orchestrator cannot hold in
full (TallstackUI table wiring, Spatie MediaLibrary collections, Pulse recorder setup). Writing a concern
without its skill produces code that looks right but violates the module's conventions, which the
arch-guard scanners and code review then reject — a full rework cycle that delegation avoids.

**How to apply:** Load the sub-skill before the first file of that concern is written:

| If the task involves... | Load this skill before that code |
| ----------------------- | -------------------------------- |
| Livewire components     | `livewire-development`           |
| File uploads / media    | `medialibrary-development`       |
| UI / styling / layout   | `tailwindcss-development`        |
| Pulse dashboards        | `pulse-development`              |
| Refactoring verification | `code-refactoring`              |

**Examples:** A feature that adds an internship document upload touches three specialized concerns —
the Livewire upload form loads `livewire-development` (thin component + `WithFileUploads`), the
stored media loads `medialibrary-development` (collection + conversions), and the layout loads
`tailwindcss-development`. The orchestrator coordinates, the sub-skills own the specifics.

**Pitfalls to avoid:**

- Writing the Blade/UI of a feature without `tailwindcss-development` because "it's just markup" —
  TallstackUI/palette conventions are project-specific.
- Handling media uploads inline in the Action without `medialibrary-development` — collection
  registration and conversion configs will be wrong.
- Loading every sub-skill unconditionally "for safety" — load only the skills the feature actually
  touches (unneeded skill loads bloat context).

**Verification:** For each specialized concern in the feature, the matching skill was loaded before
that code was written; no concern was implemented from generic knowledge.

---

## Upstream and Downstream Handoffs

**What it enforces:** The orchestrator knows where work comes from and where it goes. Upstream:
`spec-writing` (feature specs) and `code-refactoring` (refactored code). Downstream: `pest-testing`
(test suite), `sync-docs` (doc updates), and the specialized sub-skills during the build.

**Why it matters:** The implementation pipeline is only as strong as its boundaries. If the
orchestrator silently starts from stale specs (missing the `spec-writing` upstream) or finishes
without feeding `pest-testing` and `sync-docs`, the feature lands with untested requirements and
drifting docs — the two defects the whole pipeline exists to prevent.

**How to apply:** On entry, confirm the upstream contract is satisfied (spec exists with requirement
IDs — see `rules/spec-driven-implementation.md`; refactored code is already extracted to Actions by
`code-refactoring`). On completion, hand off to `pest-testing` (write/verify the spec-traced tests)
and `sync-docs` (align module docs with the shipped code). During the build, hand off per concern to
the sub-skill table above.

**Pitfalls to avoid:**

- Treating the feature as done after the code works in the browser — the pipeline's downstream
  (tests + docs) is part of done.
- Re-implementing upstream work (e.g., rewriting an Action that `code-refactoring` already extracted).

**Verification:** The final report names the downstream handoffs that were completed (test suite
result, doc sync); no upstream contract was re-derived in the orchestrator.

---

## M/L Tasks — Stage by Layer and Verify Each Stage

**What it enforces:** For M/L features, the build is staged by layer/concern and each stage is
verified before the next begins (see `rules/build-order.md` §Stage Verification).

**Why it matters:** An orchestrator that throws all sub-skills at a large feature in parallel
produces an unreviewable diff and compounds cross-layer errors. Staging lets each layer settle and be
verified independently, which is exactly what the orchestrator is positioned to enforce — the
sub-skills each see only their slice.

**How to apply:** Classify the feature size (Size Triage). For M, stage internally and batch
verification with one checkpoint before commit. For L, split into sessions, inform the user with a
session plan, and end each session with `git status` + `git diff` review, targeted verification, and
a short report.

**Pitfalls to avoid:**

- Starting the next layer before the current layer's targeted check passes.
- Letting an L feature expand into one giant uncommitted change set.

**Verification:** The transcript shows per-stage verification before advancing; L features have a
user-approved session plan.

---

## References

| Topic                 | Asset                                        |
| --------------------- | -------------------------------------------- |
| Skill Map             | `AGENTS.md` §Skill Map                       |
| Size Triage           | `.agents/skills/agent-workflow/SKILL.md`     |
| Phase Context (roles) | `feature-building/SKILL.md` §Phase Context   |
