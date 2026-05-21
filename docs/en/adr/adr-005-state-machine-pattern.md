# ADR-005: State Machine Pattern

## Status
Accepted (Revised 2026-05-21)

## Context
Many entities in the system have well-defined lifecycles with explicit states, transition
rules, and terminal endpoints. Examples:

- **Internship status**: DRAFT → PUBLISHED → ACTIVE → COMPLETED / CANCELLED
- **Submission status**: DRAFT → SUBMITTED → GRADED / RETURNED
- **Registration status**: PENDING → REGISTERED → PLACED → ACTIVE → COMPLETED / SUSPENDED
- **Report status**: DRAFT → SUBMITTED → IN_REVIEW → GRADED / REVISION_NEEDED

Without a state machine, status transitions are handled with ad-hoc if/else chains scattered
across Actions, making them impossible to audit centrally and prone to missing validations.

Spatie's `laravel-model-states` was initially evaluated for persistence-aware state machines
but was never adopted by any Eloquent model. The package has been removed. State management
uses a simpler two-layer approach with enums + entities.

## Decision

Two layers enforce state machine behavior:

### Layer 1 — StatusEnum contract (database persistence)

Every stateful table stores status as a plain string column. The column is cast to a `StatusEnum`
backed enum at the Eloquent layer. Each enum implements `canTransitionTo()`, `isTerminal()`,
and `validTransitions()`.

```php
enum InternshipStatus: string implements StatusEnum
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function validTransitions(): array
    {
        return match ($this) {
            self::DRAFT => [self::Published, self::Cancelled],
            self::PUBLISHED => [self::ACTIVE, self::CANCELLED],
            self::ACTIVE => [self::COMPLETED, self::CANCELLED],
            self::COMPLETED => [],
            self::CANCELLED => [],
        };
    }
}
```

This is the authoritative transition map — every Action that changes status should call
`$currentStatus->canTransitionTo($newStatus)` before applying the change.

### Layer 2 — Entity State Classes (business logic)

For domains with complex transition rules, an Entity class provides business rule validation
without framework dependencies. The entity is a `final readonly` class extending `BaseEntity`,
exposed via a named accessor on the model.

```php
// Model accessor
public function asInternshipState(): InternshipState
{
    return InternshipState::fromModel($this);
}

// Usage in Action
$internship->asInternshipState()->canTransitionTo(InternshipStatus::ACTIVE);
```

Entity state classes contain pure business rules (zero framework dependencies beyond the
`Model` import in `fromModel()`).

### Spatie laravel-model-status

The `spatie/laravel-model-status` package is used directly by two models:
- `User` — tracks account lifecycle statuses
- `Registration` — tracks registration workflow statuses

Both models use the `HasStatuses` trait directly from the Spatie package. This is kept
because these models need persisted status history (via the `statuses` polymorphic table).

## Consequences
- **Positive**: Transition rules are documented in code — open the enum to see valid transitions.
- **Positive**: State machine logic is testable in isolation — enum tests verify transitions
  without any persistence layer.
- **Positive**: Entity state classes can attach behavior without polluting the model.
- **Positive**: The database remains simple — a single `status` string column, no polymorphic
  state tables.
- **Negative**: Transition validation is not automatic — Actions must call
  `canTransitionTo()` explicitly. No model event or observer enforces it.
- **Negative**: Cross-state business logic ("when transitioning to COMPLETED, check report
  score > 0") still lives in Actions, not in the state machine.

## References
- `app/Domain/Core/Contracts/StatusEnum.php` — contract interface
- `app/Domain/Internship/Enums/InternshipStatus.php` — example StatusEnum
- `app/Domain/Internship/Entities/InternshipState.php` — example entity state class
- `app/Domain/Internship/Models/Internship.php` — `asInternshipState()` accessor
