# Issue Template — Section-by-Section Guidance

> **Last updated:** 2026-08-25 **Changes:** sinkronkan Scope & Impact dengan label wajib P0-P3 & severity critical/high/medium/low

Every issue follows the unified template. This asset explains what each section is FOR — why it
exists, what a weak version looks like, and how to fill it so the issue is actionable. The template
structure itself lives in the SKILL.md; this file is the usage manual for that structure.

---

## Title — `{type}: {module}/{submodule} — {short description}`

**What it enforces:** The title carries the type, the module/submodule path, and a one-line
description of the concern — nothing else.

**Why it matters:** The title is the issue's filter surface: trackers, dashboards, and
`scan_issues.py` group by it. A title that omits type or module makes triage guess; a title that
describes the solution instead of the problem biases the fix.

**How to apply:**

- `bug: enrollment/registration — duplicate entry on concurrent submit`
- `feature: reports/report — add CSV export for grade cards`
- `security: auth/login — rate limit bypass via header manipulation`
- `refactor: user/profile — extract business rules to Entity`

**Pitfalls to avoid:**

- "Fix bug" — no type, no module, no description.
- A title that states the proposed fix ("change the query to ...") rather than the problem.

**Verification:** The title parses into `{type}: {module}/{submodule} — {concern}`, and the type
matches the label applied to the issue.

---

## Description — Describe the Problem, Not the Solution

**What it enforces:** The Description states the PROBLEM concretely, with the failure the user
experiences. It does not prescribe the fix. For bugs: what happened vs. what should have happened.
For features: a user story or problem statement.

**Why it matters:** Describing the problem gives the worker freedom to choose the best fix. A
solution-stated description front-loads implementation decisions without their trade-offs, and a
vague problem ("things are broken") gives no acceptance target. Problem statements are also
reusable evidence for spec-audit when the resolved issue is traced back to a requirement.

**How to apply:**

Bug example — state the concrete failure and the expected behavior:

> When two students submit the registration form simultaneously, both requests pass the quota check
> before either transaction commits, resulting in over-quota placement (1 slot filled by 2
> students).

Feature example — state the user story:

> Coordinators need to export finalized grade cards as CSV for offline verification. Currently the
> only option is on-screen table view.

**Pitfalls to avoid:**

- "The query is slow, make it use an index" — solution-stated; the problem (latency, population) is
  missing.
- A description that reproduces the whole conversation instead of the distilled failure.

**Verification:** The Description names the observable failure and the expected behavior; no fix is
proposed inside it.

---

## Scope & Impact — Module, Files, Severity, Priority

**What it enforces:** The Scope & Impact table names the module, submodule, affected files,
dependencies, severity, and priority; the Impact description narrates consequences for users or
development.

**Why it matters:** This section is the triage payload. Without it, a worker cannot scope their
first edit and the maintainer cannot rank the issue. Severity (how bad is the outcome) and priority
(how urgently must it go) are separate axes and both are explicitly filled.

**How to apply:**

| Field              | Value                                   |
| ------------------ | --------------------------------------- |
| **Module**         | {Module}                                |
| **Submodule**      | {Submodule}                             |
| **Files affected** | `{file}`, `{file}`                      |
| **Dependencies**   | {module or task that is a prerequisite} |
| **Severity**       | `critical` / `high` / `medium` / `low` (label wajib) |
| **Priority**       | `P0` / `P1` / `P2` / `P3` (label wajib) |

> **Label wajib:** `Severity` dan `Priority` di tabel HARUS sama persis dengan label GitHub yang
> dipasang pada issue (`critical`/`high`/`medium`/`low` + `P0`/`P1`/`P2`/`P3`). Lihat
> `rules/issue-types-and-labels.md` § Label Wajib & § Severity vs Priority.

Add a narrative impact line: "This affects all 500+ students during registration week. Every
over-quota placement requires manual cleanup by admin."

**Pitfalls to avoid:**

- A files-affected list that names the whole module directory.
- Severity filled but priority blank (or vice versa) — either gap stalls triage.
- Dependency listed as "none" when a human pipeline (e.g., "must follow the spec rewrite") exists.

**Verification:** Every field in the table has a value; impact is quantified (population, latency,
count).

---

## Reproduction (Bugs Only)

**What it enforces:** Bug issues contain reproducible Steps to Reproduce, Expected Behavior, Actual
Behavior, and Environment. The steps must be repeatable by someone who has never seen the bug.

**Why it matters:** A bug that cannot be reproduced cannot be verified as fixed. Incomplete repro
steps force the worker into investigation loops; missing environment pinning causes
"works-on-my-machine" churn across SQLite/MySQL/PostgreSQL and queue driver differences.

**How to apply:**

1. Steps to Reproduce: numbered, minimal, end-to-end — each step observable.
2. Expected Behavior: what should happen at the last step.
3. Actual Behavior: what actually happens — including the error message verbatim.
4. Environment: PHP version (8.4.x), database (SQLite/MySQL/PostgreSQL), queue driver
   (sync/database/redis), browser (for frontend issues).

**Pitfalls to avoid:**

- "Just try submitting the form" — which form, with what data, how many times?
- Omitting the exact error text — "it errors out" is not evidence.
- Environment left at defaults when the bug is environment-sensitive (e.g., only on PostgreSQL).

