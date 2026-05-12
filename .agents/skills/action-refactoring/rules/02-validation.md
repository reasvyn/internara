# Validation

The Action is the **authoritative source** of validation. The Livewire component may repeat rules for UX, but the Action is the safety net.

## Structure

```php
class CreateAcademicYearAction
{
    public function execute(array $data): AcademicYear
    {
        $validated = Validator::validate($data, [
            'name' => ['required', 'string', 'max:50'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'is_active' => ['boolean'],
        ]);

        return AcademicYear::create($validated);
    }
}
```

## Rules

- Use `Validator::validate()` — throws `ValidationException` automatically
- Use `Validator::make()` + `->validate()` for custom error messages
- Throw `RuntimeException` for business rule violations (not validation errors)
- Keep validation rules in the Action, NOT in Form Requests (unless shared across multiple Actions)
- The Action validates the final input before persistence — the component's `$this->validate()` is only for UX

## What NOT to do

```php
// ❌ Validation in Livewire component only
class UserManager extends Component
{
    public function save()
    {
        $this->validate(['name' => 'required']); // No Action validation
        User::create($this->formData); // Direct DB access
    }
}

// ❌ No validation in Action
class CreateUserAction
{
    public function execute(array $data): User
    {
        return User::create($data); // No validation!
    }
}
```

```php
// ✅ Validation in both (component for UX, Action for safety)
class UserManager extends Component
{
    public function save(CreateUserAction $action): void
    {
        $this->validate(); // UX: inline errors
        $action->execute($this->formData); // Action validates again
    }
}
```
