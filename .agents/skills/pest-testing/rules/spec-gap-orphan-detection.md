# Spec-Gap & Orphan Detection — Coverage as Spec Compliance

> **Last updated:** 2026-08-17 **Changes:** extracted from SKILL.md — comprehensive rewrite

## Intent

Coverage is measured in **spec requirements covered**, never in lines of code. This rule defines how
to audit the suite in both directions: a requirement with no test is a **spec gap** that must be
filled; a test with no requirement is **orphan noise** that must be removed. The result of the audit
is a report of requirements covered, gaps still open, and tests removed and why.

## Rationale

A suite that maps 1:1 to requirement IDs is self-explaining and trustworthy. The two failure modes
are symmetric and both corrupt the signal:

- **Spec gap (requirement untested).** The behavior exists in the spec and probably in code, but no
  test protects it. A future refactor breaks it and nothing goes red. Gaps are invisible in a line
  coverage report, which is exactly why coverage-by-lines was abandoned: you can reach 100% line
  coverage while half your requirements are untested.
- **Orphan test (test without a requirement).** A test left behind after its requirement was removed,
  or written for behavior no spec names. Orphans inflate the suite's runtime and RAM (~2GB+ / 10+
  min full) for zero verification value, and — worse — they create false confidence: a green orphan
  looks like coverage but guards an undocumented guarantee that nobody remembers.

Either defect, left unfixed, makes the suite both slower and less reliable than a smaller correct one.

## How to Apply

Run the audit bidirectionally:

**Requirement → Test (find gaps).** Walk the governing spec's requirement list and ask, for each ID:
is there a `test("{SpecID}-{ReqID}: ...")` in the module suite?

| If a requirement … | Then |
|--------------------|------|
| Has no test | It is a **spec gap** — write the test per `rules/spec-driven-minimalism.md` |
| Names an NFR/metric that is untestable at code level | Record it as deliberately not-tested (e.g. "load time"); do not pad to cover it |

**Test → Requirement (find orphans).** Walk the suite and ask, for each test: which current
requirement does this verify?

| If a test … | Then |
|-------------|------|
| Traces to no current requirement | It is **orphan noise** — delete it |

When a spec requirement is removed or rewritten, its tests follow in the same change: removal deletes
the tests, rewrites update them. Never leave a test pointing at a deleted requirement "just in case".

## Examples

Spec `R6BMW-reports.md` lists `FR-1` (generate grade card), `FR-2` (export CSV), and `NFR-1`
(report renders under 2 seconds).

```php
// Audit PASSES if these exist:
test("R6BMW-FR-1: Grade card renders the grading breakdown", ...);
test("R6BMW-FR-2: Report exports accepted rows as CSV", ...);
// NFR-1 is deliberately not-tested (metric, untestable at code level) — noted, not padded.
```

```php
// Audit FAILS on this orphan: it references no current R6BMW requirement
test("R6BMW: report page shows the sidebar", ...);
// → delete it (UI-detail, no requirement)
```

## Anti-Patterns & Pitfalls

- **Given a failing test, deleting the test instead of the requirement.** The spec is the source of
  truth; if the behavior matters, keep the requirement and fix the test — do not prune the guard to
  make CI green.
- **Back-filling "coverage".** Writing padding tests to raise an arbitrary percentage instead of
  closing real requirement gaps (reverse of `spec-driven-minimalism.md`).
- **NFR padding.** Writing a test that asserts "the page loads" to "cover" an NFR that is really a
  performance metric — the test adds no requirement coverage.
- **Orphan tolerance.** Letting a deleted requirement's test linger "in case it comes back" — the
  spec, not nostalgia, decides.

## Verification & Detection

The audit result is part of every test-related report:

- **Report:** requirements covered (with IDs), spec gaps still open, tests removed and why.
- **Scanner preview:** parse test descriptions and compare against spec requirement IDs.

```bash
# All distinct requirement IDs currently covered by tests
rg -o '[A-Z0-9]{5}-(FR|NFR|UC)-[0-9]+' tests/ | sort -u

# All requirement IDs the spec files define (for the gap side)
rg -o '^>? ?\*\*(FR|NFR|UC)-[0-9]+\*\*' docs/specs/*.md | sort -u
```

Targeted verification after filling gaps or pruning orphans:

```bash
vendor/bin/pest --testsuite={ModuleName}
php artisan test --compact --filter={TestName}
```