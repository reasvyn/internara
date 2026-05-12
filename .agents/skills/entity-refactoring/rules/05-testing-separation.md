# Testing Separation

Model tests need a database. Entity tests don't. Keep them separate.

## Model Tests (need DB)

```php
uses(RefreshDatabase::class);

it('scopes active users', function () {
    User::factory()->active()->create();
    User::factory()->locked()->create();

    $active = User::active()->get();

    expect($active)->toHaveCount(1);
});
```

## Entity Tests (no DB)

```php
it('suspended user cannot log in', function () {
    $entity = new Apprentice(
        status: AccountStatus::SUSPENDED,
        isLocked: false,
    );

    expect($entity->canLogin())->toBeFalse();
});

it('active year cannot be deleted', function () {
    $state = new AcademicYearState(isActive: true);

    expect($state->canBeDeleted())->toBeFalse();
});
```

## File Location

```
tests/
├── Unit/
│   ├── Entities/        ← Pure PHP, no DB needed
│   │   ├── User/ApprenticeTest.php
│   │   ├── AcademicYear/AcademicYearStateTest.php
│   │   └── ...
│   └── Models/          ← Use RefreshDatabase
│       ├── UserTest.php
│       └── ...
└── Feature/             ← Integration tests
    └── ...
```

## What Each Tests

| Test | What It Covers | DB Needed |
|---|---|---|
| `Entities/*Test.php` | Business rules, state transitions, capability checks | No |
| `Models/*Test.php` | Relationships, scopes, casts, accessors | Yes |
| `Actions/*Test.php` | Action orchestration, validation, side effects | Yes (opt-in) |
| `Feature/*Test.php` | End-to-end workflows | Yes |
