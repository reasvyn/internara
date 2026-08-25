# spatie/laravel-activitylog — Dependency Reference

> **Last updated:** 2026-08-25 **Changes:** feat — initial dependency reference for spatie/laravel-activitylog 5.0.0

## Description

Conceptual reference for **Spatie Activity Log 5** (`spatie/laravel-activitylog 5.0.0`) — the
audit-trail foundation recording who did what, when, from where.

---

## Installed & Role

| | |
|---|---|
| Installed | `5.0.0` (`composer.json`: `^5.0`) |
| Role | Persisted activity log (`activity_log` table) with causer attribution |

---

## Core Concepts

| Concept | What it is |
|---------|-----------|
| **Activity model** | Each entry stores description, subject model, causer (user), properties bag, and timestamps |
| **Auto-logging** | `LogsActivity` trait records model create/update/delete events into the log |
| **Causer resolution** | Authenticated user attached automatically; overridable per write |
| **Query API** | Fluent retrieval (`Activity::causedBy($user)->forSubject($model)`) powering audit views |

---

## How Internara Uses It

- Wrapped by the dual-channel **SmartLogger** (`app/Core/Services/SmartLogger.php`) adding PII
  masking and file+DB routing on top of raw activity writes
- Audit UI in SysAdmin observability (`app/SysAdmin/Observability/Livewire/AuditLogManager.php`)
  backed by a custom `ActivityLog` core model
- Command Actions log through `$this->log()` as part of the transaction pattern
- Package conventions and SmartLogger rules: `activitylog-development` skill,
  [`architecture/logging-pattern.md`](../../architecture/logging-pattern.md)

## Quick References

- [Official docs](https://spatie.be/docs/laravel-activitylog) — full package documentation
- [`docs/architecture/logging-pattern.md`](../../architecture/logging-pattern.md) — SmartLogger design
