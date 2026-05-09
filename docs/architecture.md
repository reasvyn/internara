# Architecture: Action-Oriented MVC

Internara uses a **flat Action-Oriented MVC** architecture. Files are grouped by **Functional Layer**, then by **Business Context**.

```
app/
├── Actions/          # Business logic entry points (single execute())
├── Casts/            # Custom Eloquent casts
├── Channels/         # Custom notification channels
├── Console/Commands/ # Artisan commands
├── Contracts/        # Interfaces (ColorableEnum, LabelEnum)
├── Data/             # Data transfer objects
├── Entities/         # Business rules (pure PHP, no ORM)
├── Enums/            # Constants & types
├── Events/           # Domain events
├── Exceptions/       # AppException hierarchy
├── Http/             # Controllers, Middleware, Requests
├── Jobs/             # Queued jobs
├── Livewire/         # Reactive UI components
├── Models/           # Eloquent persistence (flat, no sub-namespace)
├── Notifications/    # Mail, broadcast, database channels
├── Policies/         # Authorization (shared BasePolicy + concerns)
├── Providers/        # Service providers
├── Rules/            # Validation rules
├── Services/         # Infrastructure (DashboardService, EnvironmentAuditor)
└── Support/          # SmartLogger, Settings, AppInfo, helpers
```

Each layer groups files by business context (e.g. `Actions/Internship/`, `Entities/Internship/`, `Enums/Internship/`). Browse the codebase for the full listing.

## Principles

| Principle | Rule |
|---|---|
| **Action Pattern** | Logic in `*Action` classes with a single `execute()` method |
| **Thin Controllers** | Livewire/Controllers delegate all logic to Actions |
| **Flat Models** | All models in `app/Models/`, no sub-namespace |
| **BaseModel** | Abstract base with `HasUuids`, non-incrementing string keys |
| **Entities** | Pure business rules, no ORM. Exposed via `as{EntityName}()` on the model |
| **Auth Boundary** | `User` stays `extends Authenticatable` for Laravel ecosystem |

## Data Flow

```
User Input → Livewire/Controller → Action → Model → Database
                                    ↓
                              Flash/Notification
```

Actions orchestrate between Entities (rules), Models (persistence), and Services (infrastructure).

## Entity Pattern

Business rules extracted from Models into plain PHP objects. Models expose entities via named `as{EntityName}()` methods — never a generic `entity()` method:

```php
class User extends Authenticatable
{
    public function asApprentice(): Apprentice
    {
        return new Apprentice(
            status: AccountStatus::tryFrom($this->latestStatus()?->name ?? ''),
            isLocked: $this->locked_at !== null,
        );
    }
}
```

### Entity Rules

1. **No convenience delegation** — callers go through `$model->as{EntityName}()->method()`.
2. **No `entity()` method** — each model uses a named accessor matching its entity.
3. **Pure entities** — no Model imports, no framework dependencies, testable without database.
4. **BaseEntity** — entities extend `BaseEntity` for shared structure if needed.

## Exception Hierarchy

All exceptions derive from `AppException`:

| Exception | Purpose |
|---|---|
| `ActionException` | Business rule violations within Actions |
| `DomainException` | Domain logic errors |
| `InfrastructureException` | External service / infrastructure failures |
| `PresentationException` | UI / presentation layer errors |

## Role Mapping

| Role | Domain | Context |
|---|---|---|
| Student | Mentee | Participants |
| Teacher | Mentor | School supervisors |
| Supervisor | Mentor | Industry supervisors |
| Admin | Admin | School management |
| SuperAdmin | Admin | System infrastructure |

## Naming Conventions

| Layer | Pattern |
|---|---|
| Action | `app/Actions/{Context}/{Verb}{Noun}Action.php` |
| Entity | `app/Entities/{Context}/{Name}.php` |
| Model | `app/Models/{Name}.php` |
| Livewire | `app/Livewire/{Context}/{Name}.php` |
| Data | `app/Data/{Context}/{Name}.php` |
| Policy | `app/Policies/{Context}/{Name}Policy.php` |
| Enum | `app/Enums/{Context}/{Name}.php` |