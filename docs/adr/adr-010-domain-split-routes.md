# ADR-010: Domain-Split Routes

## Status
Accepted

## Context
The application has 23 groups of routes (admin, auth, internship, registration, etc.).
Laravel's default `routes/web.php` file grows unmanageably large as routes accumulate:
navigation is linear, merge conflicts are frequent, and related routes are not co-located
with their domain.

Two alternatives considered:
1. **Single `routes/web.php`**: Simple, traditional — but becomes a 500+ line file with
   unrelated routes interleaved. Every developer working on routes creates merge conflicts.
2. **Domain-split files**: One route file per domain, each living alongside its domain's
   code. `routes/web.php` requires them all.

## Decision
Routes are split into 23 domain-specific files under `routes/web/{domain}.php`.
The master `routes/web.php` requires them in dependency order — foundational domains
(Setup, Core, Auth) first, then business domains, then cross-cutting domains (Admin).

Route naming follows `{prefix}.{resource}.{action}` (e.g., `admin.internships.briefings`).
All routes use `->name()` for named route generation via `route()`.

Livewire components are auto-discovered from `app/Domain/*/Livewire/` by
`DomainServiceProvider`, so route files reference component class names directly rather than
Blade view paths.

## Consequences
- **Positive**: Each route file is small and focused (9-43 lines). Navigation is predictable —
  routes for Internship live in `routes/web/internship.php`.
- **Positive**: Merge conflicts are rare — two developers working on different domains modify
  different files.
- **Positive**: Routes are co-located with their domain conceptually, even though they live
  in `routes/web/` rather than inside `app/Domain/*/`.
- **Positive**: Removing a domain means deleting one route file instead of hunting through a
  single large file.
- **Negative**: Load order matters — `routes/web.php` must require files in the correct
  dependency sequence. Mistaking the order can cause "route already defined" errors.
- **Negative**: Adding a new domain requires adding its route file AND updating
  `routes/web.php` — two steps instead of one.
- **Negative**: Route caching requires all domain files to be loadable — a parse error in
  any domain file breaks the entire cache.

## References
- `routes/web.php` — master file with requires in dependency order
- `routes/web/` — 23 domain route files
- `docs/routes.md`
- `app/Domain/Core/Providers/DomainServiceProvider.php` — Livewire auto-discovery
