# Entity Business Rules

## What It Enforces

Business rules are encapsulated in pure PHP Entity classes — `final readonly` classes extending `BaseEntity` with zero framework dependencies. Entities live in `app/{Module}/Entities/` and are instantiated through a `fromModel(Model): static` factory method.

## Why It Matters

Entities separate business logic from data access. This means:
- Business rules can be tested without a database (pure function calls)
- The same business logic is reused everywhere it's needed
- Rules are centralized — changing a business requirement means changing one class
- Rules are explicit and named, not hidden in conditionals

## When It Applies

Create an Entity when a Model accumulates boolean capability checks (`canX()`), state transition logic, date-based business rules, or multi-field validation. The Entity extracts only the state it needs from the Model through `fromModel()`.

Entity vs Enum decision:
- Use an Enum for simple constants with light methods (`isTerminal()`, `canTransitionTo()`)
- Use an Entity for multiple fields, complex logic, and date calculations

The Entity constructor receives all state as typed properties. Methods return business answers: `canLogin()`, `isSuspended()`, `requiresSetup()`, `canBeDeleted()`.

Exceptions: If a Model has no business rules, it doesn't need an Entity. Create one only when rules accumulate.
