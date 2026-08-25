# Issue Quality — Completeness & Actionability

> **Last updated:** 2026-08-25 **Changes:** perketat aturan label — wajib 3 label (Type+Severity P0-P3); sinkron dengan registry baru

A GitHub Issue is only worth filing if a developer or an AI agent can start working from it without
asking a single follow-up question. Every rule below exists because an issue that fails it stalls the
worker — a follow-up question round-trip is the most expensive thing an issue can cause. Quality is
measured on actionability: **can the reader start without context we have and they don't?**

---

## One Issue = One Concern

**What it enforces:** An issue tracks exactly one concern. A bug stay a bug; a feature stays a
feature. Never combine a bug and a feature, or two unrelated bugs, in a single issue.

**Why it matters:** Combined concerns cannot be closed atomically — resolving the bug leaves the
feature half-done and indescribable in a commit, and the issue lingers in the tracker with an
ambiguous true state. Prioritization, severity, labels, and triage all assume one concern per issue;
mixing them corrupts all four.

**How to apply:** Before writing, state the single concern in one sentence. If a second sentence is
needed to describe a different concern, split it into a second issue. Title and body must both focus
on the one concern.

**Pitfalls to avoid:**

- "This issue fixes the registration bug and adds CSV export while I'm here" — two concerns.
- A bug report whose acceptance criteria describe a separate feature.

**Verification:** The title and description address one concern; the labels, severity, and acceptance
criteria all describe that same concern.

---

## Scope Must Be Specific

**What it enforces:** The issue names the concrete module, submodule, files, and behavior — never "Fix
enrollment module" or "Improve the login flow".

**Why it matters:** A broad scope is unknowable scope. A worker reading "Fix enrollment module"
cannot plan, estimate, or verify anything; they must enter a discovery loop the issue should have
already completed. Specific scope ("Prevent duplicate registration on concurrent submit") is
immediately actionable and verifiable.

**How to apply:** Replace broad subjects with the exact behavior or file set. Name the module,
submodule, and affected files in the Scope & Impact table. Quantify the boundary of the work: what
changes, what does not.

**Pitfalls to avoid:**

- "Fix enrollment" — which of the dozen enrollment flows?
- "Add more tests" — for what behavior, traced to which requirement?
- A scope paragraph that describes the entire module instead of the change.

**Verification:** A reader can list the files they will touch and the behavior they will change
without asking questions.

---

## Impact Must Be Measurable

**What it enforces:** Impact statements use numbers and concrete consequences, not feelings.
"System becomes slow" is rejected; "query takes 3s instead of 200ms for 1000 students" is accepted.

**Why it matters:** Measurable impact is what ranking and prioritization run on. "Slow" is relative
to nothing; "3s → 200ms at 1000 students" is actionable evidence that decides severity, urgency, and
which sprint slot the issue gets. It also gives the eventual fix a before/after target to verify
against.

**How to apply:** State the affected population (how many users/records), the current measurement,
and the expected target. Use the Impact description field of the template to narrate
consequence: "This affects all 500+ students during registration week. Every over-quota placement
requires manual cleanup by admin."

**Pitfalls to avoid:**

- Writing "users are confused" — confused vs. blocked, how many, how often?
- Omitting the measurement basis — 3s on what hardware, under what load?
- Quantifying impact for a feature that has no spec yet — note the spec gap instead of inventing
  numbers.

**Verification:** Every impact statement includes a number (population, time, latency, count) and a
target; severity in the Scope & Impact table follows from that measurement.

---

## Recommended Approach Is Mandatory for Technical Issues

**What it enforces:** A technical issue (bug, refactor, perf, architecture) must include a
Recommended Approach section describing HOW to fix it — not just "fix this". When real trade-offs
exist, it must present at least two approaches with pros/cons and name the recommended one.

**Why it matters:** The approach is bankable knowledge. The filing agent or reporter already did the
investigation; discarding it forces the worker to redo analysis from scratch. Two approaches with
trade-offs document that a decision was made deliberately, so review doesn't debate "why not the
other way?" after the work is done.

**How to apply:** For each approach, describe the files changed, the pattern used
(`docs/guides/arch/{pattern}-pattern.md`), and how the data flow changes. List pros and cons
concretely. Mark the recommended one and justify it in Design Decisions.

**Pitfalls to avoid:**

- "Recommended Approach: fix the query" — that's a wish, not an approach.
- One approach with no alternatives when the fix clearly has trade-offs.
- Recommending an approach that violates an invariant (e.g., moving a business rule into a Model).

**Verification:** The Recommended Approach names changed files and a pattern; 2+ approaches with
pros/cons exist when trade-offs are material; the chosen one is flagged.

---

## Design Decisions Are Mandatory for Technical Issues

**What it enforces:** The issue documents the design decisions already made and their rationale —
a decision table of "Decision | Chosen | Rationale", not a prose aside.

