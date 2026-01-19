# Database Testing: Persistence Integrity

Database tests in Internara verify that our schemas, model concerns, and complex queries behave as
expected. Given our **Modular Monolith** architecture, database tests are crucial for ensuring
referential integrity in the absence of physical foreign keys.

---

## 1. Schema Verification

Ensure that migrations create the correct column types and indexes.

### 1.1 UUID Integrity

```php
it('uses uuid for primary keys', function () {
    $model = User::factory()->create();

    expect(Str::isUuid($model->id))->toBeTrue();
});
```

---

## 2. Testing Shared Concerns

Since traits like `HasStatuses` and `HasAcademicYear` are shared across modules, we must verify
their behavior in each domain context.

### 2.1 Testing Status Transitions

```php
it('records an audit trail for status changes', function () {
    $reg = InternshipRegistration::factory()->create();
    $reg->setStatus('approved', 'Criteria met.');

    expect($reg->statuses)->toHaveCount(2); // Initial + Approved
});
```

### 2.2 Academic Year Scoping

```php
it('auto-scopes journals to the active academic year', function () {
    setting(['active_year' => '2025/2026']);
    $journal = Journal::factory()->create();

    expect($journal->academic_year)->toBe('2025/2026');
});
```

---

## 3. Best Practices

1.  **Isolation**: Always use the `RefreshDatabase` concern. This resets the database state between
    tests.
2.  **Factories**: Use model factories to generate test data. Keep factories lean and focused on
    valid data.
3.  **No Inter-Module Dependencies**: If testing a relation between `Module A` and `Module B`,
    manually set the ID in the factory rather than using a factory relation.

---

_Database tests protect our "Authoritative Data." They ensure that even as the schema evolves, our
business rules remain consistently applied to our persisted state._
