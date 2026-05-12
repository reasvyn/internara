# Entity Delegation

Business rules belong in Entities, not in Actions. Actions call Entity methods to check rules.

## Structure

```php
// ✅ Entity encapsulates the rule
final readonly class AcademicYearState extends BaseEntity
{
    public function canBeDeleted(): bool
    {
        return ! $this->isActive;
    }
}

// ✅ Action delegates to Entity
class DeleteAcademicYearAction
{
    public function execute(AcademicYear $year): void
    {
        if (! $year->asAcademicYearState()->canBeDeleted()) {
            throw new RuntimeException('Cannot delete active academic year.');
        }

        // ... proceed with deletion
    }
}
```

## What to Delegate

| Instead of inline check in Action | Entity method |
|---|---|
| `if ($user->locked_at !== null)` | `$user->asApprentice()->isLocked()` |
| `if ($year->is_active)` | `$year->asAcademicYearState()->canBeDeleted()` |
| `if ($internship->status === 'draft')` | `$internship->asPeriod()->isDraft()` |
| Complex date calculations | `$period->isAcceptingRegistrations()` |
| Multi-field state checks | `$registration->asState()->canBeApproved()` |

## When NOT to Use an Entity

Simple property checks that don't encapsulate business logic can stay inline:

```php
// ✅ Simple check, no entity needed
if ($user->email === null) { ... }

// ❌ Business rule disguised as simple check
if ($user->locked_at !== null && $user->hasRole('student')) { ... }
// → Should be: $user->asApprentice()->canLogin()
```

## Testing Benefit

```php
// Entity test — no database needed
test('active year cannot be deleted', function () {
    $state = new AcademicYearState(isActive: true);
    expect($state->canBeDeleted())->toBeFalse();
});
```
