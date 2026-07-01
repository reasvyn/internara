---
name: livewire-development
description: SDLC Phase: IMPLEMENTATION (Sub-skill). Specialized Livewire component development — building new components, debugging reactivity, file uploads, real-time validation, CRUD tables.
upstream:
  - feature-building
downstream:
  - pest-testing
  - tailwindcss-development
  - sync-docs
---

> **⚠️ Context Awareness Required:** Before following any instruction in this skill,
> read [context-awareness.md](context-awareness.md). Do NOT trust numbers, paths,
> class names, or method signatures without verifying them in the actual codebase.
> The codebase evolves independently of this document — verify, don't assume.
> **Rule:** If the skill says a number/path/name, verify it in the code first.
>
> Detailed implementation patterns (lifecycle hooks, validation, events, file uploads,
> computed properties, pagination, testing) are in [references/patterns.md](references/patterns.md).


# Livewire Development Skill

## When to Activate

Apply this skill for any task involving Livewire — building new components, debugging reactivity, handling file uploads, implementing real-time validation, managing CRUD tables, or migrating existing components.

## SDLC Context

| Role | Skill |
|------|-------|
| **Upstream (input)** | `feature-building` — roadmap task requiring Livewire component |
| **This skill** | **IMPLEMENTATION (Livewire)** — produces Livewire components + views |
| **Downstream (output)** | `pest-testing` — tests for component |
| | `tailwindcss-development` — styling during component build |
| | `sync-docs` — documentation after component creation |
| **Phase** | [Planning] → [Analysis] → [Design] → Implementation → [Testing] → [Maintenance] |

## Thin Component Rule

Livewire components handle **only**:
- UI state: form bindings, modal visibility, search input, selection state
- UX validation: inline validation (the Action re-validates authoritatively)
- Delegation: calling Actions via method injection
- Flash messages: success/error feedback via `flash()` (PHPFlasher)

They must **NOT** contain:
- Business logic: no `Model::create()`, `DB::transaction()`, direct mutations
- Business rules: no `if ($status === 'x')` — delegate to Entities
- Side effects: no logging, event dispatching, notification sending — those belong in Actions

## Key References

- **BaseRecordManager**: `app/Core/Livewire/BaseRecordManager.php` — CRUD base class with pagination, search, sort, selection, bulk/mass actions
- **WithRecordSelection**: `app/Core/Livewire/Concerns/WithRecordSelection.php`
- **WithSorting**: `app/Core/Livewire/Concerns/WithSorting.php`
- **Architecture**: `docs/architecture.md#data-flow`
- **Livewire Pattern**: `docs/architecture/livewire-pattern.md`
- **Service Pattern**: `docs/architecture/service-pattern.md` — infrastructure logic vs domain logic
- **Support Pattern**: `docs/architecture/support-pattern.md` — static utilities, no constructor injection
- **Livewire Docs**: `https://livewire.laravel.com/docs`
- **Implementation Patterns**: `references/patterns.md` — lifecycle hooks, validation, events, file uploads, computed properties, pagination, testing

## Component Location & Auto-Discovery

Components are auto-discovered by `AppServiceProvider` from `app/{Module}/Livewire/` and `app/Livewire/`.

| Scope | Directory | Alias Pattern | Example |
|-------|-----------|---------------|---------|
| Submodule | `app/{Module}/{SubModule}/Livewire/` | `{kebab-module}.{kebab-submodule}.{kebab-name}` | `admin.user.user-manager` |
| Cross-submodule | `app/{Module}/Livewire/` | `{kebab-module}.{kebab-name}` | `user.profile-editor` |
| Shared | `app/Livewire/` | `{kebab-component-name}` | `livewire.lang-switcher` |

Views mirror the app structure:

| Scope | View Path |
|-------|-----------|
| Submodule | `resources/views/{module}/{submodule}/{component-name}.blade.php` |
| Root | `resources/views/{module}/{component-name}.blade.php` |
| Shared | `resources/views/livewire/{component-name}.blade.php` |

## Best Practices — File Organization

### When to Extract to Submodule Livewire Directory

| Scenario | Location |
|----------|----------|
| Component is specific to one submodule's workflow | `app/{Module}/{SubModule}/Livewire/{Name}.php` |
| Component spans multiple submodules (e.g., a dashboard) | `app/{Module}/Livewire/{Name}.php` |
| Component is truly cross-module (e.g., a shared picker) | `app/Core/Livewire/{Name}.php` |

### Form Object Extraction Threshold

Extract a Form Object when the form has:
- **5+ fields**, OR
- **Conditional validation rules**, OR
- **Logic shared across multiple components**

```php
// app/{Module}/Livewire/Forms/{Entity}Form.php
class InternshipForm extends Form
{
    public string $name = '';
    public string $startDate = '';
    public string $endDate = '';
    public ?string $departmentId = null;
    public string $status = 'draft';

    public function rules(): array
    {
        return match ($this->status) {
            'published' => [
                'name' => ['required', 'string', 'max:255'],
                'startDate' => ['required', 'date', 'after:today'],
                'endDate' => ['required', 'date', 'after:startDate'],
                'departmentId' => ['required', 'exists:departments,id'],
            ],
            default => [
                'name' => ['required', 'string', 'max:255'],
                'startDate' => ['nullable', 'date'],
                'endDate' => ['nullable', 'date'],
            ],
        };
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'department_id' => $this->departmentId,
        ];
    }
}
```

