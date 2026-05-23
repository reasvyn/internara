# ADR-016: Verification & Approval Column Pattern

## Status
Accepted

## Context
Six tables in the schema implement a "verify/approve" concept — a user action that
records who did what and when. These emerged independently during development and
created three different column patterns:

### Pattern A — Verification (has an explicit verifier)
| Table | Columns |
|---|---|
| `attendances` | `verified_by` (FK→users), `verified_at` (timestamp), `is_verified` (boolean) |
| `logbooks` | `verified_by` (FK→users), `verified_at` (timestamp), `is_verified` (boolean) |
| `registration_documents` | `verified_by` (FK→users), `verified_at` (timestamp) |

### Pattern B — Processing (application-style approval)
| Table | Columns |
|---|---|
| `account_applications` | `processed_by` (FK→users), `processed_at` (timestamp), `rejection_reason` (text) |
| `placement_change_requests` | `processed_by` (FK→users), `processed_at` (timestamp), `rejection_reason` (text) |
| `absence_requests` | `processed_by` (FK→users), `processed_at` (timestamp) |

### Pattern C — Incomplete verification (missing FK)
| Table | Columns |
|---|---|
| `supervision_logs` | `is_verified` (boolean), `verified_at` (timestamp) — **no `verified_by`** |

### Other related patterns
| Table | Columns |
|---|---|
| `submissions` | `graded_by` (FK→users), `graded_at` (timestamp) |
| `reports` | `graded_by` (FK→users), `graded_at` (timestamp) |
| `certificates` | `issued_by` / `revoked_by` (FK→users), `issued_at` / `revoked_at` (timestamp) |
| `incident_reports` | `resolved_by` (FK→users), `resolved_at` (timestamp) |
| `report_revisions` | `requested_by` (FK→users), `requested_at` (timestamp) |

This fragmentation has three negative consequences:
1. **Inconsistency**: `supervision_logs` lacks a `verified_by` FK, making it impossible
   to query "who verified this" without application-level inference.
2. **Duplication**: The same pattern (`{actor}_by` + `{actor}_at`) is re-implemented in
   10+ tables with no shared abstraction.
3. **Audit gaps**: Without a shared contract, some tables might omit the actor FK
   (as `supervision_logs` did), losing audit trail data.

Two approaches were considered to fix this:

1. **Polymorphic verifications table**: A single `verifications` table with
   `morphs_to: verifiable_type/verifiable_id`, `verified_by`, `verified_at`, `notes`.
   All 10+ tables drop their individual columns and reference the polymorphic table.
2. **Standardized convention + architecture test**: Keep per-table columns but enforce
   a strict naming convention with an architecture test that catches drift.

## Decision

### Approach 2 selected — Standardized convention with enforcement

Polymorphic verification was rejected for these reasons:

**ADR-002 (Domain-First) conflict**: A `verifications` table in Core would need
`morphs_to` columns containing class names from business domains
(`App\Domain\Attendance\Models\Attendance`). This creates an implicit dependency from
Core to every domain — violating Core's zero-dependency rule.

**Query complexity**: Every verification lookup requires a polymorphic query:
```php
$verifications = Verification::where('verifiable_type', Attendance::class)
    ->where('verifiable_id', $attendance->id)
    ->get();
```
This is less readable than `$attendance->verified_by` and cannot leverage Eloquent
relationship auto-joining.

**Column mismatch**: Each verification context has unique columns:
- Attendance: needs `clock_in_latitude/longitude` alongside verification
- RegistrationDocument: needs `admin_notes` alongside verification
- AccountApplication: needs `rejection_reason` (a concept absent from attendance verification)
A polymorphic table would push all context-specific data into a JSON `metadata` column,
losing type safety and queryability.

### Standardized convention

All verification/approval columns across the schema MUST follow this convention:

```
{action}_by      FK → users(id)  nullable
{action}_at      timestamp        nullable
```

Where `{action}` is a past-tense verb describing the action:
- `verified` — marked as true/correct
- `processed` — handled administratively
- `graded` — scored and given feedback
- `issued` — officially published
- `revoked` — withdrawn
- `resolved` — closed after investigation
- `requested` — initiated a change

Rules:
1. `{action}_by` MUST always exist when `{action}_at` exists (no orphan timestamps).
2. `{action}_by` MUST be a foreign key to `users(id)`.
3. Boolean companion columns (`is_verified`, `is_approved`) MUST NOT exist alongside
   a status enum that already expresses the state. If the status enum has a `Verified`
   or `Approved` case, use that instead of a boolean.

### Architecture test enforcement

A new architecture test verifies:
- Every table with a column matching `*_at` (timestamp) has a corresponding `*_by` column
  that is a FK to `users(id)`.
- No table has both a boolean `is_*` column AND a status enum with a matching case.

### Remediation required

| Table | Issue | Fix |
|---|---|---|
| `supervision_logs` | Missing `verified_by` FK | Add `verified_by` FK → users, remove `is_verified` boolean |

## Consequences
- **Positive**: The convention is simple and predictable — developers know to look for
  `{action}_by` and `{action}_at` on any table with verification.
- **Positive**: No new tables, no polymorphic complexity, no Core domain dependency.
- **Positive**: Architecture test catches drift automatically in CI.
- **Positive**: Existing queries (`$attendance->verified_by`) continue working.
- **Negative**: The pattern is still duplicated across 10+ tables — no code reuse.
  Mitigated by `HasAuditTrail` trait (ADR-015) for the logging aspect.
- **Negative**: `supervision_logs` requires a migration to add the missing FK.
- **Negative**: Breaking existing queries that check `is_verified` boolean on
  `supervision_logs` — they must switch to `status === SupervisionLogStatus::Verified`.

## References
- `app/Domain/Attendance/Models/Attendance.php` — pattern example
- `app/Domain/Logbook/Models/Logbook.php` — pattern example
- `app/Domain/Mentor/Models/SupervisionLog.php` — needs remediation
- `docs/erd/06-daily.md` — attendance/logbook ERD
- `docs/erd/07-mentoring.md` — supervision log ERD
- `tests/Arch/` — new architecture test needed
