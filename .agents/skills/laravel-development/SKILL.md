---
name: laravel-development
description: "SDLC Phase: IMPLEMENTATION (Core). Laravel framework development — routing, middleware, service container, Eloquent, validation, and framework conventions. Supersedes laravel-best-practices for 1:1 dependency mapping (laravel/framework)."
upstream:
  - feature-building
downstream:
  - code-writing
  - livewire-development
  - sync-docs
---

# Laravel Development — Framework Core

> **Prerequisite:** Read `AGENTS.md §Context Awareness` for project orientation. For module-specific overrides, also load `laravel-best-practices` (now legacy alias).

## When to Activate

Use this skill for any Laravel framework concern — routes, middleware, service providers, container bindings, Eloquent queries, validation, and framework upgrades. It is the canonical skill for `laravel/framework` (1 dep = 1 skill). For Livewire components load `livewire-development`; for general UI load `ui-development`.

## Workflow

Follow `AGENTS.md §Agent Workflow` canonical pipeline. This skill adds Laravel-specific guidance and delegates to downstream skills for implementation details.

## Skill Rules

| Rule | Asset | Applies when |
|------|-------|--------------|
| Service container & providers (bindings, discovery, auto-registration) | `.agents/rules/service-container.md` | Registering services, providers, or Blade namespaces |
| Routing & middleware (route files, Livewire routes, role middleware) | `.agents/rules/routing-middleware.md` | Creating or modifying routes/middleware |
| Eloquent & validation (eager loading, query patterns, FormRequest/DTO) | `.agents/rules/eloquent-validation.md` | Any model query or validation logic |

General code conventions (Action triad, Entity/DTO) are in `code-writing`; module architecture is in `laravel-best-practices` (legacy, now delegates here).

## References

| Topic | Doc |
|-------|-----|
| Laravel docs | `search-docs` with `laravel/framework` |
| Module architecture | `docs/guides/arch/modular-pattern.md` |
| Action pattern | `docs/guides/arch/action-pattern.md` |
