# Business Rules in Entities

## What It Enforces

ALL business rules — any conditional logic that makes a business decision — must be centralized in
Entity classes. Scattered conditionals in Actions, Livewire components, and Blade templates must be
extracted into named Entity methods.

## Why It Matters

Business rules scattered across the codebase create several problems:

- The same rule is implemented differently in different places (inconsistency)
- Changing a rule requires finding and updating every location (maintenance cost)
- Rules are tested only incidentally through integration tests (test fragility)
- New developers can't find where a business decision is made (discoverability)

Centralizing rules in Entities means the same business decision is always asked the same way:
`$model->asEntity()->canX()`. The rule has one home, one test, and one point of change.

## When It Applies

Any time you see:

- Boolean capability checks: `if ($year->is_active && !$year->internships()->exists())` →
  `$year->asAcademicYearState()->canBeDeleted()`
- State transition logic: `if ($registration->status === 'pending' && $registration->placement_id)`
  → `$registration->asRegistrationState()->canBeApproved()`
- Date calculations with status checks → `$period->isAcceptingRegistrations()`
- Complex permissions combining multiple fields → Entity method with clear name

Enum methods (like `$status->isTerminal()`, `$status->canTransitionTo($target)`) follow the same
principle — they centralize rules that would otherwise be scattered as string comparisons.

Exceptions: Trivial single-field reads that have no conditional logic
(`if ($user->email === null)`). Additionally, the Entity is not for data queries — use scopes on the
Model for that.
