# Core — Documentation Overview

> Last updated: 2026-06-03
> **Status:** ✅ **Fully Implemented** — Comprehensive overview of the Core domain.

Provides foundational infrastructure, base classes, and application-wide utilities

For complete technical reference including API, models, actions, and components, see [core-reference.md](core-reference.md).

---

## Key Principles

- BaseModel provides standard persistence features
- BaseAction enforces single-responsibility pattern
- BasePolicy standardizes authorization
- Shared contracts and utilities available to all domains

### Mandatory Base Classes

Every layer within the project must extend the corresponding Core base class:

| Layer | Base Class | Location |
|---|---|---|
| **Model** | `BaseModel` (or `Authenticatable`) | `app/Domain/Core/Models/BaseModel.php` |
| **Action** | `BaseAction` | `app/Domain/Core/Actions/BaseAction.php` |
| **Entity** | `BaseEntity` (final readonly) | `app/Domain/Core/Entities/BaseEntity.php` |
| **Policy** | `BasePolicy` | `app/Domain/Core/Policies/BasePolicy.php` |
| **Livewire CRUD** | `BaseRecordManager` | `app/Domain/Core/Livewire/BaseRecordManager.php` |
| **Controller** | `BaseController` | `app/Domain/Core/Http/Controllers/BaseController.php` |
| **Form Request** | `FormRequest` (Core's request) | `app/Domain/Core/Http/Requests/FormRequest.php` |
| **DTO** | `Data` | `app/Domain/Core/Data/Data.php` |
| **Exception** | `AppException` or `DomainException` | `app/Domain/Core/Exceptions/` |
| **Enum** | Must implement `LabelEnum` | `app/Domain/Core/Contracts/LabelEnum.php` |
| **Logging** | Use `SmartLogger` | `app/Domain/Core/Support/SmartLogger.php` |

---

## Context Boundary

Foundational - all domains depend on Core. Core has minimal external dependencies.

---

## Domain Rules

- All models extend BaseModel or Authenticatable
- All business logic encapsulated in Actions
- Authorization checked through Policies
- Consistent exception handling across domains

---

## Quick References

### Actions & Business Logic
- **1** actions across all aggregates
- Business logic operations for core domain

### Data & Persistence
- **2** models managing core data
- Eloquent relationships and queries

### User Interface
- **3** Livewire components for real-time interaction
- Views in `resources/views/core/`

### Authorization
- **1** authorization policies
- Role-based access control per resource

---

For complete technical reference, see [core-reference.md](core-reference.md).
