---
name: action-refactoring
description: "Apply when creating or refactoring Action classes in the Action-Oriented MVC pattern. Ensures Actions are single-responsibility, handle validation, persistence, side effects, and use proper error handling. Also covers when business rules should be delegated to Entities and static utilities to Support."
license: MIT
metadata:
  author: internara
---

# Action Refactoring: Action Layer Architecture

Blueprint for keeping Action classes focused, consistent, and properly layered.

## Action Responsibilities

```
┌─────────────────────────────────────────────────────┐
│                   Action Layer                        │
│                                                       │
│  • Validate input (Validator facade)                  │
│  • Orchestrate persistence (DB::transaction)          │
│  • Dispatch side effects (audit, events, notifs)      │
│  • Delegate business RULES to Entities                │
│  • Delegate static UTILITIES to Support               │
│                                                       │
│  NOT responsible for:                                 │
│  • Business rules / state checks → Entity             │
│  • UI state / form binding → Livewire Component       │
│  • Static helpers / formatting → Support              │
└─────────────────────────────────────────────────────┘
```

## Rules

| # | Rule | File |
|---|------|------|
| 1 | [Single Responsibility](rules/01-single-responsibility.md) | One Action = one operation |
| 2 | [Validation](rules/02-validation.md) | Authoritative validation lives here |
| 3 | [Side Effects](rules/03-side-effects.md) | Audit, events, notifications |
| 4 | [Entity Delegation](rules/04-entity-delegation.md) | Delegate rules to Entities |
| 5 | [Error Handling](rules/05-error-handling.md) | Consistent exceptions |
| 6 | [Structure & Naming](rules/06-structure-naming.md) | File location and conventions |
