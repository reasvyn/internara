# Coding Rules — Practical Application Guide

> **Last updated:** 2026-07-03 **Changes:** initial — practical patterns and verification checklist,
> not doc replacement

This is NOT a replacement for `docs/conventions.md`. It's a practical checklist of what to verify
when writing code. Read `docs/conventions.md` for the full spec.

## Before Writing Any Class

Ask yourself:

1. **Does this class already exist?** — `find app/ -name '*{Concept}*'`
2. **What base class should it extend?** — Read the actual base class declaration
3. **Where does it live?** — `app/{Module}/{SubModule}/` — is there already a submodule for it?
4. **What's the equivalent existing class?** — `find app/{Module} -name '*Action.php' | head -5`
5. **What do the tests expect?** — `find tests -path '*{Module}*{Concept}*'`

## Class Contract Checklist

### Action

```php
// Verify these when writing or reviewing an Action
#[ ] Extends correct base class (BaseCommandAction / BaseReadAction / BaseProcessAction)
#[ ] Has exactly one public method: execute()
#[ ] Returns typed value (ActionResponse, Model, Collection, void, etc.)
#[ ] 3+ params in execute() → uses a BaseData DTO
#[ ] Calls $this->transaction() for DB writes
#[ ] Calls $this->log() after mutation
#[ ] Business rules checked via Entity (not inline)
#[ ] Throws RejectedException for violations (not RuntimeException)
```

### Entity

```php
#[ ] final readonly class extends BaseEntity
#[ ] Has fromModel(Model $model): static
#[ ] All properties are private, constructor-promoted
#[ ] Methods are business questions only: canBeDeleted(), isActive(), etc.
#[ ] Does NOT import: Actions, Services, Livewire, Controllers, HTTP
#[ ] Does NOT import Model anywhere except fromModel() parameter type
```

### DTO

```php
#[ ] final readonly class extends BaseData
#[ ] Properties are only: string, int, float, bool, enum, Carbon, nested DTO
#[ ] Does NOT import: Models, Entities, Actions, Livewire
```

### Model

```php
#[ ] Extends BaseModel (or BaseAuthenticatable for user models)
#[ ] Uses #[Fillable([...])] attribute (NOT $fillable/$guarded)
#[ ] Has protected static function newFactory()
#[ ] Has entity bridge methods: asXxxEntity(): XxxEntity
#[ ] NO business logic methods (canX/isX/hasX — those go in Entities)
```

### Enum

```php
#[ ] Implements LabelEnum (all enums)
#[ ] Implements StatusEnum (state machine enums)
#[ ] validTransitions() uses exhaustive match() on all cases
#[ ] Terminal states return empty array from validTransitions()
```

## Translation Key Patterns

When adding a user-facing string:

- NEVER hardcode English text
- Always use `__('key')`
- Convention: `{module}.{sub_noun}.{descriptive_key}` (e.g., `setting.messages.saved`)
- Always add key to BOTH `lang/en/` and `lang/id/`
- Check `lang/{locale}/{module}.php` for existing keys in the same module
