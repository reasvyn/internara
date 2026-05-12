# Architecture

Internara follows a **flat Action-Oriented MVC** pattern. Files are grouped by functional layer first, then by business context.

## Layer Overview

```
app/
├── Actions/       Business logic entry points (single execute() method)
├── Casts/         Custom Eloquent attribute casts
├── Channels/      Custom notification channels
├── Console/       Artisan commands
├── Contracts/     Interfaces (ColorableEnum, LabelEnum)
├── Data/          Data transfer objects
├── Entities/      Business rules (plain PHP, no framework dependencies)
├── Enums/         Constants and types grouped by domain
├── Events/        Domain events
├── Exceptions/    AppException hierarchy
├── Http/          Controllers, middleware, form requests
├── Jobs/          Queued jobs
├── Livewire/      Reactive UI components
├── Models/        Eloquent models (flat namespace, no sub-directories)
├── Notifications/ Mail, broadcast, and database notification classes
├── Policies/      Authorization policies grouped by domain
├── Providers/     Service providers
├── Rules/         Custom validation rules
├── Services/      Infrastructure services (DashboardService, EnvironmentAuditor)
└── Support/       Utilities (Settings, SmartLogger, BrandColors, AppInfo, helpers)
```

Each layer groups files by business context. For example, `Actions/Internship/`, `Entities/Internship/`, `Enums/Internship/`.

## Design Principles

| Principle | Description |
|---|---|
| **Action Pattern** | Business logic lives in action classes with a single `execute()` method |
| **Thin Controllers** | Controllers and Livewire components delegate all logic to actions |
| **Flat Models** | All Eloquent models live directly in `app/Models/` with no sub-namespace |
| **BaseModel** | Abstract base class providing UUIDs, non-incrementing string keys |
| **Entities** | Business rules live in plain PHP objects — no ORM, no framework dependencies |
| **Auth Boundary** | `User` extends `Authenticatable` for Laravel ecosystem compatibility |

## Data Flow

```
User Input → Livewire/Controller → Action → Model → Database
                                    ↓
                              Flash / Notification
```

Actions orchestrate between entities (rules), models (persistence), and services (infrastructure).

## Entity Pattern

Models expose domain entities through named accessor methods — never a generic `entity()` method:

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

Entity rules:
- Callers always go through `$model->as{EntityName}()->method()`
- No `entity()` method — each model uses a named accessor
- Entities are pure PHP — no Eloquent imports, no framework dependencies, testable without a database
- Entities may extend `BaseEntity` for shared structure

## Exception Hierarchy

All exceptions extend `AppException`. See [Conventions](conventions.md#11-exceptions) for the full exception class hierarchy and usage guidelines.

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
