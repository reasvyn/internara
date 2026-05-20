# Entity Rules (Refactoring)

## What It Enforces

Inline business rule conditionals in Livewire components and Actions must be extracted into Entity classes. Status comparisons (`if ($x->status === 'active')`), date calculations, and multi-field conditionals are moved to named boolean methods on `final readonly` Entity classes.

## Why It Matters

Inline conditionals scatter business knowledge across the codebase. A status check like `if ($year->is_active && $year->internships()->exists())` appears in multiple places, each potentially with subtle differences. Extract it once as `$year->asAcademicYearState()->canBeDeleted()` and every caller asks the same question the same way. The Entity is testable without a database and becomes the single source of truth for that business rule.

## When It Applies

When refactoring, look for these inline patterns in components and Actions:
- `if ($model->status === 'x')` → Entity method
- `if ($model->end_date < now())` → Entity method
- Nested conditionals checking multiple fields → Entity method
- `if ($status->value === 'suspended')` → Enum method `$status->isTerminal()`

The Entity is `final readonly`, extends `BaseEntity`, and has a `fromModel(Model): static` factory. The Model exposes it through a named accessor (`asAcademicYearState()`). Actions and components call `$model->asEntity()->canX()` — never inline the condition.

Exceptions: Trivial single-property reads without conditional logic: `if ($user->email === null)` can stay inline.
