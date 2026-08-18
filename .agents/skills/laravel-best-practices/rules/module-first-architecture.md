# Module-First Architecture — Directory Placement & Action Boundaries

> **Last updated:** 2026-08-17 **Changes:** extracted from SKILL.md — comprehensive rewrite

Internara does **not** follow the stock Laravel layer-first layout. The module is the top-level
organizing unit; every layer lives *inside* the module it serves. Treating the project as a
layer-first Laravel app (one `app/Models/`, one `app/Http/Livewire/`, Services-in-the-default-hiccup)
is the single most common source of drift from the architecture.

---

## Intent

Files live in `app/{Module}/{SubModule}/` under their layer subdirectory, business logic is owned by
Actions (not Services), validation happens in Form Objects (not FormRequest classes), and Actions
accept DTOs (never raw arrays). These four conventions keep the codebase module-colocated, layered,
and testable.

## Rationale — What Fails Without It

- **Layer-first placement fragments modules.** If models accumulate in `app/Models/` as projects
  grow, cross-module coupling silently increases and a module's full stack can no longer be read in
  one directory. The module map (`docs/modules/index.md`) and module reference docs enumerate files
  per module; any file sitting outside its module becomes invisible to those docs and to
  module-scoped scans.
- **Business logic in Services** bypasses the Action Triad's transactional and audit guarantees
  (Command/Process Actions own `$this->transaction()` + `$this->log()`). A Service that mutates
  silently drops both.
- **FormRequest classes duplicate Livewire's validation** — Livewire already validates via Form
  Objects with real-time feedback; a FormRequest serves HTTP controllers Internara does not use, and
  a parallel validation layer diverges from the Livewire form.
- **Raw arrays for Action input** are unnameable and reorder-dangerous. A 4th parameter added
  positionally breaks every call site without a compiler catching it, and the Action's signature
  stops describing its inputs.

## How to Apply

### Directory placement — module-first

| Stock Laravel                          | Internara                                        |
| -------------------------------------- | ------------------------------------------------ |
| `app/Models/` contains all models      | Models live in `app/{Module}/{SubModule}/Models/` |
| `app/Http/Livewire/` contains all      | Components live in `app/{Module}/{SubModule}/Livewire/` |
| `app/Policies/` contains all policies  | Policies live in `app/{Module}/{SubModule}/Policies/` |

Matching rule: the directory path mirrors `{Module}/{SubModule}/{Layer}`. When creating a new class,
first look for an existing submodule that owns the concern; creating a parallel directory fragments
the module.

### Actions replace Services

- Business logic goes in **Actions** (Command/Read/Process) — never in Services.
- Services are for **infrastructure logic only**: environment checks, system utilities, third-party
  integrations that have no business meaning.
- Support classes are for **static utilities with zero side effects** — pure helpers, no I/O.

```php
// Business logic → Command Action
final readonly class RegisterInternAction extends BaseCommandAction
{
    public function execute(RegisterInternData $data): ActionResponse
    {
        return $this->transaction(fn () => /* business flow */);
    }
}

// Infrastructure logic → Service
final class EnvironmentHealthService
{
    public function diskSpaceHealthy(): bool { /* infra check */ }
}

// Pure helper → Support class
final class CsvNormalizer
{
    public static function slug(string $value): string { /* pure transform */ }
}
```

### No FormRequest classes

- Use **Livewire Form Objects** (`Livewire\Form`) for component validation — they give per-field
  real-time validation and a `toArray()` boundary.
- Shared validation (rules reused across components/Actions) lives in an **Entity static `rules()`**
  method or a dedicated **Rules class**, so one definition serves many callers.

```php
// app/{Module}/{SubModule}/Livewire/Forms/{Name}Form.php
final class ProfileForm extends \Livewire\Form
{
    public string $email = '';

    public function rules(): array { return ['email' => ['required', 'email']]; }
}
```

### DTO over array

- **3+ parameters to an Action → use a `BaseData` DTO.**
- **Never pass a raw `array` to `execute()`.**

```php
// 3+ positional params → DTO
final readonly class PlacementData extends BaseData
{
    public function __construct(
        public int $internId,
        public int $companyId,
        public ?string $note,
    ) {}
}

// Good — named, immutable, testable
$result = $action->execute(PlacementData::from([
    'intern_id' => $this->internId,
    'company_id' => $this->companyId,
    'note' => $this->note,
]));
```

## Anti-Patterns & Pitfalls

- Creating `app/Models/Foo.php` instead of `app/Foo/{SubModule}/Models/Foo.php` "because it's just a
  model".
- A Service method that calls `Model::create()` — that is Command Action territory (C1-adjacent:
  mutations owned by Actions, and Services do infra only).
- Adding a FormRequest "for cleanliness" in a Livewire-only codebase.
- `execute(array $data)` with the caller building a positional array — unnameable, breaks silently.

## Verification

- Every new class path matches `app/{Module}/{SubModule}/{Layer}/`.
- Every Action with 3+ inputs accepts a DTO; no `execute(array ...)` in committed code.
- No `app/Http/Requests/` classes; validation lives in Form Objects or Entity `rules()`/Rules classes.
- `python3 scripts/scan_violations.py` (C7 DTO checks) and `python3 scripts/scan_class_contracts.py`
  (Action contracts) report clean.