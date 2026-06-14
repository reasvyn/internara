# ADR-011: Cross-Module Communication Discipline

> **Status:** Accepted
> **Last updated:** 2026-06-14
> > **Changes:** sync — fix Program event path (Events/ → Internship/Events/)

## Context

The Action-based MVC architecture organizes code into 19 modules, each owning a complete vertical slice. Business processes naturally span multiple modules — student registration involves Enrollment, Program, and User modules. Closing a program involves Assessment, Certification, and Reports modules.

Four communication patterns exist with different coupling trade-offs:

1. **Direct import** — straightforward, pragmatic, tight coupling
2. **Events** — loose coupling, fire-and-forget
3. **Shared contracts** — moderate coupling, interface-based
4. **Action delegation** — explicit dependency, moderate coupling

## Decision

Cross-module imports are **allowed**. The goal is developer velocity, not architectural purity. Use the simplest pattern that works. The following hierarchy serves as guidance, not enforcement.

### 1. Core Contracts (Layer 3)

Shared interfaces in `App\Core\Contracts\`. Any module implements them, any module consumes them through the container. Currently defined: `LabelEnum`, `StatusEnum`, `ColorableEnum`, `SendsNotifications`.

### 2. Module Events (Layer 9)

Events decouple side effects from core business logic. A Command Action dispatches an event; listeners in any module react. Event classes are concrete, lightweight DTOs with public readonly properties. Events belong to the emitting module. Listeners should implement `ShouldQueue` for non-critical side effects.

```
Internship\Actions\CreateInternshipAction
  → event(new InternshipCreated(...))
    → Internship\Listeners\NotifyAdmins (same module)
    → SysAdmin\Listeners\InvalidateCache (different module)
```

### 3. Action Delegation

A module calls another module's Action through its public `execute()` method. Any Action type may delegate to other modules' Actions. Prefer events over delegation when side effects are fire-and-forget.

```php
class CloseInternshipAction extends BaseAction
{
    public function __construct(
        protected readonly FinalizeAssessmentsAction $finalizeAssessments, // Assessment module
        protected readonly IssueCertificatesAction $issueCertificates,    // Certification module
    ) {}

    public function execute(Internship $program): void
    {
        $this->finalizeAssessments->execute($program);
        $this->issueCertificates->execute($program);
    }
}
```

### 4. Direct Import

Straightforward cross-module access when no decoupling is needed. This is the default choice — only reach for events or contracts when you need loose coupling.

## Consequences

- **Positive**: Direct imports are allowed, removing the biggest friction point in daily development.
- **Positive**: Events remain available for decoupling when side effects accumulate.
- **Positive**: No architecture test maintenance burden for cross-module rules.
- **Negative**: Direct imports create tighter coupling — a change in one module's model can break another module. Mitigated by test coverage and code review.

## References

- `app/Core/Contracts/` — Shared contracts
- `app/Program/Internship/Events/InternshipCreated.php` — Event example
- `app/Providers/AppServiceProvider.php` — Contract bindings, listener registration
- `docs/architecture.md` — Cross-Module Communication section
