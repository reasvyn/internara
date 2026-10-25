# Routes — Route Structure, Middleware & Naming

## Description

Route structure, middleware stack, named route conventions, module split across 16 route files, and
URL design.


## Prerequisites

See [Installation](../installation.md#prerequisites) for full server requirements and verification commands.

## Steps

This document is reference-oriented. For installation and setup procedures, see:

1. [Installation](../installation.md) — server preparation and CLI provisioning
2. [Setup Wizard](../setup-wizard.md) — browser-based initial configuration
3. [Post-Setup](../post-setup.md) — initial data population after wizard completion

## Philosophy

> 📖 Authoritative reference: [Modular Pattern](../arch/modular-pattern.md) — module-colocated vertical slicing & Route & Controller Patterns (§13). Per-module route ownership is a direct manifestation of the Modular Monolith principle (each module owns its full stack; cross-module internals are never imported).

### Submodule Route Files

Modules with multiple submodules may split routes into per-submodule files for better colocation.
When a submodule grows large enough to warrant its own route file, place it alongside the module
route file using the `{submodule}.php` naming convention (no module prefix):

```
routes/web/
├── auth.php                  # Module-level routes (shared/general)
├── login.php                 # Submodule: Login
├── password.php              # Submodule: Password
├── settings.php              # Module-level routes
├── locale.php                # Submodule: Locale
├── theme.php                 # Submodule: Theme
└── ...

```

The master `routes/web.php` `require`s submodule files after the parent module file. Both formats
are valid:

| Scenario | File | Example |
|----------|------|---------|
| Small module, all routes together | `{module}.php` | `auth.php` |
| Large module, submodule split | `{submodule}.php` | `login.php` |
| Mixed (some shared, some split) | Both files | `settings.php` + `locale.php` |

**Rule:** A module _may_ have submodule route files — this is _not_ required. Keep routes in the
parent module file unless the submodule has 5+ routes or belongs to a distinct business domain.

---

## Architecture

The master file `routes/web.php` iterates the module registry (`ModuleManager::names()`) and
`require`s each `routes/web/{lowercase_module}.php` that exists, so load order follows
`config/module.php`. Modules with submodules may additionally `require` submodule-specific route
files. If two files register the same route name, the later one wins.

The three modules without a route file are `core` (no HTTP surface of its own), `evaluation`
(models only, no UI yet), and `ui` (Blade components are resolved through the view namespace, not
routes).

```mermaid
flowchart LR
    subgraph routes/
        direction TB
        web_php[routes/web.php]
        web[web/]
        console[console.php]
        channels[channels.php]
        breadcrumbs[breadcrumbs.php]
        ai[ai.php<br/>(not implemented)]
    end

    web_php --> web

    subgraph routes/web/
        direction TB
        setup[setup.php]
        auth[auth.php]
        user[user.php]
        sysadmin[sysadmin.php]
        document[document.php]
        academic[academic.php]
        partner[partner.php]
        program[program.php]
        enrollment[enrollment.php]
        assignment[assignment.php]
        assessment[assessment.php]
        journal[journal.php]
        incident[incident.php]
        certification[certification.php]
        report[report.php]
        setting[setting.php]
    end

    web --> setup
    web --> auth
    web --> user
    web --> sysadmin
    web --> document
    web --> academic
    web --> partner
    web --> program
    web --> enrollment
    web --> assignment
    web --> assessment
    web --> journal
    web --> incident
    web --> certification
    web --> report
    web --> setting

```

Route files contain:

- `declare(strict_types=1)`
- Class imports for the handlers used in that file
- Route definitions grouped by middleware (guest, auth, role-specific)
- Named routes using `->name()` with dot-separated naming

Two route types exist:

- **Livewire pages** (`Route::livewire()`) — full-page components that handle both GET and POST.
  Used for most interactive features.
- **Controller endpoints** (`Route::get()`) — traditional controller methods. Used for downloads,
  document rendering, file serving, and the logout action.

Additional route files outside `web/`:

| File              | Purpose                                                              | Status          |
| ----------------- | -------------------------------------------------------------------- | --------------- |
| `console.php`     | Artisan command registrations                                        | Active          |
| `channels.php`    | Broadcasting channel definitions (registered in `bootstrap/app.php`) | Active          |
| `breadcrumbs.php` | TallStackUI breadcrumb definitions (loaded via `config/tallstackui.php`) | Active       |
| `ai.php`          | MCP route scaffold — `Mcp::web(...)` is commented out                | Not implemented |

---

## Global Middleware Pipeline (Every Request)

The following middleware runs on every web request, in order:

```mermaid
flowchart LR
    A[Request] --> B[web<br/>Laravel core]
    B --> C[SecurityHeadersMiddleware]
    C --> D[LogContextMiddleware]
    D --> E[RequireSetupAccessMiddleware]
    E --> F[SetLocaleMiddleware]
    F --> G[ConcurrencyGateMiddleware]
    G --> H[Route Handler]

```

1. `web` (Laravel core) — session, CSRF, encryption, cookies
2. `SecurityHeadersMiddleware` — Content-Security-Policy, X-Frame-Options, Permissions-Policy
3. `LogContextMiddleware` — request tracing (request ID, session ID)
4. `RequireSetupAccessMiddleware` — redirects unauthenticated visitors to `/setup` when the system
   has not been installed yet. Allows bypass for Livewire subrequests and the `/setup` route itself.
5. `SetLocaleMiddleware` — language preference from session/database
6. `ConcurrencyGateMiddleware` — blocks concurrent writes from multiple devices for the same account
7. Route handler — Livewire or Controller routes

Global middleware is registered in `bootstrap/app.php`:

```php
$middleware->web(
    append: [
        SecurityHeadersMiddleware::class,
        LogContextMiddleware::class,
        RequireSetupAccessMiddleware::class,
        SetLocaleMiddleware::class,
        ConcurrencyGateMiddleware::class,
    ],
);

```

---

## Route-Specific Middleware

These middleware are applied per-route or per-group:

| Alias             | Class                         | Applied To                                                                 | Purpose                                                                             |
| ----------------- | ----------------------------- | -------------------------------------------------------------------------- | ----------------------------------------------------------------------------------- |
| `setup.protected` | `ProtectSetupRouteMiddleware` | Routes in `routes/web/setup.php`                                           | Token-gates the setup wizard, rate-limits access, self-destructs after installation |
| `guest`           | Laravel core                  | Login, register, forgot-password                                           | Blocks authenticated users                                                          |
| `auth`            | Laravel core                  | Most application routes                                                    | Requires authenticated session                                                      |
| `auth.throttle`   | `AuthThrottleMiddleware`      | All auth routes (login, register, forgot/reset password, confirm password) | Global rate limit (30 requests/min/IP) across all auth endpoints                    |
| `role:{roles}`    | `CheckRoleMiddleware`         | Admin, teacher, supervisor routes                                          | Aborts 403 if user lacks required role                                              |
| `concurrency.gate`| `ConcurrencyGateMiddleware`   | Write routes that must not run from two devices at once                    | Rejects a second concurrent write session for the same account                      |

---

## Route Naming Convention

Route names should be clear and preferably describe the URL path. There is no rigid prefix
convention — name routes based on their URL structure:

| URL Pattern | Route Name | Rationale |
|-------------|------------|-----------|
| `/login` | `login` | Simple, matches the path |
| `/admin/users` | `admin.users.index` | Describes admin section + resource |
| `/student/supervision-logs` | `student.supervision-logs` | Describes student portal + resource |
| `/supervision/logs` | `supervision.logs` | Describes path segments |

**Principle:** Route names should make it obvious which URL they point to. Use dot notation to
mirror the URL path hierarchy.

---

## Livewire Auto-Discovery

Livewire components are NOT registered in route files. The `AppServiceProvider` scans
`app/*/Livewire/` at boot, automatically registering each component with the alias
`{kebab-module}.{kebab-class-name}` (submodule components) or `{kebab-component-name}` (shared
components).

A new Livewire component works immediately without any registration step — just create the class and
its Blade view. The route file only needs `Route::livewire('/path', Component::class)`.

---

## Adding a Route

1. Open `routes/web/{module}.php` for the relevant module
2. Add `Route::livewire()` or `Route::get()` inside the correct middleware group
3. Name it with `->name('{prefix}.{resource}.{action}')`
4. Add sidebar menu entry in `config/menu.php`

For a submodule route: open `routes/web/{submodule}.php` (or create it if it does not
exist). Add the `require` in `routes/web.php` after the parent module file.

For a new module: create `routes/web/{module}.php`, add `require` in `routes/web.php` at the correct
position for load-order precedence.

---

## Route Caching (Tier 2+)

```bash
php artisan route:cache

```

Before caching, ensure no route files contain Closure routes (replace with controller classes).
`Route::livewire()` is compatible with route caching. Clear and rebuild after route changes:

```bash
php artisan route:clear
php artisan route:cache

```

### Infrastructure Context

| Tier       | Route Handling          | Caching                            |
| ---------- | ----------------------- | ---------------------------------- |
| 1 (Shared) | Standard — no cache     | ❌ Not needed                      |
| 2 (VPS)    | Cached after deployment | ✅ `php artisan route:cache`       |
| 3 (HA)     | Cached per server       | ✅ `route:cache` after each deploy |

---

## Where to Find It

- `routes/web.php` — master file with `require`s in dependency order
- `routes/web/` — 16 module route files (plus optional submodule files: `{submodule}.php`)
- `routes/console.php` — Artisan command registrations
- `routes/channels.php` — broadcasting channel definitions (not implemented)
- `routes/ai.php` — AI integration routes
- `bootstrap/app.php` — global middleware registration
- `app/Modules/Core/Http/Middleware/` — global middleware classes
- `app/Modules/Auth/Domain/Permission/Http/Middleware/` — role-check middleware (CheckRole)
- `app/Modules/Auth/Domain/Login/Http/Middleware/` — auth throttle middleware (AuthThrottle)
- `app/Modules/Setup/Domain/Installation/Http/Middleware/` — setup middleware classes
- `config/menu.php` — sidebar navigation mapping routes to menu items
- [Infrastructure](infrastructure.md) — tier-based infrastructure design
---

## Troubleshooting

| Symptom | Cause | Fix |
|---------|-------|-----|
