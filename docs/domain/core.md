# Core Domain
> Last updated: 2026-05-31
> Changes: docs: audit — all 48 reference items Implemented
> **Status:** ✅ **Fully Implemented** — all 48 files in [reference](core-reference.md) exist


## Purpose

Core is the architectural foundation — every domain depends on it, it depends on no domain.
Core has zero business logic. Its sole purpose is to provide the structural and infrastructural
guarantees that every business domain builds upon.

Core is organized into four distinct layers, each with a specific responsibility:

```
Layer 3 — Contracts       Interfaces that business domains implement and consume
Layer 4 — Base Classes    Abstract classes that every domain class extends
Layer 4 — Infrastructure  Cross-cutting utilities (logging, cache, security, PII)
Layer 4 — Framework       Laravel-specific bridges (middleware, channels, commands)
```

---

## Design Principles

### 1. Zero Dependency on Business Domains

Core MUST NOT import any class from `App\Domain\{BusinessDomain}\*`. Its dependencies are
limited to:

- PHP 8.4+ standard library
- Laravel framework (`Illuminate\*`)
- Spatie packages (`Spatie\Activitylog\*`, `Spatie\Permission\*`)
- Composer packages (dompdf, livewire, etc.)

This rule is absolute and enforced by code review. Violation means the imported
functionality belongs in a Core contract or a dedicated domain.

### 2. Contracts over Concrete Implementations

Cross-domain communication uses interfaces defined in Core, never concrete classes from
other domains. A domain that needs to send notifications depends on `SendsNotifications`
(the interface), not on `SendNotificationAction` (the implementation). Binding happens
in `DomainServiceProvider`.

### 3. Base Classes Enforce Consistency

Every architectural layer has exactly one base class in Core. All domain classes in that
layer must extend it. This guarantees:

- UUID primary keys on all models (via `BaseModel`)
- Transaction-wrapped mutations with audit logging (via `BaseAction`)
- Testable business rules with zero framework dependencies (via `BaseEntity`)
- Consistent role and ownership authorization (via `BasePolicy`)
- Standardized CRUD behavior with search, filter, sort, pagination (via `BaseRecordManager`)

The single exception is `User` model, which must extend `Authenticatable` (Laravel
requirement) but manually applies the same UUID conventions.

### 4. Failures Are Classified by Origin

Two parallel exception trees exist because two fundamentally different failure modes exist:

| Origin | Root | Example |
|---|---|---|
| Framework / infrastructure | `AppException` | Database down, validation failed, rate limited |
| Business rule violation | `DomainException` | Invalid state transition, duplicate registration |

Domain catch blocks target `DomainException` without accidentally catching framework
errors, and vice versa. Both trees use a shared `HasExceptionContext` trait for consistent
hint, context, and CLI-friendly output.

### 5. All Logging Goes Through a Single Gateway

`SmartLogger` is the sole entry point for all logging. It writes to two channels
simultaneously:

- **System log** (file) — technical debugging, errors, performance
- **Activity log** (database) — business audit trail, compliance

PII masking is applied automatically at the key-name level before data reaches either
channel. This guarantees that passwords, tokens, and personal identifiers never appear
in plain-text log files.

### 6. Cache Keys Are Registered in a Single Source of Truth

Every cache key in the application is declared as a constant in `CacheKeys`. No inline
string literals. This makes cache dependencies discoverable, prevents key collisions,
and enables systematic invalidation. Each constant documents its TTL and invalidation
trigger.

### 7. Gradual Migration Is a First-Class Concern

Core provides patterns that support incremental adoption of architectural ideals:

- `Data` DTO supports `fromArray()` so Action inputs can migrate from `array` to typed
  DTO without breaking existing callers
- `HandlesActionErrors` trait can be used independently of `BaseAction` for Read Actions
  that still need error boundary protection
- Events and listeners are optional — side effects can start inline in Actions and be
  extracted into listeners when a second reaction is needed

---

## What Core Does NOT Provide

Core deliberately excludes certain things to maintain its zero-business-logic mandate:

| Excluded | Reason | Belongs In |
|---|---|---|
| Business enums (AccountStatus, InternshipStatus) | Enum values encode domain knowledge | Respective business domains |
| Domain events | Events carry domain-specific payloads | Respective business domains |
| Validation rules for business entities | Rules reference domain knowledge | Entities or Form Objects in business domains |
| Feature-specific middleware | Middleware that checks business state | Respective business domains |
| Seeders or factories | Data generation is domain-specific | `database/seeders/`, `database/factories/` |
| Translations | UI text is domain-specific | `lang/{locale}/` files |
| Route definitions | Routes map to domain-specific controllers | `routes/web/{domain}.php` |
| Migrations that reference business data | Schema design encodes domain relationships | `database/migrations/` |

---

## Domain Boundary

Core is the architectural foundation — every business domain depends on Core, but Core depends on no business domain. It provides base classes, contracts, exceptions, logging infrastructure, and framework integration that every domain consumes. Core contains zero business logic. Business enums, domain events, validation rules for entities, feature-specific middleware, seeders, factories, translations, route definitions, and migrations that reference business data all belong to their respective business domains.

---

## Key Features

- UUID primary keys on all domain models
- Transaction-wrapped mutations with dual-channel audit logging
- Business rule objects with zero framework dependencies
- Role and ownership authorization enforced at route, Livewire, and policy layers
- CRUD list pages with search, filter, sort, pagination, and bulk actions
- State machine enforcement at the contract level (valid transitions, terminal states)
- Cross-domain communication via interfaces, never concrete classes
- Parallel exception tree separating framework failures from business rule violations
- Dual-channel logging (system file + activity database) with automatic PII masking
- Centralized cache key registry preventing key collisions and enabling systematic invalidation
- Content Security Policy, X-Frame-Options, and security headers on all responses
- Request tracing with unique request IDs, user identity, and response timing
- Validation failure as exception instead of HTTP redirect
- 15-point system health verification
- Expired data pruning via scheduled cleanup
- Gradual migration patterns: optional DTOs, optional events, error boundary traits
- Display flash toast notifications for validation errors with actionable messages.
- Render consistent error pages with request tracing IDs visible for debugging.
