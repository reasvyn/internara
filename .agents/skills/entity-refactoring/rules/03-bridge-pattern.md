# Bridge Pattern

## What It Enforces

Models expose their associated Entity through a named accessor method following the pattern
`as{EntityName}(): EntityType`. The accessor name describes the business role the model plays, not
just the class name. Callers always go through this named method rather than a generic `entity()`
accessor.

## Why It Matters

A named accessor communicates intent. `$user->asApprentice()` tells the reader that in this context
the User is being treated as an Apprentice — a specific business role with specific rules. A generic
`$user->entity()` could mean anything and reveals nothing about the business concept being accessed.

The naming convention also makes it easy to find all callers of a particular business role. Grepping
for `asApprentice()` is more useful than grepping for a generic pattern.

## When It Applies

Always when a Model has an associated Entity. The accessor method is a one-liner that calls the
Entity's `fromModel()` factory.

The accessor name should describe the business role, not mirror the class name: User →
`asApprentice()`, not `asUserState()`. Internship → `asPeriod()`, not `asInternshipState()` —
because in context the Internship acts as a period that accepts or rejects registrations.

Exceptions: If the Model name and the business role are the same concept (e.g., AcademicYear is
always just an AcademicYear), `asAcademicYearState()` is acceptable. But prefer the business role
name when it differs from the model name.
