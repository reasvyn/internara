# Cross-Module Communication Discipline
> Last updated: 2026-06-01
> Changes: feat: relaxed cross-module import restrictions for development speed


## Status
Accepted

## Context

The Action-based MVC architecture (ADR-002) organizes code into 16 modules, each owning
its complete vertical slice. Business processes naturally span multiple modules — a student
registration involves the Registration, Placement, Mentee, and Internship modules. Closing
a program involves the Internship, Assessment, Certificate, and User modules.

Multiple communication patterns exist:

1. **Direct import** — straightforward, pragmatic
2. **Events** — loose coupling, fire-and-forget
3. **Shared contracts** — moderate coupling, interface-based
4. **Action delegation** — explicit dependency

## Decision

Cross-module imports are **allowed**. The goal is developer velocity, not architectural purity.
Use the simplest pattern that works for your use case. The following hierarchy serves as
guidance, not enforcement.

### 1. Core Contracts (Layer 3)

Shared interfaces defined in `App\Core\Contracts\`. Any module can implement a Core
contract, and any module can consume it through Laravel's service container.

```
App\Core\Contracts\SendsNotifications
    ↑ implements                   ↑ calls via DI
App\User\Actions\        App\Program\Actions\
SendNotificationAction           CreateInternshipAction
```

**Currently defined:**
- `LabelEnum` — human-readable labels for all enums
- `StatusEnum` — lifecycle management (transitions, terminal states)
- `ColorableEnum` — badge/color variants for statuses
- `SendsNotifications` — notification dispatch abstraction

### 2. Module Events (Layer 9)

Events decouple side effects from core business logic. A Command Action dispatches an event;
listeners in the same or different module react.

```
Internship\Actions\CreateInternshipAction
  → event(new InternshipCreated(...))
    → Internship\Listeners\NotifyAdminsInternshipCreated (same module)
    → Admin\Listeners\InvalidateDashboardCache (different module)
```

**Guidelines:**
- Event classes are concrete, lightweight DTOs with public readonly properties
- Events belong to the module that emits them
- Listeners can live in any module
- Listeners SHOULD implement `ShouldQueue` for non-critical side effects

### 3. Action Delegation

A module may call another module's Action through its public `execute()` method.

```php
class CloseInternshipAction extends BaseAction
{
    public function __construct(
        protected readonly FinalizeAssessmentsAction $finalizeAssessments, // Assessment module
        protected readonly IssueCertificatesAction $issueCertificates,     // Certificate module
    ) {}

    public function execute(Internship $program): void
    {
        $this->finalizeAssessments->execute($program);
        $this->issueCertificates->execute($program);
    }
}
```

**Guidelines:**
- Any Action type may delegate to other modules' Actions
- Prefer events over delegation when side effects are fire-and-forget

## Consequences

- **Positive**: Direct imports are allowed, removing the biggest friction point in daily
  development.
- **Positive**: Events remain available for decoupling when side effects accumulate.
- **Positive**: No architecture test maintenance burden.
- **Negative**: Direct imports create tighter coupling between modules — a change in one
  module's model can break another module. Mitigated by existing test coverage.

## References

- `app/Core/Contracts/` — shared contracts
- `app/Program/Events/InternshipCreated.php` — event example
- `app/Providers/AppServiceProvider.php` — contract bindings, listener registration
- `docs/architecture.md` — Cross-Module Communication section
- `docs/architecture.md` — Dependency Rules table
