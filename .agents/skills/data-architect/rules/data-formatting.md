# Data Formatting — Storage vs Presentation

> **Last updated:** 2026-08-25 **Changes:** new skill — formatting for data-architect

## Intent

Data is stored in canonical form and formatted only at the presentation boundary. No presentation formatting leaks into storage, Entities, or DTOs.

## What it enforces

- **Store canonical, format at boundary:**
  - Dates: UTC in DB (`datetime`), format for display via `Carbon::locale(app()->getLocale())->isoFormat()` in Livewire/Alpine/Blade.
  - Numbers/currency: raw `decimal`/`int` in DB, format via `Number::locale(app()->getLocale())->format()` or `__()` keys with `:amount`.
  - JSON: typed structs/DTOs in code, `json` column at storage; never `json_encode` hand-rolled in the Action without a DTO.
- **i18n:** All display strings via `__()` (see `ui-development`); dates/numbers via locale-aware helpers, not `date('m/d/Y')`.
- **Precision:** Money as `decimal(15,2)` + `Money` struct or integer cents — never `float`. Percentages as typed `int` (basis points) when they drive business rules.
- **No formatting in Entity/DTO storage shape:** `Entity::toPersistableArray()` returns canonical values; `Livewire`/`Blade` own the formatted getters (`getDisplayDateAttribute`, Livewire computed).

## How to apply

```php
// Storage (migration)
$table->decimal('amount', 15, 2);
$table->timestamp('starts_at'); // UTC

// Presentation (Livewire)
public function getDisplayStartsAtAttribute(): string {
    return Carbon::parse($this->startsAt)->locale(app()->getLocale())->isoFormat('D MMMM YYYY');
}
```

PDF/CSV exports reuse the same presentation helpers — no duplicated `format()` in the exporter.

## Pitfalls to avoid

- Storing `“Rp 1.000.000”` formatted string in DB — store `1000000` + format on read.
- `date('d-m-Y', strtotime($model->date))` in Blade — use `Carbon::locale()`.
- `float $amount` for money in DTO/Entity — use `string` decimal + `Money` struct or `int` cents.
- Formatting in DTO `toArray()` that is also used for persistence — split `toPersistableArray()` vs `toDisplayArray()`.

## Verification

- `grep -R "number_format\|date(" resources/views --include="*.blade.php"` should be minimal — locale helpers are preferred.
- `npx prettier --check` / `vendor/bin/pint --dirty --test` clean.
- No `float` money fields in migrations/DTOs/Entities (PHPStan strict).
