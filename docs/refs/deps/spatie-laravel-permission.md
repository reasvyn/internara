# spatie/laravel-permission — Dependency Reference

> **Last updated:** 2026-08-25 **Changes:** feat — initial dependency reference for spatie/laravel-permission 8.3.0

## Description

Conceptual reference for **spatie/laravel-permission 8** (`spatie/laravel-permission 8.3.0`) —
the RBAC engine behind Internara's five flat roles and permission checks.

---

## Installed & Role

| | |
|---|---|
| Installed | `8.3.0` (`composer.json`: `^8.0`) |
| Role | Roles + permissions persisted in DB, registered on Laravel's Gate |

---

## Core Concepts

| Concept | What it is |
|---------|-----------|
| **Roles & permissions** | Many-to-many tables (`roles`, `permissions`, pivots); permissions attach to roles and/or directly to models via `HasRoles` trait |
| **Gate integration** | `Role`/`Permission` objects resolve through Laravel's authorization — `@role`, `can()`, policies compose naturally |
| **Caching** | Permission registrar caches lookups; invalidated automatically on change (cache tag aware) |
| **Blade directives** | `@hasrole('teacher')` style templating guards |

---

## How Internara Uses It

- Five flat roles (Super Admin excluded via `Gate::before`) — hierarchy documented in
  [`foundation/rbac.md`](../../guides/rbac.md)
- Policies per module under `app/{Module}/{Submodule}/Policies/`; role checks centralized in
  `app/Core/Policies/Concerns/AuthorizesRoles.php`
- Used throughout Livewire components and controllers (`hasRole`, `givePermissionTo`)
- Package conventions live in the `permission-development` skill

## Quick References

- [Official docs](https://spatie.be/docs/laravel-permission) — full package documentation
- [`docs/guides/rbac.md`](../../guides/rbac.md) — Internara's role model
- [`docs/guides/arch/policy-pattern.md`](../../guides/arch/policy-pattern.md) — three-layer authorization