### Trait Extraction for Reusable Behavior

When the same logic (e.g., guide toggle, locale switching) appears in multiple components:

```php
// app/{Module}/Livewire/Concerns/WithGuide.php
trait WithGuide
{
    public bool $showGuide = false;

    public function toggleGuide(): void
    {
        $this->showGuide = ! $this->showGuide;
    }
}
```

```php
class UserManager extends BaseRecordManager
{
    use WithGuide;
}
```

Traits live at `app/{Module}/Livewire/Concerns/` or `app/Core/Livewire/Concerns/` for cross-module reuse.

## Common Mistakes

### Missing `wire:key` in Loops

Every Livewire component inside `@foreach` needs a unique `wire:key`. Without it, Livewire cannot track component identity, causing DOM diffing issues, lost state, and flickering.

```blade
{{-- ❌ Wrong --}}
@foreach($users as $user)
    @livewire('user.profile.profile-editor', ['userId' => $user->id])
@endforeach

{{-- ✅ Correct --}}
@foreach($users as $user)
    @livewire('user.profile.profile-editor', ['userId' => $user->id], key($user->id))
@endforeach
```

### `$this->all()` for Mass Assignment

`$this->all()` exposes ALL public properties to mass assignment — including `$search`, `$perPage`, `$filters`, etc. Never use it.

```php
// ❌ Wrong — exposes every public property
User::create($this->all());

// ✅ Correct — explicit mapping
User::create($this->form->toArray());
User::create($this->only(['name', 'email']));
```

### Catching Wrong Exception Type

Actions throw `RejectedException` (extends `ModuleException`), NOT `RuntimeException` or `\Exception`.

```php
// ❌ Wrong — too broad, catches infrastructure errors
try {
    $action->execute($data);
} catch (\Exception $e) {
    flash()->error($e->getMessage());
}

// ✅ Correct — targets business rule violations
try {
    $action->execute($data);
} catch (RejectedException $e) {
    flash()->error($e->getMessage());
}
```

### Forgetting `$dispatch` Naming

Event names passed to `$dispatch()` must be kebab-case:

```php
// ❌ Wrong — camelCase
$this->dispatch('userSaved');

// ✅ Correct — kebab-case
$this->dispatch('user-saved');
```

### Overusing `#[Computed]` for Trivial Properties

```php
// ❌ Wrong — trivial getter adds unnecessary complexity
#[Computed]
public function userName(): string
{
    return $this->user->name;
}

// ✅ Correct — just use $this->user->name directly in Blade
```

### Calling Actions from `mount()`

```php
// ❌ Wrong — mount() should not trigger side effects
public function mount(): void
{
    $this->save(new CreateUserAction(...)); // Side effect on render!
}

// ✅ Correct — mount() sets initial state, user action triggers the mutation
public function mount(): void
{
    $this->name = old('name', '');
}
```

### Livewire Component Naming Collisions

Two components with the same class name in different submodules will collide. Use distinct names:

```php
// ❌ app/Academics/AcademicYear/Livewire/Manager.php
// ❌ app/SysAdmin/User/Livewire/Manager.php  ← collision!

// ✅ app/Academics/AcademicYear/Livewire/AcademicYearManager.php
// ✅ app/SysAdmin/User/Livewire/UserManager.php
```

## Verification

- [ ] No inline `Model::create/update/delete`, `DB::`, `Mail::`, `Log::` calls?
- [ ] Actions injected via method parameter, not resolved manually?
- [ ] `RejectedException` caught and displayed as flash message?
- [ ] `wire:key` on all `@foreach` loops?
- [ ] `#[Computed]` attribute for computed properties (not trivial getters)?
- [ ] Bulk/mass operations use `performBulkAction` / `performMassAction`?
- [ ] `#[Url]` or `#[Locked]` used instead of manual query string parsing?
- [ ] Event names are kebab-case (`user-saved` not `userSaved`)?
- [ ] File uploads use `$this->validate()` with correct MIME rules, not raw `Storage::put()`?
- [ ] `mount()` does NOT trigger Action side effects — only sets initial state?
- [ ] `wire:model` modifiers selected intentionally (`.blur` for email, `.live` for search)?
- [ ] Loading states added for all async operations (`wire:loading` with spinners)?
- [ ] Form Objects extracted for forms with 5+ fields or conditional rules?
- [ ] `$dispatchTo()` used over `$dispatchGlobal()` when targeting a specific component?
- [ ] `#[Reactive]` used for child props that must update when parent changes?
- [ ] Pagination page resets on filter/search changes (`updatedSearch()` etc.)?
- [ ] All exception types in catch blocks are precise (`RejectedException` not `\Exception`)?
- [ ] No `$this->all()` passed to `create()` / `update()`?
