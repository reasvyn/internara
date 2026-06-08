# Shared

> **Last updated:** 2026-06-08

Cross-cutting concrete implementations, concrete exceptions, common DTOs, global UI components, support utilities, and helper traits used across all business modules but not belonging to Core infrastructure.

## Purpose & Boundary

Shared fills the gap between Core's abstract base classes and each module's domain-specific code. It contains concrete utilities, reusable exceptions, and global UI elements that any module may import. Shared must remain strictly domain-agnostic — it must never reference business module classes or contain business rules.

Out of scope: domain-specific logic, module enums, module models, feature-specific anything.

## Submodules

Shared has no submodules. Organized by directory directly under `app/`:

- **Data/** — Cross-module DTOs: `AuditCheck`, `AuditReport`. Used for health monitoring and compliance reporting.
- **Enums/** — System-wide enums: `CsvRowResult`, `AuditCategory`, `AuditStatus`.
- **Exceptions/** — Concrete exceptions covering common HTTP error scenarios: `ConflictException` (409), `NotFoundException` (404), `RateLimitException` (429), `RejectedException` (422), `UnauthorizedException` (401), `ValidationFailedException` (422). All extend Core's exception hierarchy.
- **Livewire/** — Global reusable components: `LangSwitcher` (locale toggle), `ThemeSwitcher` (dark/light mode). Concerns: `WithSorting`, `WithRecordSelection`.
- **Policies/Concerns/** — Reusable authorization traits: `AuthorizesRoles`, `AuthorizesOwnership`. Used by all module policies.
- **Support/** — Static utilities: `CacheKeys` (centralized cache key registry), `Color` (hex manipulation), `CsvHandler` (CSV parsing/generation), `Environment` (system environment detection), `HandlesActionErrors` (error normalization), `HasModelStatuses` (status enum integration), `PasswordRules` (password policy presets), `PiiMasker` (PII redaction for logs), `Integrity` (data integrity checks).
- **helpers.php** — Global helper functions: `setting()`, `brand()`, `app_info()`.

## Key Concepts

### Separation from Core

Core provides abstract contracts and base classes. Shared provides concrete implementations. The distinction prevents framework-level abstractions from being polluted with application-specific defaults.

### Centralized Registry

`CacheKeys` is the single source of truth for all cache key strings. Every module must register its cache keys here rather than hardcoding them. This prevents key collisions and enables centralized cache management.

### Global Helpers

The three helper functions (`setting()`, `brand()`, `app_info()`) are the primary way any code — Blade templates, Livewire components, Actions — accesses runtime configuration. They resolve through the Settings fallback chain (override → cache → config → default).

## Dependencies

- Core (base classes, contracts)

## Used By

Every business module.
