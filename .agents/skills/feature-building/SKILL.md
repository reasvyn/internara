# Feature Building Skill

## When to Activate

Apply this skill when building any new feature, modifying existing code, or adding a new domain concept. This skill encodes the full feature lifecycle — from understanding domain context through testing and quality checks.

## Core Principles

Every feature follows a layered architecture where each layer has a distinct responsibility:

Livewire Components handle UI state (form bindings, modal visibility) and delegate to Actions. Actions handle validation, orchestrate persistence in transactions, and dispatch side effects. Models handle data access (queries, relationships, scopes). Entities handle pure business rules without framework dependencies. Enums define labeled constants and state machines with transition validation.

Data flows unidirectionally: User input enters through a Livewire component, which calls an Action, which reads/writes through a Model, checks business rules through an Entity, and emits audit/event side effects.

## Feature Workflow

1. Understand the domain: read `docs/en/domain/{domain}.md` for lifecycle context
2. Create migration and Model (UUID PK, BaseModel, Fillable attribute, HasFactory)
3. Create Entity if business rules exist (final readonly, BaseEntity, fromModel bridge)
4. Create Enum if state machine (string-backed, LabelEnum/StatusEnum)
5. Create Action (BaseAction, single execute, validation, transaction, entity delegation)
6. Create Policy if authorization needed (BasePolicy, role/ownership gates)
7. Create Livewire component (thin, delegates to Actions, BaseRecordManager for CRUD tables)
8. Create Blade view (maryUI, Tailwind, translation keys)
9. Register routes in `routes/web/{domain}.php`
10. Add translations in `lang/en/{domain}.php` and `lang/id/{domain}.php`
11. Write tests: Entity tests (no DB), Feature tests (Action/Livewire with DB)
12. Quality: run Pint, build assets, run test suite

## Layer Reference

Every layer has a canonical directory: Actions, Models, Entities, Enums, Livewire, Policies, Views, Routes, Tests, Support, Data, Contracts — all under `app/Domain/{Domain}/`. Views mirror under `resources/views/{domain}/`. Routes are per-domain in `routes/web/{domain}.php`.

## Verification Before Finalizing

- Does the feature follow the data flow: Component → Action → Model/Entity?
- Are there no inline DB mutations, business rules, or side effects in Livewire?
- Are translations provided in both English and Indonesian?
- Are tests written at the appropriate level (Entity unit vs Action feature)?
- Has Pint formatting been applied and the test suite run?
