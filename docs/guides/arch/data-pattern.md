# Data / DTO Pattern Reference — DTO Lifecycle, Immutability & Boundary Rules

## Description

This document is a comprehensive reference on the Data Transfer Object (DTO) pattern as implemented
in the Internara codebase. It covers philosophy, the `BaseData` contract, conventions, specialized
subtypes, and the testing approach. Grounded in **Data Transfer Object** (PoEAA), **Value Object** (DDD),
**Immutability**, and **Type Safety** — all mapped to Internara's Laravel stack.

---

## Non-Negotiable

Hard rules. Violations are architecture violations.

1. **DTO for 3+ params (C7).** Command and Process Actions with 3+ input parameters MUST use a DTO (`BaseData` subclass). Fewer params may use a plain array. This is C7 (invariant from `docs/conventions.md` §9).

2. **No Model/Entity imports in DTO (C6).** DTOs carry scalars, arrays, nested DTOs, and enums only. No `Model`, `Entity`, `Service`, `Action`, or `Livewire` imports. This enforces layer isolation — C6.

3. **Immutable by default.** Every DTO is declared `final readonly`. Properties can only be set once (during construction). Any "modification" returns a new instance via `merge()` or named constructors.

4. **Type-safe contracts.** PHP type hints catch mismatches at call sites instead of surfacing as cryptic array-key errors at runtime. The typed constructor signature is self-documenting.

5. **Two hydration paths.** DTOs support direct constructor instantiation (preferred when all values are available) and `fromArray()` reflection hydration (used when data arrives as an array from forms, APIs, or serialised sources).

6. **ActionResponse for Action returns.** Command and Process Actions return `ActionResponse` — a specialized result DTO with named constructors (`ok()`, `created()`, `updated()`, `deleted()`, `error()`). Never return raw arrays or booleans from Actions.

---

## How to Apply

### 1. Data Transfer Object (PoEAA)

Martin Fowler's DTO pattern defines an object that carries data between processes. In Internara, DTOs carry data between layers: Livewire → Action, Action → Entity, Action → Response. The DTO is the contract documentation — you know exactly what data an Action expects without reading its body.

**Reference:** [PoEAA — Data Transfer Object](https://martinfowler.com/eaaCatalog/dataTransferObject.html)

### 2. Value Object (DDD)

DTOs in Internara are Value Objects in the DDD sense: they have no identity (no ID), are defined by their attributes, and are immutable. Two DTOs with the same values are equal. This is distinct from Entities, which have identity and mutable state.

### 3. Immutability

The `readonly` keyword (PHP 8.2) enforces that properties can only be set once during construction. `merge()` creates a new instance — the original is never modified. This eliminates a class of bugs where shared DTO state is accidentally mutated.

### 4. Type Safety

PHP type hints on DTO properties catch mismatches at compile time. `fromArray()` validates required parameters and throws `InvalidArgumentException` for missing values. The `snake_case` → `camelCase` resolution handles both conventions transparently.

---

## Anti-Patterns

| You see... | It should be... | Violation |
|-----------|----------------|-----------|
| DTO importing `User` Model or `Internship` Entity | Remove — DTOs carry scalars/DTOs/enums only | C6 — forbidden imports |
| Action with 5 array params, no DTO | Create `VerbEntityData` DTO | C7 — DTO for 3+ params |
| DTO with mutable properties (no `readonly`) | Add `readonly` keyword | Immutability violated |
| DTO with `Model::create()` call | Move to Command Action | DTO has side effects |
| Returning raw `array` from Command Action | Return `ActionResponse` | No standardized return type |
| DTO with `public function update()` | Remove — DTOs are immutable | Mutation method on immutable |
| DTO with `Entity` property | Replace with scalar ID or nested DTO | C6 — Entity import |
| `ActionResponse` returned from Livewire without `$response->failed()` check | Always check `$response->failed()` before toast | No error handling |

---

## Quick References

- `docs/conventions.md` §6 DTO Contracts — C6, C7, BaseData, ActionResponse
- `docs/guides/arch/action-pattern.md` — Action Triad and ActionResponse
- `docs/guides/arch/entity-pattern.md` — Entity vs DTO boundary
- `app/Core/Actions/BaseAction.php` — BaseAction with transaction/logging
- `app/Core/Data/BaseData.php` — BaseData contract
- `app/Core/Data/ActionResponse.php` — ActionResponse specialized DTO
- [PoEAA — Data Transfer Object](https://martinfowler.com/eaaCatalog/dataTransferObject.html) — DTO pattern
- [DDD — Value Object](https://martinfowler.com/bliki/ValueObject.html) — Value Object concept
- [Laravel — Form Requests](https://laravel.com/docs/validation#form-request-validation) — input validation
- [PHP 8.2 Readonly Classes](https://www.php.net/manual/en/language.oop5.basic.php#language.oop5.basic.classclass) — readonly keyword
