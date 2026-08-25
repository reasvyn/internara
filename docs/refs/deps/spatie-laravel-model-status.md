# spatie/laravel-model-status — Dependency Reference

> **Last updated:** 2026-08-25 **Changes:** feat — initial dependency reference for spatie/laravel-model-status 1.20.0

## Description

Conceptual reference for **Spatie Model Status** (`spatie/laravel-model-status 1.20.0`) —
generic status attachment for Eloquent models, used here primarily for the account lifecycle.

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

## How Internara Uses It

- Account lifecycle states (activation, suspension, auto-inactivation) — see
  `User` model status handling and `SetUserStatusAction`
- The 8-state account lifecycle is specified in the auth specs
  ([`specs/index.md`](../../specs/index.md))
- Enum contract: [`architecture/enum-pattern.md`](../../architecture/enum-pattern.md)

## Quick References

- [Official docs](https://spatie.be/docs/laravel-model-status) — full package documentation
- [`docs/architecture/enum-pattern.md`](../../architecture/enum-pattern.md) — StatusEnum state machines
