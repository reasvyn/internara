# Spec-Driven Minimalism — Write Only What the Spec Requires

> **Last updated:** 2026-08-17 **Changes:** extracted from SKILL.md — comprehensive rewrite

## Intent

Tests exist **only** because a requirement in `docs/specs/{ID}-{feature}.md` demands it. Every test
maps to at least one requirement ID (`FR-*`, `NFR-*`, `UC-*`, or a §6 data contract). A test that
cannot be traced to a spec requirement is **noise** and must not be written. Coverage is measured in
**spec requirements covered**, never in lines of code.

The governing rule is: **write only the tests the spec requires, then stop.** Minimal is not thin —
every requirement the spec names (the happy path *and* each named rejection) still gets its test. It
gets exactly that, never more.

## Rationale

This is deliberate, not lazy. A padded suite fails on three axes:

- **Speed** — spec-scoped tests run in seconds versus 10+ minutes for the full suite; faster
  feedback, faster cycles, cheaper verification per change.
- **Resource use** — the full suite consumes ~2GB+ RAM; every padded test wastes RAM, disk, and CI
  time for zero verification value.
- **Cognitive load** — a suite that maps 1:1 to requirement IDs is self-explaining. There is no "why
  does this test exist?" archaeology, no fear that deleting a test breaks an undocumented guarantee.

The failure mode of the opposite doctrine (line coverage targets, "test everything just in case") is
a suite where *more* tests mean *less* signal: engineers stop trusting green, and the suite's runtime
and memory outgrow its verification value. The old per-layer coverage mandates (Enum/Entity/DTO 100%,
Actions ≥90%, Livewire ≥80%) were removed precisely because they produced padding tests; they may be
used only as an internal diagnostic, never as a mandate.

## How to Apply

1. **Read the spec first** (`docs/specs/index.md` → `docs/specs/{ID}-{feature}.md`). List the
   requirement IDs it defines.
2. **Write one test per requirement.** Each test description carries its requirement ID.
3. **Scope each test to the scenarios the spec names:** the happy path, and each rejection /
   validation rule the spec explicitly defines. No scenario beyond those.
4. **When a spec changes, its tests change.** Requirement removed → remove its tests. Requirement
   rewritten → rewrite the test to match. A test left behind with no current requirement is orphaned
   noise — delete it.

### Spec artifact → test mapping

| Spec artifact | Test mapping |
|---------------|--------------|
| `FR-*` Functional Requirement | Feature test verifying the behavior it mandates |
| `NFR-*` Non-Functional Requirement | Test only when the NFR is testable at code level (e.g. auth/security rules); skip metrics like "load time" |
| `UC-*` Use Case | Feature test for the end-to-end flow (happy path + named alternatives) |
| §6 Data contract | Unit test of the DTO/Enum/Entity shape **only if the spec defines it** |
| Nothing in any spec | **Do not write a test** |

## Examples

Spec `YB7RG-authentication.md` names `FR-12` (student can log in with valid credentials) and
`FR-13` (login rejects invalid password). The suite gets exactly these two tests:

```php
test("YB7RG-FR-12: Student can log in with valid credentials", function () {
    $student = Student::factory()->create(['password' => 'secret123']);

    $result = app(LoginAction::class)->execute(LoginData::from([
        'identifier' => $student->identifier,
        'password'   => 'secret123',
    ]));

    expect($result->success)->toBeTrue();
});

test("YB7RG-FR-13: Login rejects invalid password", function () {
    $student = Student::factory()->create(['password' => 'secret123']);

    app(LoginAction::class)->execute(LoginData::from([
        'identifier' => $student->identifier,
        'password'   => 'wrong',
    ]));
})->throws(RejectedException::class);
```

What is **not** written: a matrix of ten wrong-password variants, a test asserting the session cookie
attributes, or a test that "confirms the password column is hashed" — the spec named two requirements,
so the suite names two tests.

## Anti-Patterns & Pitfalls

- **Padding for coverage.** Adding an edge-case matrix, an extra rejection, or a UI-detail assertion
  "for safety" without a requirement ID. Ask: *which requirement does this verify?* — if none, don't
  write it.
- **Testing implementation internals.** Asserting on private methods, query counts, or method call
  order the spec never describes. Coupled and worthless to the requirement.
- **Testing framework behavior.** UUID generation, pagination mechanics, config loading — Laravel
  already tests these; a green test here proves nothing about the spec.
- **Re-anchoring to code instead of spec.** Adding a test because "the method exists" rather than
  because a requirement demands the behavior.
- **Holding onto deleted requirements.** The requirement is gone from the spec but its test lingers
  "just in case" — that is an orphan by definition.

## Verification & Detection

- Every new test in the diff carries a `{SpecID}-{ReqID}:` prefix that resolves to a current
  requirement in `docs/specs/`.
- Every requirement the spec defines has at least one test (`rules/spec-gap-orphan-detection.md`).
- Removing a test should never be blocked by an undocumented guarantee — the spec is the only contract.

```bash
# List test description prefixes to eyeball traceability
rg -n "test\(" tests/ | rg -o '[A-Z0-9]{5}-(FR|NFR|UC)-[0-9]+' | sort -u
```
