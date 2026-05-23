# Core — API Reference

Total: 43 files

## Actions

| File | Class | Extends | Description |
|---|---|---|---|
| `Core/Actions/BaseAction.php` | `BaseAction` | — | Abstract base for all domain actions with error handling and transactions |

## Console Commands

| File | Class | Extends | Description |
|---|---|---|---|
| `Core/Console/Commands/CacheWarmCommand.php` | `CacheWarmCommand` | `Command` | Pre-warms caches (routes, config, views) |
| `Core/Console/Commands/CleanupCommand.php` | `CleanupCommand` | `Command` | Cleans up old logs and temporary files |
| `Core/Console/Commands/HealthCommand.php` | `HealthCommand` | `Command` | System health check (DB, cache, storage) |

## Contracts

| File | Class/Interface | Description |
|---|---|---|
| `Core/Contracts/ColorableEnum.php` | `ColorableEnum` | Interface for enums that provide CSS color values |
| `Core/Contracts/LabelEnum.php` | `LabelEnum` | Interface for enums that provide human-readable labels |
| `Core/Contracts/SendsNotifications.php` | `SendsNotifications` | Interface for notification-sending services |
| `Core/Contracts/StatusEnum.php` | `StatusEnum` | Interface for state-machine enums with status transitions |

## Data (DTOs)

| File | Class | Extends | Description |
|---|---|---|---|
| `Core/Data/AuditCheck.php` | `AuditCheck` | `Data` | Immutable DTO for a single audit check result |
| `Core/Data/AuditReport.php` | `AuditReport` | `Data` | Immutable DTO for a full audit report |
| `Core/Data/Data.php` | `Data` | — | Abstract base for immutable readonly DTOs |

## Entities

| File | Class | Extends | Description |
|---|---|---|---|
| `Core/Entities/BaseEntity.php` | `BaseEntity` | — | Abstract readonly base for domain entities (stateless business objects) |

## Enums

| File | Class | Implements | Description |
|---|---|---|---|
| `Core/Enums/AuditCategory.php` | `AuditCategory` | `LabelEnum` | Audit check categories |
| `Core/Enums/AuditStatus.php` | `AuditStatus` | `LabelEnum` | Audit check pass/fail status |

## Exceptions

| File | Class | Extends | Description |
|---|---|---|---|
| `Core/Exceptions/AppException.php` | `AppException` | `RuntimeException` | Abstract base for all application exceptions |
| `Core/Exceptions/ActionException.php` | `ActionException` | `AppException` | Abstract base for action-level exceptions |
| `Core/Exceptions/ConflictException.php` | `ConflictException` | `ActionException` | Thrown on data conflicts (e.g., duplicate) |
| `Core/Exceptions/DomainException.php` | `DomainException` | `RuntimeException` | Abstract base for domain rule violations |
| `Core/Exceptions/InfrastructureException.php` | `InfrastructureException` | `AppException` | Abstract base for infrastructure failures |
| `Core/Exceptions/NotFoundException.php` | `NotFoundException` | `PresentationException` | Thrown when a resource is not found |
| `Core/Exceptions/PresentationException.php` | `PresentationException` | `AppException` | Abstract base for presentation-layer exceptions |
| `Core/Exceptions/RateLimitException.php` | `RateLimitException` | `InfrastructureException` | Thrown on rate limit exceeded |
| `Core/Exceptions/RejectedException.php` | `RejectedException` | `DomainException` | Thrown when a business rule rejects an operation |
| `Core/Exceptions/UnauthorizedException.php` | `UnauthorizedException` | `PresentationException` | Thrown on authorization failure |
| `Core/Exceptions/ValidationFailedException.php` | `ValidationFailedException` | `ActionException` | Thrown on input validation failure |

## Middleware

| File | Class | Description |
|---|---|---|
| `Core/Http/Middleware/LogContext.php` | `LogContext` | Middleware that adds request context to logs |
| `Core/Http/Middleware/SecurityHeaders.php` | `SecurityHeaders` | Middleware that adds security response headers |

## Controllers

| File | Class | Description |
|---|---|---|
| `Core/Http/Controllers/BaseController.php` | `BaseController` | Abstract base controller |

## Form Requests

| File | Class | Extends | Description |
|---|---|---|---|
| `Core/Http/Requests/FormRequest.php` | `FormRequest` | `LaravelFormRequest` | Abstract base form request with custom validation failure handling |

## Livewire

| File | Class | Extends | Description |
|---|---|---|---|
| `Core/Livewire/BaseRecordManager.php` | `BaseRecordManager` | `Component` | Abstract base for CRUD table Livewire components |

## Models

| File | Class | Extends | Description |
|---|---|---|---|
| `Core/Models/ActivityLog.php` | `ActivityLog` | `Activity` (Spatie) | Extended activity log model |
| `Core/Models/BaseModel.php` | `BaseModel` | `Model` | Abstract base model with UUID primary keys |

## Policies

| File | Class | Extends | Description |
|---|---|---|---|
| `Core/Policies/BasePolicy.php` | `BasePolicy` | — | Abstract base policy with ownership and role authorization |

## Support

| File | Class | Description |
|---|---|---|
| `Core/Support/Integrity.php` | `Integrity` | Utility for file integrity checks |
| `Core/Support/PiiMasker.php` | `PiiMasker` | Utility for masking PII in logs/output |
| `Core/Support/SmartLogger.php` | `SmartLogger` | Fluent logging utility supporting activity log + PSR-3 |
| `Core/Support/HandlesActionErrors.php` | `HandlesActionErrors` | Trait providing withErrorHandling() and transaction() to actions |
