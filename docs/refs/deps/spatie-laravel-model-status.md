# spatie/laravel-model-status — Dependency Reference

> **Last updated:** 2026-08-25 **Changes:** fix — corrected usage section (package is effectively unused); marked deprecated pending removal (#419)

> **⚠ DEPRECATED — DO NOT USE IN NEW CODE.** This package is scheduled for removal
> ([#419](https://github.com/reasvyn/internara/issues/419)). No model may adopt the
> `HasStatus` trait or reference `Spatie\ModelStatus\*`. Status persistence is app-owned.

## Description

Conceptual reference for **Spatie Model Status** (`spatie/laravel-model-status 1.20.0`) —
generic status attachment for Eloquent models.

**Deprecation note:** investigation for #419 found the package is *effectively unused*: no model
imports `HasStatus`, no `model_statuses` migration exists, and the only artifact is
`config/model-status.php` with no consumer. The account lifecycle runs on app-owned
implementations instead.

---

## Installed & Role

| | |
|---|---|
| Installed | `1.20.0` (`composer.json`: `^1.18`) |
| Role | Persisted status per model + status history, validated against allowed values |

---

## Core Concepts

| Concept | What it is |
|---------|-----------|
| **HasStatus trait** | Adds polymorphic statuses to any model; one current status + full history retained |
| **Allowed statuses** | Statuses validated against `getDefaultStatus` or explicit validation at set-time |
| **Queries** | Scope helpers to fetch models by current status |

In this codebase, enum-driven state machines (`StatusEnum` contract) define legal transitions at
the domain layer — this package provides the persistence, the transition rules stay in Enums/
Entities.

---

## Status Persistence Is App-Owned

Status handling does **not** flow through this package:

- `User::setStatus()` (`app/User/Models/User.php:200`) and `Registration::setStatus()`
  force-fill a plain `status` column backed by enum values
- Transitions are governed by the `StatusEnum` contract at the domain layer — persistence is a
  simple column, not a package-managed history table
- Removal plan and evidence: [#419](https://github.com/reasvyn/internara/issues/419)

## Quick References

- [Official docs](https://spatie.be/docs/laravel-model-status) — full package documentation
- [`docs/guides/arch/enum-pattern.md`](../../guides/arch/enum-pattern.md) — StatusEnum state machines
