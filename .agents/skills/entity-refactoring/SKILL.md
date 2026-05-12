---
name: entity-refactoring
description: "Apply when creating or refactoring Eloquent Models and Entity classes. Models handle data access (queries, relationships, scopes). Entities handle business rules (state checks, capability decisions) as pure PHP objects. Enforces separation between persistence and business logic."
license: MIT
metadata:
  author: internara
---

# Entity Refactoring: Model + Entity Architecture

Separation between data access (Model) and business rules (Entity).

## Layer Responsibilities

```
┌─────────────────────────────────────────────────────┐
│                   Model (Eloquent)                    │
│                                                       │
│  • Relationships (hasMany, belongsTo, etc.)           │
│  • Scopes (scopeActive, scopeLocked)                  │
│  • Attributes (casts, accessors, appends)             │
│  • Named entity accessor (as{EntityName}())           │
│  • Data queries only — NO business rules              │
└───────────────────────┬─────────────────────────────┘
                        │ callers go through
                        ▼
┌─────────────────────────────────────────────────────┐
│                  Entity (Pure PHP)                    │
│                                                       │
│  • Business rules (canLogin, canTransitionTo, etc.)   │
│  • State checks (isSuspended, isTerminal, isActive)   │
│  • Factory method fromModel(Model $model)             │
│  • Zero framework dependencies                        │
│  • Testable without a database                        │
└─────────────────────────────────────────────────────┘
```

## Rules

| # | Rule | File |
|---|------|------|
| 1 | [Model Responsibilities](rules/01-model-responsibilities.md) | Queries, relationships, scopes — NOT business rules |
| 2 | [Entity Purity](rules/02-entity-purity.md) | `final readonly`, no Eloquent, no Facades |
| 3 | [Bridge Pattern](rules/03-bridge-pattern.md) | `as{EntityName}()` accessor on Model |
| 4 | [Business Rules in Entities](rules/04-business-rules.md) | What to move from Model to Entity |
| 5 | [Testing Separation](rules/05-testing-separation.md) | Model tests need DB, Entity tests don't |
