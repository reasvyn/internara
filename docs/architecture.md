# Architecture Overview: Action-Oriented MVC

Internara uses a **flat Action-Oriented MVC** architecture. Files are grouped by **Functional Layer**, then by **Business Context**.

```
app/
├── Entities/         # Business rules (pure PHP, no ORM)
├── Actions/          # Business logic entry points (single execute())
├── Models/           # Eloquent persistence (flat, no sub-namespace)
├── Livewire/         # Reactive UI
├── Http/Controllers/
├── Services/         # Infrastructure (PDF, QR, external API)
├── Support/          # Settings, Logger, helpers
├── Enums/            # Constants & types
├── Exceptions/       # AppException, ActionException, etc.
├── Notifications/    # Mail, broadcast, database channels
├── Jobs/             # Queued jobs
├── Events/           # Domain events
├── Policies/         # Authorization
├── Channels/         # Custom notification channels
├── Casts/            # Custom Eloquent casts
├── Console/Commands/ # Artisan commands
├── Contracts/        # Interfaces
├── Rules/            # Validation rules
└── Providers/        # Service providers
```

## Principles

| Principle | Rule |
|---|---|
| **Action Pattern** | Logic in `*Action` classes with a single `execute()` method |
| **Thin Controllers** | Livewire/Controllers delegate all logic to Actions |
| **Flat Models** | All models in `app/Models/`, no sub-namespace |
| **BaseModel** | Abstract base with `HasUuids`, non-incrementing string keys |
| **Entities** | Pure business rules, no ORM. Exposed via `entity()` or `as{Context}()` |
| **Auth Boundary** | `User` stays `extends Authenticatable` for Laravel ecosystem |

## Data Flow

```
User Input → Livewire/Controller → Action → Model → Database
                                    ↓
                              Flash/Notification
```

Requests flow through Actions. Actions orchestrate between Entities (rules), Models (persistence), and Services (infrastructure).

## Entity Pattern

Business rules extracted from Models into plain PHP objects:

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

final readonly class Apprentice
{
    public function isSuspended(): bool
    {
        return $this->status === AccountStatus::SUSPENDED;
    }
}
```

Entity rules: no Model imports, no framework dependencies, testable without database.

## Role Mapping

| Role | Domain | Context |
|---|---|---|
| Student | Mentee | Participants |
| Teacher | Mentor | School supervisors |
| Supervisor | Mentor | Industry supervisors |
| Admin | Admin | School management |
| SuperAdmin | Admin | System infrastructure |

## Naming

- Action: `app/Actions/{Context}/{Verb}{Noun}Action.php`
- Entity: `app/Entities/{Context}/{Name}.php`
- Model: `app/Models/{Name}.php` (flat)
- Livewire: `app/Livewire/{Context}/{Name}.php`
