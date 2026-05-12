# Error Handling

Actions throw exceptions for business rule violations. Components catch and display them.

## Exception Hierarchy

```
RuntimeException (base for all Action errors)
├── Business rule violation (entity check failed)
├── Validation failure (already handled by Validator)
└── Infrastructure failure (DB error, file system)
```

## Pattern

```php
class UpdateInternshipAction
{
    use HandlesActionErrors;

    public function execute(Internship $internship, array $data): Internship
    {
        // Business rule check → throws RuntimeException
        if (! $internship->asPeriod()->canTransitionTo(InternshipStatus::from($data['status']))) {
            throw new RuntimeException('Invalid status transition.');
        }

        // Validation → throws ValidationException
        $validated = Validator::validate($data, [...]);

        // Persistence + side effects → wrapped in error handling
        return $this->withErrorHandling(function () use ($internship, $validated) {
            return DB::transaction(function () use ($internship, $validated) {
                $internship->update($validated);
                $this->logAudit->execute(...);
                return $internship;
            });
        }, 'Failed to update internship');
    }
}
```

## `HandlesActionErrors` Trait

```php
trait HandlesActionErrors
{
    protected function withErrorHandling(callable $callback, string $context): mixed
    {
        try {
            return $callback();
        } catch (Throwable $e) {
            Log::error("{$context}: {$e->getMessage()}", [
                'exception' => $e,
            ]);
            throw new RuntimeException($context, 0, $e);
        }
    }
}
```

## Component Side

```php
// ✅ Component catches and displays
class InternshipManager extends Component
{
    public function save(UpdateInternshipAction $action): void
    {
        try {
            $action->execute($internship, $this->formData);
            flash()->success(__('internship.saved'));
        } catch (RuntimeException $e) {
            flash()->error($e->getMessage());
        }
    }
}
```
