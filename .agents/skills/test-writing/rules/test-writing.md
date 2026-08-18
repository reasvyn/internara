# Test Writing — Follow Existing Patterns, Cover the Spec's Scenarios

> **Last updated:** 2026-08-17 **Changes:** extracted from SKILL.md — comprehensive rewrite

## Intent

Write tests by **copying the project's established test patterns**, never inventing new ones, and
cover exactly the scenarios the spec names. Extract repetitive setup into helpers.

## Rationale

Test code is read as often as product code and breaks just as easily. Two failure modes this
prevents:

1. **Pattern divergence.** Every test file that invents its own style — wrong base class, wrong DB
   trait decision, inconsistent assertion style — is a small maintenance tax now and a migration
   burden later. The project's existing tests are the living reference: a Command Action test written
   like other Command Action tests is understood by every future reader.
2. **Scenario sprawl / omission.** A test that covers scenarios the spec never names (padding, see
   `rules/spec-driven-testing.md`) is noise; a test that omits a named rejection is a spec gap. The
   discipline is symmetry: the spec names happy paths, rejections, and alternatives — the test covers
   exactly those.

## How to Apply

### Follow Existing Patterns (Don't Reinvent)

Before writing a test, always read an existing test file of the same type:

- For a **Command Action** test → read another Command Action test.
- For a **Livewire component** test → read another Livewire test.
- For an **Entity** test → read another Entity test.

Copy the structure, imports, and patterns. This avoids:
- Wrong base class usage.
- Missing `LazilyRefreshDatabase` or `use RefreshDatabase` decisions.
- Inconsistent assertion style.

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

### Test Helper Pattern

When test setup is repetitive (e.g., creating the same model hierarchy), extract into a helper:

```php
// In the test file or a shared helper
function createEnrolledStudent(): User
{
    $school = School::factory()->create();
    $department = Department::factory()->for($school)->create();
    $student = User::factory()->student()->create();
    $student->departments()->attach($department);

    return $student;
}
```

## Anti-Patterns & Pitfalls

- **Inventing structure:** a test that picks `RefreshDatabase` where every sibling uses
  `LazilyRefreshDatabase`, or a Livewire test that asserts DOM classes no sibling tests assert.
- **Duplicating setup inline:** the same school→department→student chain pasted into ten tests
  instead of one `createEnrolledStudent()` helper — the drift accumulates when the hierarchy changes.
- **Testing internals:** asserting private behaviour or framework details instead of the spec's
  observable scenario.
- **Wrong-method assertions:** `assertDatabaseHas()` where `assertModelExists()` is the established
  style (see `rules/spec-driven-testing.md`).

## Verification / Detection

- Read at least one sibling test of the same type before writing; the new file should be
  structurally indistinguishable from it.
- Coverage checklist: every spec scenario has exactly one test (happy path, rejections,
  alternatives); nothing beyond the spec is asserted.
- `vendor/bin/pest --testsuite={ModuleName}` — the new test passes and no sibling regressed.