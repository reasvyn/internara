# Data Mapping — Entity ↔ Model ↔ DTO ↔ Action

## Intent

Every boundary has an explicit, typed mapper. No implicit array pass-through, no “it happens to have the same keys.” Mapping is the only place where field renames, type coercions, and legacy shapes are allowed.

## What it enforces

- **Entity ↔ Model:** `Entity::fromModel(Model $m): self` is the canonical mapper; `Entity::toModelArray(): array` or explicit `$model->update($entity->toPersistableArray())` for the reverse. No `$model->toArray()` leaking into Actions.
- **DTO ↔ Model/Entity:** DTO `toEntity()` / `fromEntity()` helpers own coercion (e.g., `string` → `Enum`, `array` → `Struct`). No manual `Enum::from()` scattered in Livewire.
- **Request ↔ DTO:** `FormRequest` validates, DTO `fromRequest()` maps. Never `$request->all()` → Model (D5).
- **External ↔ Internal:** CSV / government export ↔ internal model via a dedicated `Mapper` or `Importer` (e.g., `CsvRowMapper`) — not inline in the Action.
- **One mapper per pair:** One class/method owns a given pair; duplicated mapping logic is deduplicated into the Entity or Mapper.

## How to apply

```php
// Model → Entity
$entity = InternshipEntity::fromModel($model);

// DTO → Entity
$entity = InternshipEntity::fromData($data);

// CSV → DTO
$rowDto = StudentImportRowData::fromCsvRow($row); // Mapper owns header→field renames
```

Action orchestrates: validate → map to DTO → `Entity::fromModel()` for invariant checks → persist via Model.

## Pitfalls to avoid

- `Model::create($request->validated())` without a DTO — loses type safety and enum coercion.
- Two mappers for the same pair in different Actions — extract to Entity/Mapper.
- Inline `$row['nama_lengkap']` ↔ `$data->name` header mapping inside an Action loop — push to `CsvRowMapper`.

## Verification

- `grep -R "->all()" app --include="*.php"` shows no raw request passthrough to persistence.
- `python3 tools/scan_violations/cli.py` — D5 clean.
- `python3 tools/scan_class_contracts/cli.py` — Entity `fromModel` exists where Models are read for domain logic.
