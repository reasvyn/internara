# Security

## What It Enforces

Mass assignment protection via `#[Fillable]`. RBAC with role middleware on route groups. Authorization via Policy classes or Gates. SQL injection prevention through parameter binding. XSS prevention through Blade's auto-escaping. CSRF protection on all forms. Rate limiting on auth routes. Secrets stored in environment variables, never in code.

## Why It Matters

Security is layered. Mass assignment protection prevents users from setting attributes they shouldn't. RBAC at the route level provides a first line of defense; Policy classes provide fine-grained authorization per-model. Parameter binding makes SQL injection impossible by separating query structure from data. Blade's `{{ }}` syntax auto-escapes output, preventing XSS.

## When It Applies

Every feature must consider:
- Mass assignment: every Model has `#[Fillable]` — never `$guarded = []`
- Route authorization: role middleware on group level, Policy for fine-grained
- SQL injection: use Eloquent or parameter binding — never string interpolation
- XSS: use `{{ }}` syntax (escaped) over `{!! !!}` (unescaped, trust only pre-sanitized content)
- CSRF: `@csrf` on every POST form
- Rate limiting: throttle login routes
- File uploads: validate type, MIME, and size; store with generated filenames
- Secrets: `env()` only in config files; use `config()` in application code
- Sensitive data: use `encrypted` cast on database columns
- Dependencies: run `composer audit` regularly

User-facing admin routes are protected by `role:super_admin|admin` middleware. Livewire components check authorization in `boot()` or via `Gate::authorize()`.

Exceptions: Pre-sanitized content that has been through an HTML purifier may use `{!! !!}` but this is rare.
