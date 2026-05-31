# Shared Domain
> Last updated: 2026-05-31
> Changes: docs: audit — all 9 reference + 17 Blade items Implemented
> **Status:** ✅ **Fully Implemented** — all PHP files and Blade components in [reference](shared-reference.md) exist


## Purpose

Shared is a collection of cross-domain utility code that doesn't belong to any single business
domain or to Core. When two or more domains need the same function — environment detection,
locale management, CSV handling, theme resolution — the logic lives here.

Shared has no Models, Controllers, Routes, Views, or database migrations. It contains only
pure utility classes, support traits, and a minimal set of Livewire components for cross-domain
UI features (language and theme switching).

---

## Design Principles

### 1. Used by at Least Two Domains

Code belongs in Shared only when consumed by at least two different business domains.
Single-domain utilities stay in their owning domain. This prevents Shared from becoming
a dumping ground for "something I don't know where to put."

### 2. Stateless and Immutable

Every class in Shared is either:

- A **final class with static methods** — no mutable state, no constructor injection
- A **final readonly object** — immutable after construction
- A **trait** — provides behavior without state
- An **enum** — self-contained constants

This guarantees that Shared utilities have no side effects and are safe to call from
anywhere in the application.

### 3. No Business Logic

Shared utilities operate on primitive types, framework abstractions, or Core contracts.
They never encode business rules — no status checks, no permission gating, no domain
concepts. Business logic belongs in business domains.

The single exception is `Theme`, which reads color values from the Settings domain.
This is a cross-cutting concern — color resolution must access the settings store, and
duplicating the color storage in Shared would violate DRY.

### 4. Backward Compatibility Matters

Every domain depends on Shared. A breaking change in a Shared utility cascades to every
consumer. Add new methods rather than changing existing signatures. Deprecate gradually
rather than removing.

### 5. Framework Dependencies Are Explicit

Shared may depend on Laravel's facades, helpers, and service container — unlike entities
in business domains which must be framework-free. This is acceptable because Shared
utilities are pure infrastructure code, not domain rules.

---

## What Shared Does NOT Contain

| Excluded | Reason | Belongs In |
|---|---|---|
| Models | Shared has no database tables | Business domains |
| Routes | Shared has no HTTP endpoints | `routes/web/{domain}.php` |
| Controllers | Shared has no HTTP handling | Business domains |
| Views (except Livewire) | UI belongs to consuming domains | Domain-specific view directories |
| Migrations | Shared has no schema | `database/migrations/` |
| Business logic | Domain rules belong in business domains | Respective business domains |
| Feature-specific utilities | Single-domain utilities stay in their domain | The consuming domain |

---

## Domain Boundary

The Shared domain owns cross-cutting utilities and infrastructure that serve at least two other business domains. It encompasses environment detection, locale management (bilingual Indonesian/English with session-based preference), a theme system that resolves color values into CSS custom properties for light and dark modes, and CSV handling for export, import, and template downloads. It also provides Livewire components for language and theme toggling, plus a development helper that logs warnings for missing translation keys.

Shared does not own any business logic, domain models, database tables, HTTP routes, controllers, or views. It never encodes domain-specific rules — no status checks, no permission gating, no business concepts. When a utility serves only a single domain, it stays in that domain rather than being promoted to Shared. Shared deliberately contains no database migrations because it has no schema of its own.

Shared references and consumes settings from the Settings domain (specifically for theme color resolution), but it does not own or manage those settings. It relies on Core for contracts and base infrastructure but provides no business-domain abstractions itself.

---

## Key Features

- Detect the runtime environment (development, production, debug mode) with centralized checks.
- Manage bilingual locale switching between Indonesian and English based on user session preference.
- Resolve application color settings into CSS custom properties for light and dark theme rendering.
- Export, import, and download template files in CSV format with optional header validation.
- Toggle between available display languages via a Livewire language switcher component.
- Toggle between light, dark, and system-default themes via a Livewire theme switcher component.
- Log warnings during development when translation keys are missing from language files.
- Switch the interface language instantly via a language dropdown selector in the navigation bar.
- Toggle between light mode, dark mode, and system-default themes via a theme selector accessible from any page.
- Import records from a CSV file with a validation summary showing accepted, skipped, and errored rows.
- Download CSV template files with pre-filled header rows and example data entries.
