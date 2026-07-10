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

# Livewire Development

> **Prerequisite:** Load `context-awareness` for project orientation. Loading `feature-building`
> provides the broader implementation flow.

## When to Activate

Use this skill when building or modifying Livewire components. Covers component structure, form
handling, validation, file uploads, table components, and reactive patterns.

## Agent Workflow

Using this skill follows 4 phases:

### 1. Construct — Knowledge, Context & Scope

- Load `context-awareness` skill for project orientation
- Read relevant docs: module docs, pattern docs, reference docs
- Understand task scope: what needs to be done, which files are affected
- Verify paths, class names, signatures against actual code (don't trust docs blindly)
- Determine approach: at least 2 options before deciding

### 2. Execute — Build/Modify Livewire Component

- Build/modify thin Livewire component — delegate to Actions
- Use Form Object for 5+ fields
- Ensure no Model::create/update/delete in component
- Use method injection for Action calls
- Catch RejectedException specifically before Throwable
- Output: thin Livewire component with Form Objects, Action delegation, and proper exception
  handling

### 3. Verify — Quality Gates

- Run linter: `vendor/bin/pint --dirty --format agent`
- Run static analysis: `vendor/bin/phpstan analyse --no-progress`
- Run unit/feature tests: `php artisan test --compact --filter={TestName}`
- Ensure pre-commit checklist is satisfied
- Check no debug calls (`dd/dump/ray`) were left behind

### 4. Report & Commit

- Deliver a comprehensive report to the user:
    - Summary of component work
    - Files created or modified
    - Test suite status (pass/fail)
- Feeds into: pest-testing (component tests), tailwindcss-development (UI styling), sync-docs (doc
  updates)
- Commit using format: `type(scope): description`
- Push if requested

## Phase Context

| Role           | Skill                                                                         |
| -------------- | ----------------------------------------------------------------------------- |
| **Upstream**   | `feature-building` (implementation flow)                                      |
| **This skill** | **IMPLEMENTATION (Sub-skill)** — Livewire-specific                            |
| **Downstream** | `pest-testing` (component tests), `tailwindcss-development` (UI), `sync-docs` |

## Thin Component Rule

Livewire components must be thin. They should contain ONLY:

- Public properties for UI state (form bindings, modal visibility, search/filter)
- Validation rules for UX feedback
- Delegation to Actions via method parameter injection
- Read-only queries (or via Read Actions)
- Authorization checks via Policies

**Never in a Livewire component:**

- `Model::create/update/delete/save` — delegate to Command Action
- `DB::transaction()` — handled by Action
- `event()` or `dispatch()` — handled by Action's `$this->dispatchEvent()`
- Business rules on record state — delegate to Entity
- `app()->make()` / `new Action()` — inject via method parameter

## Component Structure

### Directory

```
app/{Module}/{SubModule}/Livewire/{Name}.php
resources/views/{module}/{submodule}/{name}.blade.php
```

### Recommended Build Order

1. Define form properties and validation rules
2. Implement `render()` with eager-loaded query
3. Implement action methods that inject Actions
4. Add authorization via `$this->authorize()`
5. Add Blade view with maryUI components

### Form Objects

For components with 5+ form fields, extract to a Form Object:

```
app/{Module}/{SubModule}/Livewire/Forms/{Name}Form.php
```

Extends `Livewire\Form`. Contains properties, validation rules, and `toArray()`.

## Key Patterns

### Action Delegation

```php
public function save(CreateUserAction $action): void
{
    $this->form->validate();
    $result = $action->execute($this->form->toArray());

    if ($result->failed()) {
        flash()->error($result->message);
        return;
    }

    $this->resetForm();
    flash()->success($result->message);
    $this->redirect('/users');
}
```

Always catch `RejectedException` before `Throwable`:

```php
try {
    $action->execute($data);
} catch (RejectedException $e) {
    flash()->error($e->getMessage());
} catch (\Throwable $e) {
    flash()->error(__('common.error'));
}
```

### Read-Only Entity Checks

Entities may be used for UI-level decisions:

```php
public function canDelete(): bool
{
    return $this->record->asEntity()->canBeDeleted();
}
```

### Tables

Use maryUI's `x-mary-table` component with sorting and pagination. For CRUD tables, extend
`BaseRecordManager` (check current implementation).

## File Uploads

Use Livewire's `WithFileUploads` trait + Spatie MediaLibrary:

```php
use Livewire\WithFileUploads;

class ProfileEditor extends Component
{
    use WithFileUploads;

    public UploadedFile $avatar;

    public function save(UpdateProfileAction $action): void
    {
        $action->execute($this->avatar, ...);
    }
}
```

Action handles the media library call.

## Verification Checklist

- [ ] No `Model::create/update/delete` in component
- [ ] No `DB::transaction()` in component
- [ ] No `app()->make()` or `new Action()` — uses method injection
- [ ] `RejectedException` caught before `Throwable`
- [ ] Form Objects used for 5+ fields
- [ ] Validation rules defined (component or Form Object)
- [ ] Component test exists in `tests/`

## References

| Topic              | Doc                                              |
| ------------------ | ------------------------------------------------ |
| Livewire pattern   | `docs/architecture/livewire-pattern.md`          |
| Action delegation  | `docs/architecture/action-pattern.md`            |
| Form Objects       | `docs/architecture/livewire-pattern.md` (§Forms) |
| File uploads       | `docs/infrastructure/media-library.md`           |
| Testing components | `docs/architecture/testing-pattern.md`           |
| maryUI components  | maryUI docs (via `search-docs`)                  |
| Authorization      | `docs/architecture/policy-pattern.md`            |
