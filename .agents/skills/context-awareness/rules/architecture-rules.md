# Architecture Rules — Quick Reference for Verification

> **Last updated:** 2026-07-03
> **Changes:** initial — verification checklist, not doc replacement

Do NOT use this as an authoritative spec. Read `docs/architecture.md` for the full
architecture and `app/Core/Actions/` files for actual contracts. This file lists
what to check when verifying your work.

## Layer Boundary Checks

When reviewing code, verify:

### UI Layer (Livewire/Blade/Controller)
- [ ] Does NOT call `Model::create/update/delete/save` directly
- [ ] Does NOT call `DB::transaction()` or `DB::beginTransaction()`
- [ ] Does NOT use `app()->make()`, `resolve()`, or `new Action()`
- [ ] Injects Actions via method parameters (not constructor)
- [ ] Catches `RejectedException` from Action calls
- [ ] Passes DTO or typed scalars to Actions (never raw array for 3+ params)

### Business Layer (Actions)
- [ ] Extends the correct base class (Command/Read/Process)
- [ ] Has exactly one public method: `execute()`
- [ ] Command/Process: calls `$this->transaction()` for DB writes
- [ ] Command/Process: calls `$this->log()` after mutation
- [ ] Command/Process: calls `$this->dispatchEvent()` only if listener exists
- [ ] Uses `RejectedException` for business rule violations
- [ ] Does NOT catch `RejectedException` inside the Action (let it propagate)

### Data Layer (Models/Entities/DTOs)
- [ ] Entity is `final readonly`, has `fromModel(Model): static`
- [ ] Entity does NOT import Actions, Services, Livewire, Controllers
- [ ] DTO is `final readonly`, only carries scalars/enums/Carbon
- [ ] DTO does NOT import Models, Entities, Actions
- [ ] Model uses `#[Fillable]` attribute, not `$fillable` property

## Action Triad Quick Check

| Found this | Must extend | Must have | Must NOT have |
|-----------|-------------|-----------|--------------|
| Creates/updates/deletes data | `BaseCommandAction` | `$this->transaction()` + `$this->log()` | Any query-only logic |
| Complex query only | `BaseReadAction` | `Cache::remember()` for read cache | `$this->transaction()` or `$this->log()` |
| Orchestrates multiple steps | `BaseProcessAction` | `$this->transaction()` + `$this->log()` | Direct DB queries (delegate to other Actions) |

## Data Flow Verification

```
Livewire → validates → Action::execute(DTO)
                         ├── Entity::fromModel(model) → business check
                         ├── Model::create/update(values from DTO)
                         ├── $this->log()
                         ├── $this->dispatchEvent() [queued]
                         └── transaction commits → events fire
```

If any step is skipped, verify there's a valid reason documented in the code.
