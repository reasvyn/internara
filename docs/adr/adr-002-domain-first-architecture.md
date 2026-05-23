# ADR-002: Domain-First Architecture

## Status
Accepted

## Context
The application manages a complex business domain — internship management — with 24 distinct
business concepts (Registration, Internship, Assessment, Placement, etc.). Each concept has
persistence, business rules, UI components, and authorization logic.

Two organizational approaches were considered:

1. **Flat layering** (Laravel defaults): `app/Models/`, `app/Http/Controllers/`,
   `app/Livewire/`, `app/Policies/` — all models in one directory, all controllers in another,
   etc. This is the conventional Laravel structure.
2. **Domain-first**: Each business concept owns its complete vertical slice in one directory —
   `app/Domain/{Domain}/` containing Models, Actions, Livewire, Policies, Entities, Enums,
   etc.

Flat layering scatters a single feature (e.g., "submit an assignment") across 8+ directories:
the Model is in `app/Models/`, the form request in `app/Http/Requests/`, the policy in
`app/Policies/`, the Livewire component in `app/Livewire/`, the notification in
`app/Notifications/`, the event in `app/Events/`, the listener in `app/Listeners/`, and the
view in `resources/views/`. This makes it difficult to:

- Reason about feature boundaries and encapsulation
- Enforce architectural rules (e.g., "notifications should not import Livewire")
- Refactor a domain without touching unrelated code
- Onboard new developers — the cognitive overhead of navigating 8+ directories per feature

## Decision
Code is organized by business domain, not by technical layer. Every domain lives under
`app/Domain/{Domain}/` and owns its complete vertical slice. The `Core` domain provides base
classes every other domain depends on. `Shared` provides cross-domain utility code used by at
least two domains.

This structure is enforced by architecture tests (`tests/Arch/`):
- `DomainBoundariesArchTest` — domains should not import from other business domains directly
- `LayerSeparationArchTest` — controllers don't import Actions, notifications don't import
  Livewire, etc.

## Consequences
- **Positive**: A feature touches exactly one directory tree — high cohesion, low coupling.
- **Positive**: Domain boundaries are explicit and testable. Architecture tests catch violations
  in CI.
- **Positive**: Refactoring a domain (e.g., changing Registration's state machine) affects only
  `app/Domain/Registration/` and its consumers via defined interfaces.
- **Positive**: Adding a new domain is mechanical — create the directory, add subdirectories as
  needed, register routes.
- **Negative**: Slightly more boilerplate than flat layering for very simple domains (e.g.,
  a single-model domain still needs at minimum Models + Actions directories).
- **Negative**: Laravel's auto-discovery expects flat structures occasionally, requiring
  explicit registration in service providers.

## References
- `app/Domain/` (24 domain directories)
- `docs/architecture.md`
- `tests/Arch/DomainBoundariesArchTest.php`
- `tests/Arch/LayerSeparationArchTest.php`
