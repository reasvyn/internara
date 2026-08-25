# Cross-Module Communication Discipline

> **Last updated:** 2026-08-25 **Changes:** rewrite to MADR-lite industry-standard format

| Field | Value |
|-------|-------|
| Status | Accepted |
| Deciders | Reas Vyn |
| Date | 2026-08-16 |
| Technical Story | [Modular Architecture](../architecture.md) and [Modular Pattern](../guides/arch/modular-pattern.md) |

## Context and Problem Statement

Internara organizes code into 18 modules, each owning a complete vertical slice (Models,
Actions, Livewire, Policies). Business processes naturally span modules — student registration
touches Enrollment, Program, and User; closing a program touches Assessment, Certification,
and Reports.

Without guidance, cross-module calls can create tight coupling, circular dependencies, and
hidden side effects. Four communication patterns are available, each with different coupling
costs: direct import, Action delegation, module events, and shared core contracts.

**Decision Drivers:**

* Developer velocity in a single-tenant PKL system outweighs architectural purity
* Avoid circular dependencies and god modules while keeping the call graph understandable
* Side effects (notifications, cache invalidation) should not pollute core business transactions
* Contract-based decoupling should be an explicit choice, not a default tax

## Considered Options

* **Strict module boundaries** — forbid direct cross-module imports; all communication through
  events or contracts. *Pros:* strongest decoupling. *Cons:* ceremony for trivial reads, slower
  iteration, extra indirection for simple cases.
* **Events-only decoupling** — every cross-module interaction is an event. *Pros:* fully
  decoupled, testable in isolation. *Cons:* fire-and-forget hides intent, harder to trace
  mandatory workflows, overkill for synchronous reads.
* **Pragmatic hierarchy with direct imports allowed (chosen)** — allow direct imports as the
  default, provide a ranked guidance hierarchy (core contracts → events → Action delegation →
  direct import) for when looser coupling is warranted. *Pros:* lowest friction for common
  cases, decoupling available when accumulation justifies it. *Cons:* permits tighter coupling
  by default; relies on review and tests to catch breakage.

## Decision Outcome

**Chosen option: Pragmatic hierarchy with direct imports allowed** — cross-module imports are
permitted. The following hierarchy serves as guidance, not enforcement; use the simplest pattern
that satisfies the coupling need.

**1. Core Contracts (Layer 3)** — shared interfaces in `App\Core\Contracts\`
(`LabelEnum`, `StatusEnum`, `ColorableEnum`, `SendsNotifications`). Any module implements them;
any module consumes them through the container.

**2. Module Events (Layer 9)** — decouple side effects from core transactions. A Command Action
dispatches a concrete, lightweight event DTO (public readonly properties); listeners in any
module react and should implement `ShouldQueue` for non-critical work.

```
Internship\Actions\CreateInternshipAction
  → event(new InternshipCreated(...))
    → Internship\Listeners\NotifyAdmins (same module)
    → SysAdmin\Listeners\InvalidateCache (different module)
```

**3. Action Delegation** — a module calls another module's public `execute()` method. Any Action
type may delegate to other modules' Actions. Prefer events over delegation when the side effect
is fire-and-forget.

```php
class CloseInternshipAction extends BaseAction
{
    public function __construct(
        protected readonly FinalizeAssessmentsAction $finalizeAssessments, // Assessment
        protected readonly IssueCertificatesAction $issueCertificates,      // Certification
    ) {}

    public function execute(Internship $program): void
    {
        $this->finalizeAssessments->execute($program);
        $this->issueCertificates->execute($program);
    }
}
```

**4. Direct Import** — straightforward cross-module access when no decoupling is needed; the
default choice. Reach for events or contracts only when loose coupling pays for itself.

### Positive Consequences

* Direct imports remove the biggest friction point in daily development
* Events remain available for decoupling once side effects accumulate
* No architecture-test maintenance burden for cross-module rules
* Call chains stay traceable when delegation is used for mandatory workflows

### Negative Consequences

* Direct imports create tighter coupling — a change in one module's model can break another;
  mitigated by test coverage and code review

## Links

* [Modular Pattern](../guides/arch/modular-pattern.md) — module colocation and SRP rules
* [Event Pattern](../guides/arch/event-pattern.md) — event DTOs and listener conventions
* [Architecture Overview](../architecture.md) — 4-layer model and layer responsibilities
