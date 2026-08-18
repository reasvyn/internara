# Layer Test Patterns — Action, Livewire, Entity, DTO, Enum, Policy

> **Last updated:** 2026-08-17 **Changes:** extracted from SKILL.md — comprehensive rewrite

## Intent

Each layer of the architecture is tested at the depth its contract demands. Command and Read Actions
and Livewire components are exercised against the real database; pure-logic layers (Entity, DTO,
Enum) are tested in isolation with no database; Policies are tested per role. The pattern you choose
is determined by the spec requirement you are verifying — never by the shape of the class.

## Rationale

Testing every layer at full depth would be slow, redundant, and brittle. The architecture makes the
right depth obvious:

- **Actions** own all persistence and business flow — they must run against a real database or the
  queries and constraints are never exercised. A mocked Action test verifies nothing.
- **Entities** (`final readonly`, zero I/O) are pure business rules — they need no database at all,
  which keeps their tests the fastest, most stable in the suite.
- **DTOs** are plain data carriers — assert their shape per the spec §6 contract, nothing more.
- **Enums** are pure label/transition maps — test only what the spec lists.
- **Policies** need a model instance to decide on, but the decision itself is a pure boolean — no
  framework services required.

The failure mode of the wrong depth: an Action test with everything mocked (false green), an Entity
test that spins up a database (slow and pointless), or a DTO test that asserts implementation
internals instead of the contract.

## How to Apply — Pattern per Layer

### Command Action

Arrange (factory + DTO) → Act (`execute`) → Assert (`assertModelExists` + `ActionResponse`):

```php
test("{SpecID}-{ReqID}: CreateResource creates the resource", function () {
    $data = CreateResourceData::from([...]);

    $result = app(CreateResourceAction::class)->execute($data);

    expect($result)->toBeInstanceOf(ActionResponse::class);
    expect($result->success)->toBeTrue();
    assertModelExists($result->data);
});
```

A rejection the spec defines is one test with a `throws` modifier:

```php
test("{SpecID}-{ReqID}: FinalizeAction rejects non-finalized records", function () {
    $record = Record::factory()->create(['status' => 'finalized']);

    app(FinalizeAction::class)->execute($record);
})->throws(RejectedException::class);
```

### Read Action

Seed data → execute → assert the typed return and collection shape:

```php
test("{SpecID}-{ReqID}: ReadActiveRecords returns only active records", function () {
    Record::factory()->create(['active' => true]);
    Record::factory()->create(['active' => false]);

    $result = app(ReadActiveRecordsAction::class)->execute();

    expect($result)->toHaveCount(1);
    expect($result->first()->active)->toBeTrue();
});
```

### Entity

Test only the business-rule methods a requirement names; no database:

```php
test("{SpecID}-{ReqID}: Enrollment entity rejects enroll after quota", function () {
    $enrollment = Enrollment::fromModel(
        EnrollmentModel::factory()->make(['seats' => 0])
    );

    expect($enrollment->canEnroll(1))->toBeFalse();
});
```

### DTO

Assert the shape the spec's §6 contract defines; no database:

```php
test("{SpecID}-{ReqID}: CreateEnrollmentData carries the contract fields", function () {
    $data = CreateEnrollmentData::from(['student_id' => 1, 'program_id' => 2]);

    expect($data->student_id)->toBe(1);
    expect($data->program_id)->toBe(2);
});
```

### Enum

Test `label()` / `validTransitions()` only for the cases and rules the spec lists:

```php
test("{SpecID}-{ReqID}: AttendanceStatus labels each spec case", function () {
    expect(AttendanceStatus::Present->label())->toBe(__('Present'));
});

test("{SpecID}-{ReqID}: AttendanceStatus valid transitions", function () {
    expect(AttendanceStatus::Present->validTransitions())
        ->toContain(AttendanceStatus::Late);
});
```

### Livewire

Test render, mount, form submission, and authorization with `actingAs()`:

```php
test("{SpecID}-{ReqID}: AssessmentGrading submits scores for authorized teacher", function () {
    $this->actingAs($teacher)
        ->livewire(AssessmentGrading::class, ['assessment' => $assessment])
        ->call('save')
        ->assertHasNoErrors();
});
```

### Policy

Test `allow` / `deny` for each role; no database beyond the model instance:

```php
test("{SpecID}-{ReqID}: Only the owner may delete an intern document", function () {
    $policy = app(InternDocumentPolicy::class);

    expect($policy->delete($owner, $document))->toBeTrue();
    expect($policy->delete($otherUser, $document))->toBeFalse();
});
```

## Anti-Patterns & Pitfalls

- **Arrange/Act/Assert collapse** — asserting immediately after `execute` without an
  `assertModelExists` (or an `ActionResponse` check) leaves the mutation unverified.
- **Testing Entities/DTOs/Enums with a database trait** — slow, and it signals the test is doing
  more than the layer's contract requires.
- **Over-asserting Livewire internals** — assert the observable outcome (submitted state, no errors,
  redirect), not DOM structure or wire internals.
- **Policy tests that hit the DB or services** — the decision is a pure boolean; only the model
  instance is needed.
- **One giant test per class** — one test per requirement, each independently runnable
  (`rules/spec-driven-minimalism.md`).

## Verification & Detection

- Command/Read Action tests run green against the real database (`LazilyRefreshDatabase`).
- Entity/DTO/Enum tests carry no refresh trait and run green in isolation.
- Every test traces to a spec requirement ID; no test asserts a layer's internals the spec never
  names (`rules/spec-gap-orphan-detection.md`).

```bash
# Pure-logic tests should not carry a DB trait
rg -n "RefreshDatabase" tests/ | rg -i "Entity|Dto|Data|Enum" || true
```
