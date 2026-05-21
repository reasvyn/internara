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

Without a state machine, status transitions are typically handled with ad-hoc if/else chains:

```php
if ($status === 'draft' && $newStatus === 'published') {
    // allow
} elseif (...) {
    // allow
}
```

This approach scatters transition rules across the codebase, makes them impossible to audit
centrally, and allows invalid transitions to slip through when a new code path forgets to
check.

Two Spatie packages were evaluated for state management but neither was adopted at the
Eloquent model level. Instead, a layered approach using enums + entities emerged.

## Decision

Three layers enforce state machine behavior, each at a different level of abstraction:

### Layer 1 — StatusEnum contract (database persistence)

Every stateful table stores status as a plain string column. The column is cast to a `StatusEnum`
backed enum at the Eloquent layer. Each enum implements `canTransitionTo()`, `isTerminal()`,
and `validTransitions()`.

```php
enum InternshipStatus: string implements StatusEnum
{
    case Draft = 'draft';
    case Published = 'published';
    case Active = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function validTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Published, self::Cancelled],
            self::Published => [self::Active, self::Cancelled],
            self::Active => [self::Completed, self::Cancelled],
            self::Completed => [],
            self::Cancelled => [],
        };
    }
}
```

This is the authoritative transition map — every Action that changes status should call
`$currentStatus->canTransitionTo($newStatus)` before applying the change.

### Layer 2 — BaseState + Entity State Classes (business logic)

For domains with complex transition rules (e.g., Internship), an Entity class extends `BaseState`
(which wraps `Spatie\ModelStates\State`). The Entity provides a `StateConfig` that mirrors the
StatusEnum transitions but also supports pre/post transition hooks.

```php
abstract class InternshipState extends BaseState
{
    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Draft::class)
            ->allowTransition(Draft::class, Published::class)
            ->allowTransition(Draft::class, Cancelled::class);
    }
}
```

The model exposes the entity via a named accessor:
```php
$internship->asInternshipState()->canTransitionTo(InternshipStatus::Active);
```

The entity also provides `toEnum(): ?LabelEnum` to bridge back to the StatusEnum.

### Layer 3 — Spatie HasModelStates (NOT used on Eloquent models)

`Spatie\ModelStates\HasStates` trait is intentionally NOT applied to any Eloquent model.
The state machine lives entirely in the Entity layer, decoupled from persistence. The database
stores a simple string; the Entity provides transition validation; the Action orchestrates both.

### Spatie laravel-model-status — direct HasStatuses usage

The `spatie/laravel-model-status` package is used directly by two models:
- `User` — tracks account lifecycle statuses
- `Registration` — tracks registration workflow statuses

Both models use the `HasStatuses` trait directly from the Spatie package without the
`HasModelStatuses` bridge. A bridge trait was created in the Shared domain to provide
`StatusEnum`-typed wrappers, but it was **never adopted** by any model and has been
removed. The direct `HasStatuses` usage remains valid for User and Registration.

## Consequences
- **Positive**: Transition rules are documented in code — open the enum to see exactly which
  transitions are valid.
- **Positive**: Invalid transitions are impossible — `canTransitionTo()` rejects them before
  any side effects occur.
- **Positive**: State machine logic is testable in isolation — enum tests verify transitions
  without any persistence layer.
- **Positive**: Entity state classes can attach behavior (pre/post hooks) without polluting
  the Eloquent model.
- **Positive**: The database remains simple — a single `status` string column with no
  polymorphic state tables needed.
- **Negative**: Transition validation is not automatic — Actions must call
  `canTransitionTo()` explicitly. No model event or observer enforces it.
- **Negative**: Duplication between StatusEnum `validTransitions()` and Entity's
  `StateConfig::allowTransition()` — both define the same rules in different places.
- **Negative**: Cross-state business logic (e.g., "when transitioning to COMPLETED, check
  report score > 0") still lives in Actions, not in the state machine.

## References
- `app/Domain/Core/Contracts/StatusEnum.php` — contract interface
- `app/Domain/Core/States/BaseState.php` — base entity state class
- `app/Domain/Internship/States/` — example: InternshipState + Draft/Published/Active/Completed/Cancelled
- `app/Domain/Internship/Enums/InternshipStatus.php` — example StatusEnum
- `app/Domain/Internship/Models/Internship.php` — `asInternshipState()` accessor
- `app/Domain/Shared/Support/HasModelStatuses.php` — bridge trait (UNUSED, candidate for removal)
