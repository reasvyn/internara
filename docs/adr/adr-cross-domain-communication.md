# Cross-Domain Communication Discipline
> Last updated: 2026-06-01
> Changes: feat: relaxed cross-domain import restrictions for development speed


## Status
Accepted

## Context

The domain-first architecture (ADR-002) organizes code into 24 business domains, each owning
its complete vertical slice. Business processes naturally span multiple domains — a student
registration involves the Registration, Placement, Mentee, and Internship domains. Closing
a program involves the Internship, Assessment, Certificate, and User domains.

Multiple communication patterns exist:

1. **Direct import** — straightforward, pragmatic
2. **Events** — loose coupling, fire-and-forget
3. **Shared contracts** — moderate coupling, interface-based
4. **Action delegation** — explicit dependency

## Decision

Cross-domain imports are **allowed**. The goal is developer velocity, not architectural purity.
Use the simplest pattern that works for your use case. The following hierarchy serves as
guidance, not enforcement.

### 1. Core Contracts (Layer 3)

Shared interfaces defined in `App\Domain\Core\Contracts\`. Any domain can implement a Core
contract, and any domain can consume it through Laravel's service container.

```
App\Domain\Core\Contracts\SendsNotifications
    ↑ implements                   ↑ calls via DI
App\Domain\User\Actions\        App\Domain\Internship\Actions\
SendNotificationAction           CreateInternshipAction
```

**Currently defined:**
- `LabelEnum` — human-readable labels for all enums
- `StatusEnum` — lifecycle management (transitions, terminal states)
- `ColorableEnum` — badge/color variants for statuses
- `SendsNotifications` — notification dispatch abstraction

### 2. Domain Events (Layer 9)

Events decouple side effects from core business logic. A Command Action dispatches an event;
listeners in the same or different domain react.

```
Internship\Actions\CreateInternshipAction
  → event(new InternshipCreated(...))
    → Internship\Listeners\NotifyAdminsInternshipCreated (same domain)
    → Admin\Listeners\InvalidateDashboardCache (different domain)
```

**Guidelines:**
- Event classes are concrete, lightweight DTOs with public readonly properties
- Events belong to the domain that emits them
- Listeners can live in any domain
- Listeners SHOULD implement `ShouldQueue` for non-critical side effects

### 3. Action Delegation

A domain may call another domain's Action through its public `execute()` method.

```php
class CloseInternshipAction extends BaseAction
{
    public function __construct(
        protected readonly FinalizeAssessmentsAction $finalizeAssessments, // Assessment domain
        protected readonly IssueCertificatesAction $issueCertificates,     // Certificate domain
    ) {}

    public function execute(Internship $program): void
    {
        $this->finalizeAssessments->execute($program);
        $this->issueCertificates->execute($program);
    }
}
```

**Guidelines:**
- Any Action type may delegate to other domains' Actions
- Prefer events over delegation when side effects are fire-and-forget

## Consequences

- **Positive**: Direct imports are allowed, removing the biggest friction point in daily
  development.
- **Positive**: Events remain available for decoupling when side effects accumulate.
- **Positive**: No architecture test maintenance burden.
- **Negative**: Direct imports create tighter coupling between domains — a change in one
  domain's model can break another domain. Mitigated by existing test coverage.

## References

- `app/Domain/Core/Contracts/` — shared contracts
- `app/Domain/Internship/Events/InternshipCreated.php` — event example
- `app/Providers/DomainServiceProvider.php` — contract bindings, listener registration
- `docs/architecture.md` — Cross-Domain Communication section
- `docs/architecture.md` — Dependency Rules table
