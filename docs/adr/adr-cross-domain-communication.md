# Cross-Domain Communication Discipline
> Last updated: 2026-05-27
> Changes: docs: comprehensive infrastructure, architecture, and conventions overhaul


## Status
Accepted

## Context

The domain-first architecture (ADR-002) organizes code into 24 business domains, each owning
its complete vertical slice. The fundamental rule is that **no domain may import another
domain's Models, Actions, or Livewire components directly.**

However, business processes naturally span multiple domains. A student registration involves
the Registration, Placement, Mentee, and Internship domains. Closing a program involves the
Internship, Assessment, Certificate, and User domains.

The tension is clear: domains must be isolated to remain maintainable and independently
testable, but they must also cooperate to deliver end-to-end workflows.

Three communication patterns exist, each with different coupling characteristics:

1. **Direct import** — tight coupling, violates domain boundaries
2. **Events** — loose coupling, fire-and-forget
3. **Shared contracts** — moderate coupling, interface-based
4. **Action delegation** — moderate coupling, explicit dependency

Without explicit rules, developers naturally gravitate toward direct imports — the path of
least resistance. Evidence of this exists in the current codebase:

| Violation | File |
|---|---|
| `Internship\Models\Internship` imports `School\Models\AcademicYear` | `app/Domain/Internship/Models/Internship.php` |
| `Internship\Policies\CompanyPolicy` gates `Partnership\Models\Company` | `app/Domain/Internship/Policies/CompanyPolicy.php` |
| `Internship\Policies\InternshipRegistrationPolicy` gates `Registration\Models\Registration` | `app/Domain/Internship/Policies/InternshipRegistrationPolicy.php` |
| `CheckCloseReadinessAction` imports models from 5 different domains | `app/Domain/Internship/Actions/CheckCloseReadinessAction.php` |

## Decision

Cross-domain communication follows a strict hierarchy of four patterns, listed from most
preferred to least preferred.

### 1. Core Contracts (Layer 3)

Shared interfaces defined in `App\Domain\Core\Contracts\`. Any domain can implement a Core
contract, and any domain can consume it through Laravel's service container.

```
App\Domain\Core\Contracts\SendsNotifications
    ↑ implements                   ↑ calls via DI
App\Domain\User\Actions\        App\Domain\Internship\Actions\
SendNotificationAction           CreateInternshipAction
```

**Currently defined contracts:**
- `LabelEnum` — human-readable labels for all enums
- `StatusEnum` — lifecycle management (transitions, terminal states)
- `ColorableEnum` — badge/color variants for statuses
- `SendsNotifications` — notification dispatch abstraction

**Rules:**
- Contracts MUST NOT reference any business domain class, model, or type
- Contracts SHOULD be narrow (single method preferred)
- Binding SHOULD happen in `DomainServiceProvider::register()`

### 2. Domain Events (Layer 9)

Events are the primary mechanism for cross-domain communication. A Command Action dispatches
an event; listeners in the same or different domain react.

```
Internship\Actions\CreateInternshipAction
  → event(new InternshipCreated(...))
    → Internship\Listeners\NotifyAdminsInternshipCreated (same domain)
    → Admin\Listeners\InvalidateDashboardCache (different domain)
```

**Rules:**
- Event classes are concrete, lightweight DTOs with public readonly properties
- Events belong to the domain that emits them, NOT the consuming domain
- Listeners can live in any domain
- Listeners SHOULD implement `ShouldQueue` for non-critical side effects
- Events MUST NOT carry Models — carry model IDs or value objects only

### 3. Action Delegation

A domain may call another domain's Action through its public `execute()` method. This is
permitted ONLY inside a Process Action, never inside a Command Action directly.

```php
class CloseInternshipProcess extends BaseAction
{
    public function __construct(
        protected readonly FinalizeAssessmentsAction $finalizeAssessments, // Assessment domain
        protected readonly IssueCertificatesAction $issueCertificates,     // Certificate domain
    ) {}

    public function execute(Internship $program): void
    {
        // This is a Process Action — cross-domain delegation is allowed
        $this->finalizeAssessments->execute($program);
        $this->issueCertificates->execute($program);
    }
}
```

**Rules:**
- ONLY Process Actions may delegate to other domains' Actions
- The called Action MUST accept primitive types, DTOs, or its own domain's Models —
  never Models from the calling domain
- Cross-domain Action calls MUST be authorized in the calling layer
- Action delegation SHOULD be used sparingly — prefer events when possible

### 4. What is NOT Allowed

| Pattern | Example | Why | Correct Alternative |
|---|---|---|---|
| Direct Model import | `Internship\Model` uses `School\Model` | Tightly couples two domains | Use AcademicYearId value object + query in School domain |
| Cross-domain Policy | `Internship\Policy` gates `Partnership\Model` | Policy defined outside the owning domain | Move policy to `Partnership\Policies`, register in DomainServiceProvider |
| Livewire import | `Registration\Livewire` uses `Internship\Model` | UI layer reaches into another domain's persistence | Use Read Action or Core contract |

### Enforcement

Architecture tests that previously enforced these rules (`DomainBoundariesArchTest`,
`LayerSeparationArchTest`) were removed due to a `pest-plugin-arch` compatibility bug.
Until they are restored, enforcement relies on code review.

A manual audit in the codebase has identified known violations (listed in the Context
section). These should be remediated incrementally — each violation is a technical debt
item, not a blocker.

## Consequences

- **Positive**: Four explicit patterns cover all cross-domain communication needs, from
  loose coupling (events) to explicit coordination (Action delegation).
- **Positive**: Events as the primary mechanism mean domains react to changes without
  knowing about each other — adding a new listener does not modify the emitting domain.
- **Positive**: Core contracts provide a zero-dependency path for shared functionality
  (notifications, enums) that multiple domains need.
- **Positive**: Known violations are documented and tracked — not ignored.
- **Negative**: Events introduce eventual consistency — a listener may fail without the
  emitting Action knowing. Mitigated by queued listeners with retry logic.
- **Negative**: Action delegation creates an implicit dependency between domains — a
  change to `FinalizeAssessmentsAction` can break `CloseInternshipProcess`. Mitigated by
  integration tests.
- **Negative**: Process Actions must explicitly list all cross-domain dependencies in their
  constructor — no "hidden" cross-domain calls.
- **Negative**: Architecture tests are not yet restored — enforcement is manual until the
  `pest-plugin-arch` compatibility issue is resolved.

## References

- `app/Domain/Core/Contracts/` — shared contracts
- `app/Domain/Internship/Events/InternshipCreated.php` — event example
- `app/Providers/DomainServiceProvider.php` — contract bindings, listener registration
- `docs/architecture.md` — Cross-Domain Communication section
- `docs/architecture.md` — Dependency Rules table
