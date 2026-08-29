# Contracts — Interfaces, Structs, Types & Enums

> **Last updated:** 2026-08-25 **Changes:** new skill — contracts / entities / enums for data-architect

## Intent

Data shapes have a single, typed owner. Interfaces define behavior, structs/types define shape, enums define vocabulary, and Entities own invariants. Contracts are written before code and survive refactors.

## What it enforces

- **Interfaces:** Depend on abstractions where cross-module (`App\Contracts\*` or module `Contracts\`). No direct cross-module concrete imports for volatile boundaries — inject the interface.
- **Structs/Types:** Prefer `final readonly` value objects for compound values (`Money`, `DateRange`, `Address`) over associative arrays. Type every field; no `mixed` without justification.
- **Entities:** `final readonly` (C5 — no Livewire/Model/Action imports), constructed via `fromModel(Model $m): self`, business rules live here (e.g., `canTransitionTo()`, `maskedEmail()`). No persistence, no framework.
- **Enums:** `LabelEnum` / `StatusEnum` contracts (`docs/guides/arch/enum-pattern.md`): string-backed, `label()`, `tryFrom()`, exhaustively handled via `match()`.
- **Single ownership:** One canonical type per concept. No duplicated shape (`UserData` in two modules) — extract to `Core\Types` or module `Types\`.

## How to apply

```php
// Entity
final readonly class InternshipEntity {
    public function __construct(public string $id, public InternshipStatus $status) {}
    public static function fromModel(Internship $m): self { return new self($m->id, InternshipStatus::from($m->status)); }
    public function canTransitionTo(InternshipStatus $to): bool { /* invariant */ }
}

// Enum
enum InternshipStatus: string implements StatusEnum { case DRAFT = 'draft'; case PUBLISHED = 'published'; public function label(): string { return match($this) { ... }; } }

// DTO (see next rule) carries the struct across layers — Entity never leaks Model.
```

## Pitfalls to avoid

- `final readonly` Entity importing `Illuminate\*` or `Livewire\*` (C5 violation).
- Stringly-typed status (`$status === 'draft'`) instead of an enum.
- Associative `array $address` passed across three layers — replace with `Address` struct.
- Two `UserData` DTOs with the same fields in two modules — unify or scope explicitly.

## Verification

- `python3 tools/scan_class_contracts.py` — Entity/Enum contracts pass.
- `python3 tools/scan_violations.py` — C5 clean.
- `match` on enums is exhaustive (PHPStan level 8); `phpstan analyse` clean.
