# Spec-Driven Testing — Every Test Traces to a Requirement

> **Last updated:** 2026-08-17 **Changes:** extracted from SKILL.md — comprehensive rewrite

## Intent

A test exists only because a requirement in the governing spec `docs/specs/{ID}-{feature}.md`
(`FR-*`, `NFR-*`, `UC-*`, or a §6 data contract) demands it. Write only the tests the spec requires,
then stop. When auditing, every test must map to a requirement ID (spec gaps filled, orphan tests
removed) and no scenario beyond the spec may be tested.

## Rationale

Coverage means *spec requirements covered*, not lines of code. Two failure modes destroy a suite:

1. **Orphan noise** — tests with no requirement. They look like coverage but verify nothing the spec
   promises; they consume runtime, RAM, and count as "tests" that can fail for reasons unrelated to
   the product.
2. **Padding** — tests beyond the spec (edge-case matrices, CSS classes, framework internals). They
   write down the author's imagination, not the spec, and bloat the suite into an unmaintainable
   archive.

Spec-driven minimalism is deliberate, not lazy:
- **Speed** — spec-scoped tests run in seconds vs. 10+ minutes for the full suite.
- **Resources** — ~2GB+ RAM for the full suite is unaffordable per-edit.
- **Self-explanation** — a suite mapping 1:1 to requirement IDs needs no archaeology to understand:
  each test answers "which `FR-*` / `NFR-*` / `UC-*` does this verify?".

## How to Apply

### Write Only What the Spec Requires

Before writing any test, ask: **"which spec requirement does this verify?"**

- If the answer is "none" → the test is noise — don't write it.
- If the answer is "a requirement has no test" → that is a **spec gap** — write the test now, traced
  to the ID.

When tempted to add "one more test for safety", ask which requirement it verifies; no requirement →
don't write it.

### Test Structure

Test descriptions carry the full spec + requirement ID:

```php
describe("SE5Q9: User account management", function () {
    test("SE5Q9-FR-A4: Admin can approve a pending application", function () {
        // ...
    });
});
```

- `test("{SpecID}-{ReqID}: Test description...")`, grouped under
  `describe("{SpecID}: Test description...")`.
- Scenario is named by the spec (happy path, named rejection, or named alternative).

### Spec-Scenario Checklist

Cover exactly the scenarios the spec defines — nothing more:

- [ ] **Happy path** — the primary success scenario each FR/UC mandates.
- [ ] **Named rejections** — each `RejectedException` / validation rule the spec explicitly lists.
- [ ] **Named alternatives** — alternative flows the spec's use cases describe.

Skip unless a requirement explicitly demands it:
- Edge-case matrices (null/empty/boundary matrices not named in the spec).
- HTTP status code assertions (for API-only changes).
- UI rendering details (CSS classes, DOM structure).
- Mock-orchestration of child Actions (unless the spec requires rollback semantics).

### Spec-Test Audit Scope

When auditing tests against specs, verify these items:

- Every requirement in the spec (`FR-*` / `NFR-*` / `UC-*` / §6 contracts) has a matching test —
  **spec gaps** to fill.
- Every test maps to a requirement ID — **orphan tests** to remove.
- Test descriptions carry the full spec + requirement ID — `test("{SpecID}-{ReqID}: Test
  description...")`, grouped under `describe("{SpecID}: Test description...")`.
- No padding: no tests for behavior the spec doesn't define.
- Feature tests use `LazilyRefreshDatabase` (not `RefreshDatabase`).
- `assertModelExists()` preferred over `assertDatabaseHas()`.
- No Eloquent mocking — use factories + real database.
- `Event::fake()` positioned AFTER factory setup.

### Orphan Test Handling

If a test maps to no current spec requirement:
- Do NOT fix it to make it pass.
- Flag it as orphan noise — candidate for deletion.
- Remove it only when the user approves the trim (per-module spec-driven pruning).

## Examples

```php
// Specific: names the spec + requirement, tests only what the spec promises.
test("SE5Q9-FR-A4: Placement is rejected when the student is already on an active placement", function () {
    $student = createEnrolledStudent();
    $placement = Placement::factory()->active()->for($student)->create();

    $result = (new CreatePlacementAction())->execute(CreatePlacementData::from([...]));

    expect($result->isRejected())->toBeTrue();
});
```

## Anti-Patterns & Pitfalls

- **Padding "safety" tests:** a boundary matrix or an HTTP status assertion the spec never names.
  It is noise by definition.
- **Description without the ID:** `test("approve works")` — cast iron that the test does NOT trace
  to a spec; the ID must be in the description.
- **Fixing an orphan to make it pass** instead of flagging it for removal.
- **Auditing coverage by line counts / percentages** instead of requirement coverage (a test mapping
  1:1 to requirement IDs is the metric).
- **`assertDatabaseHas()` where `assertModelExists()` fits** — weaker and less readable.

## Verification / Detection

- `vendor/bin/pest --testsuite={ModuleName}` — run module tests, then map each to its requirement ID.
- Grep for missing description IDs: `test("` without `-FR-` / `-NFR-` / `-UC-` pattern inside the
  same file.
- Manual audit: for each spec, list its FR/NFR/UC IDs; confirm each has a test (spec gaps) and each
  test's ID exists in the spec (orphans).