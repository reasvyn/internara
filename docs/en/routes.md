# Routes

## Philosophy

Routes are owned by domains, not by a single file. Each domain registers its own routes in its own file under `routes/web/{domain}.php`. The master `routes/web.php` simply stitches them together.

This exists because a single `routes/web.php` with 200+ lines creates merge conflicts and obscures which domain owns which route. Splitting by domain means you find a registration route in `registration.php`, not by grepping a thousand-line file.

## Architecture

The master file `routes/web.php` `require`s 23 domain route files. Load order matters: if two files register the same route name, the later one wins.

Route files contain:
- `declare(strict_types=1)`
- Class imports for the handlers used in that file
- Route definitions grouped by middleware (guest, auth, role-specific)
- Named routes using `->name()` with dot-separated naming

Two route types exist:

- **Livewire pages** (`Route::livewire()`) — full-page components that handle both GET and POST. Used for most interactive features.
- **Controller endpoints** (`Route::get()`) — traditional controller methods. Used for downloads, document rendering, file serving, and the logout action.

## Middleware Pipeline

Routes pass through this middleware stack:

1. `web` (Laravel core) — session, CSRF, encryption, cookies
2. `ProtectSetupRouteMiddleware` — token-gates the setup wizard
3. `SetLocaleMiddleware` — language preference from user/session
4. `Authenticate` — session-based auth (Laravel core)
5. `CheckRoleMiddleware` — role gating via Spatie (`role:{role1|role2}` syntax)
6. Route handler

Key middleware aliases:

| Alias | Purpose |
|-------|---------|
| `guest` | Blocks authenticated users (login, register, forgot-password) |
| `auth` | Requires authenticated session |
| `setup.protected` | Token-gates the installation wizard via `ProtectSetupRouteMiddleware` |
| `role:{roles}` | Aborts 403 if user lacks any of the pipe-delimited roles via `CheckRoleMiddleware` |

## Route Naming Convention

All routes use `<prefix>.<resource>.<action>` naming. Prefixes match URL structure:

- `admin.*` — administration (role: super_admin|admin)
- `student.*` — student portal
- `teacher.*`, `supervisor.*` — mentor role portals
- `password.*` — password management (shared across roles)
- `certificates.*` — certificate operations

## Livewire Auto-Discovery

Livewire components are NOT registered in route files. The `DomainServiceProvider` scans `app/Domain/{Domain}/Livewire/` at boot, automatically registering each component with alias `{kebab-domain}.{kebab-class-name}`.

This means a new Livewire component works immediately without any registration step — just create the class and its Blade view. The route file only needs `Route::livewire('/path', Component::class)`.

## Adding a Route

1. Open `routes/web/{domain}.php` for the relevant domain
2. Add `Route::livewire()` or `Route::get()` inside the correct middleware group
3. Name it with `->name('{prefix}.{resource}.{action}')`
4. Add sidebar menu entry in `config/menu.php`

For a new domain: create `routes/web/{domain}.php`, add `require` in `routes/web.php` at the correct position for load-order precedence.

## Route Caching

Not currently enabled because `routes/web/user.php` contains a Closure route (POST `/logout`). To enable route caching, replace the Closure with a controller invokable. `Route::livewire()` calls are compatible with route caching.
