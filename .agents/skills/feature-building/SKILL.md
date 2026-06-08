---
name: feature-building
description: Apply this skill when building any new feature, modifying existing code, or adding a new module concept. It encodes the full feature lifecycle from understanding module context through testing and quality checks.
---

# Feature Building Skill

## When to Activate

Apply this skill when building any new feature, modifying existing code, or adding a new module concept. This skill encodes the full feature lifecycle — from understanding module context through testing and quality checks.

## Data Flow

User input → Livewire/Controller → Action → Model/Entity → Database

Reads: Controller/Livewire → Read Action → Model → Database (simple queries skip the Action layer).

## Key References

- **Architecture**: `docs/architecture.md` — 12-layer architecture, Action Triad, data flow
- **Conventions**: `docs/conventions.md` — base classes, naming, file structure, testing
- **Module docs**: `docs/modules/{module}.md` — module-specific lifecycle context
- **Module references**: `docs/modules/{module}-reference.md` — API reference, file paths, schemas

## Feature Workflow

### Step 1 — Understand the Module

Read `docs/modules/{module}.md` for the module's purpose, boundary, and lifecycle. Check `docs/modules/{module}-reference.md` for existing files, table schemas, and dependencies.

### Step 2 — Migration & Model

```bash
php artisan make:migration create_{table}_table
```

- Model extends `BaseModel` (UUID PK via `HasUuids`)
- Model uses `#[Fillable]` attribute (not `$fillable` property)
- Model uses `HasFactory` trait
- Migration uses `$table->uuid('id')->primary()` and `foreignUuid()->constrained()`

### Step 3 — Entity (if business rules exist)

- `final readonly` extending `BaseEntity`
- `fromModel(Model): static` factory
- Named accessor on Model (`as{Name}(): EntityType`)
- Business rule methods only — no persistence

### Step 4 — Enum (if state machine)

- `string`-backed, implements `LabelEnum`
- State machine enums additionally implement `StatusEnum` (`canTransitionTo()`, `isTerminal()`, `validTransitions()`)
- Cases use `UPPER_SNAKE`, value is lowercase

### Step 5 — Action

- Command/Process: extends `BaseAction`, single `execute()`, `transaction()`, `log()`
- Read: plain class, no base class required
- Delegate business rule checks to Entity methods

### Step 6 — Policy (if authorization needed)

- Extends `BasePolicy` (provides `AuthorizesRoles`, `AuthorizesOwnership` traits)
- Auto-discovered from `app/*/Policies/` by convention
- `super_admin` bypasses all gates via `Gate::before()`

### Step 7 — Livewire Component

- CRUD tables extend `BaseRecordManager` (pagination, search, sort, selection, bulk actions)
- Thin — delegates all writes to Actions, all complex queries to Read Actions
- Form state in Form Objects (extending `Livewire\Form`) for complex forms

### Step 8 — Blade View

- Uses maryUI components (`x-mary-table`, `x-mary-modal`, `x-mary-button`)
- Tailwind CSS v4 with `@import "tailwindcss"` + `@theme` directives
- All user-facing strings use `__()` translation helpers

### Step 9 — Routes

- Route file: `routes/web/{module}.php`
- Named routes: `{prefix}.{resource}.{action}`
- Imported in `routes/web.php` in dependency order

### Step 10 — Translations

- English: `lang/en/{module}.php`
- Indonesian: `lang/id/{module}.php`
- Every user-facing string must be translated

### Step 11 — Tests

- **Unit tests** (no DB): Entities, Enums, Data DTOs
- **Feature tests** (with DB): Actions, Livewire components
- `LazilyRefreshDatabase` over `RefreshDatabase`
- `assertModelExists()` over `assertDatabaseHas()`

### Step 12 — Quality

```bash
vendor/bin/pint --format agent
php artisan test --compact
```

## Layer Directory Mapping

| Layer | Directory |
|-------|-----------|
| Model | `app/{Module}/{SubModule}/Models/` |
| Entity | `app/{Module}/{SubModule}/Entities/` |
| Enum | `app/{Module}/{SubModule}/Enums/` or `app/{Module}/Types/` |
| Action | `app/{Module}/{SubModule}/Actions/` |
| Policy | `app/{Module}/{SubModule}/Policies/` |
| Livewire | `app/{Module}/{SubModule}/Livewire/` |
| View | `resources/views/{module}/{submodule}/` |
| Test | `tests/{Feature,Unit}/{Module}/{SubModule}/` |

Cross-submodule code lives at the module root (e.g., `app/{Module}/Actions/`, `app/{Module}/Livewire/`). Shared cross-module code lives directly under `app/` (e.g., `app/Livewire/`, `app/Data/`).

## Verification

- Data flow: Component → Action → Model/Entity?
- No inline DB mutations or business rules in Livewire?
- Translations in both English and Indonesian?
- Tests at the appropriate level (unit vs feature)?
- Pint formatted and test suite passing?
