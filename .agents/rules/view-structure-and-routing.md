# View Structure & Routing — Blade Placement and Route Conventions

Views and routes follow a deterministic layout so any agent can find "the blade for that screen" and
"the route that serves it" without archaeology. Blade views mirror the module structure; routes
follow a per-module file split with flexible naming.

---

## Intent

Views live at `resources/views/{module}/{submodule}/{action}.blade.php`, extend a layout via
`<x-layouts.app>` or a module-specific layout, keep logic in Livewire (not Blade directives), and use
Blade components for reusable fragments. Routes are split per module/submodule with the same base
pattern (no `{prefix}.{resource}.{action}` rigidity).

## Rationale — What Fails Without It

- **Views in arbitrary folders** break the module map: module reference docs enumerate
  `resources/views/{module}/...`; a view parked elsewhere is invisible to docs and to audits.
- **Logic in Blade directives** (`@php ... @endphp` blocks loading data) splits the screen's state
  between the component and the template — the thin-Livewire contract depends on the view being
  dumb. Logic must stay in the Livewire component (see `livewire-development`).
- **Skipping Blade components** for small reusable fragments duplicates markup (a confirm button, a
  status badge) across a hundred blades and each edit must touch them all.
- **Route naming rigidity or inconsistency** makes routes unguessable; a flexible-but-random name
  means callers can't infer `route()` names (see naming below).

## How to Apply

### View structure

```
resources/views/{module}/{submodule}/{action}.blade.php
```

- Extends layout: `<x-layouts.app>` or a module-specific layout.
- Livewire components for interactive sections.
- Blade components for reusable UI fragments:

```blade
<x-layouts.app>
    <x-core::ui.confirm
        :title="__('intern.confirm_delete_title')"
        :message="__('intern.confirm_delete_message')"
        :confirmText="__('common.actions.delete')"
        :cancelText="__('common.actions.cancel')"
    />
</x-layouts.app>
```

- No business logic in the blade — `render()`/Actions/Read Actions do the work.

### Route file convention

- Module-level: `routes/web/{module}.php`.
- Submodule-level: `routes/web/{submodule}.php` (no module prefix).

### Route naming

Flexible — describe the URL path. **No rigid `{prefix}.{resource}.{action}` convention.** Names
should be self-explanatory (`registration.wizard`) not mechanically assembled.

### Livewire route registration

```php
Route::livewire('/register', RegistrationWizard::class)->name('registration.wizard');
```

Middleware applied at route level: `auth`, `guest`, `role:{roles}`, `auth.throttle`.

### URL structure

| Scope   | Pattern                        | Example                                 |
| ------- | ------------------------------ | --------------------------------------- |
| Guest   | `/{resource}`                  | `/apply`, `/login`                      |
| Student | `/student/{module}/{resource}` | `/student/internships/placement-change` |
| Admin   | `/admin/{module}/{resource}`   | `/admin/internships/placements`         |

## Anti-Patterns & Pitfalls

- `@php` blocks fetching/joining data inside the blade — move to the component's `render()`.
- Inlining a reusable button/badge instead of a Blade component — duplication decays.
- A route file for a submodule with the module prefix in the filename
  (`routes/web/internship-placement.php` vs `routes/web/placement.php`) — breaks the no-prefix rule.
- Route names invented per developer (`/apply` named `apply_form`) — name from the URL path intent.

## Verification

- View path matches `{module}/{submodule}/{action}.blade.php`; no views outside that pattern.
- No `@php`/direct DB calls in blade files; interactive sections are Livewire components.
- Routes split per module/submodule; Livewire components registered with `Route::livewire(...)`.
- `python3 tools/scan_doc_links.py` clean after route renames (module reference docs list routes).
