# ADR-015: HasAuditTrail Adoption Strategy

## Status
Accepted

## Context
Six tables in the schema have verification/approval columns that record who performed
an action and when:

| Table | Column Pattern |
|---|---|
| `attendances` | `verified_by`, `verified_at`, `is_verified` |
| `logbooks` | `verified_by`, `verified_at`, `is_verified` |
| `registration_documents` | `verified_by`, `verified_at` |
| `supervision_logs` | `is_verified`, `verified_at` (no `verified_by`) |
| `account_applications` | `processed_by`, `processed_at`, `rejection_reason` |
| `placement_change_requests` | `processed_by`, `processed_at`, `rejection_reason` |
| `absence_requests` | `processed_by`, `processed_at` |

This pattern repeats with three variations:
- **Verification**: `verified_by` + `verified_at` ± `is_verified` (boolean)
- **Processing**: `processed_by` + `processed_at` ± `rejection_reason` (text)
- **Grading**: `graded_by` + `graded_at` + `score` (reports, submissions)

Core already provides a `HasAuditTrail` trait (`Core/Models/Concerns/HasAuditTrail.php`)
that automatically logs model lifecycle events (created, updated, deleted, restored,
forceDeleted) via `SmartLogger`. However, **zero models currently use this trait**.

Simultaneously, `BaseAction::log()` already provides dual-channel logging for business
operations. The gap is that model-level events (a logbook being verified, an attendance
being approved) are not automatically captured — they depend on each Action remembering
to call `$this->log()`.

## Decision

### Phase 1 — Apply HasAuditTrail to verification models

The `HasAuditTrail` trait will be applied to the four verification models:
`Attendance`, `Logbook`, `RegistrationDocument`, `SupervisionLog`.

Each model will define:
```php
protected function auditEvents(): array
{
    return ['created', 'updated', 'deleted'];
}

protected function auditModule(): string
{
    return 'Attendance'; // domain name
}
```

This ensures every create/update/delete on these models is automatically logged via
`SmartLogger` with module context, event name, and before/after snapshots.

### Phase 2 — Normalize SupervisionLog

`SupervisionLog` is inconsistent — it has `is_verified` + `verified_at` but no
`verified_by` foreign key. Before applying `HasAuditTrail`, this model will be
normalized:

- Add `verified_by` FK → `users(id)` ON DELETE SET NULL
- Remove `is_verified` boolean (redundant with `SupervisionLogStatus::Verified` enum value)

### Phase 3 — No polymorphic tables

A polymorphic `verifications` table was considered (single table for all verification
events across domains). This was rejected because:

1. **ADR-002 (Domain-First)**: Polymorphic tables in Core that reference domain models
   via `morphs_to` create an implicit dependency — Core would contain logic tied to
   domain model class names.
2. **Query complexity**: Filtering verifications per domain would require
   `WHERE verifiable_type = 'App\Domain\Attendance\Models\Attendance'` — fragile and
   not refactorable.
3. **Column mismatch**: Attendance needs geo-coordinates on verification; RegistrationDocument
   needs admin_notes. A polymorphic table would require a JSON catch-all, losing type safety.

Instead, verification stays as dedicated columns per table, but the consistent pattern
is enforced by convention + architecture test.

## Consequences
- **Positive**: Every verification action is automatically audited with full context —
  who, what, when, and the before/after state.
- **Positive**: No new database tables needed — the trait works with existing columns.
- **Positive**: `SupervisionLog` becomes consistent with the other three verification models.
- **Negative**: Phase 2 requires a migration to add `verified_by` + remove `is_verified`.
- **Negative**: Each model must opt in by applying the trait — no automatic enforcement.
- **Negative**: The trait fires on every `save()`, including routine non-verification
  updates. Mitigated by `auditEvents()` whitelist.

## References
- `app/Domain/Core/Models/Concerns/HasAuditTrail.php` — the trait
- `app/Domain/Core/Support/SmartLogger.php` — dual-channel logger
- `app/Domain/Attendance/Models/Attendance.php` — target model
- `app/Domain/Attendance/Models/Logbook.php` — target model (in Logbook domain)
- `app/Domain/Registration/Models/RegistrationDocument.php` — target model
- `app/Domain/Mentor/Models/SupervisionLog.php` — target model (needs normalization first)
- `docs/domain/core.md` — HasAuditTrail documentation
