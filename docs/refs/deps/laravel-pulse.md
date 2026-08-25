# Laravel Pulse — Dependency Reference

> **Last updated:** 2026-08-25 **Changes:** feat — initial dependency reference for laravel/pulse v1.8.0

## Description

Conceptual reference for **Laravel Pulse v1.8** (`laravel/pulse *`) — the first-party production
monitoring dashboard tracking application health (requests, slow queries, jobs, exceptions).

---

## Installed & Role

| | |
|---|---|
| Installed | `v1.8.0` (`composer.lock`; `composer.json`: `*`) |
| Role | Observability dashboard + server-side recorders |

---

## Core Concepts

| Concept | What it is |
|---------|-----------|
| **Recorders** | Pluggable collectors capturing usage signals — slow queries, slow requests, slow jobs, exceptions, queue backlog |
| **Ingest & aggregation** | Signals buffered and aggregated into Pulse's storage tables, keeping overhead low on production traffic |
| **Dashboard** | Pre-built UI (default `/pulse`) with cards per signal type; extensible with custom cards |
| **Authorization** | Dashboard access gated by a dedicated middleware (`Laravel\Pulse\Http\Middleware\Authorize`) configured in `config/pulse.php` |

---

## How Internara Uses It

- Recorder set and domain gating configured in `config/pulse.php`
- Part of the observability stack alongside SmartLogger — operations guidance in
  [`foundation/system-observability.md`](../../guides/system-observability.md)
- Dashboard customization conventions: `pulse-development` skill

## Quick References

- [Official docs](https://laravel.com/docs/pulse) — installation, recorders, custom cards
- [`docs/guides/system-observability.md`](../../guides/system-observability.md) — observability runbook
