# Service Pattern — Infrastructure Logic, Scope & Constructor Injection

## Description

Boundaries between Services (infrastructure logic), Support (static utilities), and Actions (domain
business logic), with scope and placement rules. Grounded in **Service Layer Pattern** (PoEAA),
**Single Responsibility Principle** (SOLID), **God Object** anti-pattern, and **Factory Pattern** — all
mapped to Internara's Action-first architecture.

---

## Non-Negotiable

Hard rules. Violations are architecture violations.

1. **Services are infrastructure, never domain.** A Service contains **infrastructure logic** (framework, environment, routing, system state) — never **domain business logic** (rules about internships, students, grades, enrollments). Domain logic belongs in Actions + Entities. This is SRP (SOLID).

2. **Services must never mutate state.** No `DB::transaction()`, no `Model::create/update/delete`, no activity log writes, no event dispatch. If it writes to the database, it must be a Command Action.

3. **Services must never call Actions.** If a class composes multiple Actions, it IS a Process Action. The Service layer adds nothing here.

4. **Constructor injection required.** All Services use constructor property promotion for framework dependencies. Static methods are permitted only for framework hooks that require static callables (e.g., `PulseGuard::viewPulse()`).

5. **Services are the exception, not the rule.** Prefer Actions for all business operations. A class belongs in `Services/` only when all five criteria are met: infrastructure logic, instance methods, does not fit a single Action, not a mutation, not a complex query.

---

## How to Apply

### 1. Service Layer Pattern (PoEAA)

Martin Fowler's Service Layer pattern defines a service as an operation that coordinates multiple domain objects. In Internara, the Action Triad (Command/Read/Process) replaces the traditional Service Layer — each Action is a single operation with its own contract, transaction, and logging. Services exist only for infrastructure concerns that cross module boundaries.

**Reference:** [PoEAA — Service Layer](https://martinfowler.com/eaaCatalog/serviceLayer.html)

### 2. SRP (SOLID) — Single Responsibility

Each Service has exactly one reason to change. If a Service accumulates domain business logic, extract it into an Entity + Action. The God Object anti-pattern emerges when a Service grows beyond its single responsibility.

### 3. God Object Anti-Pattern

A God Object is a class that knows too much or does too much. Warning signs:
- More than 10 public methods
- Mixed domain and infrastructure logic
- Constructor with 5+ dependencies
- Methods that call other methods in the same class in complex chains

Solution: Extract into focused Actions (one per business operation) and Services (one per infrastructure concern).

### 4. Factory Pattern

Services may act as Factories when they create infrastructure objects (e.g., `ModuleService` discovers and registers modules). This is distinct from Command Actions that create domain entities.

---

## Anti-Patterns

| You see... | It should be... | Violation |
|-----------|----------------|-----------|
| `DashboardService` with 15+ methods | Split into focused Read Actions | God Object — too many responsibilities |
| Service calling `CreateUserAction` | Extract orchestration into Process Action | Service calls Actions |
| Service with `DB::transaction()` | Move to Command Action | Service mutates state |
| Service with domain rule (`canStudentGraduate()`) | Extract to Entity + Action | Domain logic in Service |
| Support class with constructor injection | Rename and move to Service | Support/Service boundary violation |
| Service with static methods (non-framework) | Convert to Support or Action | Wrong method style |
| `AppService` handling auth + cache + config | Split into `AuthService`, `CacheService` | God Object — mixed concerns |
| Service that dispatches events | Move to Command Action | Service has side effects |

---

## Quick References

- `docs/conventions.md` §Service Boundaries — when Services are appropriate
- `docs/adr/adr-action-pattern-over-services.md` — ADR for Action preference
- `docs/guides/arch/action-pattern.md` — Action Triad reference
- `docs/guides/arch/support-pattern.md` — Support vs Service boundaries
- [PoEAA — Service Layer](https://martinfowler.com/eaaCatalog/serviceLayer.html) — Service Layer pattern
- [SOLID — Single Responsibility](https://en.wikipedia.org/wiki/Single_responsibility_principle) — SRP principle
- [God Object](https://en.wikipedia.org/wiki/God_object) — anti-pattern
- [Factory Pattern](https://en.wikipedia.org/wiki/Factory_method_pattern) — creational pattern
- [Laravel — Service Container](https://laravel.com/docs/container) — DI and service binding
