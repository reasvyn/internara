---
name: livewire-refactoring
description: "Apply when refactoring Livewire components to follow the Action-Oriented MVC pattern. This skill ensures components stay thin, business logic moves to Actions, business rules move to Entities, static utilities move to Support, and reusable UI patterns are extracted into shared components."
license: MIT
metadata:
  author: internara
---

# Livewire Refactoring: Action-Oriented MVC

Architecture blueprint for keeping Livewire components thin and maintainable.

## Layer Responsibilities

```
┌─────────────────────────────────────────────────────┐
│                 Livewire Component                   │
│  UI state, form binding, event dispatch,             │
│  delegating to Actions                               │
├─────────────────────────────────────────────────────┤
│                   Action Layer                        │
│  Business logic: validation, persistence,            │
│  side effects (audit, events, notifications)         │
├─────────────────────────────────────────────────────┤
│                   Entity Layer                        │
│  Business rules: state transitions, capability       │
│  checks — pure PHP, no framework dependencies        │
├─────────────────────────────────────────────────────┤
│                   Support Layer                       │
│  Static utilities: helpers, formatters,              │
│  reusable algorithms                                 │
└─────────────────────────────────────────────────────┘
```

## Rules

| # | Rule | File |
|---|------|------|
| 1 | [Action Pattern](rules/01-action-pattern.md) | Actions handle validation + persistence + side effects |
| 2 | [Entity Rules](rules/02-entity-rules.md) | Business rules in pure PHP entities, testable without DB |
| 3 | [Component Structure](rules/03-component-structure.md) | Livewire components only handle UI state and delegation |
| 4 | [Reusable Components](rules/04-reusable-components.md) | Extract repeated UI patterns into shared components |

## Quick Reference

### Livewire Component (allowed)

```php
class UserManager extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $showModal = false;
    public array $formData = [];

    public function save(CreateUserAction $action): void
    {
        $this->validate();
        $action->execute($this->formData);
        $this->showModal = false;
        flash()->success(__('user.created'));
    }

    public function render()
    {
        return view('livewire.user.user-manager', [
            'users' => User::query()->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))->paginate(10),
        ]);
    }
}
```

### Action (business logic)

```php
class CreateUserAction
{
    use HandlesActionErrors;

    public function __construct(
        protected readonly LogAuditAction $logAuditAction,
    ) {}

    public function execute(array $data): User
    {
        $validated = Validator::validate($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
        ]);

        return DB::transaction(function () use ($validated) {
            $user = User::create($validated);
            $this->logAuditAction->execute(...);
            event(new UserCreated($user));
            return $user;
        });
    }
}
```

### Entity (business rules)

```php
final readonly class Apprentice extends BaseEntity
{
    public function __construct(
        private AccountStatus $status,
        private bool $isLocked,
    ) {}

    public function isSuspended(): bool { ... }
    public function canTransitionTo(self $target): bool { ... }
}
```

## Verification Checklist

- [ ] Component has no inline `DB::` or `Model::create/update/delete`
- [ ] Component has no inline `Validator::make()` — rules in Action or FormRequest
- [ ] Component has no inline business rule checks — moved to Entity
- [ ] Component has no static helper methods — moved to Support
- [ ] Repeated modal/table/list patterns extracted to shared component
- [ ] All Actions have a single `execute()` method
- [ ] All Entities are `final readonly` and extend `BaseEntity`
- [ ] Entities have zero framework imports (no Eloquent, no Facades)
