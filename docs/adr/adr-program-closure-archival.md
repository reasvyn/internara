# ADR-013: Program Closure & Archival

> **Last updated:** 2026-06-14 **Changes:** sync — initial metadata sync with new format

## Description

Completed internship programs are archived rather than deleted, preserving all associated data
(registrations, placements, logbooks, assessments) in read-only state.

## Context

The program lifecycle covers registration through certification. Once certificates are issued,
schools have two post-certification requirements:

### Program Closure

When all students have completed placements, been assessed, and received certificates, the program
must be formally closed. Closure involves verifying completeness (assessments finalized, submissions
graded, attendance verified, supervision logs signed, certificates issued), computing and locking
final grades, triggering a Program Quality Evaluation, and marking the program as `ARCHIVED`.

### School Archives

Indonesian regulations require schools to retain student records, grade reports, and program
documentation for 5+ years after completion. Data cannot be deleted, must be preserved in immutable
state, and must remain accessible for read-only viewing. Graduated alumni may need certificate
access.

### Existing Infrastructure

`CheckCloseReadinessAction` (exists), `ArchiveStudentAccountsAction` (exists),
`AccountStatus::ARCHIVED` (exists), `InternshipStatus::COMPLETED` (exists). Missing: a coordinating
Process Action, data snapshot mechanism, read-only archive UI, `InternshipStatus::ARCHIVED`, and
cohort-based alumni marking.

Two approaches were considered:

1. **Soft close** — mark as COMPLETED, leave records mutable, rely on policies. Simpler but no
   integrity guarantees.
2. **Hard archive** — immutable snapshot at closure time. Source records locked behind `ARCHIVED`
   status gate.

## Decision

**Hard archive with immutable snapshot** selected. Program closure is coordinated by a
`CloseProgramProcess` with 7 steps:

```
CloseProgramProcess
  ├─ 1. CheckCloseReadinessAction
  │      Verify all assessments, submissions, attendance, certificates
  │
  ├─ 2. Trigger Program Quality Evaluation (Evaluation module)
  │      Admin/teacher evaluation required before closure proceeds
  │
  ├─ 3. FinalizeAssessmentsAction
  │      Compute final weighted grade, freeze scores
  │
  ├─ 4. IssueCertificatesAction (if not already issued)
  │      Batch-issue remaining certificates
  │
  ├─ 5. ArchiveProgramAction
  │      Create immutable snapshot, lock all records, transition to ARCHIVED
  │
  ├─ 6. ArchiveStudentAccountsAction
  │      Mark active students as alumni (read-only dashboard)
  │
  └─ 7. GenerateArchiveReportAction
         Generate summary document for school records
```

### Data Snapshot

Captures at closure time: student roster, final grade composites, attendance summary, logbook
statistics, assignment scores, rubric scores, evaluation results, certificate serial numbers. Stored
as a versioned JSON document in an `archives` table.

### Archived Program Lifecycle

```
DRAFT → PUBLISHED → ACTIVE → COMPLETED → ARCHIVED
                                         ↓ (exceptional, super_admin only)
                                      COMPLETED
```

`ARCHIVED` is terminal — no further transitions. Un-archive is exceptional (super_admin only,
requires audit trail). Archived programs are read-only everywhere.

### Alumni Accounts

Students in archived programs get `AccountStatus::ARCHIVED`. They can log in with a read-only
dashboard (view certificates, past grades) but cannot register for new programs, submit logbooks, or
clock attendance.

### Retention

Archived data is retained indefinitely. No automatic deletion. Schools that need deletion after
regulatory expiry must use database-level operations (documented but not automated).

## Consequences

- **Positive**: Regulatory compliance — immutable archive preserves student records at the moment of
  closure.
- **Positive**: Data integrity — `ARCHIVED` status prevents writes at model, policy, and UI levels.
- **Positive**: Alumni accounts remain accessible for certificate and grade viewing.
- **Positive**: Un-archive is possible in exceptional circumstances with full audit trail.
- **Negative**: Data snapshot duplicates existing operational data. At school scale, this storage
  cost is negligible.
- **Negative**: Un-archive is complex — reversing the snapshot requires careful handling. Only
  super_admin.
- **Negative**: `ARCHIVED` on student accounts prevents re-registration. Schools allowing
  re-enrollment must use a different status.

## References

- `app/Program/Internship/Actions/ReadCloseReadinessAction.php` — Readiness verification
- `app/User/UserManagement/Actions/ArchiveStudentAccountsAction.php` — Student archive
- `app/User/Enums/AccountStatus.php` — ARCHIVED status
- `app/Program/Internship/Enums/InternshipStatus.php` — Program lifecycle enum
- `docs/architecture.md` — Action Triad (Process Actions) section
- `docs/specs/internship-lifecycle.md` — Program lifecycle specification
