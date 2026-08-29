# Section Content Rules — PS, Goals, UCs, FRs, NFRs, DDs & Metrics

Each spec section has content rules that turn slack prose into verifiable requirements. These rules
are what make a spec implementable ("the behavior is unambiguous") and testable ("the requirement is
assertable"). A section that breaks these rules produces requirements nobody can test and goals
nobody can check.

---

## §1 Problem Statements

- Each PS must describe a **problem, not a solution**. "Students forget to log their daily activity"
  is a problem; "Add a reminder notification" is a solution — the solution belongs in FRs/DDs.
- PS must explain **why the problem matters** (consequences of not solving it). "This causes manual
  reconciliation of attendance at the end of the month" — not just "this is a problem".
- **Group related problems** — don't create separate PSs for symptoms of the same root cause.

**Failure mode if ignored:** a PS that states a solution ("Add X") duplicates an FR and leaves the
Implementer trying to extract the actual pain; a PS with no consequence makes prioritization
impossible.

---

## §2 Goals & Non-Goals

- **Goals must be measurable or verifiable** — "Reduces administration time" is not; "Reduces
  reconciliation time to under 30 minutes per cohort" is.
- **Non-Goals must be explicit** — they prevent scope creep during implementation. Every NG is a
  boundary: "No multi-tenant support in v1."
- **If a Non-Goal becomes a Goal later, update the spec and add a DD explaining the change** — this
  preserves history and records the decision (Spec-First, decision transparency).

**Failure mode if ignored:** a feature with no Non-Goals accretes scope ("while we're here...")
because nothing is declared out-of-bounds; an NG silently promoted to a goal without a DD hides the
decision from auditors.

---

## §3 User Stories / Use Cases

Each UC has **Actor / Preconditions / Flow / Postconditions**:

- Use cases must cover the **primary path AND important alternatives** — happy path alone leaves
  rejection branches implied.
- **Preconditions must be checkable** — not vague like "system is ready"; instead "student is enrolled
  and placed at company X with an active internship period".
- **Postconditions must be verifiable** — not vague like "user is happy"; instead "the logbook entry is
  persisted with status `draft` and an audit event `LogbookEntryCreated` was dispatched".
- Include **error/edge cases as separate flows when important** — data-loss edges (concurrent edits,
  expired placements) deserve their own UC.

**Failure mode if ignored:** an uncheckable precondition ("user is ready") cannot be arranged in a
test; an unverifiable postcondition cannot be asserted; a missing alternative flow silently drops a
rejection behavior from the implementation.

---

## §4 Functional Requirements

- **Every FR must be uniquely identifiable** (for test traceability) — see `requirement-ids.md`.
- **Atomic — one requirement per line.** "FR-X1: The system must accept daily activity entries and
  allow teachers to mark incidents" is two requirements; split it.
- **Use "must" (mandatory), "should" (strongly recommended), "may" (optional)** — strength drives
  implementation priority and which FRs get tests.
- **Reference data contracts from §6 when FR involves specific data structures** — `FR-X2` cites
  `StudentData` shape rather than re-listing fields.

**Failure mode if ignored:** a compound FR produces a compound test (which fails ambiguously), or a
partial implementation that "covers" the FR leaving the second half unimplemented and undetectable by
`spec-audit`.

---

## §5 Non-Functional Requirements

- **NFR must have a measurable target when possible** (time, size, count) — "Fast" → "page load under
  2s P95", "Secure" → "no plaintext secrets in storage".
- **Separate categories:** Security, Performance, Reliability, Usability, Maintainability.
- **NFR must be testable — if you can't test it, rewrite it.** "Usable" is not testable; "all
  destructive actions require confirmation" is.

**Failure mode if ignored:** an untestable NFR ("the app should feel fast") passes every audit because
nothing can assert it; the claimed guarantee silently rots.

---

## §7 Design Decisions

- **Only document decisions that are non-obvious or have significant trade-offs.**
- **Don't document "obvious" choices** (e.g., "we used PHP because the project is PHP").
- **Each DD must explain what was rejected and why** — a DD with only a rationale and no rejected
  alternative gives no evidence the trade-off was weighed.
- **If a DD is later overturned, update it with a note and add a new DD** — preserve the original
  decision record and add the reversal, rather than rewriting history.

**Failure mode if ignored:** undocumented trade-off decisions get re-litigated weekly by new
contributors; a subtle architectural choice made during implementation but never recorded drifts to
the "no requirement" bucket in spec-audit.

---

## §8 Success Metrics

- **Metrics must be measurable** — not "fast"; use "< 30s", "# of minutes saved per print run".
- **Include both positive metrics** (what should work — "100% of enrollments land in the report") and
  **negative metrics** (what should NOT happen — "zero duplicate log entries in a cohort quarter").
- **Metrics should be achievable** — aspirational but realistic targets; a target nobody can hit
  becomes noise; a target already hit trivially gives no signal.

**Failure mode if ignored:** a metric that can't be measured ("better UX") can't be verified after
release; missing negative metrics allow regressions ("it works" measured only as "it ran") to pass.

---

## §9 Roadmap

- **Prerequisites are enforcement, not decoration** — only list specs whose implementation is a hard
  dependency (code literally calls classes from the prerequisite).
- Prerequisite table must name the **specific artifact** provided (class, method, config key, state
  flag) — not just "Auth module".
- **Build Guide** is 1-2 sentences, active voice, tells the developer what to do next.
- **Next Steps** lists only **direct** downstream specs (not transitive A→B→C).
- **"Connection" column** names the artifact that flows and the mechanism that transfers it.
- **Leaf specs** (no downstream consumers) say "End of lifecycle — no downstream consumers".
- **Foundation specs** (no upstream dependencies) say "No prerequisites — this is a foundational spec".

**Failure mode if ignored:** a decoration prerequisite ("requires Auth to exist") that is actually a
soft nice-to-have blocks implementation orders and drives phantom Issues; a missing artifact name
("what It Provides" = "authentication") leaves the implementer unable to find the dependency.

---

## Verification / Detection

Review each spec section against its section rules; `spec-audit` Area 6 and Area 5 automate: §1 ≥1 PS,
§2 both tables, §3 ≥1 full UC, §4 atomic FRs with IDs, §5 NFRs present, §7 ≥1 DD with all three fields,
§8 metrics with targets, §9 all three subsections present and dependency-resolved.
