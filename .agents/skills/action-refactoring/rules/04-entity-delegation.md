# Entity Delegation

## What It Enforces

Business rules — any logic that answers "can this operation proceed?" — must be delegated to Entity
methods, not checked inline in Actions. The Action calls the Entity's named boolean method (e.g.,
`$year->asAcademicYearState()->canBeDeleted()`) and throws `RejectedException` if the rule fails.

## Why It Matters

Inline business rules scatter conditionals across Actions, making them hard to find, test, and
change. When the rule lives in an Entity, it has:

- A single location that is the source of truth for that business decision
- Testability without a database (Entities are pure PHP)
- Reusability across multiple Actions that need to check the same rule
- A readable name that documents the business intent (`canBeDeleted()` vs
  `if ($active && $hasRecords)`)

The Entity pattern also surfaces implicit business knowledge. A scattered
`if ($year->is_active && $year->internships()->exists())` becomes the explicit concept
`AcademicYearState::canBeDeleted()`.

## When It Applies

Any time an Action needs to check whether an operation is valid before proceeding. If the check
involves more than a single property read, it belongs in an Entity.

Simple property reads that don't encapsulate logic can stay inline: `if ($user->email === null)` is
fine. But `if ($user->locked_at !== null && $user->hasRole('student'))` combines two fields into a
business concept (`canLogin()`) and belongs in an Entity.

Exceptions: Trivial single-field null checks or boolean property reads that have no business meaning
beyond the raw value.
