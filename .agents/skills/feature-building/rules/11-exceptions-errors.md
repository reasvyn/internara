# Exceptions & Errors

## Hierarchy

All exceptions extend `AppException` (abstract, extends `RuntimeException`):

```
AppException
├── ActionException       // Action execution failures
├── DomainException       // Business rule violations
├── InfrastructureException  // DB, filesystem, external service failures
└── PresentationException    // UI/rendering errors
```

## Error Handling in Actions

Use the `HandlesActionErrors` trait for consistent wrapping:

```php
class CreateUserAction
{
    use HandlesActionErrors;

    public function execute(array $data): User
    {
        return $this->withErrorHandling(function () use ($data) {
            return DB::transaction(function () use ($data) {
                // ...
            });
        }, 'Failed to create user');
    }
}
```

## Business Rule Violations

Throw `RuntimeException` (or `DomainException`) from Actions when a business rule is violated:

```php
if (! $year->asAcademicYearState()->canBeDeleted()) {
    throw new RuntimeException('Cannot delete an active academic year.');
}
```

The Livewire component catches these and shows a flash message:

```php
try {
    $action->execute($year);
    flash()->success(__('deleted'));
} catch (RuntimeException $e) {
    flash()->error($e->getMessage());
}
```

## Debug Functions

Never use `dd()`, `dump()`, `ray()`, `var_dump()`, `print_r()`, or `die()` in application code (enforced by architecture tests).
