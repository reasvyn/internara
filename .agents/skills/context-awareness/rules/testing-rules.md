# Testing Rules — What to Verify

> **Last updated:** 2026-08-17 **Changes:** test naming convention — `describe("{SpecID}: Test description...")` +
> `test("{SpecID}-{ReqID}: Test description...")`; spec-driven doctrine — tests trace to spec requirements,
> coverage measured in requirements not lines; minimalism rationale

This is NOT a replacement for `docs/architecture/testing-pattern.md` or
`docs/infrastructure/testing.md`. Use this as a quick checklist when writing or reviewing tests.

## Core Rule

**Tests verify the spec — nothing more.** A test exists because a requirement in
`docs/specs/{ID}-{feature}.md` (`FR-*` / `NFR-*` / `UC-*` / §6 contract) demands it. A test that cannot
be traced to a requirement is noise: don't write it, and flag existing ones for removal. Coverage is
measured in **spec requirements covered**, not lines of code.

**Write only the tests the spec requires, then stop** — this accelerates development and verification
(spec-scoped tests run in seconds vs. 10+ minutes for the full suite), reduces resource usage (~2GB+
RAM for the full suite), and reduces cognitive overwhelm: a suite mapping 1:1 to requirement IDs is
self-explaining and needs no archaeology to understand.

## Test Structure

```
tests/{Module}/{SubModule}/{Name}Test.php
```

All tests live under `tests/{Module}/` — there is no Unit vs Feature directory split.
Tests that need a database use `LazilyRefreshDatabase`; pure logic tests do not.

## Quick Checklist Per Test

### Every Test

```
[ ] Description prefixes the full spec + requirement ID: test("{SpecID}-{ReqID}: Test description..."), grouped under describe("{SpecID}: Test description...")
[ ] Scenario is named by the spec (happy path or a named rejection/alternative)
[ ] No padding — no edge-case matrices, internals, or framework behavior the spec doesn't name
[ ] If it maps to no requirement → orphan: candidate for deletion
```

### Action Tests (Feature)

```
[ ] Traces to a spec FR/UC
[ ] Uses LazilyRefreshDatabase (not RefreshDatabase)
[ ] Uses real factories, no Mockery for Eloquent
[ ] Tests the spec-defined happy path and the named RejectedException/validation rules
[ ] Uses assertModelExists() over assertDatabaseHas()
[ ] Event::fake() AFTER factory creation (not before)
```

### Livewire Tests (Feature)

```
[ ] Traces to a spec FR/UC (render/mount/authorization only where the spec requires)
[ ] Uses LazilyRefreshDatabase
[ ] Tests render: assertSuccessful(), assertViewIs()
[ ] Tests mutations via Action calls
[ ] Tests validation errors: assertHasErrors()
[ ] Tests authorization: assertForbidden() or actingAs()
[ ] No Eloquent mocking — real factories
```

### Entity/DTO/Enum Tests (Unit)

```
[ ] Tested only when the spec's §6 contract defines them
[ ] No LazilyRefreshDatabase (no DB needed)
[ ] Entity: test only the business-rule methods a requirement names
[ ] DTO: test the shape the spec defines (fromArray()/toArray())
[ ] Enum: label() / validTransitions() only for the cases and rules the spec lists
```

## Mocking Rules

| Scenario        | Use                              | Never Use                     |
| --------------- | -------------------------------- | ----------------------------- |
| Eloquent models | Factories + real DB              | `Mockery::mock(Model::class)` |
| External HTTP   | `Http::fake()`                   | Real HTTP calls               |
| File system     | `Storage::fake()`                | `File::shouldReceive()`       |
| Queue           | `Queue::fake()`                  | Real queue worker             |
| Notifications   | `Notification::fake()`           | Real mail sending             |
| Events          | `Event::fake([Specific::class])` | `Mockery::spy()`              |
| Cache           | `Cache::fake()`                  | `Cache::shouldReceive()`      |
| Auth            | `actingAs($user)`                | Auth facade mock              |
| Cookies         | `Cookie::fake()`                 | `Cookie::shouldReceive()`     |

**Rule of thumb:** If you're using `shouldReceive()`, you're probably doing it wrong. Prefer
`fake()` methods which are scoped to the test and don't leak between tests.

## Spec Coverage Targets

| Signal | Meaning | Action |
| ------ | ------- | ------ |
| Requirement with no test | **Spec gap** | Write the test, traced to the ID |
| Test with no requirement | **Orphan noise** | Candidate for deletion |
| Scenario beyond the spec | **Padding** | Do not write / remove |
