# Laravel Framework — Dependency Reference

> **Last updated:** 2026-08-25 **Changes:** feat — initial conceptual reference for laravel/framework v13.24.0 (version reconciled against composer.lock)

## Description

Conceptual reference for **Laravel 13** (`laravel/framework v13.24.0`, pinned in `composer.lock`)
as the application backbone of Internara: what the release delivers, the core concepts every
contributor must know, and how this repository builds on them. Component-specific behavior
(queues, cache, sessions) lives in [`infrastructure/`](../../infrastructure/index.md).

---

## Version & Support Lifecycle

| | |
|---|---|
| Installed | `laravel/framework v13.24.0` (`composer.json`: `^13.0`) |
| Released | March 17, 2026 (annual cadence) |
| PHP requirement | 8.3 – 8.5 (Internara pins `^8.4`) |
| Bug fixes until | Q3 2027 |
| Security fixes until | March 17, 2028 |
| Upgrade character | Minor-effort from 12.x — most apps upgrade without changing application code |

---

## What Laravel 13 Delivers

- **First-party AI SDK** — unified API for text generation, tool-calling agents, embeddings,
  audio, images, vector stores
- **JSON:API resources** — first-party serialization compliant with the JSON:API spec
  (resource objects, relationship inclusion, sparse fieldsets, links)
- **Request forgery protection** — CSRF middleware formalized as `PreventRequestForgery` with
  origin-aware verification, token-based protection preserved
- **Queue routing by class** — central default queue/connection rules via `Queue::route(...)`
- **Expanded PHP attributes** — declarative, colocated configuration: `#[Middleware]`,
  `#[Authorize]` on controllers; `#[Tries]`, `#[Backoff]`, `#[Timeout]`, `#[FailOnTimeout]` on jobs
- **`Cache::touch(...)`** — extend a cached item's TTL without retrieving and re-storing
- **Semantic / vector search** — native vector queries and embedding workflows (PostgreSQL +
  `pgvector`)

Notable hardening changes from 12.x relevant to any upgrade review: cache `serializable_classes`
config defaults to `false` (deserialization gadget-chain protection), cache/Redis key prefixes use
hyphenated suffixes, `upsert()` validates a non-empty `uniqueBy` even on MySQL/MariaDB, and
polymorphic pivot table name inference now generates pluralized names for custom pivot models.

---

## Core Concepts

The framework's mental model — everything below resolves through these primitives:

| Concept | What it is | Why it matters here |
|---------|-----------|---------------------|
| **Service container** | Central DI registry; classes resolve dependencies by constructor type-hint | C2 forbids `app()->make` — Actions receive dependencies via constructor injection resolved by the container |
| **Service providers** | Bootstrap point; `register()` binds into the container, `boot()` runs after all bindings; registered in `bootstrap/providers.php` | Module wiring and third-party package registration |
| **Facades** | Static proxies to container services (`Cache::get()` → instance behind the scenes) | Terse syntax with testability; used across infrastructure code |
| **Routing & middleware** | Declarative HTTP entry points with layered request filters | Routes split per module under `routes/web/{module}.php`; middleware groups enforce auth/state |
| **Eloquent ORM** | ActiveRecord models: conventions over configuration, relations, eager loading | Models are persistence-only (D4 `#[Fillable]`); business rules live in Entities above them |
| **Migrations & schema builder** | Versioned schema evolution in PHP | All 37+ domain tables; FKs require `onDelete`/`onUpdate` (D6) |
| **Validation** | Form Requests / inline validators feeding typed data onward | D5: raw `$request->all()` never reaches create/update paths |
| **Events & listeners** | Decoupled side effects, queued dispatch after commit | BaseEvent pattern; `dispatchEvent()` in Command Actions |
| **Queues & jobs** | Deferred work across connections (sync → database → Redis) | `app/Jobs/`; sync queue on shared hosting, workers on VPS/Docker |
| **Blade & Livewire integration** | Templating + reactive components without leaving the server | Presentation layer is Livewire 4 + TallStackUI, not an API-first stack |
| **Authentication & policies** | Session auth, gates/policies for authorization at every layer | Flat RBAC via spatie/laravel-permission with `Gate::before` super-admin bypass |
| **Testing harness** | `RefreshDatabase` variants, fakes for queue/mail/events, Pest integration | Pest 4 suites per module; spec-traceable tests |
| **Artisan CLI** | Extensible command console (`make:*`, custom commands) | `app/Console/`, plus project commands like `setup:install`, `system:health` |

---

## How Internara Builds on It

Framework capability → project convention that shapes its use:

| Laravel primitive | Internara rule |
|-------------------|----------------|
| Container auto-resolution | Constructor injection only — no service locator (C2) |
| Eloquent models | Persistence only; business rules in `final readonly` Entities; mutations flow through Action Triad (C1) |
| Query builder / raw SQL | Bindings mandatory — no unparameterized SQL (C3) |
| Cache manager | Keys registered in `config/cache-keys.php`, never inline strings (C4) |
| Exception hierarchy | Business rejections throw `RejectedException`, not `RuntimeException` (C8) |
| Logging manager | Wrapped by dual-channel SmartLogger with PII masking |
| Scheduler / queues | Deployment-tier dependent: cron endpoint on shared hosting, dedicated worker on VPS/Docker |
| Laravel Pulse | Production observability dashboard |

Ecosystem packages pinned alongside the framework (versions resolved in `composer.lock`):
Livewire 4, TallStackUI 4, spatie packages (permission, media-library, activitylog,
model-status), barryvdh/laravel-dompdf, laravel-lang/lang, Laravel Pulse, Laravel Tinker.

---

## Quick References

- [Release notes](https://laravel.com/docs/13.x/releases) — official 13.x release documentation
- [Upgrade guide](https://laravel.com/docs/13.x/upgrade) — full 12.x → 13.x change list
- [`docs/architecture.md`](../../architecture.md) — how the framework is layered under the Action MVC model
- [`docs/infrastructure/index.md`](../../infrastructure/index.md) — component-level operations (queue, cache, session)
