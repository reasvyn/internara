# Component Structure & Routing — Placement and Shape

A Livewire component's placement, build order, Form Object extraction, and route registration follow
fixed conventions. Each convention exists because a specific integration failure follows when it is
skipped: a component in the wrong directory breaks discovery and the docs' file tables; a 5+-field
component that skips a Form Object bloats `mount()`/`save()` with redundant property blocks; a route
in the wrong file breaks the module's URL contract.

---

## Directory Placement Is Fixed

**What it enforces:** Components live at `app/{Module}/{SubModule}/Livewire/{Name}.php` and their
views at `resources/views/{module}/{submodule}/{name}.blade.php`. Names match the resources path.

**Why it matters:** Module colocation is the architecture's organizing principle (S2 — Sustain). A
component outside its module's `Livewire/` directory breaks directory scanning, module reference
docs, and the `scan_doc_links.py` consistency checks that enumerate module file
trees. Mismatched names between the component and its blade make the next reader guess which view a
component renders.

**How to apply:**

```
app/{Module}/{SubModule}/Livewire/{Name}.php
resources/views/{module}/{submodule}/{name}.blade.php
```

Create the component with `php artisan make:livewire {module}.{Name}` style tooling (or follow the
exact existing module pattern) and place the blade in the mirroring view path.

**Pitfalls to avoid:**

- Inverting the case or appending `Component` to a name the module docs table expects without it.
- Placing components under `app/Livewire/` globally to "save a directory" — that is not module
  colocation.

**Verification:** The component path matches `app/{Module}/{SubModule}/Livewire/{Name}.php`; the
blade mirrors it; the module reference doc's file table matches the actual directory.

---

## Recommended Build Order

**What it enforces:** New components are built in a fixed order: form properties + validation rules →
`render()` with eager-loaded query → action methods that inject Actions → authorization via
`$this->authorize()` → Blade view with TallstackUI `x-ts-*` components.

**Why it matters:** The order builds the component bottom-up through its dependencies. Properties and
validation define the data contract before `render()` uses it; `render()` needs its query before
action methods reference records; authorization is added once actions exist to protect; the Blade
view is written last, against the completed component API. Skipping ahead (view first) produces
bindings that reference properties or methods that have not been designed.

**How to apply:**

1. Define `public` form properties and validation rules (in the component or its Form Object).
2. Implement `render()` with an eager-loaded query to avoid N+1.
3. Implement action methods that inject Actions (see `rules/thin-component.md`).
4. Add authorization via `$this->authorize()` or Policy calls on each mutating method.
5. Write the Blade view with TallstackUI `x-ts-*` components matching the declared properties.

**Pitfalls to avoid:**

- Writing the blade first and designing the component API around it.
- `render()` queries without eager loading — N+1 on listing pages.
- Adding authorization only at the final review, after mutating methods exist.

**Verification:** The component's methods and properties exist before the view references them; the
view binds only to declared properties; every mutating method is authorized.

---

## Form Objects for 5+ Fields

**What it enforces:** Components with 5 or more form fields extract those fields into a Form Object
at `app/{Module}/{SubModule}/Livewire/Forms/{Name}Form.php`, extending `Livewire\Form`, containing
the properties, validation rules, and a `toArray()` method.

**Why it matters:** Five-plus fields inline in a component means a dozen properties, a wall of
validation rules, and duplicated reset/validation boilerplate — the component stops being readable,
and the "thin component" rule becomes unreachable. A Form Object groups the fields' state, rules,
and serialization in one named unit with a single `toArray()` contract the Action consumes directly.

**How to apply:**

```php
namespace App\Enrollment\Livewire\Forms;

use Livewire\Form;

final class RegisterForm extends Form
{
    public string $studentId = '';
    public string $internshipId = '';
    public string $startDate = '';
    public string $endDate = '';
    public bool $termsAccepted = false;

    protected array $rules = [
        'studentId' => ['required', 'exists:students,id'],
        'internshipId' => ['required', 'exists:internships,id'],
        'startDate' => ['required', 'date'],
        'endDate' => ['required', 'date', 'after:startDate'],
        'termsAccepted' => ['accepted'],
    ];

    public function toArray(): array
    {
        return [
            'student_id' => $this->studentId,
            'internship_id' => $this->internshipId,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
        ];
    }
}
```

The component then calls `$this->form->validate()` and passes `$this->form->toArray()` to the
Action. The Form Object only **prepares** data — it never calls an Action itself.

**Pitfalls to avoid:**

- A Form Object extending something other than `Livewire\Form` — it then lacks the livewire
  validation/reset lifecycle.
- The Form Object calling `SomeAction::execute()` directly — data preparation stays in the form,
  mutation stays in the Action (and the component).
- Keeping 5+ fields inline "because they're small" — the threshold exists for readability, not size.

**Verification:** Every component with 5+ form fields has a Form Object; the Form Object extends
`Livewire\Form` and contains no Action calls.

---

## Route Registration Follows Module Files

**What it enforces:** Livewire components are registered directly in route files — module-level
`routes/web/{module}.php`, submodule-level `routes/web/{submodule}.php` (no module prefix). Naming
describes the URL path; middleware (`auth`, `guest`, `role:{roles}`, `auth.throttle`) is applied at
route level.

**Why it matters:** The route file layout mirrors the module architecture — routes belong to their
module's file, so `routes/web/` stays navigable and the docs' Routes tables stay accurate. Applying
middleware at route level (not inside the component) keeps access rules visible with the URL, where
security review looks for them. URL structure encodes the persona: `/student/...` vs `/admin/...`.

**How to apply:**

```php
// routes/web/{submodule}.php (no module prefix)
Route::livewire('/register', RegistrationWizard::class)->name('registration.wizard');
```

| Scope       | Pattern                        | Example                                 |
| ----------- | ------------------------------ | --------------------------------------- |
| Guest       | `/{resource}`                  | `/apply`, `/login`                      |
| Student     | `/student/{module}/{resource}` | `/student/internships/placement-change` |
| Admin       | `/admin/{module}/{resource}`   | `/admin/internships/placements`         |

**Pitfalls to avoid:**

- Registering a module route in the global `web.php` "for speed" — it breaks the module file
  convention and the docs' route tables.
- Putting auth logic in the component instead of route middleware.
- A submodule route prefixed with its module name when `routes/web/{submodule}.php` already scopes
  the URL.

**Verification:** Every component is registered in its module's (or submodule's) route file with
appropriate middleware; the URL matches the persona table; route names describe the resource.

---

## References

| Topic                     | Asset                                         |
| ------------------------- | --------------------------------------------- |
| Livewire pattern          | `docs/guides/arch/livewire-pattern.md`       |
| Form Objects              | `docs/guides/arch/livewire-pattern.md` §Forms |
| Routing & URL structure   | `docs/guides/infra/routes.md`               |
| Modular routing           | `docs/guides/arch/modular-pattern.md` §Routes |
