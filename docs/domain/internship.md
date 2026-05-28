# Internship Domain

## Purpose

Internship is the core operational domain — program definitions, document requirements, reports,
groups, phases, and the full program lifecycle from DRAFT through ARCHIVED.

---

## Design Principles

### 1. Program Lifecycle

Programs flow through DRAFT → PUBLISHED → ACTIVE → COMPLETED → ARCHIVED. Each transition
has explicit preconditions. Only ARCHIVED programs are immutable.

### 2. Report Workflow

Student reports go through DRAFT → SUBMITTED → REVISION_REQUIRED → APPROVED. Revisions
are tracked in `report_revisions` table with version history.

### 3. Closure Readiness

Before a program can be closed, `CheckCloseReadinessAction` verifies:
- All assessments finalized
- All submissions graded
- All attendance verified
- All supervision logs signed
- All certificates issued

---

## Models

| Model | Key Fields |
|---|---|
| `Internship` | name, dates, academic_year_id, status |
| `InternshipDocumentRequirement` | internship_id, document_id, is_mandatory |
| `InternshipGroup` | name, internship_id, placement_id |
| `InternshipGroupMember` | group_id, registration_id, mentor_id, role |
| `InternshipPhase` | name, dates, order, internship_id |
| `Report` | registration_id, title, status, score |
| `ReportRevision` | report_id, round, feedback |
| `Document` | name, content, category |

## Actions (21 total)

| Action | Type |
|---|---|
| `CreateInternshipAction` | Command |
| `UpdateInternshipAction` | Command |
| `DeleteInternshipAction` | Command |
| `BatchUpdateInternshipStatusAction` | Command |
| `CreateRequirementAction` | Command |
| `UpdateRequirementAction` | Command |
| `DeleteRequirementAction` | Command |
| `CreateInternshipGroupAction` | Command |
| `UpdateInternshipGroupAction` | Command |
| `DeleteInternshipGroupAction` | Command |
| `AddMemberToGroupAction` | Command |
| `RemoveMemberFromGroupAction` | Command |
| `CreateInternshipPhaseAction` | Command |
| `UpdateInternshipPhaseAction` | Command |
| `DeleteInternshipPhaseAction` | Command |
| `CreateReportAction` | Command |
| `SubmitReportAction` | Command |
| `ApproveReportAction` | Command |
| `RequestReportRevisionAction` | Command |
| `AddSupervisorReportNotesAction` | Command |
| `CheckCloseReadinessAction` | Read/Process |

## Where to Find It

- `app/Domain/Internship/Models/`
- `app/Domain/Internship/Actions/` — 21 Actions
- `app/Domain/Internship/Enums/` — InternshipStatus, ReportStatus, GroupRole
- `app/Domain/Internship/Events/InternshipCreated.php`
- `app/Domain/Internship/Policies/` — 5 Policies
