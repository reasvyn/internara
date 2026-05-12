# Single Responsibility

One Action class = one business operation. Named `{Verb}{Noun}Action`.

## Structure

```php
class CreateUserAction
{
    public function execute(array $data): User
    {
        // validate → persist → audit → return
    }
}
```

## Do

- One `execute()` method with a clearly named return type
- Constructor injection for dependencies (always `protected readonly`)
- Focused on ONE operation: "create user", not "create user and send email and update settings"

## Don't

```php
// ❌ Multiple operations in one Action
class UserAction
{
    public function create(array $data): User { ... }
    public function update(User $user, array $data): User { ... }
    public function delete(User $user): void { ... }
}
```

```php
// ✅ Separate Actions
class CreateUserAction { public function execute(...): User { ... } }
class UpdateUserAction { public function execute(...): User { ... } }
class DeleteUserAction { public function execute(...): void { ... } }
```

## When to Split

| Signal | Action |
|---|---|
| More than 3 dependencies | Extract smaller Actions |
| Conditional flows (`if` on operation type) | Split into separate Actions |
| Multiple return paths with different meanings | Split |
