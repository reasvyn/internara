# Entity Relationship Diagram — Internara

> 75 tables across 24 domains. All primary keys are UUID v4 unless noted.
> Timestamps (`created_at`, `updated_at`) exist on all tables unless noted.

---

## Lifecycle Map

```
    01-auth ──────────────────────────────────────────────────────────────┐
      │ Identity & Access                                                 │
      │                                                                   ▼
02-institution ───► 03-partnership ───► 04-internship ───► 05-registration
      │                   │                   │                   │
      │                   ▼                   ▼                   ▼
      │           companies   internships        registrations
      ▼               partnerships       placements          mentees
   schools
   departments                                                        │
   academic_years                                                      │
   settings                                                            ▼
   setup                                                    06-daily ──┤
                                                           07-mentoring┤
                                                           08-assignment┤
                                                           09-assessment┤
                                                              10-report┤
                                                              11-guidance┤
                                                              12-evaluation
                                                                       │
                                                                       ▼
                                                              13-admin
                                                              14-infra
```

---

## Data Lifecycles

| # | File | Lifecycle | Domain(s) | Tables |
|---|---|---|---|---|
| 01 | [01-auth.md](01-auth.md) | Identity & Access | `Auth`, `User` | 10 |
| 02 | [02-institution.md](02-institution.md) | Institutional Setup | `School`, `Settings`, `Setup` | 5 |
| 03 | [03-partnership.md](03-partnership.md) | Companies & Partnerships | `Partnership`, `Placement` | 4 |
| 04 | [04-internship.md](04-internship.md) | Internship Program | `Internship`, `Schedule` | 5 |
| 05 | [05-registration.md](05-registration.md) | Student Registration | `Mentee`, `Registration`, `Document` | 5 |
| 06 | [06-daily.md](06-daily.md) | Daily Execution | `Attendance`, `Logbook` | 3 |
| 07 | [07-mentoring.md](07-mentoring.md) | Mentoring & Teams | `Mentor` | 4 |
| 08 | [08-assignment.md](08-assignment.md) | Assignments & Submissions | `Assignment` | 3 |
| 09 | [09-assessment.md](09-assessment.md) | Assessment & Grading | `Assessment` | 6 |
| 10 | [10-report.md](10-report.md) | Reports & Certification | `Internship`, `Certificate` | 4 |
| 11 | [11-guidance.md](11-guidance.md) | Guidance & Incidents | `Guidance`, `Incident` | 3 |
| 12 | [12-evaluation.md](12-evaluation.md) | Evaluations & Notifications | `Evaluation`, `Admin` | 3 |
| 13 | [13-admin.md](13-admin.md) | Admin & Audit | `Core`, `Admin` | 5 |
| 14 | [14-infra.md](14-infra.md) | Infrastructure | Laravel/Pulse/Spatie | 11 |

---

## Legend

```
TABLE_NAME             — Application table
├── column             — Column name
│   type               — Data type
│   constraints        — PK, FK, UQ, NOT NULL, DEFAULT, NULLABLE
│   FK → Table.col     — Foreign key reference
│   comment            — Developer-facing annotation
│   idx                — Index
│   uq                 — Unique constraint
└──

Relationship notation:
  1 ──1    One to one
  1 ──*    One to many
  * ──*    Many to many (with pivot)
  ════════  Junction/pivot table

Referential actions:
  CAS   CASCADE on delete
  SNU   SET NULL on delete
  NOD   NO ACTION / RESTRICT
  NUL   NULL ON DELETE
```

---

## Key Conventions

| Convention | Applies To |
|---|---|
| **UUID primary keys** | All domain tables (`varchar(36)`). System tables use `integer` auto-increment |
| **Foreign keys** | `foreignUuid()->constrained()` with explicit `onDelete` behavior |
| **Timestamps** | `created_at` + `updated_at` on all tables. Append-only logs omit `updated_at` |
| **Soft deletes** | Only `assessments` uses `softDeletes()` |
| **Status state machines** | Spatie Model States via `statuses` polymorphic table for complex workflows |
| **Enum statuses** | Simple string-based status columns with `LabelEnum` + `StatusEnum` contracts |
| **JSON columns** | Used for flexible schemas: `config`, `metadata`, `content`, `properties` |
| **Polymorphic** | Spatie: `activity_log`, `media`, `statuses`, `model_has_roles`, `model_has_permissions` |
| **Pivot tables** | `registration_mentor`, `team_user`, `role_has_permissions` |

---

## Index Strategy

| Index Type | Purpose |
|---|---|
| **Composite FK+status** | Most-common query pattern: filter by foreign key + status (e.g., `[registration_id, status]`) |
| **Unique user+date** | Enforces one-record-per-day: `attendances`, `logbooks` |
| **Status-only** | Lightweight filter for status-based listing pages |
| **Foreign key** | All FK columns indexed individually or as part of composites |

---

## Master Entity-Relationship Map

```
USERS ───1:1─── Profile
  │
  ├──1:*── Mentee ──1:*── Registration ──*:── Mentor (pivot: registration_mentor)
  │                                                            │
  │      Registration (central entity)                         │
  │      ├──1:*── Attendance                                   │
  │      ├──1:*── Logbook                                      │
  │      ├──1:*── AbsenceRequest                               │
  │      ├──1:*── SupervisionLog                               │
  │      ├──1:*── RegistrationDocument                         │
  │      ├──1:*── Submission                                   │
  │      ├──1:*── Assessment                                   │
  │      ├──1:*── Report                                       │
  │      ├──1:*── Presentation                                 │
  │      ├──1:*── Certificate                                  │
  │      ├──1:*── IncidentReport                               │
  │      └──1:*── PlacementChangeRequest                        │
  │
  ├──1:*── Mentor ──*:── Registration (pivot: registration_mentor)
  ├──1:*── SupervisionLog (as supervisor)
  │
  ├──1:*── LoginHistory
  ├──1:*── AccountRestriction
  ├──1:*── AccountRecoveryCode
  ├──1:*── ActivationToken
  ├──1:*── AccountStatusHistory
  ├──1:*── Notification
  ├──1:*── SuspiciousLoginAttempt
  ├──1:*── HandbookAcknowledgement
  │
  ├──*:── Team (pivot: team_user)
  ├──1:── Team (as owner)
  │
  ├──*:── Roles (pivot: model_has_roles)
  └──*:── Permissions (pivot: model_has_permissions)

SCHOOL ──1:*── Department
  │
  └──1:*── Profile

ACADEMIC_YEAR ──1:*── Internship
                     │
                     ├──1:*── Placement ──*:1── Company
                     │                        │
                     │                        └──1:*── Partnership
                     │
                     ├──1:*── Briefing ──1:*── BriefingAttendance
                     ├──1:*── Assignment ──*:1── AssignmentType
                     ├──1:*── Rubric ──1:*── Competency ──1:*── Indicator
                     ├──1:*── DocumentRequirement ──*:1── Document
                     └──1:*── Schedule

REPORT ──1:*── ReportRevision
CERTIFICATE_TEMPLATE ──1:*── Certificate
HANDBOOK ──1:*── HandbookAcknowledgement
PRESENTATION ──1:*── PresentationExaminer
EVALUATION (polymorphic target)
```

---

## Reading Order

Start with [01-auth.md](01-auth.md) (identity foundation), then follow the lifecycle in order:
**Setup → Partnership → Program → Registration → Execution → Assessment → Certification**
