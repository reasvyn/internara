# Blade Presentation — No Business or UI Logic in Blade

> **Last updated:** 2026-08-25 **Changes:** add Blade presentation rule — no business/UI logic in .blade.php

Blade files are pure presentation. No business logic or UI computation of any size may live in `.blade.php`. All derived state must be computed in the Livewire component (public properties or `#[Computed]`) or in Alpine.js (`x-data`) for lightweight client-side interactivity.

---

## Intent

Keep Blade as a trivial binding layer: `{{ $value }}`, `@foreach ($items as $item)`, `@if ($isVisible)`. Every calculation — percentages, ratios, `max()`, stage/funnel assembly, formatted dates with business meaning, permission-derived visibility — is prepared in Livewire before rendering.

## What it enforces

- `@php` blocks containing calculations, array assembly, or business branching are forbidden in Blade.
- `@if` / `@elseif` / `@else` with inline PHP expressions **should be avoided**. Do not write `@if ($user->role === 'admin' && $stats['x'] > 0)` or `@if (auth()->user()->hasRole('super_admin'))`. Blade should only gate on a precomputed boolean (`@if ($isSuperAdmin)`) when needed, or use Spatie directives (`@hasrole('super_admin')`, `@role`) for RBAC — e.g., `@hasrole('super_admin')` instead of `hasRole()` checks. Even then, prefer Alpine (`x-show` / `x-if` with `x-data`) for UI-only toggles and Livewire boolean properties for server-driven visibility.
- As a guideline, **`@if` in Blade should be avoided where possible** — it is not strictly forbidden, but preferred alternatives are Livewire booleans (server-driven) and Alpine state (client-driven); `@if` remains only as a trivial gate on an already-computed flag, never an expression with business logic.
- Livewire owns derived metrics (`public int $completionRate`, `public array $pipelineStages`, `public array $pipelineDrops`, `public bool $isSuperAdmin`), computed in `mount()` or `#[Computed]` getters.
- Alpine.js owns lightweight UI state (`x-data="{ open: false }"` for toggles, dropdowns, local filtering) and never re-implements server business rules.

## Why it matters

Blade `@php` calculations are untyped, untestable, and invisible to arch-guard scans. Moving logic to Livewire makes it typed, unit-testable, and traceable to the spec; Blade becomes reviewable for accessibility and localization only.

## How to apply

**Livewire (correct):**
```php
// AdminDashboard.php
public array $pipelineStages = [];
public int $pipelineMaxV = 0;
public int $completionRate = 0;

public function mount(ReadAdminDashboardAction $action): void {
    $stats = $action->execute();
    $this->completionRate = $stats['totalStudents'] > 0 ? (int) round(($stats['certificatesIssued'] / $stats['totalStudents']) * 100) : 0;
    // ... assemble $pipelineStages, compute $pipelineMaxV, drops, absorption, etc.
}
```
```blade
{{-- admin.blade.php --}}
<span>{{ $completionRate }}%</span>
@foreach ($pipelineStages as $i => $stage)
    <span>{{ $stage['drop'] }}%</span>
@endforeach
```

**Blade (wrong):**
```blade
@php $rate = $total > 0 ? round(($completed / $total) * 100) : 0; @endphp
@php $stages = [...]; $maxV = max(array_column($stages,'v')); @endphp
```

## Pitfalls to avoid

- A "tiny" `@php $rate = ...` — no size threshold, move it to Livewire.
- Assembling `$stages` or `$filters` in Blade instead of the component.
- Computing visibility (`$canEdit = $model->status === 'x'`) in Blade instead of reading `$isEditable` from Livewire/Entity.
- Writing `@if (auth()->user()?->hasRole('super_admin'))` in Blade — expose `public bool $isSuperAdmin` from Livewire and use `@if ($isSuperAdmin)` or Alpine `x-show="isSuperAdmin"` instead.

## Verification

- `grep -R "@php" resources/views/**/*.blade.php` returns no hits with business logic (only trivial presentational assignments if unavoidable, and even those should be justified).
- `grep -R "@if.*\(.*->.*\|@if.*\(.*\[.*\]\|@if.*\(.*===` in Blade returns no hits — `@if` contains only a single boolean variable, never an expression.
- Every derived value and visibility flag rendered in Blade is a public property or `#[Computed]` getter of its Livewire component (or an Alpine `x-data` boolean).
- `docs/conventions.md` §14 and `docs/architecture/livewire-pattern.md` §1.1 are the SSOT.
