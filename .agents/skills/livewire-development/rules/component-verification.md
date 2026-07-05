# Component Verification — Ensure Thin, Safe Components

Checklist to verify Livewire components do not violate architecture.

## Thin Component Rules

- [ ] No `Model::create/update/delete/save` — delegate to Command Action
- [ ] No `DB::transaction()` or `DB::beginTransaction()`
- [ ] No `app()->make()` / `resolve()` / `new Action()`
- [ ] No inline business rules (`if ($status === 'x')`) — delegate to Entity
- [ ] No side effects: `event()`, `Notification::send()`, `Log::info()`
- [ ] No maryUI Toast methods: `$this->success()`, `$this->error()`

## Action Injection

- [ ] Actions injected via method parameters (not constructor)
- [ ] `RejectedException` caught before `Throwable`
- [ ] Catch block: business error → `$e->getMessage()`, infra error → generic message
- [ ] DTO or typed scalars passed to Action (not raw array for 3+ params)

## Form Objects

- [ ] Forms with 5+ fields → extract to Form Object (`Livewire\Form`)
- [ ] Form Object only prepares data, does not call Action directly
- [ ] Validation rules defined (component or Form Object)

## Read-Only Entity Access

- [ ] Entity only for READ-ONLY UI decisions (show/hide button)
- [ ] WRITE decisions still go through Action

## Destructive Patterns to Avoid

- ❌ `wire:confirm` for destructive operations without two-step confirmation
- ❌ Form Object extending anything other than `Livewire\Form`
- ❌ Action injection in constructor (must be method parameter)
- ❌ `$this->all()` passed directly to Action
