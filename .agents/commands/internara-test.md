---
name: internara-test
description: Test Internara features — spec-driven Pest tests, spec-gap/orphan detection, minimal targeted suites. Use when test, testing, pest, coverage, spec-gap, orphan, or quality is mentioned.
---

# Test Command — Spec-Driven Tests (Internara)

Delegates to the `internara-tester` agent (testing specialist) and the `pest-testing`/`test-writing`
skills. Every test traces to an FR/NFR/UC ID in `docs/specs/*.md`; no orphan tests, no padding.

Scope: $ARGUMENTS

If $ARGUMENTS is empty, ask what behavior to test and which spec/filter to target.

## Steps

1. **Locate the governing spec.** `docs/specs/index.md` → FR/NFR/UC IDs. No spec → ask
   `internara-planner` to write it first.
2. **Map requirement → test 1:1.** Use Pest format `describe("{SpecID}: ...")` +
   `it("{ReqID}: ...")`; factories via Eloquent builder, never mock Eloquent.
3. **Write the minimal suite.** Only the tests the spec requires, then stop. Detect spec gaps
   (requirement with no test) and orphans (test with no requirement).
4. **Verify once, batched.** `vendor/bin/pint --dirty --test` + targeted
   `vendor/bin/pest --testsuite={Module}` or `php artisan test --compact --filter={Class}`. Full suite
   (~2GB+, 10+ min) only on-demand.
5. **Report.** Spec gaps filled, orphans removed, targeted verification result.

## Validation

- [ ] Tests trace to requirement IDs; coverage = requirements covered, not lines
- [ ] No orphan/padded tests; suite runs fast (spec-scoped)