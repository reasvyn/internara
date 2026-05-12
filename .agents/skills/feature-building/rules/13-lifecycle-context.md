# Lifecycle Context

Internara manages internships through 8 sequential phases. Every feature belongs to one of these phases.

## Phase Map

```
Phase 0: System Setup       → Install, wizard, admin creation
Phase 1: Foundation          → School, departments, academic years, users
Phase 2: Internship Planning → Programs, companies, placements, requirements
Phase 3: Registration        → Applications, registrations, direct placement
Phase 4: Operations          → Logbook, attendance, assignments, supervision
Phase 5: Assessment          → Rubrics, scoring, finalization, mentor evaluation
Phase 6: Period Closing      → Complete internships, reports, data lock
Phase 7: Archiving           → Archive accounts, lock periods, GDPR
```

## State Machine Pattern

Major entities follow validated state transitions via Enums:

```
Internship:  DRAFT → PUBLISHED → ACTIVE → COMPLETED (↘ CANCELLED)
Logbook:     DRAFT → SUBMITTED → VERIFIED (↘ REVISION_REQUIRED → DRAFT)
Submission:  DRAFT → SUBMITTED → VERIFIED/GRADED (↘ REVISION_REQUIRED)
Account:     PROVISIONED → ACTIVATED → VERIFIED → [RESTRICTED|SUSPENDED|INACTIVE] → ARCHIVED
Assessment:  OPEN → FINALIZED
```

Transition validation is defined in **Enum classes** (e.g., `InternshipStatus::canTransitionTo()`).
Business rules around transitions are in **Entity classes** (e.g., `RegistrationState::canBeApproved()`).

## RBAC Context

| User Role | Functional Role | Participates In |
|---|---|---|
| `super_admin` | Admin | All phases, system config |
| `admin` | Admin | Phases 1-7 |
| `teacher` | Mentor | Phases 4-5 (verify logbook, grade, supervise) |
| `student` | Mentee | Phases 3-5 (register, logbook, attendance, assignments) |
| `supervisor` | Mentor | Phases 4-5 (supervision, evaluation) |

## Adding a New Feature

1. Identify which lifecycle phase it belongs to
2. Check existing Entities for reusable business rules
3. Follow the Action → Entity → Model → Livewire → View pipeline
4. Register in the appropriate route group and sidebar menu group
5. Add translations for both EN and ID
6. Consider what state transitions are needed
7. Determine which roles should have access
