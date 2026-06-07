---
name: entity-refactoring
---

# Entity Refactoring Skill

## When to Activate

Apply this skill when creating new Models, when a Model accumulates boolean capability checks or
conditional logic that feels out of place, when you see inline business rules in Controllers or
Livewire components, or when writing tests for business logic that currently require a database.

## Core Principles

The Model and Entity layers serve two fundamentally different concerns. Models handle data access —
relationships, scopes, casts, and queries. Entities handle business rules — canX checks, state
transitions, and capability decisions — as pure PHP objects with zero framework dependencies.

This separation exists because:

- Business rules are easier to test without a database
- Models already have many responsibilities (serialization, relationships, events)
- Business rules change for different reasons than data access patterns
- Entities can be reused across different data sources

## Layer Responsibilities

Models own: relationships, scopes, attribute casting, the entity bridge accessor
(`as{Name}State()`), media collections, and factory definitions. They explicitly do NOT own:
`canX()`, `isY()`, state transition logic, date calculations based on business rules, or any
conditional that combines multiple fields into a decision.

Entities own: all business rules as `final readonly` classes extending `BaseEntity`. They receive
extracted state through a `fromModel(Model): static` factory. They import nothing from the framework
except `Illuminate\Database\Eloquent\Model` in that single factory method.

## Bridge Pattern

Every Model exposes its Entity through a named accessor: `as{EntityName}(): EntityType`. The
accessor name describes the business role, not just the class name. For example, `User` has
`asApprentice()` rather than `asUserState()`, because in this context the user acts as an
apprentice.

## Verification Before Finalizing

- Does the Model use `#[Fillable]` attribute, not `$fillable` property?
- Does the Model extend BaseModel (UUID PK)?
- Is the Entity `final readonly` and extending BaseEntity?
- Does the Entity have `fromModel(Model): static`?
- Does the Entity have zero Eloquent/Facade/ServiceProvider imports?
- Do business rules live in the Entity, not the Model or Action?
- Do all Enums implement `LabelEnum` or `StatusEnum`?
- Does `declare(strict_types=1)` appear on every file?
