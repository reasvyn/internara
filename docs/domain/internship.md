# Internship Domain

## Purpose

Internship is the core operational domain — it defines what an internship IS as a program. 
Programs set the boundaries (date ranges, academic year, department), specify the requirements 
(minimum attendance, mandatory briefings, required documents, assessment periods), define the 
structure (briefings, reports, types of required work), and establish the rules that all other 
domains enforce. Without a program definition, there are no placements to fill (Placement 
domain), no registrations to process (Registration domain), no assignments to grade (Assignment 
domain), no attendance to track (Attendance domain), no evaluations to collect (Evaluation 
domain), no logbooks to review (Logbook domain), and no certificates to issue (Certificate 
domain). The Internship domain is where it all starts.

## Boundary

**In scope:** Internship program definitions (name, description, objectives, date range, academic 
year association, department ownership, program type), briefing management (topic, presenter, 
schedule, materials, mandatory/optional flag, attendance recording link), report requirement 
definitions (types of reports required, deadlines relative to program timeline, format 
specifications, optional template references), program-level completion requirements (minimum 
attendance percentage, minimum number of passing assignments, mandatory briefings list, required 
documents list, required guidance acknowledgements, assessment periods, minimum total hours), 
program visibility and enrollment eligibility rules (which students can enroll, prerequisites, 
capacity limits), program status lifecycle (draft, open, active, closed, archived) with 
transition gates.

**Out of scope:** Slot allocation and capacity management (Placement domain manages company slots 
and student-to-slot matching), student application and enrollment processing (Registration domain 
manages the application wizard and approval), company partnerships and agreements (Partnership 
domain manages external relationships), day-to-day task definitions (Assignment domain creates 
tasks within a program's context), daily attendance recording and status computation (Attendance 
domain), certificate physical issuance (Certificate domain), evaluation rubrics and competency 
criteria (Assessment domain), guidance document content and acknowledgements (Guidance domain), 
scheduling of individual events beyond briefings (Schedule domain manages the event calendar).

## Key Concepts

**Internship Programs.** A program is the central organizing entity that defines everything about 
an internship. Key attributes include: name and description (what the program is about), 
objectives (learning outcomes and goals), start and end dates (the temporal boundary of the 
program), academic year association (ensuring date range validity), department ownership (which 
department runs the program), program type (full-time, part-time, industry placement, research 
internship), and visibility settings (which students can see and apply). Programs have a 
lifecycle: DRAFT (being planned, not visible to anyone except admins), OPEN (accepting 
registrations from eligible students), ACTIVE (the internship period is underway — 
registrations are closed, operational activity is happening), CLOSED (the internship period has 
ended, completion processing is underway), and ARCHIVED (historical record, no further operations 
possible). Transitions are gated: OPEN requires the program to have at least one defined 
requirement; ACTIVE requires the start date to have arrived; CLOSED requires the end date to have 
passed; ARCHIVED can only happen after CLOSED.

**Briefings.** Briefings are organized informational sessions that students attend as part of 
their internship program. Each briefing has: a topic or title, a presenter (could be an internal 
staff member, an external speaker, or a company representative), a scheduled date and time (or 
multiple sessions for the same briefing), a location or virtual meeting link, and optional 
pre-reading or reference materials. Briefings are categorized as MANDATORY (attendance is 
required for completion — tracked by the Attendance domain) or OPTIONAL (recommended but not 
required). The Internship domain defines the briefing and its attendance requirement; the 
Schedule domain handles the calendar event and the Attendance domain tracks who actually attended.

**Report Requirements.** Programs define what written reports students must produce. Each report 
type (e.g., Progress Report, Final Report, Technical Documentation, Reflective Essay) has: a name 
and description, a deadline or deadline rule (e.g., "2 weeks before the program end date"), a 
format specification (PDF, document, presentation), a minimum and maximum length, an optional 
template reference (linking to the Document domain for a report template), and the assessment 
criteria or rubric to be used for grading. Report submission is handled by the Assignment or 
Document domains, but the requirement definition — what needs to be submitted, when, and in 
what format — belongs to the Internship domain.

**Program Requirements.** Each program specifies the definitive set of criteria that students 
must meet to successfully complete the internship. These requirements are consumed by the 
Registration domain's completion check logic but are authored and managed here. Typical 
requirements include: minimum attendance percentage (e.g., 90%), minimum number of assignments 
with a passing grade, mandatory briefings that must be attended, required documents that must be 
submitted, required guidance documents that must be acknowledged, assessment periods that must be 
completed (e.g., both mid-term and final evaluations received), and minimum total logged hours. 
Requirements apply uniformly to all students in the program, though individual accommodations can 
be made through the Registration domain.

**Program Lifecycle and State Gating.** The program's lifecycle state gates what operations are 
possible at each stage in other domains. While a program is DRAFT, no registrations can be 
submitted, no assignments can be created, no attendance can be recorded. When it transitions to 
OPEN, the Registration domain opens its application wizard for the program. When it transitions 
to ACTIVE, the Assignment domain allows task creation, the Attendance domain enables clock-in, 
the Logbook domain enables entry creation, and the Mentor domain activates mentor assignments. 
When it transitions to CLOSED, the system runs completion checks and enables certificate 
issuance. This lifecycle ensures that operational domains never receive data out of temporal 
context.

