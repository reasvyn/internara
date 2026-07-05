# Audit Checklist — Layer-by-Layer Verification

Quick-reference checklist to ensure no critical layer is missed during audit. Do NOT use as spec —
see `docs/` for details.

## Prerequisites

- [ ] `context-awareness` loaded
- [ ] `git status` clean (no uncommitted changes)
- [ ] Baseline test suite passes before audit starts

## Layer 4 — UI (Livewire/Blade/Controller)

- [ ] No `Model::create/update/delete/save` in Livewire
- [ ] No `DB::transaction()` in Livewire
- [ ] No `app()->make()` / `new Action()` — use method injection
- [ ] `RejectedException` caught before `Throwable`
- [ ] No `{!! !!}` without inline justification comment
- [ ] Every mutation method has `$this->authorize()`

## Layer 3 — Business (Actions/Events)

- [ ] Every Action extends correct base class (Command/Read/Process)
- [ ] Exactly one public method: `execute()`
- [ ] Command/Process: `$this->transaction()` + `$this->log()`
- [ ] Business rules → `RejectedException`, not `RuntimeException`
- [ ] Event dispatched only if listener exists
- [ ] DTO for 3+ params, ActionResponse for structured return

## Layer 2 — Data (Models/Entities/DTOs)

- [ ] Entity: `final readonly`, `fromModel()`, zero I/O
- [ ] Entity does not import Actions, Services, Livewire, Controllers
- [ ] DTO: `final readonly`, scalars/enums/Carbon only
- [ ] Model: `#[Fillable]`, not `$fillable`/`$guarded`
- [ ] Cache keys in `config/cache-keys.php`, not inline
- [ ] Foreign keys: `foreignUuid()->constrained()` + explicit `onDelete()`

## Layer 1 — Infra (Services/Support/Config)

- [ ] Services: infrastructure logic only (not domain rules)
- [ ] Support: static-only, zero side effects
- [ ] Config files follow documented schema
