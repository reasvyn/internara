# Program Closure & Archival

> **Last updated:** 2026-08-25 **Changes:** rewrite to MADR-lite industry-standard format

| Field | Value |
|-------|-------|
| Status | Accepted |
| Deciders | Reas Vyn |
| Date | 2026-08-16 |
| Technical Story | [Internship Lifecycle Spec](../specs/7C5WM-internship-lifecycle.md) and [Action Pattern](../guides/arch/action-pattern.md) § Process Actions |

## Context and Problem Statement

The program lifecycle runs registration through certification. Post-certification two
requirements exist: formally **close** the program once all students have completed, been
assessed, and received certificates, and **archive** it for 5+ year regulatory retention as an
immutable, read-only record. Closure must verify completeness (assessments, submissions,
attendance, supervision logs, certificates), compute and lock final grades, trigger Program
Quality Evaluation, and transition to `ARCHIVED`. Regulation then demands no deletion,
immutable preservation, and continued read-only access — including certificate retrieval for
alumni. Existing pieces include `CheckCloseReadinessAction`, `ArchiveStudentAccountsAction`,
`AccountStatus::ARCHIVED`, and `InternshipStatus::COMPLETED`; missing are a coordinating
Process, snapshot mechanism, archive UI, `InternshipStatus::ARCHIVED`, and cohort alumni
marking.

**Decision Drivers:**

* Regulatory 5+ year retention with immutable, auditable proof at closure time
* Integrity — archived records must be unwritable at model, policy, and UI layers
* Alumni continuity — graduates retain read-only certificate/grade access
* Reversibility for exceptional cases (super_admin) with full audit trail

## Considered Options

* **Soft close** — mark `COMPLETED`, leave records mutable, rely on policies alone.
  *Pros:* simplest. *Cons:* no integrity guarantee; mutable history undermines audit.*
* **Hard archive with immutable snapshot (chosen)** — at closure create a versioned snapshot,
  lock source records behind `ARCHIVED` status gates, and mark students as alumni.
  *Pros:* regulatory compliance, locked integrity, alumni access, reversible with audit.*

## Decision Outcome

**Chosen option: Hard archive with immutable snapshot** — closure is coordinated by
`CloseProgramProcess` in 7 steps:

```
CloseProgramProcess
  ├─ 1. CheckCloseReadinessAction — verify assessments, submissions, attendance, certificates
  ├─ 2. Trigger Program Quality Evaluation (Evaluation module) — required before proceeding
  ├─ 3. FinalizeAssessmentsAction — compute weighted grades, freeze scores
  ├─ 4. IssueCertificatesAction — batch-issue any remaining certificates
  ├─ 5. ArchiveProgramAction — snapshot + lock records, transition to ARCHIVED
  ├─ 6. ArchiveStudentAccountsAction — mark active students as alumni (read-only)
  └─ 7. GenerateArchiveReportAction — summary document for school records
```

**Data Snapshot** — at closure captures: roster, grade composites, attendance summary, logbook
stats, assignment and rubric scores, evaluation results, certificate serials. Stored as a
versioned JSON document in an `archives` table.

**Lifecycle:**

```
DRAFT → PUBLISHED → ACTIVE → COMPLETED → ARCHIVED
                                         ↓ (exceptional, super_admin only)
                                      COMPLETED
```

`ARCHIVED` is terminal; un-archive is exceptional, super_admin-only, with audit trail.

**Alumni Accounts** — `AccountStatus::ARCHIVED` students retain login to a read-only dashboard
(certificates, grades) but cannot register, submit logbooks, or clock attendance. Re-enrollment
requires a different status.

**Retention** — archived data retained indefinitely; no automatic deletion. Post-regulatory
expiry deletion is manual, database-level, documented but not automated.

### Positive Consequences

* Regulatory compliance via immutable archive at closure time
* Integrity enforced at model, policy, and UI layers
* Alumni retain certificate and grade access
* Exceptional un-archive remains possible with audit trail

### Negative Consequences

* Snapshot duplicates operational data (negligible at school scale)
* Un-archive is complex — requires careful reversal, super_admin only
* `ARCHIVED` prevents re-registration; re-enrollment needs a separate status

## Links

* [Internship Lifecycle Spec](../specs/7C5WM-internship-lifecycle.md) — lifecycle requirements
* [Action Pattern](../guides/arch/action-pattern.md) — Process Action coordination
* [Architecture Overview](../architecture.md) — where archival sits in the 4-layer model