**Why it matters:** Unrecorded decisions get re-litigated during code review ("why not optimistic
locking?"), costing the whole team a round-trip the issue author already resolved. The rationale is
the audit trail: it prevents repeated questions and preserves the reasoning if the chosen approach is
later revisited.

**How to apply:** After surveying the problem, capture every choice you made while recommending — the
approach shape, the enforcement point, the data structure. Fill the table; add a narrative example
when the rationale is non-obvious:

| Decision | Chosen | Rationale |
|----------|--------|-----------|
| Locking strategy | Pessimistic lock via `lockForUpdate()` | Optimistic retry logic adds complexity; registration volume is low (< 10/min) so pessimistic is acceptable |
| Where to enforce | Command Action, not DB constraint | Business rule (quota check) belongs in domain layer; DB constraint is defense-in-depth |

**Pitfalls to avoid:**

- A Decisions section that restates Approach A instead of recording the rejected alternatives.
- No rationale column — "chose X" without "why not Y" is half a decision.
- Leaving decisions unwritten because they felt obvious at the time.

**Verification:** Every material choice in the issue has a Chosen value and a Rationale that names
what was considered and rejected.

---

## No Sensitive Information, Ever

**What it enforces:** The issue never includes credentials, API keys, tokens, PII, plaintext
passwords, or personal data. File references use relative paths within the project — never absolute
machine paths that leak environment or username structure.

**Why it matters:** Issues are semi-public records with long life and wide readership (other
developers, AI agents, audit logs, export tooling). A committed credential cannot be un-committed —
rotation is required. Absolute paths in issues break on any other machine and leak authoring
environment details.

**How to apply:** Redact or replace with placeholders (e.g., `<API_KEY>`). Use project-relative
paths (`app/Enrollment/Actions/RegisterInternAction.php`). If the reproduction requires secrets,
describe the shape needed, not the secret itself. Screenshot/log excerpts are scrubbed before
attaching.

**Pitfalls to avoid:**

- Pasting a `.env` value "to help reproduce".
- Including a real database value or a student's name in a bug example.
- An absolute path like `/home/name/...` or `/Users/name/...`.

**Verification:** Scan the issue body for `key`, `token`, `password`, `secret`, credential-shaped
strings, and absolute paths before submitting (see Issue Quality Gates below).

---

## Label According to Type, Using Repo-Defined Labels (+ Label Wajib)

**What it enforces:** Every issue carries **minimal 3 label wajib**: label Type yang sesuai tipenya
**plus** satu Severity (`critical`/`high`/`medium`/`low`) **plus** satu Priority (`P0`/`P1`/`P2`/`P3`)
— lihat `rules/issue-types-and-labels.md` § Label Wajib & § Label Dasar. Semua label dipilih dari
registry repo — tidak ada label ad-hoc.

**Why it matters:** Labels are the tracker's filter dimension. Ad-hoc labels fragment the label space
and silently fall off filters and reports (`scan_issues.py` groups by them). A `bug` issue with no
`bug` label hides from bug dashboards; a mislabeled `security` issue can blow past urgency. Tanpa
label Severity/Priority, issue tidak terurut di board triage meski tipenya benar.

**How to apply:** Select the type first (bug/feature/security/refactor/performance/test/docs/chore),
then apply the matching Type label. Lalu tambahkan **satu** Severity dan **satu** Priority yang
mencerminkan Scope & Impact. Total minimal 3 label; tambah Area/Auxiliary hanya jika relevan.

**Pitfalls to avoid:**

- Inventing a label name mid-issue because "it fits better" (mis. `urgent` alih-alih `P0`).
- Applying the label after the fact from memory without checking `gh label list`.
- Hanya memberi label Type tanpa Severity/Priority — triage akan menolak issue sebagai belum lengkap.

**Verification:** Issue memiliki tepat 1 Type + 1 Severity + 1 Priority dari registry; label ada di
`gh label list`; nilai Severity/Priority di body sama dengan label yang dipasang.

---

## Reference the Spec — Every Issue Traces to a Requirement

**What it enforces:** Spec-first applies to issues too. A bug must reference the `FR-*` / `NFR-*` /
`UC-*` ID it contradicts; a feature/refactor issue must reference its governing spec — or explicitly
note that a spec must be written first. No behavior without a requirement.

**Why it matters:** Issues feed implementation, and implementation without a requirement is orphan
work (Spec-First Doctrine). The ID gives the worker the authoritative intent to test against and
lets `spec-audit` later trace the resolved issue back to its requirement. Claiming a bug without the
contradicted requirement ID makes the fix's correctness unprovable.

**How to apply:** In the Description or Related section, cite `docs/specs/{ID}-{feature}.md` and the
requirement ID. For features/refactors, name the governing spec; if none exists, add a line:
"Spec required — write `docs/specs/{ID}-{feature}.md` before implementation" and mark that as a
blocking prerequisite.

**Pitfalls to avoid:**

- A bug that omits the requirement it violates because the reporter "didn't check".
- A feature issue referencing a spec that no longer contains the requirement — verify the ID in the
  current spec file.

**Verification:** The issue body contains a requirement ID (or an explicit spec-must-be-written
note); a reviewer can open the cited spec and find the requirement.

---

## Deduplicate Before Filing

**What it enforces:** Before creating an issue, check for duplicates — run
`python3 scripts/scan_issues.py` and search existing open issues for the same concern/module. If the
concern is already tracked, link to the existing issue instead of filing a new one.

**Why it matters:** Duplicate issues split attention, double-assign work, and produce divergent
history for one concern (Dedup-Align Doctrine). A second issue is never a "backup copy"; it is noise
that buries the real signal.

**How to apply:** In the Construct phase, run the scanner and read the existing open issues for the
module. If a match exists, either update the existing issue (add evidence) or link it in the Related
section of your new angle — never with the full duplicate body. Only file new when the concern is
genuinely untracked.

**Pitfalls to avoid:**

- Filing "because it's easier than searching" — the search takes minutes, the duplicate lives for
  years.
- Filing a near-duplicate with a different title "to be safe".

**Verification:** The scanner report is checked before filing; the resulting issue has no existing
open issue describing the same concern/module.

---

## Pre-Existing Defects Are Filed Immediately

**What it enforces:** A warning, error, or defect discovered during other work that cannot be safely
fixed in-session (needs design decisions, significant effort, or is out of scope) becomes a GitHub
issue in the same run — not at "the end", not "next time". A defect noticed is a defect tracked.

**Why it matters:** Un-tracked defects evaporate. By the next session the repro details, the exact
warning, and the context are gone. Filing at discovery captures the evidence and unblocks the
current session from carrying it; deferral is how the mysql-index bug lives for six months.

**How to apply:** When you notice a defect you cannot fix safely, open an issue immediately with the
evidence gathered (error message, file:line, repro steps, severity). Continue the current work
afterward. If the root cause is unclear, still file it with what is known — the issue itself
documents the investigation gap.

**Pitfalls to avoid:**

- "I'll file it when I'm done with the feature" — done-features get committed without the issue.
- Fixing a defect that needs a design decision without filing first — behavior-changing fixes need
  the issue's Recommended Approach + Design Decisions to be reviewable.

**Verification:** Every session ends with its notices either fixed or represented by an open issue;
the session report names any deferred defect and its issue URL.

---

## Quality Gates — Verification Before Submitting

**What it enforces:** Before creating the issue, a self-review passes: the issue is understandable
without additional context, technical terms are explained, scope is specific, no sensitive data, no
duplicates, label matches type, spec is referenced. Every section below is checked.

**Why it matters:** The gates are what "actionable" means operationally. Each failed gate is a
follow-up question the worker will ask, and the whole point of the issue is to make that
unnecessary. Gate checks are seconds long at write time and hours long if deferred to review.

**How to apply:** Run the verification checklist from the SKILL.md:

- Title uses the format `{type}: {module}/{submodule} — {description}`
- Description explains the problem, not the solution
- Scope & Impact defines the specific module and files
- Severity (`critical`/`high`/`medium`/`low`) and priority (`P0`/`P1`/`P2`/`P3`) are filled **and match the GitHub labels**
- Recommended Approach has pros/cons
- Design Decisions are documented
- No sensitive information
- Label matches the issue type
- No duplication with existing issues

**Pitfalls to avoid:**

- Submitting after "writing it, so it must be complete".
- Explaining jargon that the target worker (possibly a fresh AI agent) does not know — every
  technical term the issue relies on is spelled out or linked.

**Verification:** The checklists above pass; a colleague fresh to the module can start the issue
without a question.

---

## Destructive Patterns — Never Write These

**What it enforces:** The following issue shapes are recognized failure modes and are never
produced:

- An issue combining a bug + a feature request
- A generic title ("Fix bugs" — which bugs?)
- A description that is just "please fix this"
- A technical issue with no recommended approach
- An issue containing credentials, API keys, or personal data
- A scope so wide it cannot be planned ("Make the app better")

**Why it matters:** These patterns are the exact shapes that fail every gate above. Recognizing them
by name lets the writer self-correct before submission instead of discovering the failure in review.

**How to apply:** Before submitting, read the title and body as a stranger would. If any destructive
pattern matches, rewrite until none do — split the concern, tighten the scope, add the approach,
scrub the secrets.

**Pitfalls to avoid:**

- Keeping a destructive pattern "for internal context" — issues are externally read.

**Verification:** The submitted issue matches none of the destructive patterns.

---

## References

| Topic                            | Asset                                       |
| -------------------------------- | ------------------------------------------- |
| Unified issue template           | `rules/issue-template.md` (this skill)      |
| Issue types & labels             | `rules/issue-types-and-labels.md` (this skill) |
| Pre-existing defects             | `AGENTS.md` §Pre-existing Defects — Fix or File |
| Dedup & alignment                | `AGENTS.md` §Clean Code & Dedup-Align Doctrine |
| Issue scanner                    | `scripts/scan_issues.py`                    |
| Spec-first doctrine              | `AGENTS.md` §Spec-First Doctrine            |