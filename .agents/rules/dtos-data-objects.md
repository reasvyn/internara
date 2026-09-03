# DTOs & Data Objects

## Intent

Data crosses boundaries only as typed, validated DTOs. DTOs are the single transformation point between untrusted input and trusted domain.

## What it enforces

- **BaseData:** All DTOs extend `BaseData` (or `Spatie\LaravelData\Data`) with `declare(strict_types=1)`. No plain arrays for 3+ params (C7 — Action must accept DTO).
- **Forbidden imports (C6):** DTOs import no Model/Entity/Service. They carry scalars, enums, and other DTOs only.
- **Construction:** `fromArray(array $data): static` or `fromRequest(FormRequest $r): static` — never `new DTO($request->all())`. Validate in FormRequest or DTO pipeline.
- **Immutability:** DTOs are effectively readonly; map to Entity/Model via explicit `toArray()` — no magic.
- **Single DTO per Action input:** Command/Process `execute(DTO $data): ActionResponse` — one DTO, one Action.

## How to apply

```php
final class CreateInternshipData extends BaseData {
    public function __construct(
        public readonly string $title,
        public readonly InternshipStatus $status,
        public readonly ?string $companyId,
    ) {}
    public static function fromRequest(InternshipRequest $r): self {
        return new self($r->validated('title'), InternshipStatus::from($r->validated('status')), $r->validated('company_id'));
    }
}
```

Use in Action: `public function execute(CreateInternshipData $data): ActionResponse`.

## Pitfalls to avoid

- `execute(string $title, string $status, ?string $companyId)` with 3+ params — wrap in DTO (C7).
- `DTO` importing `Internship $model` — violates C6; pass the scalar/enum instead.
- `Model::create($dto->toArray())` without `#[Fillable]` whitelist — ensure D4 still applies.

## Verification

- `python3 tools/scan_violations.py` — C6/C7 clean.
- `python3 tools/scan_class_contracts.py` — DTO extends BaseData, `fromArray`/`fromRequest` present.
