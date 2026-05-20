# Action Refactoring Skill

## When to Activate

Apply this skill whenever creating, modifying, or reviewing Action classes or when refactoring business logic out of Livewire components or Controllers into proper Action classes. Trigger on any operation that involves validation, persistence, side effects, or business rule enforcement.

## Core Principles

Actions are the orchestration layer — they coordinate what happens during a business operation without making business decisions themselves. An Action receives input, validates it, asks an Entity whether the operation is allowed, persists changes in a transaction, and emits side effects (logs, events, notifications).

Key constraints:
- One Action = one business operation, expressed as a single `execute()` method
- Validation is authoritative here — not just a UX concern
- Business rule questions go to Entities, not inline conditionals
- All persistence and side effects happen inside a database transaction
- Dependencies are injected via constructor promotion

## Layer Boundaries

Actions sit between Livewire/Controllers (which handle UI state) and Models (which handle data access). Entities sit beside Actions as pure business-rule objects. Actions must never contain inline `canX()` checks — those belong in Entities. Actions must never contain raw SQL queries or direct static helper logic — those belong in Support.

## Verification Before Finalizing

- Does the Action have exactly one `execute()` method?
- Does it extend BaseAction or use the HandlesActionErrors trait?
- Are business rule checks delegated to Entity methods?
- Are all DB writes wrapped in a transaction?
- Are side effects (logging, events, notifications) inside the transaction?
- Does it throw RejectedException for rule violations, not RuntimeException?
- Is validation run before business logic?
- Are static utilities (formatting, generation, parsing) delegated to Support classes?
