# Fix-Now vs Issue — Auto-Fix Criteria, Spec-Lagging Fix, and GitHub Issue Standards

> **Last updated:** 2026-08-17 **Changes:** extracted from SKILL.md — comprehensive rewrite

Phase 4 decides which findings are fixed directly in the audit run (minor auto-fix, spec-lagging
catch-up, test-gap fill) and which become GitHub Issues. The split is a scope-discipline device: the
audit **does not implement features** and **does not rewrite the spec casually** — but it IS mandated
to fix its own catch-up work. This rule defines both sides precisely.

---

## Auto-Fix Criteria (fix directly, no GitHub Issue)

Fix directly when **ALL** of these are true:

- **Trivial fix:** Takes < 30 seconds to fix
- **No behavior change:** Fix is purely cosmetic or documentary
- **High confidence:** No ambiguity about the correct fix
- **Low risk:** Fix cannot break anything

**Examples of auto-fixable issues:**

| Finding | Fix |
|---------|-----|
| Broken cross-reference ID | Update the `(ID)` reference in spec |
| Stale `Last updated` metadata | Update date and changes line |
| Typo in class name reference | Fix the typo in spec |
| Missing Quick Reference entry | Add the entry |
| Wrong file path in Quick Reference | Update the path |
| Missing §9 Build Guide text | Write brief build guide |
| Spec section empty with obvious content | Fill from code inspection |
| **Spec lags implementation** | **Update spec immediately** — code is authoritative, spec catches up now |
| **Missing test for spec'd component** | **Write test now** — Test-Gap Fill Rule (spec-traceable) |
| **Failing spec'd test** | **Fix test now** — align to spec |

**Why the four-criteria gate:** the gate keeps "auto-fix" from becoming "uncontrolled edits". A 30-second
metadata correction is safe; a re-written §6 contract is not — that goes to an Issue unless it's a
lagging catch-up (below). If any criterion fails, the finding becomes an Issue.

---

## Spec-Lagging Fix Rule (mandatory)

> **Spec-Lagging Fix Rule (mandatory):** Whenever an audit finding shows the **spec is behind the
> implementation** — the code (or a spec it depends on) documents something the spec doesn't yet —
> fix the spec **immediately in this run**, not as a GitHub Issue. The implementation is the evidence;
> the spec must catch up the moment drift is detected.

**Applies to:**

- Code→Spec drift (code exists, spec doesn't document it)
- Spec whose contracts (signatures, names, paths, values) are older than the code
- Agent guides & skills that document stale values a spec amendment changed (align to spec)

**This overrides the general "no spec rewriting" rule for the lagging case only** — a spec that is
**ahead** of code (missing implementation) still goes to GitHub Issues as a TODO.

**Why it is mandatory:** when the code is the newer, correct artifact, deferring the spec update means
the next implementer follows a wrong spec. The catch-up is documentary — it carries no behavior risk —
so there is no reason to wait.

---

## Test-Gap Fill Rule (mandatory companion)

(Full rule in `run-and-write-tests.md` — referenced here for completeness.)

> Whenever a spec'd component has **no test file** or an FR/NFR has **no traceable test** — and the
> governing code exists — **write the spec-traceable tests now in this run**, not as a GitHub Issue.

**This overrides the general "no code fixing" rule for the test-writing case only** — the audit writes
**tests**, never business logic.

---

## GitHub Issue Criteria (create an Issue when)

- **Non-trivial fix** requiring code changes or **spec rewrites that are NOT a lagging catch-up**
- **Behavior question** — spec and code disagree and it's unclear which is correct
- **Missing implementation** — an FR has no corresponding code (its tests are written once the
  implementation lands — do not write tests for unimplemented behavior)
- **Contract violation** — a class violates its architectural contract

**Why these boundaries:** issues are for work with risk, ambiguity, or scope — exactly the work the
audit must NOT do silently. Note the missing-implementation row: no tests for it, because a test for
code that doesn't exist cannot pass.

---

## Issue Format

Use the `issue-writing` skill template. Issue type depends on finding:

| Finding Type | Issue Type | Label |
|-------------|-----------|-------|
| Spec out of date (code is correct) | `docs` | `docs`, `spec-audit` |
| Code out of date (spec is correct) | `bug` | `bug`, `spec-audit` |
| Missing implementation | `feature` | `enhancement`, `spec-audit` |
| Contract violation | `refactor` | `refactor`, `spec-audit` |
| Behavior disagreement | `bug` or `docs` | `spec-audit` (clarify in body) |

**Issue title format:** `[spec-audit] {type}: {spec_name} — {short description}`

Examples:
- `[spec-audit] bug: authentication.md — LoginAction signature mismatch`
- `[spec-audit] docs: {ID}-password-reset.md — cross-ref ID should reference canonical spec`
- `[spec-audit] feature: profile-management.md — FR-PM-4 has no implementation`
- `[spec-audit] refactor: profile-management.md — ProfileEditor violates Livewire contract`

**Issue body must include:**

- **Spec reference** — which spec, which FR/NFR/section
- **Code reference** — which file, which class, which line
- **Scope** — Code / Testing / Security / Documentation / Dependencies
- **Violation** — which rule/pattern is violated (reference doc and section)
- **Drift direction** — Spec→Code or Code→Spec
- **Evidence** — git log, code inspection, or spec analysis
- **Severity** — Critical / High / Medium / Low
- **Recommendation** — which side to update and why (brief approach)

**Why the body is this dense:** an issue filed from an audit must be actionable days later by a human
or agent without re-running the audit. The body is the audit's memory.

---

## Phase 5 & 6 — Finalize, Commit, Report

- **Update Issues** — phase status, active-work findings, blockers, spec count in Quick References
- **Commit** — after `git status` + `git diff` review:
  ```
  git commit -m "docs(spec-audit): synchronize specs with implementation

  - Auto-fixed: {N} minor issues (cross-refs, metadata, paths)
  - Tests written: {N} (Test-Gap Fill, spec-traceable)
  - Tests run: {N} ({pass/fail})
  - GitHub Issues created: {N} (see issue list)
  - Roadmap updated: {changes}
  - Specs audited: {N} ({scope description})"
  ```
- **Push** — if the user requested it or the scope was a full audit
- **Report** — deliver the visual report (structure in `audit-workflow.md`) even with zero findings

---

## Anti-Patterns / Pitfalls

- **Auto-fixing behavior changes** — the audit never rewrites business logic, only specs/doc + tests.
- **Filing test gaps as issues** — they are filled in-run, per the Test-Gap Fill Rule.
- **Filing spec-lagging drift as an issue** — the spec is ahead-of-code (missing impl) when it's an
  issue; behind-the-code drift is fixed in-run.
- **Writing tests for unimplemented FRs** — wait for the implementation; the FR stays a `feature`
  issue.
- **No `[spec-audit]` prefix in title** — the prefix is how the label and downstream filtering find
  audit issues.
- **Not updating existing issues for duplicates** — check existing issues before filing (prevent
  duplicates).

## Verification / Detection

- Every finding resolved: auto-fixed, issue-filed, or explicitly deferred with a reason.
- Auto-fixed items pass the four-criteria gate; tests written pass before the audit closes.
- Issues carry the `[spec-audit]` prefix, the required body fields, and reference the governing spec
  section.
- No business logic changed during the audit; only specs, docs, guides, and test files were touched.