## Requirements

### User Stories & Rules

- **Admin:** As an admin, I want to create internship programs so that students have a framework to register and participate
- **Admin:** As an admin, I want to configure program requirements (attendance, assignments, briefings) so that completion criteria are defined
- **Admin:** As an admin, I want to schedule briefings so that students receive necessary orientation and information
- **Admin:** As an admin, I want to manage report requirements so that students know what deliverables are expected
- **Admin:** As an admin, I want to transition programs through their lifecycle so that the system operates within the correct temporal context
- **Student:** As a student, I want to view available internship programs so that I can make an informed choice
- **Student:** As a student, I want to access briefing materials and schedules so that I can prepare for sessions
- **Student:** As a student, I want to submit required reports so that I meet program completion criteria
- **System:** As the system, I want to gate operations by program state so that no domain receives data out of temporal context
- Program dates must fall entirely within the associated academic year's date range — no 
overlap or out-of-bounds dates.
- Program requirements apply uniformly to all enrolled students at the program level; individual 
accommodations are managed as exceptions through the Registration domain.
- Briefings must be scheduled before their occurrence date; retroactive briefing creation is not 
permitted.
- A program cannot be deleted if it has any active, closed, or completed registrations — it can 
only be archived.
- Report requirement changes apply prospectively only — they do not affect students who have 
already started the program.
- Each program must define at least one completion criterion and one assessment period to be 
eligible for OPEN status.
- Program status transitions are irreversible in the forward direction: ACTIVE cannot return to 
OPEN; CLOSED cannot return to ACTIVE without explicit administrative override.
- Archived programs are entirely read-only — no new data can be created against an archived 
program context.

### Process Flow

```
DRAFT ──→ PUBLISHED ──→ ACTIVE ──→ CLOSED ──→ ARCHIVED
            │              │
            ↓              ↓
         CANCELLED      CANCELLED
```

- **DRAFT**: Being planned, visible only to admins
- **PUBLISHED** (was OPEN): Accepting registrations from eligible students
- **ACTIVE**: Internship period underway — registrations closed, operations active
- **CLOSED**: Period ended, completion processing underway
- **ARCHIVED**: Historical record, entirely read-only
- **CANCELLED**: Terminated before completion

Transitions: PUBLISHED requires at least one defined requirement. ACTIVE requires start date. CLOSED requires end date. ARCHIVED requires CLOSED first.

### Key Operations

| Action | Description |
|--------|-------------|
| `CreateInternshipAction` | Creates a new internship program |
| `UpdateInternshipAction` | Updates an existing internship program |
| `DeleteInternshipAction` | Deletes a draft internship program |
| `BatchUpdateInternshipStatusAction` | Batch transitions programs to a new status |
| `CheckCloseReadinessAction` | Checks if a program is ready to close |
| `CreateBriefingAction` | Creates a briefing session for a program |
| `RecordBriefingAttendanceAction` | Records student attendance at a briefing |
| `OverrideBriefingAttendanceAction` | Admin override for briefing attendance |
| `CreateReportAction` | Creates a report submission record |
| `SubmitReportAction` | Submits a report for review |
| `ApproveReportAction` | Approves a submitted report |
| `RequestReportRevisionAction` | Requests revisions on a submitted report |
| `AddSupervisorReportNotesAction` | Adds supervisor notes to a report |

### Technical Reference

| Layer | Artifacts |
|-------|-----------|
| **Models** | `Internship`, `Briefing`, `BriefingAttendance`, `Report`, `ReportRevision`, `InternshipDocumentRequirement` |
| **Entities** | `InternshipPeriod` (registration window checks, academic year boundaries), `InternshipState` (deletion gating, status checks) |
| **Enums** | `InternshipStatus` — `DRAFT`, `PUBLISHED`, `ACTIVE`, `COMPLETED`, `CANCELLED`; `ReportStatus` — `DRAFT`, `SUBMITTED`, `REVISION_REQUIRED`, `APPROVED`; `RequirementType` — `DOCUMENT`, `SKILL`, `TEXT` |
| **States** | `Draft`, `Published`, `Active`, `Completed`, `Cancelled` |
| **Livewire** | `InternshipManager`, `BriefingManager`, `ReportWriter`, `RequirementManager` |
| **Policies** | `InternshipPolicy`, `InternshipRegistrationPolicy`, `CompanyPolicy` |
| **Events** | `InternshipCreated` |
| **Notifications** | `InternshipCreatedNotification`, `RegistrationNotification` |
| **Rules** | `OpenForRegistration` (validation rule) |

## Dependencies

| Dependency | Reason |
|---|---|
| School | Programs belong to departments within schools; academic years from School constrain 
program date ranges |
| Partnership | Programs may be linked to partner company agreements for student placement slots |
| Registration | Student enrollment in programs is managed by Registration; program is the 
context for all registration, placement, and operational activity |
| Core | BaseAction, BaseModel, SmartLogger |


