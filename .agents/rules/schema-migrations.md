# Schema & Migrations

## Intent

Every persistent data shape is declared as a versioned, reversible migration with explicit foreign-key behavior and indexes. Schema is the contract between code and storage; it must be precise, auditable, and free of implicit defaults.

## What it enforces

- **UUID v7 PK:** `$table->uuid('id')->primary()` for all new tables (ADR `adr-uuid-primary-keys.md`). No auto-increment.
- **FK with explicit behavior (D6):** Every `foreignUuid()->constrained('{table}')` must declare `cascadeOnDelete()`, `onDelete('set null')`, `onDelete('restrict')`, etc., plus matching `onUpdate()`. No implicit `no action`.
- **Indexes:** `->index(['col'])` for every `WHERE` / `ORDER BY` / `JOIN` column; composite `->index(['a','b'])` for common multi-column filters. FK columns alone do not satisfy this — add explicit indexes.
- **One migration per table or logical change:** Single-responsibility migrations; include `->timestamps()` or `->softDeletes()` where the pattern doc requires.
- **Types:** `decimal` with precision for money, `date`/`datetime` in UTC at storage, `json` only for truly schemaless payloads (otherwise normalize).
- **Naming:** `snake_case` columns/tables; migration file `YYYY_MM_DD_HHMMSS_create_{table}_table.php`.

## How to apply

```php
Schema::create('attendances', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
    $table->foreignUuid('registration_id')->constrained('registrations')->cascadeOnDelete();
    $table->date('date');
    $table->time('clock_in');
    $table->time('clock_out')->nullable();
    $table->string('status');
    $table->foreignUuid('verified_by')->nullable()->constrained('users')->onDelete('set null');
    $table->timestamp('verified_at')->nullable();
    $table->index(['user_id', 'date']); // composite for frequent filter
    $table->timestamps();
});
```

Run `php artisan migrate --pretend` before committing; verify FK behavior with `php artisan migrate:fresh --seed` on a throwaway DB.

## Pitfalls to avoid

- Auto-increment `id` on new tables.
- `foreignId()` without `->constrained()` + explicit `onDelete`.
- Relying on FK auto-index to satisfy a `WHERE`/`ORDER BY` index requirement.
- Mixing two tables or two logical changes in one migration file.

## Verification

- `python3 tools/scan_conventions/cli.py` — D6 (FK) clean.
- `grep -R "foreignUuid\|foreignId" database/migrations --include="*.php"` shows explicit `onDelete`.
- `php artisan migrate:fresh --seed` succeeds; FKs behave as declared.
- `docs/refs/modules/{module}-reference.md` lists the schema change.
