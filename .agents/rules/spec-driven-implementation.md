# Spec-Driven Implementation — Requirement-Bound Execution

The governing spec is the source of truth for everything feature-building produces. The feature
build is not "implement what the user asked" — it is "implement exactly what the spec defines, and
nothing else." This rule makes that concrete: how to find the spec, what to extract from it, what to
do when it is missing, and how to keep the build anchored to requirement IDs at every stage.

---

## Locate the Governing Spec Before Any Work

**What it enforces:** Before designing or writing a single file, read the governing spec from
`docs/specs/` and list the `FR-*` / `NFR-*` / `UC-*` requirement IDs this feature must satisfy. No
behavior change, feature, or fix may proceed without a corresponding requirement ID.

**Why it matters:** The spec defines intent, requirements, scope, and acceptance criteria. User
wording, ad-hoc reasoning, and existing code can all be wrong or stale — the spec outranks them. A
feature built without a requirement produces orphan code that `spec-audit` must later unwind, and
tests cannot be traced to anything the organization can review. Every future change to the feature
(including the current one) starts by re-reading this spec, so it must be right before the build
starts.

**How to apply:**

1. In Construct, locate the spec via `docs/specs/index.md` — identify the module or feature spec
   that governs this work (foundation, module, or feature).
2. Read the spec fully, especially:
   - **§9 Roadmap** (or equivalent) for prerequisites and build sequence — it can reorder the build.
   - The requirements sections — collect the `FR-*` / `NFR-*` / `UC-*` IDs this feature must satisfy.
   - Data contracts, design decisions, and success metrics — these MUST guide implementation, not
     just the feature description.
3. Keep the requirement list visible through the build. Each slice of work you complete should be
   checkable against at least one ID on that list.
4. Verify paths, class names, and signatures against actual code — do not trust docs blindly. On a
   code/spec mismatch, check git history before deciding which side is authoritative (see
   `AGENTS.md` §Doc Drift Detection).

**Examples:**

A feature spec for registration quota enforcement contains `FR-42` ("system prevents over-quota
placement"). The build order and tests follow from that ID:

- The Command Action `RegisterInternAction` implements the quota check the requirement names.
- The Entity exposes `canRegister()`/`wouldExceedQuota()` returning `bool` so the business rule
  lives in the domain layer.
- The Pest test is titled `REG-42: prevents over-quota placement on concurrent submit` — it traces
  to the requirement, is not padded.

**Pitfalls to avoid:**

- Reading only the feature description and skipping the requirement IDs — this is how "close but
  not what the spec says" implementations happen.
- Proceeding with no spec "because the fix is obvious". If no requirement exists, write it into the
  spec first (`spec-writing` skill) and get user approval — spec-first, never fix-first.
- Trusting an old module doc over the spec when they disagree. The spec is authoritative; align the
  doc to it as part of the work (Dedup-Align Doctrine).

**Verification:** Every completed slice traces to at least one `FR-*` / `NFR-*` / `UC-*` ID; every
test written asserts a spec requirement; no test exists for behavior the spec does not name.

---

## Missing or Incomplete Spec — Stop and Write It First

**What it enforces:** If the feature spec is missing, incomplete, or stale, implementation does not
start. The agent loads `spec-writing`, writes or amends the spec, obtains user approval, and only
then continues the build.

**Why it matters:** Building on a missing or drifting spec bakes wrong assumptions into code and
tests simultaneously. The rework cost of rebuilding a feature is far higher than the cost of writing
the spec up front, and the spec is the shared contract every downstream consumer (pest-testing,
sync-docs, issue-writing) reads.

**How to apply:** In the Construct phase, attempt to locate the spec. If it does not exist, load
`spec-writing`, draft the spec using its 11-section template with requirement IDs, and surface it to
the user for approval before any implementation work. If the spec exists but is incomplete for this
feature, amend it (recording the change in the commit message) before implementing.

**Pitfalls to avoid:**

- Writing code first "to learn what the spec should say", then writing a spec to match the code.
- Amending a spec silently without recording the decision — spec changes are audit events.
- Deferring the missing-spec problem to "later" while building anyway.

**Verification:** No build slice starts without a governing spec file present in `docs/specs/`
referencing this feature's requirement IDs.

---

## Consider at Least Two Approaches Before Deciding

**What it enforces:** The design step compares at least two implementation approaches before
committing. The chosen approach is documented (in the spec's design-decision section or an ADR) with
its rationale.

**Why it matters:** A single-approach design is usually the first idea that came to mind, not the
best one. Comparing alternatives surfaces trade-offs (complexity, performance, architecture
conformance) early, when they are cheap to change, and gives reviewers a baseline for why the chosen
path exists. It also feeds the Recommended Approach section of any issue filed against this work.

**How to apply:** During Design, sketch two candidate solutions for the non-obvious parts of the
feature (e.g., "pessimistic lock in the Action vs. DB unique constraint", "Form Object vs. inline
properties"). Compare them on the invariants that matter — architecture conformance, single-tenant
simplicity, spec compliance — and record the choice plus rationale in the spec's design-decision
section or an ADR. Then implement the chosen one.

**Pitfalls to avoid:**

- Skipping alternatives for "obvious" designs — quota checks, locking, and idempotency are exactly
  the places where the second approach changes the outcome.
- Choosing an approach that violates an invariant (e.g., putting a business rule in the Model) just
  because it is shorter to write.

**Verification:** The feature's design section names at least two approaches and states why the
chosen one won.

---

## Verify Paths, Names, and Signatures Against Code

**What it enforces:** Every class name, file path, and method signature referenced during planning is
checked against the actual codebase before being used. Docs and specs are navigation aids, not
guarantees of what exists.

**Why it matters:** Stale docs cause two classes of failure: referencing a file that does not exist
(build breaks, imports fail), and silently renaming an existing class to match a stale doc (duplicate
class, broken references elsewhere). Both waste an entire feedback cycle.

**How to apply:** Before writing `App\Enrollment\Actions\RegisterInternAction`, confirm the namespace
and directory exist by reading `app/Modules/Enrollment/`, and confirm whether the Action base class is
`BaseCommandAction` in this codebase by reading `app/Modules/Core/Actions/`. When a doc and the code
disagree, check `git log -p -- {file}` to see which changed last, then align the outlier (Doc Drift
Resolution in `AGENTS.md` §Doc Drift Detection).

**Pitfalls to avoid:**

- Blindly following a module reference doc's file table without listing the directory.
- Assuming base classes, DTO bases, and helpers have the exact signature a pattern doc shows.

**Verification:** Every path and class referenced in the implementation plan resolves to an existing
file before code is written; the final `git diff` shows no invented file paths.

---

## References

| Topic                        | Asset                                        |
| ---------------------------- | -------------------------------------------- |
| Feature spec index           | `docs/specs/index.md`                        |
| Spec template & 11 sections  | `.agents/skills/spec-writing/SKILL.md`       |
| Doc drift resolution         | `.agents/rules/architecture-rules.md`  |
| Spec-first doctrine          | `AGENTS.md` §Spec-First Doctrine             |
| Spec↔code sync audit         | `.agents/skills/spec-audit/SKILL.md`         |
