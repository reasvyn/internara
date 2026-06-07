# Shared — Documentation Overview

> Last updated: 2026-06-05 Changes: Created shared module overview to separate cross-cutting
> utilities, DTOs, enums, exceptions, and global UI components from Core

Cross-cutting utility classes, concrete exceptions, common DTOs, global UI components, and helper
traits that are shared across all business modules but do not belong to core infrastructure or base
classes.

For complete technical reference including API, files, and namespace details, see
[shared-reference.md](shared-reference.md).

---

## Key Principles

- **Separation from Core** — The Core module only contains abstract base classes (like `BaseModel`,
  `BaseAction`, `BasePolicy`), contracts (like `LabelEnum`, `StatusEnum`), and foundational
  infrastructure (like `SmartLogger`, security middleware). Shared contains concrete cross-module
  implementations, static utilities, and global UI helpers.
- **Zero Business Domain Rules** — Shared components must not contain business logic specific to any
  domain module (e.g., user profiles or placement quotas). They provide generic structures (like CSV
  processing, audit data types, or rate limiting exceptions) that any module can use.
- **Global UI Reusability** — Common, interactive UI elements (like the language switcher and theme
  switcher) are located in the Shared directory to be easily embedded across layout templates.
- **Centralized Registry** — All application-wide cache keys, static settings, and utility rule
  presets (like password rules) are centralized in Shared to prevent duplication and naming
  collisions.

---

## Context Boundary

Shared resides outside individual business modules at the root level of the `app/` directory (Layers
5–11 in the 12-layer architecture). Every business module may import Shared classes. Shared depends
on Core base classes, contracts, and Laravel/Spatie packages. It is divided into:

- **app/Data/**: Common DTOs that represent cross-module data structures (e.g., system audit
  results).
- **app/Enums/**: Cross-module enums that represent system-wide values (e.g., row-processing
  results, audit category/status).
- **app/Exceptions/**: Concrete exceptions thrown by actions, controllers, or services.
- **app/Livewire/**: Global, reusable UI components and Livewire traits.
- **app/Policies/**: Reusable authorization helper traits.
- **app/Support/**: General helper classes, static utilities, and domain-agnostic tools.

---

## Module Rules

- Shared classes must not reference or import classes from business modules (e.g., `App\User` or
  `App\Partners`). They must remain strictly domain-agnostic.
- Any helper utility that performs write operations must be placed in a business module or
  implemented as a Command Action within a module; Shared only contains static helpers and stateless
  utilities.
- Concrete exceptions representing common validation, permission, or resource errors must extend
  `AppException` or `ModuleException` from the Core module.
- All global cache keys must be defined as constants in the Shared `CacheKeys` class rather than
  hardcoded in individual modules.

---

## Submodules

Shared has no submodules. It is organized into directories directly under `app/`:

- **Data/**: `AuditCheck`, `AuditReport`
- **Enums/**: `CsvRowResult`, `AuditCategory`, `AuditStatus`
- **Exceptions/**: `ConflictException`, `NotFoundException`, `RateLimitException`,
  `RejectedException`, `UnauthorizedException`, `ValidationFailedException`
- **Livewire/**: `LangSwitcher`, `ThemeSwitcher`, and concerns (`WithSorting`,
  `WithRecordSelection`)
- **Policies/**: Concerns (`AuthorizesRoles`, `AuthorizesOwnership`)
- **Support/**: `CacheKeys`, `Color`, `CsvHandler`, `Environment`, `HandlesActionErrors`,
  `HasModelStatuses`, `PasswordRules`, `PiiMasker`, `Integrity`
- **Settings/Support/** (cross-reference): `Locale`, `Theme` — localization & dynamic theming

---

## Error Handling & Failure Modes

- **Circular Dependencies**: Importing business module code inside a Shared helper creates a
  circular dependency, leading to coupling that breaks modularity. Enforced by static analysis.
- **Cache Key Collisions**: Hardcoding cache strings directly in actions rather than using
  `CacheKeys` constants can lead to accidental overwrites. Developers must always add new keys to
  the central registry.
- **Bypassing Typed Exceptions**: Throwing raw PHP exceptions (`RuntimeException`) instead of the
  concrete exceptions in `app/Exceptions/` prevents the presentation layer from returning structured
  API responses (like 404 or 422 JSON errors) or tracking them cleanly in logs.

---

## Quick References

### Data & Persistence

- **2** common DTOs (`AuditCheck`, `AuditReport`)
- Centralized structures for health monitoring and compliance checks.

### User Interface

- **2** global Livewire components (`LangSwitcher`, `ThemeSwitcher`)
- Used in the base layout to manage localization and dark/light mode toggle.

### Support & Helpers

- **11** static support utilities managing formatting, locale selection, masking, and environment
  configuration.

---

For complete technical reference, see [shared-reference.md](shared-reference.md).
