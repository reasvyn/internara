# Run-the-Spec's-Tests & Test-Gap Fill — Mandatory Audit Duties

> **Last updated:** 2026-08-17 **Changes:** extracted from SKILL.md — comprehensive rewrite

`spec-audit` does NOT stop at "test files exist". Recent updates made two behaviors **mandatory** in
every audit that touches tests:

1. **Run the audited spec's test suite** and confirm it passes — a failing or absent suite is a
   finding, not a silent pass.
2. **Write spec-traceable tests for any spec'd component that lacks them** — in-run, not deferred,
   not filed as issues.

This rule is the authoritative statement of those two duties. They override the skill's general
"no code fixing / no spec rewriting" rules *only* for the test surface.

---

## Duty 1 — Run the Spec's Tests

### Why it is mandatory

A "test file exists" check is structural only. It does not tell you whether the file is:

- **Stale** — referencing renamed classes/paths (`T-3`)
- **Orphaned** — covering behavior no requirement names
- **Failing** — the suite red but nobody noticed (`T-4`)

A spec whose suite fails is a spec whose contract is broken *today*. Running the suite converts
"assume green" into verified evidence — the same evidence discipline as every other audit area.

### How to apply

```
For each spec'd component:
  Check tests/{Module}/{Component}Test.php exists
  Read test file, compare scenarios against spec FRs
  vendor/bin/pest --testsuite={Module}   # run the spec's tests (targeted filter if available)
```

- Execute the audited spec's suite (module suite, or a targeted filter when the suite is large).
- Treat a **failing test** as finding `T-4` and **fix it immediately** — align the test to the spec
  (or fix code if the spec is authoritative).
- Treat a **missing test file** as finding `T-1` and write it (Duty 2).

### Failure mode if ignored

A failing spec'd suite ships red; nobody knows the contract is broken; the next implementation builds
on a broken baseline and its tests fail for the wrong reason.

---

## Duty 2 — Test-Gap Fill Rule (mandatory)

> **Test-Gap Fill Rule (mandatory):** Whenever an audit finding shows a spec'd component has **no
> test file** or an FR/NFR has **no traceable test** — and the governing code exists — **write the
> spec-traceable tests now in this run**, not as a GitHub Issue. Writing tests for existing code is
> part of the audit workflow (Phase 2, Area 4), not deferred work.

### When to apply it

- Spec'd Action/Livewire/Entity/Policy with no test file → write it
- FR/NFR ID with no covering test scenario → write the scenario
- Stale test that references renamed paths/classes → **update**, don't delete
- A spec marked 🟩 Implemented in the index matrix but with no tests (Spec-Gap) → write tests

### Rules for tests written during audit

- **Follow the `pest-testing` skill:** spec-driven, `describe("{SpecID}: Test description...")` +
  `test("{SpecID}-{ReqID}: Test description...")`, one test per FR/NFR — no orphan tests, no padding.
- **Run the new tests and confirm they pass** before the audit closes.
- **Tests assert the spec, and the spec is authoritative** — if the code contradicts the spec, fix the
  spec first (Spec-First Doctrine), then write/align the test.

### Why "write now" not "file issue"

- The audit's purpose is bidirectional sync; an untested spec'd component is not synced.
- Writing tests for *existing, implemented* code carries zero implementation risk — it is verification
  work, exactly what the audit phase is for.
- Deferring via Issues creates "test debt" issues that compete with feature work and rot.

### The boundary — what the audit may NOT write

The Test-Gap Fill Rule overrides the general "no code fixing" rule **only for the test-writing case**.
The audit writes **tests**, never business logic:

- No new Actions, Entities, controllers, or routes are written during the audit.
- No tests are written for **unimplemented behavior** (an FR with no code) — those tests are written
  once the implementation lands; until then the FR is a `feature` issue (`fix-or-issue.md`).

### Failure mode if ignored

Spec'd components stay untested; coverage gaps are "known but unscheduled"; `spec-audit` outputs
reports that confirm drift instead of closing it. The audit becomes paperwork.

---

## The Spec-Lagging Fix Rule (companion duty)

> **Spec-Lagging Fix Rule (mandatory):** whenever an audit finding shows the **spec is behind the
> implementation** — the code (or a spec it depends on) documents something the spec doesn't yet —
> fix the spec **immediately in this run**, not as a GitHub Issue.

Applies to:

- Code→Spec drift (code exists, spec doesn't document it)
- Spec whose contracts (signatures, names, paths, values) are older than the code
- Agent guides & skills that document stale values a spec amendment changed (align to spec)

**Why it pairs with test-gap fill:** when the code contradicts the spec, the spec is fixed first
(Spec-First), and *then* the new test asserts the corrected contract. Skipping the spec fix means
writing a test against a spec you know is wrong.

---

## Decision-Matrix Reference (test-related rows)

| Finding | Resolution |
|---------|-----------|
| Missing test | **Write tests immediately** — Test-Gap Fill Rule (spec-traceable, per `pest-testing`) |
| Failing test | **Fix test immediately** — align test to spec (or fix code if spec is authoritative) |
| FR not implemented | **Create GitHub Issue** — track as TODO; tests deferred until implementation lands |

---

## Anti-Patterns / Pitfalls

- **Only structural check** — asserting a test file exists without running it or reading its FR
  coverage.
- **Filing a missing test as an issue** — test gaps are closed in-run, never deferred.
- **Writing tests for unimplemented FRs** — until the code exists, the test cannot pass; the FR stays
  an issue and the test waits.
- **Deleting stale tests instead of updating them** — `T-3` stale tests reference renamed things; the
  test logic is usually still valid — update the reference.
- **Fixing code in the name of test-gap fill** — the audit writes tests; changing business logic is
  `code-refactoring`'s job (issue or downstream skill).
- **Skipping the run** — writing a new test and not running it means the audit closes without
  evidence the suite is green.

---

## Verification / Detection

- [ ] The audited spec's test suite was **executed** and is **passing**.
- [ ] Every spec'd Action/Livewire/Entity/Policy has a test file (`{SpecID}-{ReqID}` naming).
- [ ] Every FR/NFR has at least one traceable test scenario.
- [ ] New tests written during the audit were run and pass.
- [ ] No business logic written during the audit (test files and spec fixes only).
- [ ] Any spec-code contradiction was resolved spec-first before tests were written.