**Verification:** Following Steps 1-N reproduces the Actual Behavior exactly; all four subsections
are present.

---

## Acceptance Criteria (Features / Refactors)

**What it enforces:** Feature/refactor issues end with a checklist of acceptance criteria — concrete
conditions every one of which must hold for the issue to close.

**Why it matters:** Without acceptance criteria, "done" is a judgment call that review disputes.
Criteria convert the issue into a verifiable contract: implementation and reviewers check the same
list, and the eventual test suite traces to it.

**How to apply:** Write each criterion as an observable condition, not an aspiration. For a CSV
export issue:

- [ ] `Export Grade Card as CSV` button renders on finalized grade cards only
- [ ] Exported file opens in a spreadsheet app with header row + one row per grade card
- [ ] Export respects the same authorization as the on-screen view

**Pitfalls to avoid:**

- Criteria that restate the solution instead of the outcome ("Refactor the query" vs. "Query executes
  in < 200ms").
- One giant bullet that bundles three outcomes.
- Features without criteria shipped "because the bug part has repro steps".

**Verification:** Each criterion is independently checkable; closing the issue requires all boxes
checked.

---

## Recommended Approach — 2 Options When Trade-Offs Exist

**What it enforces:** The section describes the recommended resolution approach in technical detail
— for bugs, refactors, perf, and architecture issues. When approaches carry significant trade-offs,
at least two are presented with pros/cons each and one is flagged Recommended.

**Why it matters:** This is the banked investigation. The approach's pros/cons are the decision
record; the recommended flag is the decision. Review then challenges the recommendation, not the
void where it should be.

**How to apply:**

- Approach A (Recommended): technical description — files changed, pattern used
  (`docs/architecture/{pattern}-pattern.md`), how the data flow changes — plus Pros and Cons bullets.
- Approach B: the alternative, same depth, its own Pros/Cons.
- The comparison belongs in Design Decisions; the approaches section is the what, not the why.

**Pitfalls to avoid:**

- A single approach with no alternative when locking, retries, or architecture options exist.
- "Approach B: do nothing" — alternatives are concrete options with real trade-offs.
- Recommending a pattern that violates project invariants (C1-C8, D1-D6).

**Verification:** Technical issues contain at least one approach with files + pattern; material
trade-offs have a second approach; the recommended one is flagged.

---

## Design Decisions — Chosen + Rationale Table

**What it enforces:** Design decisions made during the investigation are recorded as a table of
Decision | Chosen | Rationale, with the rationale naming what was rejected and why.

**Why it matters:** The rationale is the audit trail. Re-litigating "why pessimistic locking?" at
code review costs hours; the table answers in seconds and survives to guide future changes.

**How to apply:** Capture every choice the Recommended Approach implies:

| Decision   | Chosen          | Rationale   |
| ---------- | --------------- | ----------- |
| {Decision} | {Chosen option} | {Rationale} |
| {Decision} | {Chosen option} | {Rationale} |

Rationale shape: "chose X over Y because {reason}".

**Pitfalls to avoid:**

- Recording only the chosen option with a rationale that restates it.
- Leaving the table empty for "simple" technical issues — simple choices are still decisions.

**Verification:** Every material choice in the Recommended Approach appears in the table with a
rationale naming the rejected alternative.

---

## Related — Links, Not Copies

**What it enforces:** The Related section links related issues, ADRs, and docs — never duplicates
their content.

**Why it matters:** Linked context is deduplicated, always-current context (Dedup-Align Doctrine).
Copied content drifts when the original changes and inflates the issue.

**How to apply:** Link the related issue (`#123`), the ADR (`docs/adr/index.md` entry), or the doc
with a short descriptor. Use project-relative paths for internal files and `#id` for issues.

**Pitfalls to avoid:**

- Reproducing an ADR's rationale inside the issue.
- Linking a related issue without saying why it is related.

**Verification:** Every Related entry is a link with a one-line reason; no adjacent section duplicates
a linked document's content.

---

## Implementation Notes (for AI Agents)

**What it enforces:** Technical issues include an Implementation Notes section giving the worker the
navigation shortcuts: pattern doc to follow, module context, a reference file, and the applicable
invariants.

**Why it matters:** AI agents and new developers waste their first pass locating the pattern doc and
re-reading invariants they must not break. Pre-wiring these turns "read the whole codebase" into
"open these four links".

**How to apply:**

- Pattern to follow: link to `docs/architecture/{pattern}-pattern.md`
- Module context: link to `docs/modules/{module}.md`
- Reference file: `{path/to/existing/implementation}` already following the pattern
- Note invariants: the relevant `AGENTS.md` / `docs/conventions.md` rules (C1-C8, D1-D6)

**Pitfalls to avoid:**

- Linking a pattern doc that the recommended approach does not actually use.
- Omitting invariants the fix will touch (e.g., a fix inside a Livewire component must note C1).

**Verification:** The worker can open the section's links and start; the invariant list covers every
layer the fix will modify.

---

## References

| Topic                        | Asset                                       |
| ---------------------------- | ------------------------------------------- |
| Unified template (structure) | `issue-writing/SKILL.md` §Unified Issue Template |
| Completeness & actionability | `rules/issue-quality.md` (this skill)       |
| Issue types & labels         | `rules/issue-types-and-labels.md` (this skill) |