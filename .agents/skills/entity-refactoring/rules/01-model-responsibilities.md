# Model Responsibilities

## What It Enforces

Models are strictly data access objects. They define relationships, scopes, casts, attributes, the entity bridge accessor, and factory configuration. They explicitly must NOT contain business rule checks (canX, isY methods), static utility methods, or any logic that answers business questions.

## Why It Matters

Models already carry significant framework responsibility: serialization, relationship loading, event dispatching, attribute casting, and more. Adding business rules overloads them with concerns that change for different reasons. A business rule changes because the business requirement changes. A relationship changes because the data model changes. These should not live in the same class.

Moving business rules to Entities also makes them testable without a database and reusable across multiple data sources. The model becomes thinner, more focused, and easier to reason about.

## When It Applies

Always when defining Model classes. The litmus test: "Would this method still make sense if I swapped the database for an API?" If yes (it's about data access — a relationship, a scope, a cast), keep it in the Model. If no (it's about business logic — can this user do X, is this record in the right state), move it to the Entity.

Allowed in Models: relationships (hasMany, belongsTo), scopes (scopeActive), casts, accessors (getXAttribute), entity bridge (as{EntityName}()), media collections/conversions, HasFactory, formatting helpers (pure string formatting only).

Not allowed in Models: canLogin(), isActive(), canBeDeleted(), hasAvailableSlots(), or any static utility methods.

Exceptions: Pure formatting convenience methods (e.g., `initials()`, `avatarUrl()`) that only transform existing data without business logic are acceptable on Models.
